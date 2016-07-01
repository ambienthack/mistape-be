/**
 * dialogFx.js v1.0.0
 * http://www.codrops.com
 *
 * Licensed under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Copyright 2014, Codrops
 * http://www.codrops.com
 */
(function (window) {

    'use strict';

    var support = {animations: Modernizr.cssanimations},
        animEndEventNames = {
            'WebkitAnimation': 'webkitAnimationEnd',
            'OAnimation': 'oAnimationEnd',
            'msAnimation': 'MSAnimationEnd',
            'animation': 'animationend'
        },
        animEndEventName = animEndEventNames[Modernizr.prefixed('animation')],
        onEndAnimation = function (el, callback) {
            var onEndCallbackFn = function (ev) {
                if (support.animations) {
                    if (ev.target != this) return;
                    this.removeEventListener(animEndEventName, onEndCallbackFn);
                }
                if (callback && typeof callback === 'function') {
                    callback.call();
                }
            };
            if (support.animations) {
                el.addEventListener(animEndEventName, onEndCallbackFn);
            }
            else {
                onEndCallbackFn();
            }
        };

    function extend(a, b) {
        for (var key in b) {
            if (b.hasOwnProperty(key)) {
                a[key] = b[key];
            }
        }
        return a;
    }

    function DialogFx(el, options) {
        this.el = el;
        this.options = extend({}, this.options);
        extend(this.options, options);
        this.isOpen = false;
        this._initEvents();
    }

    DialogFx.prototype.options = {
        // callbacks
        onOpenDialog: function () {
            return false;
        },
        onCloseDialog: function () {
            return false;
        },
        onOpenAnimationEnd: function () {
            return false;
        },
        onCloseAnimationEnd: function () {
            return false;
        }
    };

    DialogFx.prototype._initEvents = function () {
        var self = this;

        // esc key closes dialog
        document.addEventListener('keydown', function (ev) {
            var keyCode = ev.keyCode || ev.which;
            if (keyCode === 27 && self.isOpen) {
                self.toggle();
            }
        });

        this.el.querySelector('.dialog__overlay').addEventListener('click', this.toggle.bind(this));
    };

    DialogFx.prototype.toggle = function () {
        var self = this;
        if (this.isOpen) {
            jQuery(this.el).removeClass('dialog--open');
            jQuery(self.el).addClass('dialog--close');

            onEndAnimation(this.el.querySelector('.dialog__content'), function () {
                jQuery(self.el).removeClass('dialog--close');
                self.options.onCloseAnimationEnd(self);
            });

            // callback on close
            this.options.onCloseDialog(this);
        }
        else {
            jQuery(this.el).addClass('dialog--open');

            // callback on open
            this.options.onOpenDialog(this);

            onEndAnimation(this.el.querySelector('.dialog__content'), function () {
                jQuery(self.el).removeClass('dialog--close');
                self.options.onOpenAnimationEnd(self);
            });
        }
        this.isOpen = !this.isOpen;
    };

    // add to global namespace
    window.DialogFx = DialogFx;

})(window);


/**
 * Mistape
 */
