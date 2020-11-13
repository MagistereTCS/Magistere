<?php

defined('MOODLE_INTERNAL') || die();

class CronParsingException extends Exception
{
    public function __construct($cron)
    {
        parent::__construct("Failed to parse the following CRON expression: {$cron}");
    }
}


class CronTranslator
{
    private static $extendedMap = [
        '@yearly' => '0 0 1 1 *',
        '@annually' => '0 0 1 1 *',
        '@monthly' => '0 0 1 * *',
        '@weekly' => '0 0 * * 0',
        '@daily' => '0 0 * * *',
        '@hourly' => '0 * * * *'
    ];

    public static function translate($cron)
    {
        if (isset(self::$extendedMap[$cron])) {
            $cron = self::$extendedMap[$cron];
        }

        try {
            $fields = static::parseFields($cron);
            $orderedFields = static::orderFields($fields);
            $fieldsAsObject = static::getFieldsAsObject($fields);

            $translations = array_map(function ($field) use ($fieldsAsObject) {
                return $field->translate($fieldsAsObject);
            }, $orderedFields);

            return ucfirst(implode(' ', array_filter($translations)));
        } catch (\Throwable $th) {
            echo '###'.$cron.'###';
            throw new CronParsingException($cron);
        }
    }

    protected static function parseFields($cron)
    {
        $fields = explode(' ', $cron);

        return [
            new MinutesField($fields[0]),
            new HoursField($fields[1]),
            new DaysOfMonthField($fields[2]),
            new MonthsField($fields[3]),
            new DaysOfWeekField($fields[4]),
        ];
    }

    protected static function orderFields($fields)
    {
        // Group fields by CRON types.
        $onces = static::filterType($fields, 'Once');
        $everys = static::filterType($fields, 'Every');
        $incrementsAndMultiples = static::filterType($fields, 'Increment', 'Multiple');

        // Decide whether to keep one or zero CRON type "Every".
        $firstEvery = reset($everys)->position ?? PHP_INT_MIN;
        $firstIncrementOrMultiple = reset($incrementsAndMultiples)->position ?? PHP_INT_MAX;
        $numberOfEverysKept = $firstIncrementOrMultiple < $firstEvery ? 0 : 1;

        // Mark fields that will not be displayed as dropped.
        // This allows other fields to check whether some
        // information is missing and adapt their translation.
        foreach (array_slice($everys, $numberOfEverysKept) as $field) {
            $field->dropped = true;
        }

        return array_merge(
            // Place one or zero "Every" field at the beginning.
            array_slice($everys, 0, $numberOfEverysKept),

            // Place all "Increment" and "Multiple" fields in the middle.
            $incrementsAndMultiples,

            // Finish with the "Once" fields reversed (i.e. from months to minutes).
            array_reverse($onces)
        );
    }

    protected static function filterType($fields, ...$types)
    {
        return array_filter($fields, function ($field) use ($types) {
            return $field->hasType(...$types);
        });
    }

    protected static function getFieldsAsObject($fields)
    {
        return (object) [
            'minute' => $fields[0],
            'hour' => $fields[1],
            'day' => $fields[2],
            'month' => $fields[3],
            'weekday' => $fields[4],
        ];
    }
}

class CronType
{
    const TYPES = [
        'Every', 'Increment', 'Multiple', 'Once',
    ];

    public $type;
    public $value;
    public $count;
    public $increment;

    private function __construct($type, $value = null, $count = null, $increment = null)
    {
        $this->type = $type;
        $this->value = $value;
        $this->count = $count;
        $this->increment = $increment;
    }

    public static function every()
    {
        return new static('Every');
    }

    public static function increment($increment, $count = 1)
    {
        return new static('Increment', null, $count, $increment);
    }

    public static function multiple($count)
    {
        return new static('Multiple', null, $count);
    }

    public static function once($value)
    {
        return new static('Once', $value);
    }

