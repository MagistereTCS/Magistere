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
 * @package    atto_centralizedmedia
 * @copyright  2019 TCS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_centralizedmedia-button
 */

/**
 * Atto centralizedmedia selection tool.
 *
 * @namespace M.atto_centralizedmedia
 * @class Button
 * @extends M.editor_atto.EditorPlugin
 */

var COMPONENTNAME = 'atto_centralizedmedia',
    CSS = {
        URLINPUT: 'atto_centralizedmedia_urlentry',
        NAMEINPUT: 'atto_centralizedmedia_nameentry'
    },
    SELECTORS = {
        URLINPUT: '.' + CSS.URLINPUT,
        NAMEINPUT: '.' + CSS.NAMEINPUT
    },
    TEMPLATE = '' +
        '<form class="atto_form">' +
            '<label for="{{elementid}}_atto_centralizedmedia_urlentry">{{get_string "enterurl" component}}</label>' +
            '<input class="fullwidth {{CSS.URLINPUT}}" type="url" id="{{elementid}}_atto_centralizedmedia_urlentry" size="32"/><br/>' +
            '<button class="openmediabrowser" type="button">{{get_string "browserepositories" component}}</button>' +
            '<label for="{{elementid}}_atto_centralizedmedia_nameentry">{{get_string "entername" component}}</label>' +
            '<input class="fullwidth {{CSS.NAMEINPUT}}" type="text" id="{{elementid}}_atto_centralizedmedia_nameentry"' +
                    'size="32" required="true"/>' +
            '<div class="mdl-align">' +
                '<br/>' +
                '<button class="submit" type="submit">{{get_string "createmedia" component}}</button>' +
            '</div>' +
        '</form>';

Y.namespace('M.atto_centralizedmedia').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {

    /**
     * A reference to the current selection at the time that the dialogue
     * was opened.
     *
     * @property _currentSelection
     * @type Range
     * @private
     */
    _currentSelection: null,

    /**
     * A reference to the dialogue content.
     *
     * @property _content
     * @type Node
     * @private
     */
    _content: null,

    initializer: function() {
        if (this.get('host').canShowFilepicker('centralizedmedia')) {
            this.addButton({
                icon: 'icon',
                iconComponent: 'atto_centralizedmedia',
                callback: this._displayDialogue
            });
        }
    },

    /**
     * Display the centralizedmedia editing tool.
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
            headerContent: M.util.get_string('createmedia', COMPONENTNAME),
            focusAfterHide: true,
            focusOnShowSelector: SELECTORS.URLINPUT
        });

        // Set the dialogue content, and then show the dialogue.
        dialogue.set('bodyContent', this._getDialogueContent())
                .show();
    },

    /**
     * Return the dialogue content for the tool, attaching any required
     * events.
     *
     * @method _getDialogueContent
     * @return {Node} The content to place in the dialogue.
     * @private
     */
    _getDialogueContent: function() {
        var template = Y.Handlebars.compile(TEMPLATE);

        this._content = Y.Node.create(template({
            component: COMPONENTNAME,
            elementid: this.get('host').get('elementid'),
            CSS: CSS
        })).addClass('atto-centralized-resourses-media');

        this._content.one('.submit').on('click', this._setMedia, this);
        this._content.one('.openmediabrowser').on('click', function(e) {
            e.preventDefault();
            this.get('host').showFilepicker('centralizedmedia', this._filepickerCallback, this);
        }, this);


        return this._content;
    },

    /**
     * Update the dialogue after an centralizedmedia was selected in the File Picker.
     *
     * @method _filepickerCallback
     * @param {object} params The parameters provided by the filepicker
     * containing information about the image.
     * @private
     */
    _filepickerCallback: function(params) {
        if (params.url !== '') {
            this._content.one(SELECTORS.URLINPUT)
                    .set('value', params.url);
            this._content.one(SELECTORS.NAMEINPUT)
                    .set('value', params.file);
        }
    },

    /**
     * Update the centralizedmedia in the contenteditable.
     *
     * @method setMedia
     * @param {EventFacade} e
     * @private
     */
    _setMedia: function(e) {
        e.preventDefault();
        this.getDialogue({
            focusAfterHide: null
        }).hide();

        var form = e.currentTarget.ancestor('.atto_form'),
            url = form.one(SELECTORS.URLINPUT).get('value'),
            name = form.one(SELECTORS.NAMEINPUT).get('value'),
            host = this.get('host');

        if (url !== '' && name !== '') {
            host.setSelection(this._currentSelection);
            var mediahtml = '';
            
            if(url.indexOf("[[[cr_") === 0)
            {
              mediahtml = Y.Escape.html(url);
            }else{
              mediahtml = '<a href="' + Y.Escape.html(url) + '">' + name + '</a>';
            }

            host.insertContentAtFocusPoint(mediahtml);
            this.markUpdated();
        }
    }
});
