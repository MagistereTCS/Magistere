YUI.add('moodle-atto_geshi-button', function (Y, NAME) {

/* jshint ignore:start */
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

/*
 * @package    atto_geshi
 * @copyright  2019 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_geshi-button
 */

/**
 * Atto text editor geshi plugin.
 *
 * @namespace M.atto_geshi
 * @class button
 * @extends M.editor_atto.EditorPlugin
 */

var COMPONENTNAME = 'atto_geshi',
    CSS = {
        FORM: 'atto_geshi_form',
        TEXTAREA: 'atto_geshi_textarea'
    },
    SELECTORS = {
        FORM: '.' + CSS.FORM,
        CODE: '.' + CSS.TEXTAREA
    },
    TEMPLATE = '' +
        '<form class="{{CSS.FORM}}">' +
        '<label for="{{elementid}}_atto_geshi_textarea">{{get_string "insertcodeintextarea" component}}</label>' +
        '<span>{{get_string "openspan" component}}</span><br/>' +
        '<textarea class="fullwidth {{CSS.TEXTAREA}}" id="{{elementid}}_atto_geshi_textarea" rows="25" cols="32"></textarea><br/>' +
        '<span>{{get_string "closespan" component}}</span><br/>' +
        '<div class="mdl-align">' +
        '<br/>' +
        '<button class="submit" type="submit">{{get_string "insertcode" component}}</button>' +
        '</div>' +
        '</form>';

Y.namespace('M.atto_geshi').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {
    /**
     * A reference to the current selection at the time that the dialogue
     * was opened.
     *
     * @property _currentSelection
     * @type Range
     * @private
     */
    _currentSelection: null,

    initializer: function() {
        this.addButton({
            icon: 'icon',
            iconComponent: 'atto_geshi',
            buttonName: 'python',
            callback: this._displayDialogue
            // callbackArgs: 'python'
        });
    },

    /**
     * Display the Geshi selector.
     *
     * @method _displayDialogue
     * @private
     */
    _displayDialogue: function() {
        // Store the current selection.
        this._currentSelection = this.get('host').getSelection();
        if (this._currentSelection === false) {
            return;
        }

        var dialogue = this.getDialogue({
            headerContent: M.util.get_string('insertcode', COMPONENTNAME),
            focusAfterHide: true
        }, true);

        // Set the dialogue content, and then show the dialogue.
        dialogue.set('bodyContent', this._getDialogueContent())
            .show();
    },

    /**
     * Return the dialogue content for the tool.
     *
     * @method _getDialogueContent
     * @private
     * @return {Node} The content to place in the dialogue.
     */
    _getDialogueContent: function() {
        var template = Y.Handlebars.compile(TEMPLATE);

        var content = Y.Node.create(template({
            component: COMPONENTNAME,
            elementid: this.get('host').get('elementid'),
            CSS: CSS
        }));

        content.one('.submit').on('click', this._insertCode, this);
        return content;
    },

    /**
     * Insert the code into the editor.
     *
     * @method _insertCode
     * @param {EventFacade} e
     * @private
     */
    _insertCode: function(e) {
        // var code = e.target.getData('code');
        e.preventDefault();
        // Hide the dialogue.
        this.getDialogue({
            focusAfterHide: null
        }).hide();

        var form = e.currentTarget.ancestor(SELECTORS.FORM),
            code = form.one(SELECTORS.CODE).get('value'),
            host = this.get('host');

        if (code !== '') {
            code = code.replace(/(?:\r\n|\r|\n)/g, '<br>');
            code = '<span syntax = "python">' + code + '</span>';
            host.setSelection(this._currentSelection);

            // And add the code.
            host.insertContentAtFocusPoint(code);

            // And mark the text area as updated.
            this.markUpdated();

            this.editor.focus();
        }
    }
});

}, '@VERSION@', {"requires": ["moodle-editor_atto-plugin"]});