    public static function parse($expression)
    {
        // Parse "*".
        if ($expression === '*') {
            return static::every();
        }

        // Parse fixed values like "1".
        if (preg_match("/^[0-9]+$/", $expression)) {
            return static::once((int) $expression);
        }

        // Parse multiple selected values like "1,2,5".
        if (preg_match("/^[0-9]+(,[0-9]+)+$/", $expression)) {
            return static::multiple(count(explode(',', $expression)));
        }

        // Parse ranges of selected values like "1-5".
        if (preg_match("/^([0-9]+)\-([0-9]+)$/", $expression, $matches)) {
            $count = $matches[2] - $matches[1] + 1;
            return $count > 1 
                ? static::multiple($count) 
                : static::once((int) $matches[1]);
        }

        // Parse incremental expressions like "*/2", "1-4/10" or "1,3/4".
        if (preg_match("/(.+)\/([0-9]+)$/", $expression, $matches)) {
            $range = static::parse($matches[1]);
            if ($range->hasType('Once', 'Every')) {
                return static::Increment($matches[2]);
            }
            if ($range->hasType('Multiple')) {
                return static::Increment($matches[2], $range->count);
            }
        }

        // Unsupported expressions throw exceptions.
        throw new CronParsingException($expression);
    }

    public function hasType()
    {
        return in_array($this->type, func_get_args());
    }
}


class DaysOfMonthField extends Field
{
    public $position = 2;

    public function translateEvery($fields)
    {
        if ($fields->weekday->hasType('Once')) {
            return "chaque {$fields->weekday->format()}";
        }

        return 'chaque jour';
    }

    public function translateIncrement()
    {
        if ($this->count > 1) {
            return "{$this->count} jour sur {$this->increment}";
        }

        return "chaque {$this->increment} jours";
    }
    
    public function translateMultiple()
    {
        return "{$this->count} jours par mois";
    }
    
    public function translateOnce($fields)
    {
        if ($fields->month->hasType('Once')) {
            return; // MonthsField adapts to "On January the 1st".
        }
        
        if ($fields->month->hasType('Every') && ! $fields->month->dropped) {
            return; // MonthsField adapts to "The 1st of every month".
        }

        if ($fields->month->hasType('Every') && $fields->month->dropped) {
            return 'le ' . $this->format() . ' de chaque mois';
        }

        return 'le ' . $this->format();
    }

    public function format()
    {
        if ($this->value == 1) {
            return $this->value . 'er';
        }

        return $this->value . 'ème';
    }
}

class DaysOfWeekField extends Field
{
    public $position = 4;

    public function translateEvery()
    {
        return 'Chaque année';
    }

    public function translateIncrement()
    {
        if ($this->count > 1) {
            return "{$this->count} jour de la semaine sur {$this->increment}";
        }

        return "Chaque {$this->increment} jour de la semaine";
    }
    
    public function translateMultiple()
    {
        return "{$this->count} jour par semaines";
    }
    
    public function translateOnce($fields)
    {
        if ($fields->day->hasType('Every') && ! $fields->day->dropped) {
            return; // DaysOfMonthField adapts to "Every Sunday".
        }

        return "les {$this->format()}s";
    }

    public function format()
    {
        if ($this->value < 0 || $this->value > 7) {
            throw new \Exception();
        }

        return [
            0 => 'dimanche',
            1 => 'lundi',
            2 => 'mardi',
            3 => 'mercredi',
            4 => 'jeudi',
            5 => 'vendredi',
            6 => 'samedi',
            7 => 'dimanche',
        ][$this->value];
    }
}

abstract class Field
{
    public $expression;
    public $type;
    public $value;
    public $count;
    public $increment;
    public $dropped = false;
    public $position;

    public function __construct($expression)
    {
        $this->expression = $expression;
        $cronType = CronType::parse($expression);
        $this->type = $cronType->type;
        $this->value = $cronType->value;
        $this->count = $cronType->count;
        $this->increment = $cronType->increment;
    }

