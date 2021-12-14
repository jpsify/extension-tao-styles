/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 *
 */

/**
 * @author Dieter Raber <dieter@taotesting.com>
 */
define([
    'jquery',
    'i18n',
    'lodash',
    'core/promise',
    'ui/feedback',
    'ui/uploader',
    'ui/dialog',
    'tpl!taoStyles/editor/operatedBy'
], function ($, __, _, Promise, feedback, uploader, dialog, obTpl) {
    'use strict';


    var form = document.forms['tao-styles-form'],
        $form = $(form);
    
    var data = {};
    var originalData = {};

    var cssLink;

    var operatedBy;
    var opText;

    var ns = 'styleeditor';

    var requireConfirmation = false;


    /**
     * Errors to unordered list
     *
     * @param {Array} errors
     * @returns {String}
     */
    function errorsToHtml(errors) {
        return '<ul><li>' + errors.join('</li><li>') + '</li></ul>';
    }

    /**
     * Toggle button states
     *
     * @param state
     */
    function changeButtonState(state) {
        $form.find('.bottom-action-bar button').each(function () {
            this.disabled = state;
        });
    }


    /**
     * Display a confirmation dialog,
     * The "ok" button will save and resolve.
     * The "cancel" button will reject.
     *
     * @param {String} message - the confirm message to display
     * @returns {Promise} resolves once saved
     */
    function confirmBefore(message){
        return new Promise(function(resolve, reject){
            var confirmDlg;
            if(_.isEqual(data, originalData)){
                return resolve();
            }
            if(requireConfirmation){
                return reject();
            }
            requireConfirmation = true;
            confirmDlg = dialog({
                message: message,
                buttons:  [{
                    id : 'dontsave',
                    type : 'regular',
                    label : $form.find('.bottom-action-bar [role="style-reset"]').attr('title'),
                    close: true
                },{
                    id : 'cancel',
                    type : 'regular',
                    label : $form.find('.bottom-action-bar [role="style-saver"]').attr('title'),
                    close: true
                }],
                autoRender: true,
                autoDestroy: true,
                onDontsaveBtn : resolve,
                onCancelBtn : function onCancelBtn () {
                    confirmDlg.hide();
                    return reject();
                }
            })
                .on('closed.modal', function(){
                    requireConfirmation = false;
                });
        });
    }


    /**
     * Handle form submission
     */
    function initForm() {

        // Handle fields that require validation
        $form.find('button[type="submit"]').on('click.' + ns, function () {
            if (!this.form.checkValidity()) {
                this.form.elements.operatedByEmail.scrollIntoView();
            }
        });

        // reset to original state
        $form.find('button[type="reset"]').on('click.' + ns, function (e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            confirmBefore(__('This will discard all your current changes, are you sure?'))
                .then(function(){
                    form.reset();
                    $form.find(':checked').parents('figure').trigger('click');
                    data = _.cloneDeep(originalData);
                    applyLogo(data);
                    $('.file-drop .icon-close').trigger('click');
                    changeButtonState(true);
                })
                .catch(_.noop); //do nothing but prevent uncaught error
        });

        // Handle form submission
        $form.on('submit.' + ns, function (e) {

            var errorMsg = __('An error occurred while saving your theme');
            var errorMsgUrl = __('Logo link URL is not valid');
            var regexUrl = new RegExp("^http(s)?:\/\/[^<>\(\)]+$");

            e.preventDefault();

            if (data.link && !regexUrl.test(data.link)) {
                feedback().error(errorMsgUrl);
                return false;
            }

            $.ajax({
                type: form.method,
                url: form.action,
                data: {theme: data},
                success: function (response) {
                    if(_.isNull(response)) {
                        feedback().error(errorMsg);
                    }
                    else {
                        originalData = data;
                    }
                    feedback()[response.type](response.msg);
                },
                error: function () {
                    feedback().error(errorMsg);
                },
                dataType: 'json'
            });
        });

        // set initially selected logo
        if (form.querySelector('.logoUrl').getAttribute('src') === "") {
            data.logoUrl = '';
        } else {
            data.logoUrl = form.querySelector('.logoUrl').src;
        }
    }


    /**
     * Change theme on selection
     */
    function initThemeSelection() {
        $form.find('figure').on('click.' + ns, function () {
            var self = this;
            data.stylesheet = self.dataset.css;
            data.label = self.querySelector('label').textContent.trim();
            data.id = self.querySelector('[name="id"]').value;
            data.colors = (function () {
                var params = self.querySelectorAll('object>param'),
                    i = params.length,
                    colors = {};

                while (i--) {
                    colors[params[i].name] = params[i].value;
                }
                return colors;
            }());

            $('link[href^=\'data:text/css;charset=utf-8;base64\']').remove();

            cssLink = cssLink || form.querySelector('link[rel="stylesheet"]');
            cssLink.href = data.stylesheet;

            changeButtonState(false);
        });
        $form.find(':checked').parents('figure').trigger('click');
    }


    /**
     * Display the logo and add it to data
     *
     * @param logoData
     */
    function applyLogo(logoData) {
        data.logoUrl = logoData.logoUrl ? logoData.logoUrl : 'data:' + logoData.mime + ';base64,' + logoData.image;
        $('#tao-main-logo, .logoUrl').each(function () {
            this.src = data.logoUrl;
        });
    }


    /**
     * Mimic operated by feature
     */
    function initOperatedBy() {
        $('body>footer>.rgt').empty().append($(obTpl()));

        var $formFields = null,
            $current,
            i,
            formElementNamesToSave = [
                'operatedByEmail',
                'operatedByName',
                'link',
                'message'
            ]
        ;

        for(i in formElementNamesToSave) {
            $current = form.elements[formElementNamesToSave[i]];
            applyFormField($current);
            if ($formFields == null) {
                $formFields = $($current);
            } else {
                $formFields = $formFields.add($($current));
            }
        }
        applyOperatedBy();

        $formFields.on('invalid.' + ns, function (e) {
            var text = $(e.target).parent().find('span').text().trim(),
                msg;
            e.target.setCustomValidity('');
            if(e.target.type === 'email' && e.target.validity.typeMismatch) {
                msg = __('Please provide a valid email address');
                e.target.setCustomValidity(msg);
            }
            else if(e.target.type === 'url' && e.target.validity.typeMismatch) {
                msg = __('Please provide a valid logo link! (start with http:// or https://)');
                e.target.setCustomValidity(msg);
            }
            else if (!e.target.validity.valid) {
                msg = __('"%s" cannot be left blank', text);
                e.target.setCustomValidity(msg);
            }

        }).on('input.' + ns, function (e) {
            var msg, hasError = false;
            applyFormField(this);
            e.target.setCustomValidity('');
            if (e.target.type === 'email' && e.target.validity.typeMismatch) {
                msg = __('Please provide a valid email address');
                e.target.setCustomValidity(msg);
                hasError = true;
            }
            else if (e.target.type === 'url' && e.target.validity.typeMismatch) {
                msg = __('Please provide a valid logo link! (start with http:// or https://)');
                e.target.setCustomValidity(msg);
                hasError = true;
                changeButtonState(false);
            }
            if (!hasError) {
                applyOperatedBy();
                applyLogoDetails();
                changeButtonState(false);
            }
        });
    }


    /**
     * Add a form field to save.
     *
     * @param field
     */
    function applyFormField(field) {
        data[field.name] = field.value;
    }


    /**
     * Add operated by to the footer
     */
    function applyOperatedBy() {
        operatedBy = operatedBy || document.querySelector('body>footer .operator-by');
        opText = opText || operatedBy.querySelector('.operator-name');

        if (data['operatedByName'] && data['operatedByEmail']) {
            operatedBy.href = 'mailto:' + data['operatedByEmail'];
            opText.textContent = data['operatedByName'];
        }
    }

    /**
     * Add logo details to the logo
     */
    function applyLogoDetails() {
        $('#tao-main-logo').parent()
            .attr('title', data['message'])
            .attr('href', data['link']);
    }

    /**
     * Initialize logo upload
     */
    function initUpload() {

        // file uploader
        var errors = [];

        var $uploader = $('#upload-container');

        $uploader.on('upload.uploader', function (e, file, logoData) {
            applyLogo(logoData);
            changeButtonState(false);

        }).on('fail.uploader', function (e, file, err) {
            errors.push(__('Unable to upload file %s : %s', file.name, err));

        }).on('end.uploader', function () {
            if (errors.length > 0) {
                feedback().error(errorsToHtml(errors), {encodeHtml: false});
            }
            //reset errors
            errors = [];

        }).on('create.uploader', function () {
            $('.logo-area').on('click', function() {
                $('[name="content"]').trigger('click');
            });
        });

        // no need for additional verification, the backend takes care of this
        $uploader.uploader({
            upload: true,
            uploadUrl: $uploader.data('url')
        });
    }


    /**
     * @exports
     */
    return {
        setup: function setup() {
            initForm();
            initUpload();
            initThemeSelection();
            initOperatedBy();

            originalData = _.cloneDeep(data);
            changeButtonState(true);
        }
    };
});
