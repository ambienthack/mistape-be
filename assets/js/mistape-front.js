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
			'OAnimation'     : 'oAnimationEnd',
			'msAnimation'    : 'MSAnimationEnd',
			'animation'      : 'animationend'
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
		onOpenDialog       : function () {
			return false;
		},
		onCloseDialog      : function () {
			return false;
		},
		onOpenAnimationEnd : function () {
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

	var Deco_Mistape = {

		onReady: function () {

			Deco_Mistape.dlg = new DialogFx(document.getElementById('mistape_dialog'), {
				onOpenDialog: function(dialog) {
					$(dialog.el).css('display', 'flex');
				},
				onCloseAnimationEnd: function (dialog) {
					$(dialog.el).css('display', 'none');
					Deco_Mistape.resetDialog();
				}

			});

			var $dialog = $(Deco_Mistape.dlg.el);

			$(document).on('click', '.mistape_action', function () {
				if ($(this).is('[data-action=send]')) {
					var data = $dialog.data('report');
					if ($dialog.data('mode') === 'comment') {
						data.comment = $dialog.find('#mistape_comment').val();
					}
					Deco_Mistape.reportSpellError(data);
				}
				else if ($(this).is('[data-dialog-close]')) {
					Deco_Mistape.dlg.toggle();
				}
			});

			$(document).keyup(function (ev) {
				if (ev.keyCode === 13 && ev.ctrlKey && ev.target.nodeName.toLowerCase() !== 'textarea' && $('#mistape_dialog.dialog--open').length === 0) {
					var report = Deco_Mistape.getSelectionData();
					if (report) {
						Deco_Mistape.showDialog(report);
					}
				}
			});
		},

		showDialog: function (report) {
			if (report.hasOwnProperty('selection') && report.hasOwnProperty('context')) {
				var $dialog = $(Deco_Mistape.dlg.el);

				if ($dialog.data('mode') == 'notify') {
					Deco_Mistape.reportSpellError(report);
					Deco_Mistape.dlg.toggle();
				}
				else {
					$dialog.data('report', report);
					$dialog.find('#mistape_reported_text').html(report.preview_text);
					Deco_Mistape.dlg.toggle();
				}
			}
		},

		resetDialog: function () {
			var $dialog = $(Deco_Mistape.dlg.el);
			if ($dialog.data('mode') != 'notify') {
				$dialog.find('#mistape_confirm_dialog').css('display', '');
				$dialog.find('#mistape_success_dialog').remove();
			}
		},

		reportSpellError: function (data) {
			data.action = 'mistape_report_error';
			var $dialog = $(Deco_Mistape.dlg.el);

			$.ajax({
				type    : 'post',
				dataType: 'json',
				url     : mistape_args.ajaxurl,
				data    : data,
				success : function (response) {
					if ($dialog.data('mode') != 'notify') {
						if (response && typeof response.data != 'undefined') {
							$dialog.fadeOut(150, function () {
								$dialog.find('#mistape_confirm_dialog').hide().after(response.data);
								$dialog.fadeIn(150);
							});
						}
						else if ($('#mistape_dialog.dialog--open').length !== 0) {
							Deco_Mistape.dlg.toggle();
						}
					}
				}
			})
		},

		getSelectionData: function () {
			var parentEl, sel, selChars, selWord, textToHighlight, maxContextLength = 140;

			var stringifyContent = function (string) {
				return typeof string == 'string' ? string.replace(/\s*(?:(?:\r\n)+|\r+|\n+)\t*/gm, '\r\n').replace(/\s{2,}/gm, ' ') : '';
			};

			var isSubstrUnique = function (substr, context) {
				var split = context.split(substr);
				var count = split.length - 1;
				return count === 1;
			};

			var getExactSelPos = function(selection, context) {
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
					contents  : context,
					extensions: {
						left : contentPrepend,
						right: contentAppend
					}
				};
			};

			var restoreInitSelection = function (sel, initialSel) {
				sel.collapse(initialSel.anchorNode, initialSel.anchorOffset);
				sel.extend(initialSel.focusNode, initialSel.focusOffset);
			};

			// Check for existence of window.getSelection() and that it has a
			// modify() method. IE 9 has both selection APIs but no modify() method.
			if (window.getSelection && (sel = window.getSelection()).modify) {
				if (!sel.isCollapsed) {

					selChars = sel.toString();

					if (!selChars || selChars.length > maxContextLength) {
						return;
					}

					if (sel.rangeCount) {
						parentEl = sel.getRangeAt(0).commonAncestorContainer.parentNode;
						while (parentEl.textContent == sel.toString()) {
							parentEl = parentEl.parentNode;
						}
					}

					// Detect if selection is backwards
					var range = document.createRange();
					range.setStart(sel.anchorNode, sel.anchorOffset);
					range.setEnd(sel.focusNode, sel.focusOffset);
					var backwards = range.collapsed;
					range = null;

					var initialSel = {
						focusNode   : sel.focusNode,
						focusOffset : sel.focusOffset,
						anchorNode  : sel.anchorNode,
						anchorOffset: sel.anchorOffset
					};

					// modify() works on the focus of the selection
					var endNode = sel.focusNode, endOffset = sel.focusOffset;

					var direction = [], secondChar, oneBeforeLastChar;
					if (backwards) {
						direction = ['backward', 'forward'];
						secondChar = selChars.charAt(selChars.length - 1);
						oneBeforeLastChar = selChars.charAt(0);
					} else {
						direction = ['forward', 'backward'];
						secondChar = selChars.charAt(0);
						oneBeforeLastChar = selChars.charAt(selChars.length - 1);
					}

					sel.collapse(sel.anchorNode, sel.anchorOffset);
					sel.modify("move", direction[0], "character");

					if (null === secondChar.match(/'[\w\d]'/)) {
						sel.modify("move", direction[0], "character");
					}
					sel.modify("move", direction[1], "word");
					sel.extend(endNode, endOffset);
					sel.modify("extend", direction[1], "character");
					if (null === oneBeforeLastChar.match(/'[\w\d]'/)) {
						sel.modify("extend", direction[1], "character");
					}
					sel.modify("extend", direction[0], "word");
					if (!backwards && sel.focusOffset === 1) {
						sel.modify("extend", 'backward', "character");
					}
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

						// workaround for WebKit bug: undo last iteration
						if (lengthBefore - lengthAfter > 1) {
							sel.modify('extend', 'forward', 'character');
							break;
						}
					}

					selWord = stringifyContent(sel.toString().trim());
				}
			} else if ((sel = document.selection) && sel.type != "Control") {
				var textRange = sel.createRange();

				if (!textRange || textRange.text.length > maxContextLength) {
					return;
				}

				if (textRange.text) {
					selChars = textRange.text;
					textRange.expand("word");
					// Move the end back to not include the word's trailing space(s),
					// if necessary
					while (/\s$/.test(textRange.text)) {
						textRange.moveEnd("character", -1);
					}
					selWord = textRange.text;
					parentEl = textRange.parentNode;
				}
			}

			if (typeof parentEl == 'undefined') {
				return;
			}

			var selToFindInContext, contextsToCheck = {
				textContent: parentEl.textContent,
				innerText  : parentEl.innerText
			};

			textToHighlight = selWord;

			for (var method in contextsToCheck) {
				if (contextsToCheck.hasOwnProperty(method) && typeof contextsToCheck[method] != 'undefined') {

					// start from counting selected word occurences in context
					var scope = {selection: 'word', context: 'initial'};
					var context = stringifyContent(contextsToCheck[method].trim());
					var selWithContext = stringifyContent(sel.toString().trim());
					var selPos; // what we are looking for
					var selExactMatch = false;

					if ((selPos = getExactSelPos(selWithContext, context)) != -1) {
						selExactMatch = true;
						selToFindInContext = selWithContext;
						break;
					}

					// if there is more than one occurence, extend the selection
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
				restoreInitSelection(sel, initialSel);
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
				previewText = truncatedContext.substring(0, selPos) + selWithContextHighlighted + truncatedContext.substring(selPos + selWithContext.length) || selWithContextHighlighted
			}
			else {
				previewText = truncatedContext.replace(selWithContext, selWithContextHighlighted) || selWithContextHighlighted
			}

			return {
				selection   : selChars,
				word        : selWord,
				replace_context : selToFindInContext,
				context     : truncatedContext,
				preview_text: previewText
			};
		}
	};

	$(document).ready(Deco_Mistape.onReady);

})(jQuery);
