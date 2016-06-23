/*
 * Functions:
 *  InitFileUploader(newMessageScreen)
 * Classes:
 *  CNewMessageScreen(bInNewWindow)
 */

function InitFileUploader(fileUploaderDiv, newMessageScreen)
{
	Uploader = new qq.FileUploader({
		element: fileUploaderDiv,
		action: FileUploaderUrl,
		name: 'Filedata',
		parent: newMessageScreen,
		onCancel: newMessageScreen.resizeBody,
		sizeLimit: WebMail.Settings.attachmentSizeLimit,
		showMessage: function (message) {
			WebMail.showError(message);
		}
	});
}

function CNewMessageScreen(bInNewWindow)
{
	this.bInNewWindow = bInNewWindow;
	this._openNewWindowClassName = (bInNewWindow) ? 'wm_hide'
		: 'wm_message_right wm_open_in_new_window_control';
	this._openedNewWindow = false;
	this.id = SCREEN_NEW_MESSAGE;
	this.isBuilded = false;
	this.bodyAutoOverflow = true;
	
	this.shown = false;
	
	this._msgObj = new CMessage();
	this._newMessage = true;
	this._sendersGroups = Array();

	this._mainContainer = null;
	this._toolBar = null;
	this._headersTbl = null;

	this._bccSwitcher = null;
	this._hasBcc = false;
	this._bccCont = null;

	this._fromObj = null;
	this._fromCont = null;
	this._openInNewWindowNearFromCont = null;
	this._openInNewWindowNearToCont = null;
	this._toObj = null;
	this._ccObj = null;
	this._bccObj = null;
	this._subjectObj = null;
	this._priorityLowButton = null;
	this._priorityNormalButton = null;
	this._priorityHighButton = null;
	this._priority = 3;

	this._sensitiveNothingButton = null;
	this._sensivityConfidentialButton = null;
	this._sensivityPrivateButton = null;
	this._sensivityPersonalButton = null;
	this._sensivity = SENSIVITY_NOTHING;

	this.AutoFilling = null;
	this.PopupContacts = null;
	
	this._eModeSwitcher = null;
	this._modeSwitcherPane = null;
	this._mailConfirmationCh = null;
	this._ePlainEditorControl = null;
	this._plainEditorCont = null;

	this._uploadForm = null;
	this._uploadFile = null;
	this._attachments = [];
	this._inlineAttachments = [];
	this._rowIndex = 0;
	this._attachmentsDiv = null;
	this._attachmentsTbl = null;
	this._fileUploadCount = 0;

	//this._picturesControl = new CMessagePicturesController(false);

	this._saving = false;
	this._saveTool = null;
	this._sending = false;
	this._sendTool = null;
	this._messageLoaded = false;
	
	this._subjectCont = null;
	this._counterCont = null;
	this._counterObj = null;
	this._lastHtmlText = '';
	this._lastPlainText = '';

	this.timer = null;
	this.isSavedOrSent = false;

	this.resized = false;
	
    this._mailSaveCh = null;
    this._sendButtonCont = null;
}