(function ($) {

    // return if no args passed from backend
    if (!window.decoMistape) {
        return;
    }

    window.decoMistape = $.extend(window.decoMistape, {

        onReady: function () {
            decoMistape.initDialogFx();

            var $dialog = $(decoMistape.dlg.el);

            $(document).on('click', '.mistape_action', function () {
                if ($(this).is('[data-action=send]')) {
                    var data;
                    if (!$dialog.data('dry-run') && (data = $dialog.data('report'))) {
                        if ($dialog.data('mode') === 'comment') {
                            data.comment = $dialog.find('#mistape_comment').val();
                            $('#mistape_comment').val('');
                        }
                        decoMistape.reportSpellError(data);
                    }
                    decoMistape.animateLetter();
                }
                else if ($(this).is('[data-dialog-close]')) {
                    decoMistape.dlg.toggle();
                }
            });

            $(document).keyup(function (ev) {
                if (ev.keyCode === 13 && ev.ctrlKey && ev.target.nodeName.toLowerCase() !== 'textarea' && $('#mistape_dialog.dialog--open').length === 0) {
                    var report = decoMistape.getSelectionData();
                    if (report) {
                        decoMistape.showDialog(report);
                    }
                }
            });
        },

        initDialogFx: function() {
            decoMistape.dlg = new DialogFx(document.getElementById('mistape_dialog'), {
                onOpenDialog: function (dialog) {
                    $(dialog.el).css('display', 'flex');
                },
                onCloseAnimationEnd: function (dialog) {
                    $(dialog.el).css('display', 'none');
                    decoMistape.resetDialog();
                }
            });
        },

        animateLetter: function () {
            var dialog = $(decoMistape.dlg.el),
                content = dialog.find('.dialog__content'),
                letterTop = dialog.find('.mistape-letter-top'),
                letterFront = dialog.find('.mistape-letter-front'),
                letterBack = dialog.find('.mistape-letter-back'),
                dialogWrap = dialog.find('.dialog-wrap');

            content.addClass('show-letter');

            setTimeout(function () {
                var y = (letterTop.offset().top - letterFront.offset().top) + letterTop.outerHeight();
                letterTop.css({
                    'bottom': Math.floor(y),
                    'opacity': 1
                });
                jQuery('.mistape-letter-back-top').hide();
                if (content.hasClass('with-comment')) {
                    dialogWrap.css('transform', 'scaleY(0.5) scaleX(0.28)');
                } else {
                    dialogWrap.css('transform', 'scaleY(0.5) scaleX(0.4)');
                }
                setTimeout(function () {
                    if (content.hasClass('with-comment')) {
                        dialogWrap.css('transform', 'translateY(12%) scaleY(0.5) scaleX(0.4)');
                    } else {
                        dialogWrap.css('transform', 'translateY(28%) scaleY(0.5) scaleX(0.45)');
                    }
                    setTimeout(function () {
                        letterTop.css('z-index', '9');
                        letterTop.addClass('close');
                        setTimeout(function () {
                            dialogWrap.css({
                                'visibility': 'hidden',
                                'opacity': '0'
                            });
                            letterFront.css('animation', 'send-letter1 0.7s');
                            letterBack.css('animation', 'send-letter1 0.7s');
                            letterTop.css('animation', 'send-letter2 0.7s');
                            setTimeout(function () {
                                decoMistape.dlg.toggle();
                            }, 400)
                        }, 400)
                    }, 400)
                }, 300)
            }, 400);
        },

        showDialog: function (report) {
            if (report.hasOwnProperty('selection') && report.hasOwnProperty('context')) {
                var $dialog = $(decoMistape.dlg.el);

                if ($dialog.data('mode') == 'notify') {
                    decoMistape.reportSpellError(report);
                    decoMistape.dlg.toggle();
                }
                else {
                    $dialog.data('report', report);
                    $dialog.find('#mistape_reported_text').html(report.preview_text);
                    decoMistape.dlg.toggle();
                }
            }
        },

        resetDialog: function () {
            var $dialog = $(decoMistape.dlg.el);

            if ($dialog.data('mode') != 'notify') {
                $dialog.find('#mistape_confirm_dialog').css('display', '');
                $dialog.find('#mistape_success_dialog').remove();
            }

            // letter
            $dialog.find('.dialog__content').removeClass('show-letter');
            $dialog.find('.mistape-letter-top, .mistape-letter-front, .mistape-letter-back, .dialog-wrap, .mistape-letter-back-top').removeAttr('style');
            $dialog.find('.mistape-letter-top').removeClass('close');
        },

        reportSpellError: function (data) {
            data.action = 'mistape_report_error';

            $.ajax({
                type: 'post',
                dataType: 'json',
                url: decoMistape.ajaxurl,
                data: data
            })
        },

        getSelectionData: function () {
            // Check for existence of window.getSelection()
            if(!window.getSelection) {
                return false;
            }

            var parentEl, sel, selAnchorNode, selChars, selWord, textToHighlight, maxContextLength = 140;

            var stringifyContent = function (string) {
                return typeof string == 'string' ? string.replace(/\s*(?:(?:\r\n)+|\r+|\n+)\t*/gm, '\r\n').replace(/\s{2,}/gm, ' ') : '';
            };

            var isSubstrUnique = function (substr, context) {
                var split = context.split(substr);
                var count = split.length - 1;
                return count === 1;
            };

            var getExactSelPos = function (selection, context) {
                // if there is only one match, that's it
                if (isSubstrUnique(selWithContext, context)) {
                    return context.indexOf(selWithContext);
                }
                // check if we can get the occurrence match from selection offsets
                if (!backwards) {
                    // check anchor element
                    if (context.substring(sel.anchorOffset, sel.anchorOffset + selection.length) == selection) {
                        return sel.anchorOffset;
                    }
                    // check anchor parent element
                    var parentElOffset = sel.anchorOffset;
                    var prevEl = sel.anchorNode.previousSibling;
                    while (prevEl !== null) {
                        parentElOffset += prevEl.textContent.length;
                        prevEl = prevEl.previousSibling;
                    }
                    if (context.substring(parentElOffset, parentElOffset + selection.length) == selection) {
                        return parentElOffset;
                    }
                }
                if (backwards && context.substring(sel.focusOffset, sel.focusOffset + selection.length) == selection) {
                    return sel.anchorOffset;
                }
                return -1;
            };

            var getExtendedSelection = function (limit, nodeExtensions) {

                limit = parseInt(limit) || 40;
                nodeExtensions = nodeExtensions || {left: '', right: ''};
                var i = 0, selContent, selEndNode = sel.focusNode, selEndOffset = sel.focusOffset;

                while (i <= limit) {

                    if ((selContent = stringifyContent(sel.toString().trim())).length >= maxContextLength || isSubstrUnique(selContent, context)) {
                        return selContent;
                    }

                    // only even iteration
                    if (i % 2 == 0 && sel.anchorOffset > 0 || nodeExtensions.left.length && i < limit / 2) {
                        // reset
                        if (backwards) {
                            sel.collapseToEnd();
                        }
                        else {
                            sel.collapseToStart();
                        }
                        sel.modify("move", direction[1], "character");
                        sel.extend(selEndNode, selEndOffset);
                    }
                    else if (sel.focusOffset < sel.focusNode.length || nodeExtensions.right.length && i < limit / 2) {
                        sel.modify('extend', direction[0], 'character');
                        if (sel.focusOffset === 1) {
                            selEndNode = sel.focusNode;
                            selEndOffset = sel.focusOffset;
                        }
                    }
                    else if (i % 2 == 0) {
                        break;
                    }

                    i++;
                }

                return stringifyContent(sel.toString().trim());
            };

            var getExtendedContext = function (context, element, method) {
                var contentPrepend = '', contentAppend = '', e = element, i;
                method = method || 'textContent';

                for (i = 0; i < 20; i++) {
                    if (contentPrepend || (e = e.previousSibling) === null) {
                        break;
                    }

                    if ((contentPrepend = stringifyContent(e[method].trim())).length) {
                        context = contentPrepend + context;

                    }
                }

                // reset element
                e = element;

                for (i = 0; i < 20; i++) {
                    if (contentAppend || (e = e.nextSibling) === null) {
                        break;
                    }
                    if ((contentAppend = stringifyContent(e[method]).trim()).length) {
                        context += contentAppend;
                    }
                    else if (context.slice(-1) != ' ') {
                        context += ' ';
                    }
                }

                return {
                    contents: context,
                    extensions: {
                        left: contentPrepend,
                        right: contentAppend
                    }
                };
            };

            // check that getSelection() has a modify() method. IE has both selection APIs but no modify() method.
            // this works on modern browsers following standards
            if ((sel = window.getSelection()).modify) {
                // check if there is any text selected
                if (!sel.isCollapsed) {

                    selAnchorNode = sel.anchorNode;

                    /**
                     * So the first step is to get selection extended to the boundaries of words
                     *
                     * e.g. if the sentence is "What a wonderful life!" and selection is "rful li",
                     * we get "wonderful life" stored in selWord variable
                     */

                    selChars = sel.toString();

                    // return early if no selection to work with or if its length exceeds the limit
                    if (!selChars || selChars.length > maxContextLength) {
                        return;
                    }

                    // here we get the nearest parent node which is common for the whole selection
                    if (sel.rangeCount) {
                        parentEl = sel.getRangeAt(0).commonAncestorContainer.parentNode;
                        while (parentEl.textContent == sel.toString()) {
                            parentEl = parentEl.parentNode;
                        }
                    }

                    // Detect if selection was made backwards
                    // further logic depends on it
                    var range = document.createRange();
                    range.setStart(sel.anchorNode, sel.anchorOffset);
                    range.setEnd(sel.focusNode, sel.focusOffset);
                    var backwards = range.collapsed;
                    range = null;

                    // save initial selection to restore in the end
                    var initialSel = {
                        focusNode: sel.focusNode,
                        focusOffset: sel.focusOffset,
                        anchorNode: sel.anchorNode,
                        anchorOffset: sel.anchorOffset
                    };

                    // modify() works on the focus of the selection (not virtually) so we manipulate it
                    var endNode = sel.focusNode, endOffset = sel.focusOffset;

                    // determine second char of selection and the one before last
                    // they will be our starting point for word boundaries detection
                    var direction, secondChar, oneBeforeLastChar;
                    if (backwards) {
                        direction = ['backward', 'forward'];
                        secondChar = selChars.charAt(selChars.length - 1);
                        oneBeforeLastChar = selChars.charAt(0);
                    } else {
                        direction = ['forward', 'backward'];
                        secondChar = selChars.charAt(0);
                        oneBeforeLastChar = selChars.charAt(selChars.length - 1);
                    }

                    // collapse the cursor to the first char
                    sel.collapse(sel.anchorNode, sel.anchorOffset);
                    // move it one char forward
                    sel.modify("move", direction[0], "character");

                    // if the second character was a letter or digit, move cursor another step further
                    // this way we are certain that we are in the middle of the word
                    if (null === secondChar.match(/'[\w\d]'/)) {
                        sel.modify("move", direction[0], "character");
                    }

                    // and now we can determine the beginning position of the word
                    sel.modify("move", direction[1], "word");

                    // then extend the selection up to the initial point
                    // thus assure that selection starts with the beginning of the word
                    sel.extend(endNode, endOffset);

                    // do the same trick with the ending--extending it precisely up to the end of the word
                    sel.modify("extend", direction[1], "character");
                    if (null === oneBeforeLastChar.match(/'[\w\d]'/)) {
                        sel.modify("extend", direction[1], "character");
                    }
                    sel.modify("extend", direction[0], "word");
                    if (!backwards && sel.focusOffset === 1) {
                        sel.modify("extend", 'backward', "character");
                    }

                    // since different browser extend by "word" differently and some of them extend beyond the word
                    // covering spaces and punctuation, we need to collapse the selection back so it ends with the word
                    var i = 0, lengthBefore, lengthAfter;
                    while (i < 5 && (sel.toString().slice(-1).match(/[\s\n\t]/) || '').length) {
                        lengthBefore = sel.toString().length;
                        if (backwards) {
                            endNode = sel.anchorOffset == 0 ? sel.anchorNode.previousSibling : sel.anchorNode;
                            endOffset = sel.anchorOffset == 0 ? sel.anchorNode.previousSibling.length : sel.anchorOffset;
                            sel.modify('move', 'backward', 'character');
                            sel.extend(endNode, endOffset);
                            backwards = false;
                            direction = ['forward', 'backward'];
                        } else {
                            sel.modify('extend', 'backward', 'character');
                        }
                        lengthAfter = sel.toString().length;

                        // workaround for WebKit quirk: undo last iteration
                        if (lengthBefore - lengthAfter > 1) {
                            sel.modify('extend', 'forward', 'character');
                            break;
                        }
                    }

                    // finally, we've got a modified selection which is bound to words
                    // save it to highlight it later
                    selWord = stringifyContent(sel.toString().trim());
                }
            // this logic is for IE<10
            } else if ((sel = document.selection) && sel.type != "Control") {
                /*var textRange = sel.createRange();

                if (!textRange || textRange.text.length > maxContextLength) {
                    return;
                }

                if (textRange.text) {
                    selChars = textRange.text;
                    textRange.expand("word");
                    // Move the end back to not include the word's trailing space(s), if necessary
                    while (/\s$/.test(textRange.text)) {
                        textRange.moveEnd("character", -1);
                    }
                    selWord = textRange.text;
                    parentEl = textRange.parentNode;
                }*/
            }
            // this one is for IE11
            /*else if (sel = window.getSelection()) {
                debugger;
                var startOffset, startNode, endNode;
                range = document.createRange();
                if (range.collapsed) {
                    startNode = sel.focusNode;
                    endNode = sel.anchorNode;
                    startOffset = sel.focusOffset;
                    endOffset = sel.anchorOffset;
                }
                else {
                    startNode = sel.anchorNode;
                    endNode = sel.focusNode;
                    startOffset = sel.anchorOffset;
                    endOffset = sel.focusOffset;
                }
                while (startOffset && !startNode.textContent.slice(startOffset-1, startOffset).match(/[\s\n\t]/)) {
                    startOffset--;
                }
                while (endOffset < endNode.length && !endNode.textContent.slice(endOffset, endOffset+1).match(/[\s\n\t]/)) {
                    endOffset++;
                }
                debugger;
            }*/

            if (typeof parentEl == 'undefined') {
                return;
            }

            var selToFindInContext,
                contextsToCheck = { // different browsers implement different methods, we try them by turn
                    textContent: parentEl.textContent,
                    innerText: parentEl.innerText
                };

            textToHighlight = selWord;

            for (var method in contextsToCheck) {
                if (contextsToCheck.hasOwnProperty(method) && typeof contextsToCheck[method] != 'undefined') {

                    // start with counting selected word occurrences in context
                    var scope = {selection: 'word', context: 'initial'};
                    var context = stringifyContent(contextsToCheck[method].trim());
                    var selWithContext = stringifyContent(sel.toString().trim());
                    var selPos; // this is what we are going to find
                    var selExactMatch = false;

                    if ((selPos = getExactSelPos(selWithContext, context)) != -1) {
                        selExactMatch = true;
                        selToFindInContext = selWithContext;
                        break;
                    }

                    // if there is more than one occurrence, extend the selection
                    selWithContext = getExtendedSelection(40);
                    scope.selection = 'word extended';

                    if ((selPos = getExactSelPos(selWithContext, context)) != -1) {
                        selExactMatch = true;
                        selToFindInContext = selWithContext;
                        break;
                    }

                    // if still have duplicates, extend the context and selection, and try again
                    var initialContext = context;
                    var extContext = getExtendedContext(context, parentEl, method);
                    context = extContext.contents;
                    selWithContext = getExtendedSelection(40, extContext.extensions);
                    scope.context = 'extended';

                    if ((selPos = getExactSelPos(selWithContext, context)) != -1) {
                        selExactMatch = true;
                        selToFindInContext = selWithContext;
                        break;
                    }

                    // skip to next context getting method and start over, or exit
                    if (!selWithContext) {
                        continue;
                    }

                    if (isSubstrUnique(selWord, selWithContext) || selWord == selChars.trim()) {
                        context = selWithContext;
                        selWithContext = selWord;
                        textToHighlight = selWord;
                        scope.selection = 'word';
                        scope.context = 'extended';
                    }
                    else {
                        context = selWord;
                        selWithContext = selChars.trim();
                        textToHighlight = selChars.trim();
                        scope.selection = 'initial';
                        scope.context = 'word';
                    }

                    selPos = context.indexOf(selWithContext);

                    if (selPos !== -1) {
                        selToFindInContext = selWithContext;
                    }
                    else if ((selPos = context.indexOf(selWord)) !== -1) {
                        selToFindInContext = selWord;
                    }
                    else if ((selPos = context.indexOf(selChars)) !== -1) {
                        selToFindInContext = selChars;
                    }
                    else {
                        continue;
                    }
                    break;
                }
            }

            if (selToFindInContext) {
                sel.removeAllRanges();
            }
            else {
                decoMistape.restoreInitSelection(sel, initialSel);
                return;
            }

            if (scope.context == 'extended') {
                context = extContext.extensions.left + initialContext + ' ' + extContext.extensions.right;
            }

            var contExcerptStartPos, contExcerptEndPos, selPosInContext, highlightedChars, previewText;
            maxContextLength = Math.min(context.length, maxContextLength);

            var truncatedContext = context;

            if (context.length > maxContextLength) {

                if (selPos + selToFindInContext.length / 2 < maxContextLength / 2) {
                    selPosInContext = 'beginning';
                    contExcerptStartPos = 0;
                    contExcerptEndPos = Math.max(selPos + selToFindInContext.length, context.indexOf(' ', maxContextLength - 10));
                }
                else if (selPos + selToFindInContext.length / 2 > context.length - maxContextLength / 2) {
                    selPosInContext = 'end';
                    contExcerptStartPos = Math.min(selPos, context.indexOf(' ', context.length - maxContextLength + 10));
                    contExcerptEndPos = context.length;
                }
                else {
                    selPosInContext = 'middle';
                    var centerPos = selPos + Math.round(selToFindInContext.length / 2);
                    contExcerptStartPos = Math.min(selPos, context.indexOf(' ', centerPos - maxContextLength / 2 - 10));
                    contExcerptEndPos = Math.max(selPos + selToFindInContext.length, context.indexOf(' ', centerPos + maxContextLength / 2 - 10));
                }

                truncatedContext = context.substring(contExcerptStartPos, contExcerptEndPos).trim();

                if (selPosInContext != 'beginning' && context.charAt(contExcerptStartPos - 1) != '.') {
                    truncatedContext = '... ' + truncatedContext;
                }
                if (selPosInContext != 'end' && context.charAt(contExcerptStartPos + contExcerptEndPos - 1) != '.') {
                    truncatedContext = truncatedContext + ' ...';
                }
            }

            if (isSubstrUnique(selChars, textToHighlight)) {
                highlightedChars = textToHighlight.replace(selChars, '<span class="mistape_mistake_inner">' + selChars + '</span>')
            }
            else {
                highlightedChars = '<strong class="mistape_mistake_inner">' + textToHighlight + '</strong>';
            }

            var selWithContextHighlighted = selToFindInContext.replace(textToHighlight, '<span class="mistape_mistake_outer">' + highlightedChars + '</span>');

            if (selExactMatch && truncatedContext == context) {
                previewText = truncatedContext.substring(0, selPos) + selWithContextHighlighted + truncatedContext.substring(selPos + selWithContext.length) || selWithContextHighlighted;
            }
            else {
                previewText = truncatedContext.replace(selWithContext, selWithContextHighlighted) || selWithContextHighlighted;
            }

            return {
                selection: selChars,
                word: selWord,
                replace_context: selToFindInContext,
                context: truncatedContext,
                preview_text: previewText,
                post_id: decoMistape.getPostId(selAnchorNode)
            };
        },

        getPostId: function(el) {
            var id = null;

            $($(el).parents().add(el).get().reverse()).each(function(i, elToCheck){
                if (id = decoMistape.maybeGetPostIdFromElement(elToCheck)) {
                    return false;
                }
            });

            return id;
        },

        maybeGetPostIdFromElement: function(el) {
            var id;
            el = $(el);
            if (el.attr('id')) {
                id = el.attr('id').match(/post-id-(\d+)/) || el.attr('id').match(/post-(\d+)/);
                if (id) return id[1];
            }

            if (el.attr('class')) {
                $.each(el.attr('class').split(/\s+/), function(i, elClass) {
                    if (id = elClass.match(/post-id-(\d+)/) || elClass.match(/post-(\d+)/)) {
                        return false;
                    }
                    if (el.is('body')) {
                        if (id = elClass.match(/post-(\d+)/) || elClass.match(/(?:post|-)id-(\d+)/)) {
                            return false;
                        }
                    }
                });
                if (id) return id[1];
            }

            return false;
        },

        restoreInitSelection: function (sel, initialSel) {
            sel.collapse(initialSel.anchorNode, initialSel.anchorOffset);
            sel.extend(initialSel.focusNode, initialSel.focusOffset);
        }
    });

    $(document).ready(decoMistape.onReady);

})(jQuery);