    public function translate($fields)
    {
        foreach (CronType::TYPES as $type) {
            if ($this->hasType($type) && method_exists($this, "translate{$type}")) {
                return $this->{"translate{$type}"}($fields);
            }
        }
    }

    public function hasType()
    {
        return in_array($this->type, func_get_args());
    }

    public function times($count)
    {
        switch ($count) {
            case 1: return 'une fois';
            case 2: return 'deux fois';
            default: return "{$count} fois";
        }
    }
}

class HoursField extends Field
{
    public $position = 1;

    public function translateEvery($fields)
    {
        if ($fields->minute->hasType('Once')) {
            return 'une fois par heure';
        }

        return 'Chaque heure';
    }

    public function translateIncrement($fields)
    {
        if ($fields->minute->hasType('Once')) {
            return $this->times($this->count) . " chaque {$this->increment} heures";
        }

        if ($this->count > 1) {
            return "{$this->count} heures sur {$this->increment}";
        }

        if ($fields->minute->hasType('Every')) {
            return "de chaque {$this->increment} heures";
        }

        return "chaque {$this->increment} heures";
    }
    
    public function translateMultiple($fields)
    {
        if ($fields->minute->hasType('Once')) {
            return $this->times($this->count) . " par jour";
        }

        return "{$this->count} heures par jours";
    }
    
    public function translateOnce($fields)
    {
        return 'à ' . $this->format(
            $fields->minute->hasType('Once') ? $fields->minute : null
        );
    }

    public function format($minute = null)
    {
        $hour = $this->value;

        return $minute 
            ? "{$hour}h{$minute->format()}" 
            : "{$hour}h";
    }
}

class MinutesField extends Field
{
    public $position = 0;

    public function translateEvery()
    {
        return 'Chaque minute';
    }

    public function translateIncrement()
    {
        if ($this->count > 1) {
            return $this->times($this->count) . " chaque {$this->increment} minutes";
        }

        return "toutes les {$this->increment} minutes";
    }

    public function translateMultiple()
    {
        return $this->times($this->count) . " par heure";
    }

    public function format()
    {   
        return ($this->value < 10 ? '0' : '') . $this->value;
    }
}

class MonthsField extends Field
{
    public $position = 3;

    public function translateEvery($fields)
    {
        if ($fields->day->hasType('Once')) {
            return 'le ' . $fields->day->format() . ' de chaque mois';
        }

        return 'chaque mois';
    }

    public function translateIncrement()
    {
        if ($this->count > 1) {
            return "{$this->count} mois sur {$this->increment}";
        }

        return "chaque {$this->increment} mois";
    }
    
    public function translateMultiple()
    {
        return "{$this->count} mois par an";
    }
    
    public function translateOnce($fields)
    {
        if ($fields->day->hasType('Once')) {
            return "le {$fields->day->format()} de {$this->format()}";
        }

        return "en {$this->format()}";
    }

    public function format()
    {
        if ($this->value < 1 || $this->value > 12) {
            throw new \Exception();
        }

        return [
            1 => 'janvier',
            2 => 'février',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'aout',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'décembre',
        ][$this->value];
    }
}


/*
echo '#####'.CronTranslator::translate('* * * * *')."#####<br>\n";
echo '#####'.CronTranslator::translate('1 * * * *')."#####<br>\n";
echo '#####'.CronTranslator::translate('* 1 * * *')."#####<br>\n";
echo '#####'.CronTranslator::translate('5 10 * * *')."#####<br>\n";
echo '#####'.CronTranslator::translate('25 20 * * *')."#####<br>\n";
echo '#####'.CronTranslator::translate('* * 1 * *')."#####<br>\n";
echo '#####'.CronTranslator::translate('1 2 3 4 5')."#####<br>\n";
echo '#####'.CronTranslator::translate('1 1 1 1 1')."#####<br>\n";
echo '#####'.CronTranslator::translate('5 4 3 2 1')."#####<br>\n";
echo '#####'.CronTranslator::translate('* 1 * * *')."#####<br>\n";
*/