CNewMessageScreen.prototype = {
	placeData: function (data)
	{
		if (data != null) {
			switch (data.type){
				case TYPE_CONTACTS:
					var iCount = data.list.length;
					if (data.lookFor == '') {
					    if (iCount == 0) {
					        this.PopupContacts.hide();
						    WebMail.showReport(Lang.InfoNoContactsGroups);
					    }
					    else {
						    this.PopupContacts.fill(data.list);
					    }
					}
					else {
					    if (iCount == 0) {
					        this.AutoFilling.hide();
					    }
						else if (data.count > iCount) {
						    this.AutoFilling.fill(data.list, data.lookFor, Lang.InfoListNotContainAddress);
					    }
						else {
						    this.AutoFilling.fill(data.list, data.lookFor, '');
					    }
					}
				break;
			}
		}
	},//placeData
	
	addSenderGroup: function (sGroupId)
	{
		var hasValue = false;
		var iCount = this._sendersGroups.length;
		for (var i = 0; i < iCount; i++) {
			if (this._sendersGroups[i] == sGroupId) {
				hasValue = true;
			}
		}
		if (!hasValue) {
			this._sendersGroups[iCount] = sGroupId;
		}
	},

	SetToolsEnable: function ()
	{
		if (WebMail.allowSaveMessageToDrafts()) {
			this._saveTool.show();
			if (this._fileUploadCount > 0 || this._saving) {
				this._saveTool.disable();
			}
			else {
				this._saveTool.enable();
			}
		}
		else {
			this._saveTool.hide();
		}
		if (this._fileUploadCount > 0 || this._sending) {
			this._sendTool.disable();
		}
		else {
			this._sendTool.enable();
		}
	},

	SetErrorHappen: function ()
	{
		this._saving = false;
		this._sending = false;
		this.SetToolsEnable();
	},
	
	setMessageId: function (id, uid)
	{
		this._saving = false;
		this.SetToolsEnable();
		this._msgObj.id = id;
		this._msgObj.uid = uid;
	},

	startFileUpload: function ()
	{
		this._fileUploadCount++;
		if (this._fileUploadCount === 1) {
			this.SetToolsEnable();
		}
	},
	
	stopFileUpload: function ()
	{
		this._fileUploadCount--;
		if (this._fileUploadCount === 0) {
			this.SetToolsEnable();
		}
	},

	show: function ()
	{
		this._saving = false;
		this._sending = false;
		this.SetToolsEnable();
		this._sendersGroups = Array();
		this._mainContainer.className = 'wm_new_message';
		if (WebMail.Settings.allowBodySize) {
			this._counterCont.className = 'last_row';
			this._subjectCont.className = '';
		}
		else {
			this._counterCont.className = 'wm_hide';
			this._subjectCont.className = 'last_row';
		}
		this._toolBar.show();

		HtmlEditorField.setPlainEditor(this._ePlainEditorControl, this._eModeSwitcher, 7, true);
		var obj = this;
		this._eModeSwitcher.onclick = function () {
			obj.switchHtmlMode();
			return false;
		};
		HtmlEditorField.replace();
		/*Add saving to draft by timeout*/
		if (WebMail && WebMail.Settings) {
			var sett = WebMail.Settings;
			if (typeof(sett.idleSessionTimeout) != 'undefined' && sett.idleSessionTimeout > 0) {
				HtmlEditorField.updateEditorHandlers(
					function()
					{
						WebMail.startIdleTimer();
					},
					Array('click', 'keyup')
				);
			}
		}
		/*****/

		this.shown = true;
		this.fill();
		this.SetCounterValue();
	},
	
	restoreFromHistory: function () { },

	hide: function ()
	{
		this.shown = false;
		this.SetNewMessage();
		HtmlEditorField.hide();
		Uploader.clearAttachments();

		this.PopupContacts.hide();
		this.AutoFilling.hide();
		this._mainContainer.className = 'wm_hide';
		this._toolBar.hide();
		this._messageLoaded = false;
		this.enableForm();
		this._inlineAttachments = [];
	},
	
	ClearForm: function ()
	{
		this._toObj.value = '';
		this._ccObj.value = '';
		this._bccObj.value = '';
		this._subjectObj.value = '';
		HtmlEditorField.setHtml('');
		HtmlEditorField.setPlain('');
	},
	
	enableForm: function ()
	{
		this._fromObj.disabled = false;
		this._toObj.disabled = false;
		this._ccObj.disabled = false;
		this._bccObj.disabled = false;
		this._subjectObj.disabled = false;
		HtmlEditorField.enable();
		this._ePlainEditorControl.disabled = false;
	},
	
	disableForm: function ()
	{
		// condition for ie6 on slow connections
/*
		if (this.shown && !this._toObj.disabled) {
			this._toObj.focus();
		}
*/		
		this._fromObj.disabled = true;
		this._toObj.disabled = true;
		this._ccObj.disabled = true;
		this._bccObj.disabled = true;
		this._subjectObj.disabled = true;
		HtmlEditorField.disable();
		this._ePlainEditorControl.disabled = true;
	},
	
	clickBody: function (ev)
	{
		HtmlEditorField.clickBody();
		this.PopupContacts.clickBody(ev);
		this.AutoFilling.clickBody(ev);
	},

	_getExternalHeight: function()
	{
		var externalHeight = WebMail.getHeaderHeight();
		externalHeight += this._toolBar.getHeight();
		externalHeight += this._headersTbl.offsetHeight;
		externalHeight -= this._plainEditorCont.offsetHeight;
		externalHeight += this._modeSwitcherPane.offsetHeight;
		externalHeight += this._sendButtonCont.offsetHeight;
		return externalHeight;
	},
	
	resizeBody: function ()
	{
		if (this.isBuilded) {
			var isAuto = false;
			var widthPadding = 30;
			var width = GetWidth() - widthPadding;
			if (width < 500) {
				width = 500;
				isAuto = true;
			}
			var heightPadding = 6;
			if (Browser.ie && Browser.version < 7) {
				heightPadding = 11;
			}
			var screenHeight = GetHeight();
			var externalHeight = this._getExternalHeight();
			var height = screenHeight - externalHeight - heightPadding;
			if (height < 200) {
				height = 200;
				isAuto = true;
			}
			HtmlEditorField.resize(width, height);
			this.PopupContacts.Replace();
			this.AutoFilling.Replace();
			SetBodyAutoOverflow(isAuto);
		}
	},
	
	SetNewMessage: function ()
	{
		this._newMessage = true;
		this._msgObj = new CMessage();
		Screens[SCREEN_NEW_MESSAGE].showHandler = '';
		this._messageLoaded = true;
	},
	
	_getReplyMessage: function (msg, replyAction, replyText)
	{
		var replyMsg = new CMessage();
		var fromField = this._getFromAddrByAcctId(NON_EXISTENT_ID, WebMail.Accounts.currId);
		replyMsg.prepareForReply(msg, replyAction, fromField);
		replyMsg.fromAcctId = WebMail.Accounts.currId;
		replyMsg.fromAddr = fromField;
		if (replyMsg.hasHtml && replyText != '') {
			replyMsg.htmlBody = replyText.replace(/\r\n/gi, '<br />').replace(/\n/gi, '<br />').replace(/\r/gi, '<br />') + '<br />' + replyMsg.htmlBody;
		}
		if ((!replyMsg.hasHtml || replyMsg.hasPlain) && replyText != '') {
			replyMsg.plainBody = replyText + '\r\n' + replyMsg.plainBody;
			replyMsg.hasPlain = true;
		}
		return replyMsg;
	},
	
	UpdateMessageForReply: function (msg, replyAction, replyText)
	{
		this._newMessage = false;
		Screens[SCREEN_NEW_MESSAGE].showHandler = '';
		this._msgObj = this._getReplyMessage(msg, replyAction, replyText);
		this._messageLoaded = true;
		this.fill();
	},
	
	UpdateMessageFromContacts: function (toField, ccField, bccField)
	{
		Screens[SCREEN_NEW_MESSAGE].showHandler = '';
		this.SetNewMessage();
		this._msgObj.toAddr = toField || '';
		this._msgObj.ccAddr = ccField || '';
		this._msgObj.bccAddr = bccField || '';
		this._messageLoaded = true;
		this.fill();
	},

	UpdateMessageFromConfirmation: function (toField)
	{
		Screens[SCREEN_NEW_MESSAGE].showHandler = '';
		this.SetNewMessage();
		this._msgObj.toAddr = toField;
		this._msgObj.subject = Lang.ReturnReceiptSubject;
		this._messageLoaded = true;
		var fromField = this._getFromAddrByAcctId(NON_EXISTENT_ID, WebMail.Accounts.currId);

		if (WebMail.Settings.richDefEditor) {
			this._msgObj.htmlBody = Lang.ReturnReceiptMailText1 + ' <a href="mailto:' + fromField
				+ '">' + fromField + '</a>.<br /><br />' + Lang.ReturnReceiptMailText2;
		}
		else {
			this._msgObj.plainBody = Lang.ReturnReceiptMailText1 + ' ' + fromField + '.\r\n\r\n'
				+ Lang.ReturnReceiptMailText2;
		}
		
		this.fill();
	},

	UpdateMessageFromMiniWebMail: function (message)
	{
		Screens[SCREEN_NEW_MESSAGE].showHandler = '';
		this._msgObj = message;
		this._messageLoaded = true;
		this.fill();
	},

	UpdateMessage: function (message)
	{
		this._newMessage = false;
		Screens[SCREEN_NEW_MESSAGE].showHandler = '';
		this._msgObj = new CMessage();
		this._msgObj.prepareForEditing(message);
		this._messageLoaded = true;
		this.fill();
	},
	
	/*showPictures: function ()
	{
		if (this._msgObj.safety == SAFETY_NOTHING)
		{
			this._msgObj.showPictures();
			if (this._msgObj.hasHtml) {
				HtmlEditorField.setHtml(this._msgObj.htmlBody);
			}
			else {
				HtmlEditorField.setPlain(this._msgObj.plainBody);
			}
			this.resizeBody();
		}
	},*/
	
	_fillFromField: function ()
	{
		this._iFromIdentityId = NON_EXISTENT_ID;
		this._iFromAcctId = WebMail.Accounts.currId;
		var eFrom = this._fromObj;
		CleanNode(eFrom);
		var aIdentities = WebMail.oIdentities.aItems;
		if (aIdentities.length <= 1) {
			this._fromCont.className = 'wm_hide';
			this._openInNewWindowNearToCont.className = this._openNewWindowClassName;
			return;
		}
		this._openInNewWindowNearToCont.className = 'wm_hide';
		this._fromCont.className = 'first_row';
		var bFirstCurrAcct = true;
		for (var i = 0; i < aIdentities.length; i++) {
			var oIdentity = aIdentities[i];
			var eOpt = CreateChild(eFrom, 'option',
				[['value', oIdentity.iId + STR_SEPARATOR + oIdentity.iAcctId]]);
			eOpt.innerHTML = (oIdentity.sName.length > 0)
				? '"' + oIdentity.sName + '" &lt;' + oIdentity.sEmail + '&gt;'
				: oIdentity.sEmail;
			if (bFirstCurrAcct && oIdentity.iAcctId === WebMail.Accounts.currId) {
				this._iFromIdentityId = oIdentity.iId;
				eOpt.selected = true;
				bFirstCurrAcct = false;
			}
		}
		var oNewMessageScreen = this;
		this._fromObj.onchange = function ()
		{
			oNewMessageScreen.onFromFieldChange();
		};
	},
	
	_getFromIds: function ()
	{
		var aFromIds = this._fromObj.value.split(STR_SEPARATOR);
		if (aFromIds.length === 2) {
			var iFromIdentityId = aFromIds[0] - 0;
			var iFromAcctId = aFromIds[1] - 0;
			return {iFromIdentityId: iFromIdentityId, iFromAcctId: iFromAcctId};
		}
		else {
			return {iFromIdentityId: this._iFromIdentityId, iFromAcctId: this._iFromAcctId};
		}
	},

	onFromFieldChange: function ()
	{
		var oIds = this._getFromIds();
		if (oIds.iFromIdentityId !== this._iFromIdentityId || oIds.iFromAcctId !== this._iFromAcctId) {
			var oNewIdentity = WebMail.oIdentities.getIdentityById(oIds.iFromIdentityId, oIds.iFromAcctId);
			var oOldIdentity = WebMail.oIdentities.getIdentityById(this._iFromIdentityId, this._iFromAcctId);
			this._changeSignature(oOldIdentity, oNewIdentity);
			this._iFromIdentityId = oIds.iFromIdentityId;
			this._iFromAcctId = oIds.iFromAcctId;
		}
	},

	_getBodySize: function ()
	{
		if (HtmlEditorField.htmlMode) {
			var htmlText = HtmlEditorField.getHtml();
			var textWithoutNodes = HtmlDecode(htmlText.replace(/<br *\/{0,1}>/gi, '\r\n').replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' '));
			return textWithoutNodes.length - 1;
		}
		else {
			var text = this._ePlainEditorControl.value;
			return text.length;
		}
	},

	SetCounterValue: function ()
	{
		if (!WebMail.Settings.allowBodySize) return;
		var counterValue = WebMail.Settings.maxBodySize - this._getBodySize();
		this._counterObj.value = counterValue;
	},
	
	loadAttachment: function (attachment)
	{
		if (attachment.inline && typeof(attachment.url) == 'string' && attachment.url.length > 0) {
			HtmlEditorField.insertImageFromWindow(HtmlDecode(attachment.url));
			this._inlineAttachments.push(attachment);
			return;
		}
	},

	_fillBody: function (oIdentity)
	{
		var oMsg = this._msgObj;
		var bUseSignature = (oIdentity !== null && oIdentity.bUseSignature);
		if (oMsg.hasHtml) {
			var sHtmlBody = HtmlDecodeBody(oMsg.htmlBody);
			var sHtmlSignature = '';
			if (bUseSignature) {
				sHtmlSignature = oIdentity.getHtmlSignature();
				var iHtmlSignaturePos = sHtmlBody.lastIndexOf(sHtmlSignature);
				if (iHtmlSignaturePos > -1) {
					sHtmlSignature = '';
				}
				else {
					sHtmlSignature = '<br /><br />' + sHtmlSignature + '<br />';
				}
			}
			if (oMsg.replyMsg !== null) {
				sHtmlBody = sHtmlSignature + sHtmlBody;
			}
			else {
				sHtmlBody = sHtmlBody + sHtmlSignature;
			}
			HtmlEditorField.setHtml(sHtmlBody);
			this._ePlainEditorControl.tabIndex = -1;
		}
		else {
			var sPlainBody = HtmlDecodeBody(oMsg.plainBody);
			var sPlainSignature = '';
			if (bUseSignature) {
				sPlainSignature = oIdentity.getPlainSignature();
				var iPlainSignaturePos = sPlainBody.lastIndexOf(sPlainSignature);
				if (iPlainSignaturePos > -1) {
					sPlainSignature = '';
				}
				else {
					sPlainSignature = '\n\n' + sPlainSignature + '\n';
				}
			}
			if (oMsg.replyMsg !== null) {
				sPlainBody = sPlainSignature + sPlainBody;
			}
			else {
				sPlainBody = sPlainBody + sPlainSignature;
			}
			HtmlEditorField.setPlain(sPlainBody);
			this._ePlainEditorControl.tabIndex = 6;
			if (this._ePlainEditorControl.createTextRange) {
				var range = this._ePlainEditorControl.createTextRange();
				range.collapse(true);
				range.select();
			}
			else {
				this._ePlainEditorControl.selectionStart = 0;
				this._ePlainEditorControl.selectionEnd = 0;
			}
		}
	},
	
	_changeSignature: function (oOldIdentity, oNewIdentity)
	{
		if (HtmlEditorField.htmlMode) {
			var sOldHtmlSignature = (oOldIdentity.bUseSignature) ? oOldIdentity.getHtmlSignature() : '';
			var sNewHtmlSignature = (oNewIdentity.bUseSignature) ? oNewIdentity.getHtmlSignature(): '';
			var sHtmlBody = HtmlEditorField.getHtml();
			var iHtmlSignaturePos = sHtmlBody.lastIndexOf(sOldHtmlSignature);
			if (iHtmlSignaturePos > -1) {
				var sHtmlBody1 = sHtmlBody.slice(0, iHtmlSignaturePos);
				var sHtmlBody2 = sHtmlBody.slice(iHtmlSignaturePos + sOldHtmlSignature.length);
				sHtmlBody = sHtmlBody1 + sNewHtmlSignature + sHtmlBody2;
			}
			else {
				sHtmlBody = sHtmlBody + sNewHtmlSignature;
			}
			HtmlEditorField.setHtml(sHtmlBody);
		}
		else {
			var sOldPlainSignature = (oOldIdentity.bUseSignature) ? oOldIdentity.getPlainSignature() : '';
			var sNewPlainSignature = (oNewIdentity.bUseSignature) ? oNewIdentity.getPlainSignature() : '';
			var sPlainBody = HtmlEditorField.getPlain();
			var iPlainSignaturePos = sPlainBody.lastIndexOf(sOldPlainSignature);
			if (iPlainSignaturePos > -1) {
				var sPlainBody1 = sPlainBody.slice(0, iPlainSignaturePos);
				var sPlainBody2 = sPlainBody.slice(iPlainSignaturePos + sOldPlainSignature.length);
				sPlainBody = sPlainBody1 + sNewPlainSignature + sPlainBody2;
			}
			else {
				sPlainBody = sPlainBody + sNewPlainSignature;
			}
			HtmlEditorField.setPlain(sPlainBody);
		}
	},

	switchHtmlMode: function ()
	{
		var oIdentity = WebMail.oIdentities.getIdentityById(this._iFromIdentityId, this._iFromAcctId);
		var sHtmlSignature = (oIdentity !== null) ? oIdentity.getHtmlSignature() : '';
		var sPlainSignature = (oIdentity !== null) ? oIdentity.getPlainSignature() : '';
		if (HtmlEditorField.htmlMode) {
			if (confirm(Lang.ConfirmHtmlToPlain)) {
				var sHtmlBody = HtmlEditorField.getHtmlForSwitch();
				var iHtmlSignaturePos = sHtmlBody.lastIndexOf(sHtmlSignature);
				if (iHtmlSignaturePos > -1) {
					sHtmlBody = sHtmlBody.slice(0, iHtmlSignaturePos);
				}
				var sNewPlainBody = TextFormatter.htmlToPlain(sHtmlBody);
				HtmlEditorField.setPlain(sNewPlainBody + sPlainSignature);
			}
		}
		else {
			var sPlainBody = HtmlEditorField.getPlain();
			var iPlainSignaturePos = sPlainBody.lastIndexOf(sPlainSignature);
			if (iPlainSignaturePos > -1) {
				sPlainBody = sPlainBody.slice(0, iPlainSignaturePos);
			}
			var sNewHtmlBody = TextFormatter.plainToHtml(sPlainBody);
			HtmlEditorField.setHtml(sNewHtmlBody + sHtmlSignature);
		}
	},

	fill: function ()
	{
		if ((null != this._msgObj) && this.shown && this._messageLoaded) {
			var msg = this._msgObj;
			this.SetPriority(msg.importance);
			this.SetSensivity(msg.sensivity);
			this._mailConfirmationCh.checked = msg.mailConfirmation;
			
			/*
			 var msgInRichEditor = WebMail.Settings.richDefEditor && msg.hasHtml;
			 if (msg.safety == SAFETY_NOTHING && msgInRichEditor) {
				this._fillMessageInfo(msg);
				this._picturesControl.show();
			}
			else {
				this._picturesControl.hide();
			}
			*/
			this._fillFromField();
			this._toObj.value = msg.toAddr || '';
			this._ccObj.value = msg.ccAddr || '';
			this._bccObj.value = msg.bccAddr || '';
			if (msg.bccAddr.length == 0) {
				this._bccSwitcher.innerHTML = Lang.ShowBCC;
				this._hasBcc = false;
				this._bccCont.className = 'wm_hide';
			}
			else {
				this._bccSwitcher.innerHTML = Lang.HideBCC;
				this._hasBcc = true;
				this._bccCont.className = '';
			}
			this._subjectObj.value = msg.subject || '';
			
			var oIdentity = WebMail.oIdentities.getIdentityById(this._iFromIdentityId, this._iFromAcctId);
			this._fillBody(oIdentity);

//				var doc = HtmlEditorField.getDocument();
//				var obj = this;
//				if (doc.addEventListener)
//				{
//					doc.addEventListener('keypress', function(ev) {
//						obj.isTextChanged.call(obj, ev);
//					}, true);
//				}
//				else if (doc.attachEvent)
//				{
//					doc.attachEvent('onkeydown', function(ev) {
//						obj.isTextChanged.call(obj, ev);
//					});
//				}

			this.enableForm();
			this._inlineAttachments = [];
			Uploader.clearAttachments();
			for (var i = 0; i < msg.attachments.length; i++) {
				var att = msg.attachments[i];
				if (att.inline) {
					this._inlineAttachments.push(att);
				}
				else {
					att.id = att.tempName;
					Uploader.addAttachment(att);
				}
			}

			if (this._toObj.value.length == 0) {
			    this._toObj.focus();
			}
			else {
			    if (msg.hasHtml) {
			        HtmlEditorField.focus();
			    }
			    else {
			        this._ePlainEditorControl.focus();
			    }
			}
		}
		else if (this.shown) {
			this.ClearForm();
			this.disableForm();
		}
		if (this.shown) {
			this.resizeBody();
		}
	},//fill
	
	_getFromAddrByAcctId: function (iId, iAcctId)
	{
		var oIdentity = WebMail.oIdentities.getIdentityById(iId, iAcctId);
		if (oIdentity === null) {
			return '';
		}
		return (oIdentity.sName.length > 0)
			? '"' + oIdentity.sName + '" <' + oIdentity.sEmail + '>'
			: oIdentity.sEmail;
	},
	
	_trunkHtmlBody: function (htmlBody)
	{
		var pointer = 0;
		var counter = 0;
		var length = htmlBody.length;
		var inNode = false;
		while (pointer < length && counter < WebMail.Settings.maxBodySize) {
			var symbol = htmlBody.substr(pointer, 1);
			switch (symbol) {
				case '<':
					inNode = true;
					break;
				case '>':
					inNode = false;
					break;
				default:
					if (!inNode) {
						counter++;
					}
					break;
			}
			pointer++;
		}
		return htmlBody.substring(0, pointer);
	},

	openInNewWindow: function ()
	{
		if (this.bInNewWindow) return;
		var newMsg = this._getNewMessage();
		this._openedNewWindow = true;
		OpenNewMessageInNewWindow(newMsg);
	},

	_getNewMessage: function ()
	{
		var newMsg = new CMessage();
		var oIds = this._getFromIds();
		newMsg.iFromIdentityId = oIds.iFromIdentityId;
		newMsg.fromAddr = this._getFromAddrByAcctId(oIds.iFromIdentityId, oIds.iFromAcctId);
		newMsg.fromAcctId = oIds.iFromAcctId;
		newMsg.toAddr = this._toObj.value;
		newMsg.ccAddr = this._ccObj.value;
		if (this._hasBcc) {
			newMsg.bccAddr = this._bccObj.value;
		}
		newMsg.subject = this._subjectObj.value;
		newMsg.importance = this._priority;
		newMsg.sensivity = this._sensivity;

		if (HtmlEditorField.htmlMode) {
			var value = HtmlEditorField.getHtml();
			if (typeof(value) == 'string') {
				newMsg.hasHtml = true;
				newMsg.htmlBody = value;
				if (WebMail.Settings.allowBodySize && this._counterObj.value < 0) {
					newMsg.htmlBody = this._trunkHtmlBody(newMsg.htmlBody);
				}
			}
		}
		else {
			newMsg.hasHtml = false;
			newMsg.plainBody = this._ePlainEditorControl.value;
			if (WebMail.Settings.allowBodySize) {
				newMsg.plainBody = newMsg.plainBody.substr(0, WebMail.Settings.maxBodySize);
			}
		}
		newMsg.attachments = [];
		for (var key in Uploader.attachments) {
			if (typeof(Uploader.attachments[key]) === 'function') continue;
			newMsg.attachments.push(Uploader.attachments[key]);
		}
		newMsg.attachments = newMsg.attachments.concat(this._inlineAttachments);

		newMsg.id = this._msgObj.id;
		newMsg.uid = this._msgObj.uid;
		newMsg.replyMsg = this._msgObj.replyMsg;
		newMsg.sendersGroups = this._sendersGroups;
		newMsg.mailConfirmation = this._mailConfirmationCh.checked;
		if (this._mailSaveCh != null && WebMail.allowSaveMessageToSent()) {
			newMsg.bSaveMail = this._mailSaveCh.checked;
			var iSaveMail = (newMsg.bSaveMail) ? SAVE_MAIL_CHECKED : SAVE_MAIL_UNCHECKED;
			Cookies.create('wm_save_mail', iSaveMail);
		}

		return newMsg;
	},

	_isCorrectDataForSaving: function (newMsg, mode)
	{
		var incorrectEmails = [];

		if (newMsg.toAddr.length > 0) {
			incorrectEmails = incorrectEmails.concat(validateMessageAddressString(newMsg.toAddr));
		}
		if (newMsg.ccAddr.length > 0) {
			incorrectEmails = incorrectEmails.concat(validateMessageAddressString(newMsg.ccAddr));
		}
		if (newMsg.bccAddr.length > 0) {
			incorrectEmails = incorrectEmails.concat(validateMessageAddressString(newMsg.bccAddr));
		}
		if (incorrectEmails.length > 0) {
			var alertStr = Lang.WarningInputCorrectEmails + '\r\n' + Lang.WrongEmails;
			for (var i in incorrectEmails) {
				if (typeof(incorrectEmails[i]) === 'function') continue;
				alertStr += '\r\n' + incorrectEmails[i];
			}
			alert(alertStr);
			return false;
		}

		if (mode == SEND_MODE) {
			if (newMsg.toAddr.length < 1 && newMsg.ccAddr.length < 1 && newMsg.bccAddr.length < 1) {
				alert(Lang.WarningToBlank);
				return false;
			}

		}
		if (WebMail.Settings.allowBodySize) {
			if (this._counterObj.value < 0 && !confirm(Lang.ConfirmBodySize1 + ' ' + WebMail.Settings.maxBodySize + ' ' + Lang.ConfirmBodySize2)) {
				return false;
			}
		}
		if (mode == SEND_MODE && newMsg.subject.length == 0) {
		    return confirm(Lang.ConfirmEmptySubject);
		}

		return true;
	},
	
	saveChanges: function (mode)
	{
		if (this._fileUploadCount > 0) return;
		if (this._sending && mode == SEND_MODE) return;
		if (this._saving && mode == SAVE_MODE) return;

		var newMsg = this._getNewMessage();
		if (mode == SAVE_MODE && !WebMail.allowSaveMessageToDrafts()) return;
		var isCorrectData = this._isCorrectDataForSaving(newMsg, mode);
		if (!isCorrectData) return;

		var xml = newMsg.getInXml();
		switch (mode) {
			case SEND_MODE:
				WebMail.DataSource.cache.clearAllContactsGroupsList();
				RequestHandler('send', 'message', xml);
				this._sending = true;
				break;
			case SAVE_MODE:
				RequestHandler('save', 'message', xml);
				this._saving = true;
				break;
		}
		this.SetToolsEnable();

		if (this.bInNewWindow) {
			window.opener.MarkMsgAsRepliedHandler(newMsg);
		}
		else {
			MarkMsgAsRepliedHandler(newMsg);
		}
	},
	
	SwitchBccMode: function ()
	{
		if (this._hasBcc) {
			this._bccSwitcher.innerHTML = Lang.ShowBCC;
			this._hasBcc = false;
			this._bccCont.className = 'wm_hide';
		}
		else {
			this._bccSwitcher.innerHTML = Lang.HideBCC;
			this._hasBcc = true;
			this._bccCont.className = '';
		}
		if (Browser.gecko) {
			this.resizeBody();
		}
		else {
			HtmlEditorField.replace();
		}
	},
	
	ChangePriority: function ()
	{
		var pr = this._priority;
		switch (pr) {
			case PRIORITY_LOW:
				this.SetPriority(PRIORITY_NORMAL);
				break;
			case PRIORITY_NORMAL:
				this.SetPriority(PRIORITY_HIGH);
				break;
			case PRIORITY_HIGH:
				this.SetPriority(PRIORITY_LOW);
				break;
		}
	},

	ChangeSensivity: function ()
	{
		var sensi = this._sensivity;
		switch (sensi) {
			case SENSIVITY_NOTHING:
			case SENSIVITY_CONFIDENTIAL:
			case SENSIVITY_PRIVATE:
			case SENSIVITY_PERSONAL:
				this.SetSensivity(sensi);
				break;
		}
	},

	SetSensivity: function (sensi)
	{
		switch (sensi) {
			case SENSIVITY_NOTHING:
				this._sensivity = SENSIVITY_NOTHING;
				this._sensitiveNothingButton.changeClassName('wm_menu_item_importance wm_importance_menu', 'wm_menu_item_over_importance wm_importance_menu');
				this._sensivityConfidentialButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
				this._sensivityPrivateButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
				this._sensivityPersonalButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
				break;
			case SENSIVITY_CONFIDENTIAL:
				this._sensivity = SENSIVITY_CONFIDENTIAL;
				this._sensitiveNothingButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
				this._sensivityConfidentialButton.changeClassName('wm_menu_item_importance wm_importance_menu', 'wm_menu_item_over_importance wm_importance_menu');
				this._sensivityPrivateButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
				this._sensivityPersonalButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
				break;
			case SENSIVITY_PRIVATE:
				this._sensivity = SENSIVITY_PRIVATE;
				this._sensitiveNothingButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
				this._sensivityConfidentialButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
				this._sensivityPrivateButton.changeClassName('wm_menu_item_importance wm_importance_menu', 'wm_menu_item_over_importance wm_importance_menu');
				this._sensivityPersonalButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
				break;
			case SENSIVITY_PERSONAL:
				this._sensivity = SENSIVITY_PERSONAL;
				this._sensitiveNothingButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
				this._sensivityConfidentialButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
				this._sensivityPrivateButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
				this._sensivityPersonalButton.changeClassName('wm_menu_item_importance wm_importance_menu', 'wm_menu_item_over_importance wm_importance_menu');
				break;
			default:
				alert([sensi, SENSIVITY_NOTHING, SENSIVITY_CONFIDENTIAL, SENSIVITY_PRIVATE, SENSIVITY_PERSONAL]);
				break;
		}
	},
	
	SetPriority: function (pr)
	{
		switch (pr) {
			case PRIORITY_LOW:
				this._priority = PRIORITY_LOW;
				this._priorityLowButton.changeClassName('wm_menu_item_importance wm_importance_menu', 'wm_menu_item_over_importance wm_importance_menu');
				this._priorityNormalButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
				this._priorityHighButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
			break;
			case PRIORITY_NORMAL:
				this._priority = PRIORITY_NORMAL;
				this._priorityLowButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
				this._priorityNormalButton.changeClassName('wm_menu_item_importance wm_importance_menu', 'wm_menu_item_over_importance wm_importance_menu');
				this._priorityHighButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
			break;
			case PRIORITY_HIGH:
				this._priority = PRIORITY_HIGH;
				this._priorityLowButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
				this._priorityNormalButton.changeClassName('wm_menu_item wm_importance_menu', 'wm_menu_item_over wm_importance_menu');
				this._priorityHighButton.changeClassName('wm_menu_item_importance wm_importance_menu', 'wm_menu_item_over_importance wm_importance_menu');
			break;
			default:
				alert([pr, PRIORITY_LOW, PRIORITY_NORMAL, PRIORITY_HIGH]);
				break;
		}
	},
	
	_buildToolBar: function (container, popupMenus)
	{
		var obj = this;
		var toolBar = new CToolBar(container);
		this._toolBar = toolBar;

		if (!this.bInNewWindow) {
			toolBar.addItem(TOOLBAR_BACK_TO_LIST, BackToListHandler, true);
		}
		this._sendTool = toolBar.addItem(TOOLBAR_SEND_MESSAGE, function () {obj.saveChanges(0);}, true);
		this._saveTool = toolBar.addItem(TOOLBAR_SAVE_MESSAGE, function () {obj.saveChanges(1);}, true);

		function createSetPriorFunc(obj, pr)
		{
		    return function () {obj.SetPriority(pr);};
		}

		var div = CreateChild(document.body, 'div');
		div.className = 'wm_hide';
		var buttons = toolBar.addImportanceItem(popupMenus, div);
		this._priorityLowButton = buttons.Low;
		this._priorityLowButton.changeHandler(createSetPriorFunc(obj, PRIORITY_LOW));
		this._priorityNormalButton = buttons.Normal;
		this._priorityNormalButton.changeHandler(createSetPriorFunc(obj, PRIORITY_NORMAL));
		this._priorityHighButton = buttons.High;
		this._priorityHighButton.changeHandler(createSetPriorFunc(obj, PRIORITY_HIGH));

		function createSetSensivityFunc(obj, sensi)
		{
		    return function () {obj.SetSensivity(sensi);};
		}

		var div2 = CreateChild(document.body, 'div');
		div2.className = 'wm_hide';
		var rbuttons = toolBar.addSensivityItem(popupMenus, div2);
		this._sensitiveNothingButton = rbuttons.Nothing;
		this._sensitiveNothingButton.changeHandler(createSetSensivityFunc(obj, SENSIVITY_NOTHING));
		this._sensivityConfidentialButton = rbuttons.Confidential;
		this._sensivityConfidentialButton.changeHandler(createSetSensivityFunc(obj, SENSIVITY_CONFIDENTIAL));
		this._sensivityPrivateButton = rbuttons.Private;
		this._sensivityPrivateButton.changeHandler(createSetSensivityFunc(obj, SENSIVITY_PRIVATE));
		this._sensivityPersonalButton = rbuttons.Personal;
		this._sensivityPersonalButton.changeHandler(createSetSensivityFunc(obj, SENSIVITY_PERSONAL));

		toolBar.addItem(TOOLBAR_CANCEL, function () {
			if (obj.bInNewWindow) {
				window.close();
				return;
			}
			BackToListHandler();
		}, true);

		toolBar.addClearDiv();
		toolBar.hide();
	},
	
	_buildModeSwitcherPane: function ()
	{
		var message_options_cont = CreateChild(this._mainContainer, 'div', [['class', 'wm_new_message_options']]);
		this._modeSwitcherPane = message_options_cont;

		var a = CreateChild(message_options_cont, 'a', [['href', '#'], ['class', 'wm_editor_switcher']]);
		a.innerHTML = Lang.SwitchToPlainMode;
		this._eModeSwitcher = a;
		
		var span = CreateChild(message_options_cont, 'span');
		var inp = CreateChild(span, 'input', [['id', 'chMailConfirmation'], ['type', 'checkbox'], ['class', 'wm_checkbox']]);
		var lbl = CreateChild(span, 'label', [['for', 'chMailConfirmation']]);
		lbl.innerHTML = Lang.RequestReadConfirmation;
		WebMail.langChanger.register('innerHTML', lbl, 'RequestReadConfirmation', '');
		this._mailConfirmationCh = inp;
		this._mailConfirmationCh.checked = false;

		if (WebMail.Settings.iSaveMail != SAVE_MAIL_HIDDEN) {
			span = CreateChild(message_options_cont, 'span');
			inp = CreateChild(span, 'input', [['id', 'chSaveMailInSentItems'], ['type', 'checkbox'], ['class', 'wm_checkbox']]);
			lbl = CreateChild(span, 'label', [['for', 'chSaveMailInSentItems']]);
			lbl.innerHTML = Lang.SaveMailInSentItems;
			WebMail.langChanger.register('innerHTML', lbl, 'SaveMailInSentItems', '');
			this._mailSaveCh = inp;
			this._mailSaveCh.checked = false;
			var iSaveMail = Cookies.readInt('wm_save_mail', WebMail.Settings.iSaveMail);
			if (iSaveMail === SAVE_MAIL_CHECKED){
				this._mailSaveCh.checked = true;
			}
		}
	},

	build: function (container, popupMenus)
	{
		this._buildToolBar(container, popupMenus);
		
		this._mainContainer = CreateChild(container, 'div');
		this._mainContainer.className = 'wm_hide';

		//this._picturesControl.build(this._mainContainer);
		var tbl = CreateChild(this._mainContainer, 'table');
		this._headersTbl = tbl;
		tbl.id = 'wm_new_message';
		var RowIndex = 0;

		var tr, td;
		

		tr = tbl.insertRow(RowIndex++);
		tr.className = 'wm_hide';
		td = tr.insertCell(0);
		td.className = 'wm_new_message_title';
		td.innerHTML = Lang.From + ':';
		WebMail.langChanger.register('innerHTML', td, 'From', ':');
		td = tr.insertCell(1);
		var sel = CreateChild(td, 'select', [['class', 'wm_input'], ['style', 'width: 585px; padding-left:0px;']]);
		sel.tabIndex = 1;
		this._fromObj = sel;
		this._openInNewWindowNearFromCont = CreateChild(td, 'span', [['class', this._openNewWindowClassName]]);
		this._openInNewWindowNearFromCont.title = Lang.AltOpenInNewWindow;
		this._openInNewWindowNearFromCont.onclick = function () {
			obj.openInNewWindow();
		};
		this._fromCont = tr;
		
		tr = tbl.insertRow(RowIndex++);
		td = tr.insertCell(0);
		td.className = 'wm_new_message_title';
		var a = CreateChild(td, 'a', [['href', '#']]);
        CreateTextChild(a, Lang.To);
        CreateTextChild(td, ':');
		WebMail.langChanger.register('innerHTML', td, 'To', ':');
		td = tr.insertCell(1);
		var obj = this;
		this._openInNewWindowNearToCont = CreateChild(td, 'span', [['class', this._openNewWindowClassName]]);
		this._openInNewWindowNearToCont.title = Lang.AltOpenInNewWindow;
		this._openInNewWindowNearToCont.onclick = function () {
			obj.openInNewWindow();
		};
		
		var inp = CreateChild(td, 'input', [['type', 'text'], ['class', 'wm_input'], ['style', 'width: 580px']]);
		inp.tabIndex = 2;
		inp.onfocus = function () {
			obj.AutoFilling.SetSuggestInput(this);
		};
		inp.onchange = function() {
			obj.isSavedOrSent = false;
		};
		this._toObj = inp;
		a.onclick = function () {
		    obj.PopupContacts.ControlClick(obj._toObj, this);
		    return false;
		};
		
		tr = tbl.insertRow(RowIndex++);
		td = tr.insertCell(0);
		td.className = 'wm_new_message_title';
		a = CreateChild(td, 'a', [['href', '#']]);
        CreateTextChild(a, Lang.CC);
        CreateTextChild(td, ':');
		WebMail.langChanger.register('innerHTML', td, 'CC', ':');
		td = tr.insertCell(1);
		var nobr = CreateChild(td, 'nobr');
		inp = CreateChild(nobr, 'input', [['type', 'text'], ['class', 'wm_input'], ['style', 'width: 580px']]);
		inp.tabIndex = 3;
		inp.onfocus = function () {
			obj.AutoFilling.SetSuggestInput(this);
		};
		this._ccObj = inp;
		inp.onchange = function() {
			obj.isSavedOrSent = false;
		};
		a.onclick = function () {
		    obj.PopupContacts.ControlClick(obj._ccObj, this);
		    return false;
		};

		var span = CreateChild(nobr, 'span');
		span.innerHTML = '&nbsp;';
		a = CreateChild(nobr, 'a', [['href', '#']]);
		a.onclick = function () {obj.SwitchBccMode();return false;};
		a.innerHTML = Lang.ShowBCC;
		WebMail.langChanger.register('innerHTML', a, 'ShowBCC', '');
		a.tabIndex = -1;
		this._bccSwitcher = a;
		this._hasBcc = false;

		tr = tbl.insertRow(RowIndex++);
		tr.className = 'wm_hide';
		td = tr.insertCell(0);
		td.className = 'wm_new_message_title';
		a = CreateChild(td, 'a', [['href', '#']]);
        CreateTextChild(a, Lang.BCC);
        CreateTextChild(td, ':');
		WebMail.langChanger.register('innerHTML', td, 'BCC', ':');
		td = tr.insertCell(1);
		inp = CreateChild(td, 'input', [['type', 'text'], ['class', 'wm_input'], ['style', 'width: 580px']]);
		inp.tabIndex = 4;
		inp.onfocus = function () {
			obj.AutoFilling.SetSuggestInput(this);
		};
		inp.onchange = function() {
			obj.isSavedOrSent = false;
		};
		this._bccObj = inp;
		this._bccCont = tr;
		a.onclick = function () {
		    obj.PopupContacts.ControlClick(obj._bccObj, this);
		    return false;
		};

		tr = tbl.insertRow(RowIndex++);
		td = tr.insertCell(0);
		td.className = 'wm_new_message_title';
		td.innerHTML = Lang.Subject + ':';
		WebMail.langChanger.register('innerHTML', td, 'Subject', ':');
		td = tr.insertCell(1);
		var oArguments = [
			['type', 'text'],
			['class', 'wm_input'],
			['style', 'width: 580px']];
		if (WebMail.Settings.maxSubjectSize > 0) {
			oArguments.push(['maxlength', WebMail.Settings.maxSubjectSize])
		}
		inp = CreateChild(td, 'input', oArguments);
		inp.tabIndex = 5;
		inp.onfocus = function () {
			obj.AutoFilling.hide();
		};
		inp.onchange = function() {
			obj.isSavedOrSent = false;
		};
		inp.onkeydown = function (ev) {
			if (Keys.isTab(ev)) {
				HtmlEditorField.focused = true;
			}
		};
		this._subjectObj = inp;
		this._subjectCont = tr;

		tr = tbl.insertRow(RowIndex++);
		tr.className = 'last_row';
		td = tr.insertCell(0);
		td.className = 'wm_new_message_title';
		td.innerHTML = Lang.BodySizeCounter + ':';
		WebMail.langChanger.register('innerHTML', td, 'BodySizeCounter', ':');
		td = tr.insertCell(1);
		inp = CreateChild(td, 'input', [['type', 'text'], ['class', 'wm_input'], ['size', '3'], ['value', WebMail.Settings.maxBodySize]]);
		inp.disabled = true;
		this._counterObj = inp;
		this._counterCont = tr;
		
		tr = tbl.insertRow(RowIndex++);
		td = tr.insertCell(0);
		td = tr.insertCell(1);
		div = CreateChild(td, 'div', [['class','wm_attachment_uploading']]);
		var fileUploaderDiv = CreateChild(div, 'div', [['id', 'file-uploader']]);
		CreateChild(div, 'noscript');
		InitFileUploader(fileUploaderDiv, this);

		tr = tbl.insertRow(RowIndex++);
		td = tr.insertCell(0);
		td.colSpan = 2;
		var div = CreateChild(td, 'div');
		div.className = 'wm_input wm_plain_editor_container';
		var txt = CreateChild(div, 'textarea');
		txt.className = 'wm_plain_editor_text';
		txt.tabIndex = 6;
		txt.onchange = function() {
			obj.isSavedOrSent = false;
		};
		this._ePlainEditorControl = txt;
		this._plainEditorCont = td;

		this._buildModeSwitcherPane();

		div = CreateChild(this._mainContainer, 'div', [['class', 'wm_send_message_button_cont']]);
		this._sendButtonCont = div;
		var sendMessageButton = CreateChild(div, 'a', [['href', '#'],['class', 'wm_send_message_button']]);
		span = CreateChild(sendMessageButton, 'span');
		span.innerHTML = Lang.SendMessage;
		WebMail.langChanger.register('innerHTML', span, 'SendMessage', '');
		sendMessageButton.onclick = function() {obj.saveChanges(0);};
		
        this.PopupContacts = new CPopupContacts(GetAutoFillingContactsHandler, SelectSuggestionHandler);
		this.AutoFilling = new CPopupAutoFilling(GetAutoFillingContactsHandler, SelectSuggestionHandler);

		this.isBuilded = true;
	},//build

	hasChanges : function ()
	{
		if (this._openedNewWindow) {
			this._openedNewWindow = false;
			return false;
		}

		if ((this._toObj.value.length > 0
			|| this._ccObj.value.length > 0
			|| this._bccObj.value.length > 0) && !this.isSavedOrSent)
		{
			return true;
		}
		var count = this._getBodySize();
		if (count > 0 && !this.isSavedOrSent) return true;
		return false;
	},

	isTextChanged : function (ev)
	{
		if (isTextChanged(ev)) this.isSavedOrSent = false;
	}
};

//CNewMessageScreen.prototype._fillMessageInfo = MessageListPrototype._fillMessageInfo;

if (typeof window.JSFileLoaded != 'undefined') {
	JSFileLoaded();
}
