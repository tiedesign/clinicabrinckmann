/*
 * Functions:
 *  AddEvent
 * Objects:
 *  HtmlEditorField
 * Functions:
 *  MisspelCliq()
 *  getCodeAndWhich(ev)
 *  isTextChanged(ev)
 *  EditKeyHandle(ev)
 *  AddMisspelEvents()
 *  ReplaceWord()
 *  SpellCheck()
 *  ShowLoadingHandler()
 * Classes:
 *  CSpellchecker
 */

function AddEvent(obj, eventType, eventFunction, capture)
{
	if (obj.addEventListener) {
		if (typeof(capture) != 'boolean') capture = false;
		obj.addEventListener(eventType, eventFunction, capture);
		return true;
	}
	else if (obj.attachEvent) {
		return obj.attachEvent('on' + eventType, eventFunction);
	}
	return false;
}

var Fonts = ['Arial', 'Arial Black', 'Courier New', 'Tahoma', 'Times New Roman', 'Verdana'];

var HtmlEditorField = {
	editor: null,
	area: null,
	htmlMode: true,
	focused: false,

	_defaulFontName: 'Tahoma',
	_defaulFontSize: 2,
	
	_mainTbl: null,
	_sEditorClassName: 'wm_html_editor',
	_header: null,
	_iframesContainer: null,
	_colorPalette: null,
	_colorTable: null,

	_btnFontColor: null,
	_btnBgColor: null,
	_btnInsertLink: null,
	_btnInsertImage: null,
	_fontFaceSel: null,
	_fontSizeSel: null,

	_loaded: false,
	_designMode: false,
	_designModeStart: false,

	_colorMode: -1,
	_colorChoosing: 0,
	_currentColor: null,

	_range: null,
	_shown: false,
	
	_plainEditor: null,
	_htmlSwitcher: null,
	_waitHtml: null,
	
	_width: 0,
	_height: 0,
	
	_disabler: null,
	_disable: false,
	
	_builded: false,
	
	_tabindex: -1,

	_imgUploaderCont: null,
	_imgUploaderForm: null,
	_imgUploaderFile: null,

	_bActive: null,
	_oSignaturePane: null,

	setPlainEditor: function (plainEditor, htmlSwitcher, tabindex, useInsertImage)
	{
		this._plainEditor = plainEditor;
		this._htmlSwitcher = htmlSwitcher;
		this.replace();
		var obj = this;
		this._htmlSwitcher.onclick = function () {
			obj.onclickSwitcher();
			return false;
		};
		this._tabindex = tabindex;
		this._btnInsertImage.className = (useInsertImage && WebMail.Settings.allowInsertImage)
			? 'wm_toolbar_item' : 'wm_hide';
	},

	onclickSwitcher: function ()
	{
		if (!this._bActive && this._oSignaturePane !== null) {
			this._oSignaturePane.setUseSignature(true);
		}
		this.switchHtmlMode();
	},

	onfocus: function ()
	{
		this.focused = true;
		if (!this._bActive && this._oSignaturePane !== null) {
			this._oSignaturePane.setUseSignature(true);
		}
	},

	makeActive: function ()
	{
		this._bActive = true;
		if (this.htmlMode) {
			if (TextFormatter.removeAllTags(this.getHtml()) === Lang.SignatureEnteringHere) {
				this.setHtml('');
			}
			this._sEditorClassName = 'wm_html_editor';
			this._mainTbl.className = this._sEditorClassName;
			this.focus();
		}
		else {
			if (this._plainEditor.value === Lang.SignatureEnteringHere) {
				this._plainEditor.value = '';
			}
			this._plainEditor.className = 'wm_plain_editor_text wm_plain_editor_active';
			this._plainEditor.onfocus = function () { };
			this._plainEditor.focus();
		}
	},

	makeInactive: function (oSignaturePane)
	{
		this._bActive = false;
		this._oSignaturePane = oSignaturePane;
		if (this.htmlMode) {
			if (TextFormatter.removeAllTags(this.getHtml()) === '') {
				this.setHtml('<span style="color: #AAAAAA;">' + Lang.SignatureEnteringHere + '</span>');
			}
			this._sEditorClassName = 'wm_html_editor wm_html_editor_inactive';
			this._mainTbl.className = this._sEditorClassName;
		}
		else {
			if (this._plainEditor.value === '') {
				this._plainEditor.value = Lang.SignatureEnteringHere;
			}
			this._plainEditor.className = 'wm_plain_editor_text wm_plain_editor_inactive';
			this._plainEditor.onfocus = function () {
				oSignaturePane.setUseSignature(true);
			};
		}
	},

	getHtmlForSwitch: function ()
	{
		if (this._designMode) {
			var sHtml = this.getHtml();
			if ((Browser.ie || Browser.opera) && sHtml.length > 0) {
				sHtml = sHtml.replaceStr('<style> p { margin-top: 0px; margin-bottom: 0px; } </style>', '');
				sHtml = sHtml.replaceStr('<style> .misspel { background: url(skins/redline.gif) repeat-x bottom; display: inline; } </style>', '');
			}
			return sHtml;
		}
		else {
			return this._waitHtml;
		}
	},
	
	switchHtmlMode: function ()
	{
		if (!this._builded) return;
		if (this.htmlMode) {
			var sHtml = this.getHtmlForSwitch();
			if (confirm(Lang.ConfirmHtmlToPlain)) {
				sHtml = TextFormatter.htmlToPlain(sHtml);
				this.setPlain(sHtml);
			}
		}
		else {
			var sPlain = TextFormatter.plainToHtml(this.getPlain());
			this.setHtml(sPlain);
		}
	},
	
	loadEditArea: function ()
	{
		this._loaded = true;
		this.designModeOn();
	},
	
	disable: function ()
	{
		if (!(this._designMode && this._loaded && this._shown)) return;
		
		if (this._disabler == null) {
			this._disabler = CreateChild(document.body, 'div');
		}
		this._disabler.className = '';
		this._resizeDisabler();
		this._disable = true;
	},
	
	enable: function ()
	{
		if (!(this._designMode && this._loaded && this._shown)) return;

		if (this._disabler != null) {
			this._disabler.className = 'wm_hide';
		}
		this._disable = false;
	},
	
	_resizeDisabler: function ()
	{
		if (this._disabler != null) {
			var bounds = GetBounds(this.editor);
			this._disabler.style.position = 'absolute';
			this._disabler.style.left = bounds.Left + 'px';
			this._disabler.style.top = (bounds.Top - 1) + 'px';
			this._disabler.style.width = bounds.Width + 'px';
			this._disabler.style.height = bounds.Height + 'px';
			this._disabler.style.background = '#fff';
		}
	},
	
	_switchOnRtl: function ()
	{
		if (window.RTL && Browser.ie && this.area != null &&
				this.area.document != null && this.area.document.body != null) {
			if (Browser.version >= 7) {
				this.area.document.body.dir = 'rtl';
			}
			else {
				this.area.document.dir = 'rtl';
			}
		}
	},
	
	designModeOn: function ()
	{
		if (this._loaded && this._shown) {
			var doc = this.area.document;
			if (!Browser.ie) {
				doc = this.area.contentDocument;
			}
			try {
				doc.designMode = 'on';
				if (doc.designMode.toLowerCase() == 'on')	{
					this._designMode = true;
				}
			}
			catch (err) {}
			
			if (this._designMode && this._designModeStart) {
				this._setWaitHtml();
			}
			else {
				this._designModeStart = true;
				setTimeout('DesignModeOnHandler();', 5);
			}
		}
	},

	setBackground: function (sBackground)
	{
		this.editor.contentWindow.document.body.style.background = sBackground;
	},

	getDocument: function ()
	{
		return (Browser.ie) ? this.area.document : this.area.contentDocument;
	},

	_show: function ()
	{
		if (!this._builded)  return;

		this._colorMode = -1;
		this._mainTbl.className = this._sEditorClassName;
		if (this.editor == null) {
			var url = (window.RTL) ? EditAreaUrl + '?rtl=1' : EditAreaUrl;
			var editor = CreateChild(this._iframesContainer, 'iframe',
				[['src', url], ['frameborder', '0px'], ['id', 'EditorFrame']]);
			editor.className = 'wm_editor';
			this.editor = editor;
			this.editor.style.width = '100px';
			this.area = (Browser.ie) ? frames('EditorFrame') : editor;
		}
		this._shown = true;
		if (!this._disable && this._disabler != null) {
			this._disabler.className = 'wm_hide';
		}
	},

	hide: function ()
	{
		if (!this._builded)  return;

		if (this._shown) {
			this._mainTbl.focus();
			this.editor.tabIndex = -1;
		}
		this._shown = false;
		this._mainTbl.className = 'wm_hide';
		this._colorPalette.className = 'wm_hide';
		this._imgUploaderCont.className = 'wm_hide';
	},
	
	replace: function ()
	{
		if (!this._builded) return;

		if (this._plainEditor != null) {
			var bounds = GetBounds(this._plainEditor);
			this._mainTbl.style.position = 'absolute';
			this._mainTbl.style.left = (bounds.Left - 1) + 'px';
			this._mainTbl.style.top = (bounds.Top - 1) + 'px';
		}
		this._resizeDisabler();
	},
	
	resize: function (width, height)
	{
		if (!this._builded) return;

		this._width = width;
		this._height = height;
		if (this._plainEditor != null) {
			this._plainEditor.style.width = (width - 8) + 'px';
			this._plainEditor.style.height = (height - 8) + 'px';
		}
		this._mainTbl.style.width = (width + 3) + 'px';
		this._mainTbl.style.height = (height + 2) + 'px';
		if (this.editor != null) {
			this.editor.style.width = (width + 1) + 'px';
			var offsetHeight = this._header.offsetHeight;
			if (offsetHeight && (height - offsetHeight) > 0) {
				this.editor.style.height = (height - offsetHeight) + 'px';
			}
		}
		this.replace();
	},

    _showPlainEditor: function ()
    {
		this._header.className = 'wm_hide';
		this.htmlMode = false;
		this._htmlSwitcher.innerHTML = Lang.SwitchToHTMLMode;
		this.hide();
    },

	getPlain: function ()
	{
		if (this._builded && this._plainEditor != null) {
			return this._plainEditor.value;
		}
		return '';
	},

	setPlain: function (txt)
	{
		if (!this._builded) return;

		this._plainEditor.value = txt;
		this._showPlainEditor();
		SetCounterValueHandler();
	},
	
	_setWaitHtml: function ()
	{
		if (this._waitHtml != null) {
			this.setHtml(this._waitHtml);
		}
	},
	
	fillFontSelects: function ()
	{
		var fontName = this._comValue('FontName');
		switch (fontName) {
			case false:
			case null:
			case '':
				fontName = this._defaulFontName;
				break;
			default:
				fontName = fontName.replace(/'/g, '');
				break;
		}
		var fontSize = this._comValue('FontSize');
		switch (fontSize) {
			case '10px':
				fontSize = '1';
				break;
			case '13px':
				fontSize = '2';
				break;
			case '16px':
				fontSize = '3';
				break;
			case '18px':
				fontSize = '4';
				break;
			case '24px':
				fontSize = '5';
				break;
			case '32px':
				fontSize = '6';
				break;
			case '48px':
				fontSize = '7';
				break;
			case null:
			case '':
				fontSize = this._defaulFontSize;
				break;
			default:
				fontSize = parseInt(fontSize, 10);
				if (fontSize > 7) {
					fontSize = 7;
				}
				else if (fontSize < 1) {
					fontSize = 1;
				}
				break;
		}
		if (fontName && fontSize) {
			this._fontFaceSel.value = fontName;
			this._fontSizeSel.value = fontSize;
		}
	},
	
	_setDefaultFont: function ()
	{
		var doc = null;
		if (typeof this.area.document != 'undefined') {
			doc = this.area.document;
		}
		else if (typeof this.area.contentDocument != 'undefined') {
			doc = this.area.contentDocument;
		}
		if (doc != null) {
			doc.body.style.fontFamily = this._defaulFontName;
			this._fontFaceSel.value = this._defaulFontName;
			if (!Browser.opera) {
				switch (this._defaulFontSize) {
				case '1': 
					doc.body.style.fontSize = '10px';
					break;
				default:
				case '2':
					doc.body.style.fontSize = '13px';
					break;
				case '3':
					doc.body.style.fontSize = '16px';
					break;
				case '4': 
					doc.body.style.fontSize = '18px';
					break;
				case '5': 
					doc.body.style.fontSize = '24px';
					break;
				case '6': 
					doc.body.style.fontSize = '32px';
					break;
				case '7': 
					doc.body.style.fontSize = '48px';
					break;
				}
				this._fontSizeSel.value = this._defaulFontSize;
			}
		}
	},
	
	_blur: function ()
	{
		if (Browser.ie || Browser.opera) {
			this.area.blur();
		}
		else {
			this.editor.contentWindow.blur();
		}
	},
	
	focus: function ()
	{
		if (this._disable) return;
		HtmlEditorField.onfocus();
		if (!this._designMode) return;
		if (Browser.ie || Browser.opera) {
			this.area.focus();
		}
		else {
			this.editor.contentWindow.focus();
		}
	},
	
	_setFontCheckers: function ()
	{
		var obj = this;
		if (this.editor.contentWindow && this.editor.contentWindow.addEventListener) {
			this.editor.contentWindow.addEventListener('mousedown', function () {
				HtmlEditorField.onfocus();
			}, false);
			this.editor.contentWindow.addEventListener('mouseup', function () {
				obj.fillFontSelects();
				SetCounterValueHandler();
			}, false);
			this.editor.contentWindow.addEventListener('keyup', function () {
				obj.fillFontSelects();
				SetCounterValueHandler();
			}, false);
		}
		else if (Browser.ie) {
			this.area.document.onmousedown = function () {
				HtmlEditorField.onfocus();
			};
			this.area.document.onmouseup = function () {
				obj.fillFontSelects();
				SetCounterValueHandler();
			};
			this.area.document.onkeyup = function () {
				obj.fillFontSelects();
				SetCounterValueHandler();
			};
		}
		this._plainEditor.onmouseup = function () {
			SetCounterValueHandler();
		};
		this._plainEditor.onkeyup = function () {
			SetCounterValueHandler();
		};
	},

    _showHtmlEditor: function ()
    {
		this._mainTbl.className = this._sEditorClassName;
		this._header.className = 'wm_html_editor_toolbar';
		this.editor.tabIndex = this._tabindex;
		this.htmlMode = true;
		this._htmlSwitcher.innerHTML = Lang.SwitchToPlainMode;
    },

	setHtml: function (txt)
	{
		if (!this._builded) return;

		this._show();
		if (this._designMode) {
			var styles = '';
			if (Browser.ie) {
				styles = '<style> .misspel { background: url(skins/redline.gif) repeat-x bottom; display: inline; } </style>';
				styles += '<style> p { margin-top: 0px; margin-bottom: 0px; } </style>';
				this.area.document.open();
				this.area.document.writeln(styles + txt);
				this.area.document.close();
				this._switchOnRtl();
			}
			else {
				this.area.contentDocument.body.innerHTML = styles + txt;
			}
			this._setDefaultFont();
			this._setFontCheckers();
			this._waitHtml = null;
			this._showHtmlEditor();
			this.resize(this._width, this._height);
			SetCounterValueHandler();
		}
		else {
			this._waitHtml = txt;
			if (this._loaded) {
				this.designModeOn();
			}
		}
	},
	
	getHtml: function ()
	{
		var value = '';
		if (this._builded && this._designMode) {
			if (Browser.ie) {
				value = this.area.document.body.innerHTML;
				value = value.replace(/<\/p>/gi, '<br />').replace(/<p>/gi, '');
			}
			else {
				value = this.area.contentDocument.body.innerHTML;
				/*value = value.replace(/<\/pre>/gi, '<br />').replace(/<pre[^>]*>/gi, '');
				value = value.replace(/<\/code>/gi, '<br />').replace(/<code[^>]*>/gi, '');*/
			}
		}
		return value;
	},
	
	_comValue: function (cmd)
	{
		if (this._designMode) {
			if (typeof this.area.document != 'undefined') {
				return this.area.document.queryCommandValue(cmd);
			}
			else if (typeof this.area.contentDocument != 'undefined') {
				return this.area.contentDocument.queryCommandValue(cmd, false, null);
			}
		}
		return '';
	},

	executeStarted: function ()
	{
		if (this._executeStarted) {
			this._executeStarted = false;
			return true;
		}
		return false;
	},

	_execCom: function (cmd, param)
	{
		if (this._designMode) {
			if (!Browser.opera) {
				this.focus();
			}
			if (Browser.ie) {
				this._executeStarted = true;
				if (param) {
					this.area.document.execCommand(cmd, false, param);
				}
				else {
					this.area.document.execCommand(cmd);
				}
			}
			else {
				if (param) {
					this.area.contentDocument.execCommand(cmd, false, param);
				}
				else {
					this.area.contentDocument.execCommand(cmd, false, null);
				}
			}
			if (!Browser.opera) {
				this.focus();
			}
		}
	},

	createLink: function ()
	{
		if (Browser.ie) {
			this._execCom('CreateLink');
		}
		else if (this._designMode) {
			var bounds, top;
			bounds = GetBounds(this._btnInsertLink);
			top = bounds.Top + bounds.Height;
			HtmlEditorField.onfocus();
			window.open('linkcreator.html', 'ha_fullscreen', 
				'toolbar=no,menubar=no,personalbar=no,width=380,height=100,left=' + bounds.Left + ',top=' + top + 
				'scrollbars=no,resizable=no,modal=yes,status=no');
		}
	},

	createLinkFromWindow: function (url)
	{
		this._execCom('createlink', url);
	},
	
	unlink: function ()
	{
		if (Browser.ie) {
			this._execCom('Unlink');
		}
		else if (this._designMode) {
			this._execCom('unlink');
		}
	},

	insertImage: function ()
	{
		if (!WebMail.Settings.allowInsertImage) return;
		this._imgUploaderCont.className = 'wm_image_uploader_cont';
		this._rebuildUploadForm();
		var bounds = GetBounds(this._btnInsertImage);
		var iuStyle = this._imgUploaderCont.style;
        iuStyle.top = bounds.Top + bounds.Height + 'px';
        if (window.RTL) {
            iuStyle.right = GetWidth() - (bounds.Left + bounds.Width) + 'px';
        }
        else {
            iuStyle.left = bounds.Left + 'px';
        }
	},

	insertImageFromWindow: function (url)
	{
		if (!WebMail.Settings.allowInsertImage) return;
		this._imgUploaderCont.className = 'wm_hide';
		if (Browser.ie) {
			this._execCom('InsertImage', url);
		}
		else if (this._designMode) {
			this._execCom('insertimage', url);
		}
	},

	insertOrderedList: function ()
	{
		this._execCom('InsertOrderedList');
	},

	insertUnorderedList: function ()
	{
		this._execCom('InsertUnorderedList');
	},

	insertHorizontalRule: function ()
	{
		this._execCom('InsertHorizontalRule');
	},

	fontName: function (name)
	{
		this._fontFaceSel.value = name;
		this._execCom('FontName', name);
	},

	fontSize: function (size)
	{
		this._fontSizeSel.value = size;
		this._execCom('FontSize', size);
	},

	bold: function ()
	{
		this._execCom('Bold');
	},

	italic: function ()
	{
		this._execCom('Italic');
	},

	underline: function ()
	{
		this._execCom('Underline');
	},

	justifyLeft: function ()
	{
		this._execCom('JustifyLeft');
	},

	justifyCenter: function ()
	{
		this._execCom('JustifyCenter');
	},

	justifyRight: function ()
	{
		this._execCom('JustifyRight');
	},

	justifyFull: function ()
	{
		this._execCom('JustifyFull');
	},

	chooseColor: function (mode)
	{
		if (this._designMode) {
			if (this._colorMode == mode) {
				this._colorPalette.className = 'wm_hide';
				this._colorChoosing = 0;
				this._colorMode = -1;
			}
			else {
				this._colorMode = mode;
				var bounds = GetBounds((mode == 0) ? this._btnFontColor : this._btnBgColor);
				this._colorPalette.style.left = bounds.Left + 'px';
				this._colorPalette.style.top = bounds.Top + bounds.Height + 'px';
				this._colorPalette.className = 'wm_color_palette';

				if (Browser.ie) {
					this._executeStarted = true;
					this._range = this.area.document.selection.createRange();
					this._colorPalette.style.height = this._colorTable.offsetHeight + 8 + 'px';
					this._colorPalette.style.width = this._colorTable.offsetWidth + 8 + 'px';
				}
				else {
					this._colorPalette.style.height = this._colorTable.offsetHeight + 'px';
					this._colorPalette.style.width = this._colorTable.offsetWidth + 'px';
				}
				this._colorChoosing = 2;
			}
		}
	},

	selectFontColor: function (color)
	{
		if (this._designMode) {
			HtmlEditorField.onfocus();
			if (Browser.ie) {
				this._range.select();
				this._range.execCommand((this._colorMode == 0) ? 'ForeColor' : 'BackColor', false, color);
			}
			else {
				this.area.contentDocument.execCommand((this._colorMode == 0) ? 'ForeColor' : 'hilitecolor', false, color);
			}
			this.area.focus();
			this._colorPalette.className = 'wm_hide';
			this._colorMode = -1;
		}
	},
	
	changeLang: function ()
	{
		if (!this._builded) return;
		for (var key in this._buttons) {
			var but = this._buttons[key];
			if (typeof(but) === 'function') continue;
			if (but.imgDiv) {
				but.imgDiv.title = Lang[but.langField];
			}
		}
	},
	
	_buttons: {
		'link': {x: 0 * X_ICON_SHIFT, y: 0 * X_ICON_SHIFT, langField: 'InsertLink'},
		'unlink': {x: 1 * X_ICON_SHIFT, y: 0 * X_ICON_SHIFT, langField: 'RemoveLink'},
		'number': {x: 2 * X_ICON_SHIFT, y: 0 * X_ICON_SHIFT, langField: 'Numbering'},
		'list': {x: 3 * X_ICON_SHIFT, y: 0 * X_ICON_SHIFT, langField: 'Bullets'},
		'hrule': {x: 4 * X_ICON_SHIFT, y: 0 * X_ICON_SHIFT, langField: 'HorizontalLine'},
		'bld': {x: 5 * X_ICON_SHIFT, y: 0 * X_ICON_SHIFT, langField: 'Bold'},
		'itl': {x: 6 * X_ICON_SHIFT, y: 0 * X_ICON_SHIFT, langField: 'Italic'},
		'undrln': {x: 7 * X_ICON_SHIFT, y: 0 * X_ICON_SHIFT, langField: 'Underline'},
		'lft': {x: 8 * X_ICON_SHIFT, y: 0 * X_ICON_SHIFT, langField: 'AlignLeft'},
		'cnt': {x: 9 * X_ICON_SHIFT, y: 0 * X_ICON_SHIFT, langField: 'Center'},
		'rt': {x: 10 * X_ICON_SHIFT, y: 0 * X_ICON_SHIFT, langField: 'AlignRight'},
		'full': {x: 11 * X_ICON_SHIFT, y: 0 * X_ICON_SHIFT, langField: 'Justify'},
		'font_color': {x: 0 * X_ICON_SHIFT, y: 1 * X_ICON_SHIFT, langField: 'FontColor'},
		'bg_color': {x: 1 * X_ICON_SHIFT, y: 1 * X_ICON_SHIFT, langField: 'Background'},
		'spell': {x: 2 * X_ICON_SHIFT, y: 1 * X_ICON_SHIFT, langField: 'Spellcheck'},
		'insert_image': {x: 10 * X_ICON_SHIFT, y: 1 * X_ICON_SHIFT, langField: 'InsertImage'}
	},
	
	_addToolBarItem: function (parent, imgIndex)
	{
		var child = CreateChild(parent, 'a', [['href', 'javascript:void(0);']]);
		var cdiv = CreateChild(child, 'span');
		
		cdiv.className = 'wm_toolbar_item';
		cdiv.onmouseover = function () {
			this.className = 'wm_toolbar_item_over'; 
		};
		cdiv.onmouseout = function () {
			this.className = 'wm_toolbar_item';
		};
		var desc = this._buttons[imgIndex];
		var imgDiv = CreateChild(cdiv, 'span', [['title', Lang[desc.langField]],
			['style', 'background-position: -' + desc.x + 'px -' + desc.y + 'px']]);
		this._buttons[imgIndex].imgDiv = imgDiv;
		
		return cdiv;
	},
	
	_addToolBarSeparate: function (parent)
	{
		var child = CreateChild(parent, 'span');
		child.className = 'wm_toolbar_separate';
		return child;
	},
	
	clickBody: function ()
	{
		if (!this._builded) return;

		switch (this._colorChoosing) {
			case 2:
				this._colorChoosing = 1;
				break;
			case 1:
				this._colorChoosing = 0;
				this._colorPalette.className = 'wm_hide';
				this._colorMode = -1;
				break;
		}
	},
	
	setCurrentColor: function (color)
	{
		this._currentColor.style.backgroundColor = color;
	},

	_rebuildUploadForm: function ()
	{
		if (!WebMail.Settings.allowInsertImage) return;
		var form = this._imgUploaderForm;
		CleanNode(form);
		var inp = CreateChild(form, 'input', [['type', 'hidden'], ['name', 'inline_image'], ['value', '1']]);
		var tbl = CreateChild(form, 'table');
		var tr = tbl.insertRow(0);
		var td = tr.insertCell(0);
		var span = CreateChild(td, 'span');
		span.innerHTML = Lang.ImagePath + ': ';
		CreateChild(td, 'br');

		inp = CreateChild(td, 'input', [['type', 'file'], ['class', 'wm_file'], ['name', 'qqfile']]);
		this._imgUploaderFile = inp;
		
		td = tr.insertCell(1);
		inp = CreateChild(td, 'input', [['type', 'submit'], ['class', 'wm_button'], ['value', Lang.ImageUpload]]);
		CreateChild(td, 'br');
		inp = CreateChild(td, 'input', [['type', 'button'], ['class', 'wm_button'], ['value', Lang.Cancel]]);
		var obj = this;
		inp.onclick = function () {
			obj._imgUploaderCont.className = 'wm_hide';
		};
	},
	
	_buildUploadForm: function ()
	{
		CreateChild(document.body, 'iframe', [['src', EmptyHtmlUrl], ['name', 'UploadFrame'], ['id', 'UploadFrame'], ['class', 'wm_hide']]);
		this._imgUploaderCont = CreateChild(document.body, 'div', [['class', 'wm_hide']]);
		this._imgUploaderForm = CreateChild(this._imgUploaderCont, 'form', [['action', ImageUploaderUrl], ['method', 'post'], ['enctype', 'multipart/form-data'], ['target', 'UploadFrame'], ['id', 'ImageUploadForm']]);
		var obj = this;
		this._imgUploaderForm.onsubmit = function () {
			if (!WebMail.Settings.allowInsertImage) return false;
			if (obj._imgUploaderFile.value.length == 0) return false;
			var ext = GetExtension(obj._imgUploaderFile.value);
			switch (ext) {
				case 'jpg':
				case 'jpeg':
				case 'png':
				case 'bmp':
				case 'gif':
				case 'tif':
				case 'tiff':
					break;
				default:
					alert(Lang.WarningImageUpload);
					return false;
			}
			return true;
		};
	},
	
	_buildColorPalette: function ()
	{
		var div = CreateChild(document.body, 'div');
		div.className = 'wm_hide';
		this._colorPalette = div;
		var tbl = CreateChild(div, 'table');
		this._colorTable = tbl;
		var rowIndex = 0;
		var colors = ['#000000', '#333333', '#666666', '#999999', '#CCCCCC', '#FFFFFF', '#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#00FFFF', '#FF00FF'];
		var colorIndex = 0;
		var symbols = ['00', '33', '66', '99', 'CC', 'FF'];
		var obj = this;
		for (var jStart = 0; jStart < 6; jStart += 3) {
			for (var i = 0; i < 6; i++) {
				var tr = tbl.insertRow(rowIndex++);
				var cellIndex = 0;
				var td;
				if (rowIndex == 1) {
					td = tr.insertCell(cellIndex++);
					td.rowSpan = 12;
					td.className = 'wm_current_color_td';
					this._currentColor = CreateChild(td, 'div');
					this._currentColor.className = 'wm_current_color';
				}
				td = tr.insertCell(cellIndex++);
				td.className = 'wm_palette_color';
				td = tr.insertCell(cellIndex++);
				td.bgColor = colors[colorIndex++];
				td.className = 'wm_palette_color';
				td.onmouseover = function () {
					obj.setCurrentColor(this.bgColor);
				};
				td.onclick = function () {
					obj.selectFontColor(this.bgColor);
				};
				td = tr.insertCell(cellIndex++);
				td.className = 'wm_palette_color';
				for (var j = jStart; j < jStart + 3; j++) {
					for (var k = 0; k < 6; k++) {
						td = tr.insertCell(cellIndex++);
						td.bgColor = '#' + symbols[j] + symbols[k] + symbols[i];
						td.className = 'wm_palette_color';
						td.onmouseover = function () {
							obj.setCurrentColor(this.bgColor);
						};
						td.onclick = function () {
							obj.selectFontColor(this.bgColor);
						};
					}
				}
			}
		}
	}, //_buildColorPalette
	
	build: function (disableSpellChecker)
	{
		if (this._builded) return;
		var tbl = CreateChild(document.body, 'table');
		this._mainTbl = tbl;
		tbl.className = 'wm_hide';
		var tr = tbl.insertRow(0);
		this._header = tr;
		tr.className = 'wm_hide';
		var td = tr.insertCell(0);
		this._btnInsertLink = this._addToolBarItem(td, 'link');
		var obj = this;
		this._btnInsertLink.onclick = function () {
			obj.createLink();
		};
		var div = this._addToolBarItem(td, 'unlink');
		div.onclick = function () {
			obj.unlink();
		};
		this._btnInsertImage = this._addToolBarItem(td, 'insert_image');
		this._btnInsertImage.onclick = function () {
			obj.insertImage();
		};
		div = this._addToolBarItem(td, 'number');
		div.onclick = function () {
			obj.insertOrderedList();
		};
		div = this._addToolBarItem(td, 'list');
		div.onclick = function () {
			obj.insertUnorderedList();
		};
		div = this._addToolBarItem(td, 'hrule');
		div.onclick = function () {
			obj.insertHorizontalRule();
		};
		div = this._addToolBarSeparate(td);

		div = CreateChild(td, 'div');
		div.className = 'wm_toolbar_item';
		var fontFaceSel = CreateChild(div, 'select');
		fontFaceSel.className = 'wm_input wm_html_editor_select';
		for (var i = 0; i < Fonts.length; i++) {
			var opt = CreateChild(fontFaceSel, 'option', [['value', Fonts[i]]]);
			opt.innerHTML = Fonts[i];
			if (Fonts[i] == this._defaulFontName) {
				opt.selected = true;
			}
		}
		fontFaceSel.onchange = function () {
			obj.fontName(this.value);
		};
		this._fontFaceSel = fontFaceSel;
		div.style.margin = '0px';
		
		div = CreateChild(td, 'div');
		div.className = 'wm_toolbar_item';
		var fontSizeSel = CreateChild(div, 'select');
		fontSizeSel.className = 'wm_input wm_html_editor_select';
		for (i = 1; i < 8; i++) {
			opt = CreateChild(fontSizeSel, 'option', [['value', i]]);
			opt.innerHTML = i;
			if (i == this._defaulFontSize) {
				opt.selected = true;
			}
		}
		fontSizeSel.onchange = function () {
			obj.fontSize(this.value);
		};
		this._fontSizeSel = fontSizeSel;
		div.style.margin = '0px';
		
		div = this._addToolBarSeparate(td);
		div = this._addToolBarItem(td, 'bld');
		div.onclick = function () { 
			obj.bold();
		};
		div = this._addToolBarItem(td, 'itl');
		div.onclick = function () { 
			obj.italic();
		};
		div = this._addToolBarItem(td, 'undrln');
		div.onclick = function () { 
			obj.underline();
		};
		div = this._addToolBarItem(td, 'lft');
		div.onclick = function () { 
			obj.justifyLeft();
		};
		div = this._addToolBarItem(td, 'cnt');
		div.onclick = function () { 
			obj.justifyCenter();
		};
		div = this._addToolBarItem(td, 'rt');
		div.onclick = function () {
			obj.justifyRight();
		};
		div = this._addToolBarItem(td, 'full');
		div.onclick = function () { 
			obj.justifyFull();
		};
		this._btnFontColor = this._addToolBarItem(td, 'font_color');
		this._btnFontColor.onclick = function () { 
			obj.chooseColor(0);
		};
		this._btnBgColor = this._addToolBarItem(td, 'bg_color');
		this._btnBgColor.onclick = function () { 
			obj.chooseColor(1);
		};
		
		if (!disableSpellChecker) {
			div = this._addToolBarSeparate(td);
			div = this._addToolBarItem(td, 'spell');
			div.onclick = function () {
				SpellCheck();
			};
		}
		
		tr = tbl.insertRow(1);
		td = tr.insertCell(0);
		td.className = 'wm_html_editor_cell';
		td.colSpan = 1;
		this._iframesContainer = td;
		
		this._buildColorPalette();
		this._buildUploadForm();
		this._builded = true;
	}, //build
	
	cleanSpell_IE: function ()
	{
		if (this.area.document.selection && this.area.document.selection.createRange()) {
			var range = this.area.document.selection.createRange();
			
			// getting a cursor position
			var cursorRange = range.duplicate();
			cursorRange.moveStart('textedit', -1);
			var cursorPos = cursorRange.text.length;

			range.pasteHTML('<span id="#31337" />');
			var ghostElement = this.area.document.getElementById('#31337');
			var element = ghostElement.parentNode;
			element.removeChild(ghostElement);
			if (element.className == 'misspel') {
				var textNode = this.area.document.createTextNode(element.innerHTML);
				var elParent = element.parentNode;
				if (element.nextSibling != null) {
					elParent.insertBefore(textNode, element.nextSibling);
				}
				elParent.removeChild(element);
				
				// moving cursor to last position
				range.moveStart('textedit', -1);
				var zeta = cursorPos - range.text.length;
				range = this.area.document.selection.createRange();
				range.move('character', zeta);
				range.select();
			}
	    }
	},
	
	cleanSpell_Gecko: function ()
	{
		var sel = this.area.contentWindow.getSelection();
		var range = sel.getRangeAt(0);
		var focusOffset = sel.focusOffset;
		if (range.collapsed) {
			var element = range.commonAncestorContainer;
			if (element && element.parentNode) {
				var parent = element.parentNode;
				if (parent.className == 'misspel') {
					var newText = this.area.contentDocument.createTextNode(element.nodeValue);
					var repIn = parent.parentNode;
					repIn.replaceChild(newText, parent);
					sel.collapse(newText, focusOffset);
				}
			}
		}
	},

	updateEditorHandlers : function (eventFunction, eventsList)
	{
		var doc = Browser.ie ? this.area.document : this.area.contentWindow;
		for (var i = 0; i < eventsList.length; i++)
		{
			$addHandler(doc, eventsList[i],  eventFunction);
		}
	}	
};

function MisspelCliq() 
{
	var spell = WebMail._spellchecker;
	var lastWord = spell.currentWord;
	spell.currentWord = this.innerHTML;
	spell.misElement = this;
	var popupDiv = WebMail._spellchecker.popupDiv;
	if (spell.suggestWait) { 
		WebMail._spellchecker.DataSource.netLoader.abortRequests();
	}
	if (spell.misGetWords[spell.currentWord]) {
		CleanNode(popupDiv);
		if (spell.misGetWords[spell.currentWord].length == 0) {
			spell.popupShow(Lang.SpellNoSuggestions);
		} else {
			popupDiv.appendChild(spell.suggestionTable(spell.misGetWords[spell.currentWord]));
		}
		spell.suggestWait = false;
	}
	else {
		if (spell.currentWord != lastWord) {
			spell.suggestWait = true;
			var xml = '<param name="action" value="spellcheck" /><param name="request" value="suggest" />';
			xml += '<word>' + GetCData(spell.currentWord, false) + '</word>';
			WebMail._spellchecker.DataSource.request([], xml);
			spell.popupShow(Lang.SpellWait);
		}
	}
	WebMail._spellchecker.popupDiv.className = 'spell_popup_show';
	var bounds = GetBounds(this);
	var ifr_bounds = GetBounds(HtmlEditorField.editor);
	var browserDoc = HtmlEditorField.getDocument();
	var scrollY = WebMail._spellchecker.getScrollY(browserDoc);
	popupDiv.style.top = (bounds.Top + ifr_bounds.Top - scrollY + 20) + 'px';
	popupDiv.style.left = (bounds.Left + ifr_bounds.Left) + 'px';
}

function getCodeAndWhich(ev) {
	var key, inst, which;
	key = -1;
	if  (Browser.ie) {
		inst = HtmlEditorField.area;
		if (inst.window.event) {
			key = inst.window.event.keyCode;
			which = key;
		}
	} else if (ev) {
		key = ev.keyCode;
		which = ev.which;
	}
	return { k: key, w: which };
}

function isTextChanged(ev) {
	var kw, key, which;
	kw = getCodeAndWhich(ev);
	key = kw.k;
	which = kw.w;

	return (!(ev.ctrlKey==1 || ev.altKey==1) && //check pressed Alt or ctrl when another key was pressed.
			key != 16 &&				//shift
			key != 17 &&				//ctrl
			key != 18 &&				//alt
			key != 35 &&				//end
			key != 36 &&				//home
			key != 37 &&				//to the right
			key != 38 &&				//up
			key != 39 &&				//to the left
			key != 40 ||				//down
			(key == 0 && which != 0));	// FireFox;
}

function EditKeyHandle(ev) {
	if (isTextChanged(ev)) {
		if (Browser.ie) {
			HtmlEditorField.cleanSpell_IE();
		} else {
			HtmlEditorField.cleanSpell_Gecko();
		}
	}
}

function AddMisspelEvents() 
{
	var doc = HtmlEditorField.getDocument();
	AddEvent(doc, 'mousedown', function () { WebMail._spellchecker.popupHide(); });
	AddEvent(doc, 'scroll', function () { WebMail._spellchecker.popupRecalcCoords(); });
	var childs = doc.getElementsByTagName('span');
	for (var i = 0; i < childs.length; i++) {
		var node = childs.item(i);
		if (node.className && node.className == 'misspel') {
			if (Browser.ie) {
				node.onclick = MisspelCliq;
			}
			else {
				node.addEventListener('click', MisspelCliq, false);
			}
		}
	}
	
	if (doc.addEventListener) {
		doc.addEventListener('keypress', EditKeyHandle, true); 
	}
	else if (doc.attachEvent) {
		doc.attachEvent('onkeydown', EditKeyHandle);
	}
}

function ReplaceWord() {
	var strWord = Browser.ie ? this.innerText : this.textContent;
	var doc = HtmlEditorField.getDocument();
	var newTextNode = doc.createTextNode(strWord);
	var misElement = WebMail._spellchecker.misElement;
	var elParent = misElement.parentNode;
	elParent.replaceChild(newTextNode, misElement);
	WebMail._spellchecker.suggestWait = false;
	WebMail._spellchecker.popupHide();
}

function SpellCheck() {
	var hasOpenRequests = WebMail._spellchecker.DataSource.netLoader.hasOpenRequests();
	if (!WebMail._spellchecker.misspelWait || !hasOpenRequests) {
		WebMail._spellchecker.misGetWords = [];
		WebMail._spellchecker.misspelWait = true;
		var text = HtmlEditorField.getHtml();
		text = WebMail._spellchecker.StripMissTags(text);
		var xml = '<param name="action" value="spellcheck" /><param name="request" value="spell" />';
		xml += '<text>' + GetCData(text, true) + '</text>';
		WebMail._spellchecker.DataSource.request([], xml);
	}
}

function ShowLoadingHandler() {
	if (!WebMail._spellchecker.suggestWait) {
		WebMail.showInfo(Lang.Loading);
	}
}

function CSpellchecker() 
{
	this.misspelPos = [];
	this.misspelWait = false;
	this.suggestion = [];
	this.misElement = null;
	this.suggestWait = false;
	this.misGetWords = [];
	this.currentWord = '';
	this.popupDiv = document.getElementById('spell_popup_menu');
	this.DataSource = new CDataSource([], SpellcheckerUrl, ErrorHandler, LoadHandler, TakeDataHandler, ShowLoadingHandler);
}

CSpellchecker.prototype = {
	StrokeIt: function (word) 
	{
		return (word) ? '<span class="misspel">' + word + '</span>' : '';
	},
	
	// misspel is Array
	StrokeText: function (misspel, text) 
	{
		var newText = '';
		var lastPos = 0;
		if (text && misspel) {
			for (var i = 0; i < misspel.length; i++) {
				var misPos = misspel[i][0];
				var misLen = misspel[i][1];
				var begin = text.substring(lastPos, misPos);
				var misWord = text.substring(misPos, misPos + misLen);
				newText = newText + begin + this.StrokeIt(misWord);
				lastPos = misPos + misLen;
			}
			newText += text.substring(lastPos, text.length);
		}
		return newText;
	},
	
	StripMissTags: function (text) {
		var resText = text;
		var rep = /<span class="misspel">(.*?)<\/span>/i;
		if (Browser.ie) {
			rep = /<span class=misspel>(.*?)<\/span>/i;
		}
		var inText = rep.exec(resText);
		while (inText != null) {
			resText = resText.replace(rep, inText[1]);
			inText = rep.exec(resText);
		}
		return resText;
	},
	
	popupHide: function (caller) {
		if (caller && caller == 'document') {
			if (this.popupVisible()) {
				if (this.suggestWait) {
					this.DataSource.netLoader.abortRequests();
					this.suggestWait = false;
					this.currentWord = '';
				}
				this.popupDiv.className = 'spell_popup_hide';
			}
		}
		else {
			if (!this.suggestWait && this.popupVisible()) {
				this.popupDiv.className = 'spell_popup_hide';
			} 
		}
	},
	
	popupShow: function (text) {
		if (text) {
			CleanNode(this.popupDiv);
			var textNode = document.createElement('div');
			textNode.innerHTML = text;
			textNode.className = 'spell_spanDeactive';
			this.popupDiv.appendChild(textNode);	
			this.popupDiv.className = 'spell_popup_show';
		}
		else if (!this.popupVisible()) {
			this.popupDiv.className = 'spell_popup_show';
		}
	},
	
	popupVisible: function () {
		return (this.popupDiv.className == 'spell_popup_show') ? true : false;
	},
	
	popupRecalcCoords: function () {
		if (this.misElement) {
			var browserDoc, scrollY, bounds, ifr_bounds;
			browserDoc = HtmlEditorField.getDocument();
			scrollY = this.getScrollY(browserDoc);
			bounds = GetBounds(this);
			ifr_bounds = GetBounds(HtmlEditorField.area);
			this.popupDiv.style.top = (bounds.Top + ifr_bounds.Top - scrollY + 20) + 'px';
			this.popupDiv.style.left = (bounds.Left + ifr_bounds.Left) + 'px';
		}
	},
	
	suggestionTable: function (suggestions) {
		var sugTable, sugTBody, i, sugTRow, sugNode;
		
		sugTable = document.createElement('TABLE');
		sugTable.style.width = '180px';
		sugTBody = document.createElement('TBODY');
		sugTable.appendChild(sugTBody);
		for (i = 0; i < suggestions.length; i++) {
			sugTRow =  document.createElement('TR');
			sugNode = document.createElement('TD');
			if (Browser.ie) {
				sugNode.innerText = suggestions[i];
				sugNode.onclick = ReplaceWord;
				sugNode.onmouseover = this.Menu_hightlight_on;
				sugNode.onmouseout = this.Menu_hightlight_off;
			} else {
				sugNode.textContent = suggestions[i];
				sugNode.addEventListener('mouseover', this.Menu_hightlight_on, false);
				sugNode.addEventListener('mouseout', this.Menu_hightlight_off, false);
				sugNode.addEventListener('click', ReplaceWord, false);
			}
			sugNode.className = 'spell_spanDeactive';
			sugTBody.appendChild(sugTRow).appendChild(sugNode);
		}
		return sugTable;
	},
	
	getScrollY: function (doc) {
		var scrollY = 0;
		if (doc.body && typeof doc.body.scrollTop != 'undefined') {
			scrollY += doc.body.scrollTop;
			if (scrollY == 0 && doc.body.parentNode && typeof doc.body.parentNode != 'undefined') {
				scrollY += doc.body.parentNode.scrollTop;
			}
		} else if (typeof window.pageXOffset != 'undefined') {
			scrollY += window.pageYOffset;
		}
		return scrollY;
	},
	
	getFromXml: function (RootElement) {
		var action = RootElement.getAttribute('action');
		var spellParts = RootElement.childNodes;
		if (action == 'spellcheck') {
			var text = HtmlEditorField.getHtml();
			text = this.StripMissTags(text);
			
			this.misspelPos = [];
			for (var i = 0; i < spellParts.length; i++) {
				var mispNode = spellParts.item(i);
				if (mispNode.nodeName == 'misp') {
					var misPos = mispNode.getAttribute('pos');
					var misLen = mispNode.getAttribute('len');
					this.misspelPos[this.misspelPos.length] = [(misPos - 0), (misLen - 0)];
				}
			}
			var newText = this.StrokeText(this.misspelPos, text);
			HtmlEditorField.setHtml(newText);
			WebMail._spellchecker.misspelWait = false;
			AddMisspelEvents();
		}
		else if (action == 'suggest') {
			this.suggestion = [];
			var suggestNode = [];
			for (var i = 0; i < spellParts.length; i++) {
				suggestNode = spellParts.item(i);
				if (suggestNode.nodeName == 'word') {
					var childs = suggestNode.childNodes;
					var word = (childs.length > 0) ? Trim(childs[0].nodeValue) : '';
					this.suggestion[this.suggestion.length] = word;
				}
			}
			var s = '';
			var suggestWords = [];
			for (i = 0; i < this.suggestion.length; i++) {
				s = s +  this.suggestion[i] + ' ';
				suggestWords[i] = this.suggestion[i];
			}
			WebMail._spellchecker.misGetWords[WebMail._spellchecker.currentWord] = suggestWords;
			
			CleanNode(this.popupDiv);
			if (this.suggestion.length > 0) {
				this.popupDiv.appendChild(this.suggestionTable(this.suggestion));
			}
			else {
				this.popupShow(Lang.SpellNoSuggestions);
			}
			this.popupShow();
			WebMail._spellchecker.suggestWait = false;  
		}
		else if (action == 'error') {
			var errorStr = RootElement.getAttribute('errorstr');
			WebMail._errorObj.show(errorStr);
			WebMail._spellchecker.suggestWait = false;
			WebMail._spellchecker.misspelWait = false;			
		}
	}, 
	
	Menu_hightlight_on: function () {
		this.className = 'spell_spanActive';
	},
	
	Menu_hightlight_off: function () {
		this.className = 'spell_spanDeactive';
	}
};

/* html editor handlers */
function EditAreaLoadHandler() {
	HtmlEditorField.loadEditArea();
}

function CreateLinkHandler(url) {
	HtmlEditorField.createLinkFromWindow(url);
}

function InsertImageHandler(url) {
	HtmlEditorField.insertImageFromWindow(url);
}

function DesignModeOnHandler() {
	HtmlEditorField.designModeOn();
}
/*-- html editor handlers */

if (typeof window.JSFileLoaded != 'undefined') {
	JSFileLoaded();
}