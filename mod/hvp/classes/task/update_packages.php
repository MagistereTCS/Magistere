<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * The mod_hvp update packages
 *
 * @package    mod_hvp
 * @copyright  2019 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_hvp\task;

defined('MOODLE_INTERNAL') || die();


class update_packages extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('updatepackages', 'mod_hvp');
    }

    public function execute() {
        $h5p = new update_packages_class();
        $h5p->updateAllModules();
    }
}


class update_packages_class {
    private $modules = [
        1   => "H5P.Accordion",
        2   => "H5P.ArithmeticQuiz",
        3   => "H5P.Chart",
        4   => "H5P.Collage",
        5   => "H5P.Column",
        6   => "H5P.CoursePresentation",
        7   => "H5P.Dialogcards",
        8   => "H5P.DocumentationTool",
        9   => "H5P.DragQuestion",
        10  => "H5P.DragText",
        11  => "H5P.Blanks",
        12  => "H5P.ImageHotspotQuestion",
        13  => "H5P.GuessTheAnswer",
        14  => "H5P.IFrameEmbed",
        15  => "H5P.InteractiveVideo",
        16  => "H5P.MarkTheWords",
        17  => "H5P.MemoryGame",
        18  => "H5P.MultiChoice",
        19  => "H5P.PersonalityQuiz",
        20  => "H5P.Questionnaire",
        21  => "H5P.QuestionSet",
        22  => "H5P.SingleChoiceSet",
        23  => "H5P.Summary",
        24  => "H5P.Timeline",
        25  => "H5P.TrueFalse",
        26  => "H5P.ImageHotspots",
        27  => "H5P.ImageMultipleHotspotQuestion",
        28  => "H5P.ImageJuxtaposition",
        29  => "H5P.Audio",
        30  => "H5P.AudioRecorder",
        31  => "H5P.SpeakTheWords",
        32  => "H5P.Agamotto",
        33  => "H5P.ImageSequencing",
        34  => "H5P.Flashcards",
        35  => "H5P.SpeakTheWordsSet",
        36  => "H5P.ImageSlider",
        37  => "H5P.Essay",
        38  => "H5P.ImagePair",
        39  => "H5P.Dictation",
        40  => "H5P.BranchingScenario",
        41  => "H5P.ThreeImage",
        42  => "H5P.FindTheWords",
        43  => "H5P.InteractiveBook"
    ];
    private static $packagespath = 'mod/hvp/packages';
    private $editor;
    private $core;
    private $storage;
    public function __construct() {
        mtrace("Initialisation des classes H5P...");
        $this->editor = \mod_hvp\framework::instance('editor');
        $this->core = $this->editor->ajax->core;
        $this->storage = $this->editor->ajax->storage;
    }
    public static function uploadPackages() {
        mtrace("Installation de modules complémentaires...");
        global $CFG;
        $modules = array_filter(scandir($CFG->dirroot . DIRECTORY_SEPARATOR . self::$packagespath), function ($element) {
            return (in_array(substr($element, 0, 1), ['.', '_'])) ? false : true;
        });
            $interface = \mod_hvp\framework::instance('interface');
            $h5pvalidator = \mod_hvp\framework::instance('validator');
            $h5pstorage = \mod_hvp\framework::instance('storage');
            $h5pstorage->h5pC->mayUpdateLibraries(true);
            $total = count($modules);
            foreach ($modules as $number => $module) {
                mtrace("Module " . $module . " (" . $number . "/" . $total . ")", " ");
                $path = $CFG->tempdir . uniqid('/hvp-');
                $interface->getUploadedH5pFolderPath($path);
                $path .= '.h5p';
                $interface->getUploadedH5pPath($path);
                copy($CFG->dirroot . DIRECTORY_SEPARATOR . self::$packagespath . DIRECTORY_SEPARATOR . $module, $path);
                if(!$h5pvalidator->isValidPackage(true)) {
                    mtrace('INVALID_PACKAGE');
                } else {
                    $h5pstorage->savePackage(null, null, true);
                    $infos = \mod_hvp\framework::messages('info');
                    $errors = \mod_hvp\framework::messages('error');
                    foreach ($infos as $txt) {
                        mtrace($txt, ' ');
                    }
                    foreach ($errors as $txt) {
                        mtrace($txt, ' ');
                    }
                    if (!count($infos) && !count($errors)) {
                        mtrace("NOTHING_TO_DO");
                    }
                }
            }
    }
    public function updateAllModules() {
        mtrace("Installation et mise à jour de tous les modules...");
        $total = count($this->modules);
        foreach ($this->modules as $number => $module) {
            mtrace("Module " . $module . " (" . $number . "/" . $total . ")", " ");
            $this->libraryInstall($module);
        }
    }
    /**
     * Handles installation of libraries from the Content Type Hub.
     *
     * Accepts a machine name and attempts to fetch and install it from the Hub if
     * it is valid. Will also install any dependencies to the requested library.
     *
     * @param string $machineName Name of library that should be installed
     */
    private function libraryInstall($machineName) {
        // Determine which content type to install from post data
        if (!$machineName) {
            mtrace('NO_CONTENT_TYPE');
            return;
        }
        // Look up content type to ensure it's valid(and to check permissions)
        $contentType = $this->editor->ajaxInterface->getContentTypeCache($machineName);
        if (!$contentType) {
            mtrace('INVALID_CONTENT_TYPE');
            return;
        }
        $this->core->mayUpdateLibraries(TRUE);
        // Retrieve content type from hub endpoint
        $response = $this->callHubEndpoint(\H5PHubEndpoints::CONTENT_TYPES . $machineName);
        if (!$response) return;
        // Session parameters has to be set for validation and saving of packages
        if (!$this->isValidPackage(TRUE)) return;
        // Save H5P
        $storage = new \H5PStorage($this->core->h5pF, $this->core);
        $storage->savePackage(NULL, NULL, TRUE);
        // Clean up
        $this->storage->removeTemporarilySavedFiles($this->core->h5pF->getUploadedH5pFolderPath());
        mtrace("OK");
    }
    /**
     * Validates the package. Sets error messages if validation fails.
     *
     * @param bool $skipContent Will not validate cotent if set to TRUE
     *
     * @return bool
     */
    private function isValidPackage($skipContent = FALSE) {
        $validator = new \H5PValidator($this->core->h5pF, $this->core);
        if (!$validator->isValidPackage($skipContent, FALSE)) {
            $this->storage->removeTemporarilySavedFiles($this->core->h5pF->getUploadedH5pPath());
            mtrace('VALIDATION_FAILED');
            return FALSE;
        }
        return TRUE;
    }
    /**
     * Calls provided hub endpoint and downloads the response to a .h5p file.
     *
     * @param string $endpoint Endpoint without protocol
     *
     * @return bool
     */
    private function callHubEndpoint($endpoint) {
        $path = $this->core->h5pF->getUploadedH5pPath();
        $response = $this->core->h5pF->fetchExternalData(\H5PHubEndpoints::createURL($endpoint), NULL, TRUE, empty($path) ? TRUE : $path);
        if (!$response) {
            mtrace('DOWNLOAD_FAILED');
            return FALSE;
        }
        return TRUE;
    }
}
