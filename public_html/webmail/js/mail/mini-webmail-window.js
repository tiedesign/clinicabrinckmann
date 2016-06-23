/*
 * Objects:
 *  WebMail
 * Functions:
 *  ShowPicturesHandler(safety)
 *  BodyLoaded()
 * Classes:
 *  CPreviewPaneInNewWindow()
 */
 
var isBodyLoaded = false;

WebMail.Init = function ()
{
	this.langChanger = {
		register: function () {}
	};
	this._html = document.getElementById('html');
	this.fadeEffect = new CFadeEffect('WebMail.fadeEffect');
	this.InfoContainer = new CInfoContainer(this.fadeEffect);
	this.Accounts = new CAccounts(CurrentAccount);
	this.oIdentities = new CIdentities();
	this.oIdentities.getFromArray(Identities);
	this._setTitle();
	var dataTypes = [
		new CDataType(TYPE_CONTACTS, true, 5, false, { page: 'page', sortField: 'sort_field', sortOrder: 'sort_order' }, 'contacts_groups' )
	];
	this.DataSource = new CDataSource( dataTypes, ActionUrl, ErrorHandler, LoadHandler, TakeDataHandler, ShowLoadingInfoHandler );
	this.PopupMenus = new CPopupMenus();
	this.hideInfo();
};

WebMail.clickBody = function (ev)
{
	if (!isBodyLoaded) return;
	if (WebMail.ScreenId == SCREEN_NEW_MESSAGE) {
		NewMessageScreen.clickBody(ev);
	}
	if (WebMail.PopupMenus) {
		WebMail.PopupMenus.checkShownItems();
	}
};

WebMail.showError = function(errorDesc)
{
	this.InfoContainer.showError(errorDesc);
	if (WebMail.ScreenId == SCREEN_NEW_MESSAGE) {
		NewMessageScreen.SetErrorHappen();
	}
	else {
		PreviewPane.resetFlags();
	}
};

WebMail.hideError = function()
{
	this.InfoContainer.hideError();
};

WebMail.showInfo = function(info)
{
	this.InfoContainer.showInfo(info);
};

WebMail.hideInfo = function()
{
	this.InfoContainer.hideInfo();
};

WebMail.showReport = function(report, priorDelay)
{
	this.InfoContainer.showReport(report, priorDelay);
};

WebMail.hideReport = function()
{
	this.InfoContainer.hideReport();
};

WebMail.setTitle = function (sTitleToAdd, addedTitleFirst, discardScreenTitle)
{
	this._setTitle(sTitleToAdd, addedTitleFirst, discardScreenTitle);
};

WebMail._setTitle = function (sTitle)
{
	if (sTitle !== undefined) {
		document.title = sTitle + ' - ' + this._title;
	}
	else {
		var strTitle = (window.ViewMessage && ViewMessage.subject) ? ViewMessage.subject : '';
		var titleLangField = (window.ViewMessage) ? 'TitleViewMessage'
			: Screens[SCREEN_NEW_MESSAGE].titleLangField;
		document.title = (strTitle != '')
			? strTitle + ' - ' + this._title + ' - ' + Lang[titleLangField]
			: this._title + ' - ' + Lang[titleLangField];
	}
};

WebMail.resizeBody = function ()
{
	if (!isBodyLoaded) return;
	if (WebMail.ScreenId == SCREEN_NEW_MESSAGE) {
		NewMessageScreen.resizeBody();
	}
	else {
		PreviewPane.resize();
	}
	if (WebMail.InfoContainer) {
		WebMail.InfoContainer.resize();
	}
};

WebMail.switchToHtmlPlain = function ()
{
	PreviewPane.switchToHtmlPlain();
};

WebMail.placeData = function (data)
{
	switch (data.type) {
		case TYPE_UPDATE:
			switch (data.value) {

				case 'send_message':
					this.showReport(Lang.ReportMessageSent);
					window.opener.ClearSentAndDraftsHandler();
					if (WebMail.ScreenId === SCREEN_VIEW_MESSAGE) {
						PreviewPane.slideAndShowReplyPane(true);
					}
					break;
				case 'save_message':
					this.showReport(Lang.ReportMessageSaved);
					window.opener.ClearDraftsAndSetMessageId(data.id, data.uid);
					if (WebMail.ScreenId == SCREEN_NEW_MESSAGE) {
						NewMessageScreen.setMessageId(data.id, data.uid);
					}
					else {
						PreviewPane.setMessageId(data.id, data.uid);
						PreviewPane.slideAndShowReplyPane(true);
					}
					break;
			}
		break;
		case TYPE_CONTACTS:
			NewMessageScreen.placeData(data);
			break;
	}
};

WebMail.replyMessageClick = function (type, msg, text)
{
	var isMySavedMsg = (ViewMessage.folderType == FOLDER_TYPE_SENT
		|| ViewMessage.folderType == FOLDER_TYPE_DRAFTS);
	if (isMySavedMsg) return;
	if (!WebMail.Settings.allowComposeMessage) return;
	if (!WebMail.Settings.allowReplyMessage && (type == TOOLBAR_REPLY || type == TOOLBAR_REPLYALL)) return;
	if (!WebMail.Settings.allowForwardMessage && (type == TOOLBAR_FORWARD)) return;
	
	if (msg == null) {
		return;
	}
	if (msg.noReply && type == TOOLBAR_REPLY || msg.noReplyAll && type == TOOLBAR_REPLYALL) {
		return;
	}
	if ((msg.sensivity != SENSIVITY_NOTHING || msg.noForward) && type == TOOLBAR_FORWARD) {
		return;
	}
	if (text == undefined) text = '';
	PreviewPane.hide();
	NewMessageScreen.build(document.body, WebMail.PopupMenus);
	NewMessageScreen.show();
	NewMessageScreen.UpdateMessageForReply(msg, type, text);
	WebMail.ScreenId = SCREEN_NEW_MESSAGE;
	setTimeout('NewMessageScreen.resizeBody();', 1000);
};

WebMail.getCurrentListScreen = function ()
{
	if (this.ScreenId === SCREEN_NEW_MESSAGE) {
		return NewMessageScreen;
	}
	else {
		return PreviewPane;
	}
};

WebMail.allowSaveMessageToSent = function ()
{
	return window.opener.WebMail.allowSaveMessageToSent();
};

WebMail.allowSaveMessageToDrafts = function ()
{
	return window.opener.WebMail.allowSaveMessageToDrafts();
};

WebMail.getHeaderHeight = function ()
{
	return 0;
};


function ShowPicturesHandler(safety)
{
	PreviewPane.showPictures(safety);
}

function LoadHandler() {
	WebMail.DataSource.parseXml(this.responseXML, this.action, this.request);
}

function ErrorHandler() {
	WebMail.showError(this.errorDesc);
	switch (this.request) {
		case 'message':
			if (WebMail.ScreenId === SCREEN_VIEW_MESSAGE && (this.action === 'send' || this.action === 'save')) {
				PreviewPane.slideAndShowReplyPane(false);
			}
			break;
	}
}

function ShowLoadingInfoHandler() {
    var infoMessage = Lang.Loading;
    if (this.request == 'message') {
        switch (this.action) {
            case 'save':
                infoMessage = Lang.Saving;
                break;
            case 'send':
                infoMessage = Lang.Sending;
                break;
        }
    }
	WebMail.showInfo(infoMessage);
}

function TakeDataHandler() {
	if (this.data) {
		WebMail.placeData(this.data);
	}
}

function RequestHandler(action, request, xml) {
	WebMail.DataSource.request({action: action, request: request}, xml);
}

function BodyLoaded()
{
	Browser = new CBrowser();
	window.onresize = WebMail.resizeBody;
	document.body.onclick = WebMail.clickBody;
	WebMail.Init();
	HtmlEditorField.build(!UseDb);
	switch (OpenMode) {
		case 'view':
			PreviewPane = new CPreviewPaneInNewWindow();
			NewMessageScreen = new CNewMessageScreen(true);
			WebMail.ScreenId = SCREEN_VIEW_MESSAGE;
			break;
		case 'reply':
			NewMessageScreen = new CNewMessageScreen(true);
			NewMessageScreen.build(document.body, WebMail.PopupMenus);
			NewMessageScreen.show();
			NewMessageScreen.UpdateMessageForReply(ViewMessage, ReplyType, ReplyText);
			WebMail.ScreenId = SCREEN_NEW_MESSAGE;
			setTimeout('NewMessageScreen.resizeBody();', 1000);
			break;
		case 'new':
			NewMessageScreen = new CNewMessageScreen(true);
			NewMessageScreen.build(document.body, WebMail.PopupMenus);
			if (window.opener.NewMsg != undefined) {
				NewMessageScreen.UpdateMessageFromMiniWebMail(window.opener.NewMsg);
			}
			else {
				NewMessageScreen.SetNewMessage();
			}
			if (WebMail.Settings.allowComposeMessage) {
				NewMessageScreen.show();
			}
			WebMail.ScreenId = SCREEN_NEW_MESSAGE;
			break;
	}
	WebMail.Screens = [];
	WebMail.Screens[SCREEN_NEW_MESSAGE] = NewMessageScreen;
	isBodyLoaded = true;
	setTimeout('NewMessageScreen.resizeBody();', 1000);
}

function CPreviewPaneInNewWindow()
{
	this._mainContainer = null;
	this._picturesControl = new CMessagePicturesController(true);
	this._sensivityControl = new CMessageSensivityController();
	this._readConfirmationControl = new CMessageReadConfirmationController(this.SendConfirmation, this);
	this._previewPaneMessageHeaders = new CPreviewPaneMessageHeaders(true);
	this._oAttachmentsPane = null;
	this._msgViewer = new CMessageViewPane(false);
	this._replyPane = null;

	this._build();
	this._fill();
	this._fillMessageInfo(ViewMessage);
	this.show();
	this.resize();
}

CPreviewPaneInNewWindow.prototype = {
	showPictures: function (safety) {
		if (ViewMessage.safety != safety) {
			ViewMessage.showPictures();
			this._msgViewer.fill(ViewMessage);
			this.resize();
		}
	},

	SendConfirmation: function () {
		if (ViewMessage && ViewMessage.mailConfirmationValue && ViewMessage.mailConfirmationValue.length) {
			window.opener.SendConfirmationHandler(ViewMessage.mailConfirmationValue, ViewMessage.subject);
		}
	},

	showNearMessage: function (oNextMsg)
	{
		if (oNextMsg === null || oNextMsg === undefined || !window.opener) {
			return;
		}
		var oMsg = window.opener.WebMail.getMessage(oNextMsg, window);
		if (oMsg !== null) {
			this.setMessage(oMsg);
		}
	},

	setMessage: function (oMsg)
	{
		ViewMessage = oMsg;
		this._fill();
		this.resize();
	},

	setNextMessage: function (oNextMsg)
	{
		ViewMessage.oNextMsg = oNextMsg;
		if (oNextMsg === null) {
			this._oNextInactiveButton.show();
			this._oNextActiveButton.hide()
		}
		else {
			this._oNextInactiveButton.hide();
			this._oNextActiveButton.show()
		}
	},

	setPrevMessage: function (oPrevMsg)
	{
		ViewMessage.oPrevMsg = oPrevMsg;
		if (oPrevMsg === null) {
			this._oPrevInactiveButton.show();
			this._oPrevActiveButton.hide()
		}
		else {
			this._oPrevInactiveButton.hide();
			this._oPrevActiveButton.show()
		}
	},

	_buildToolBar: function ()
	{
		var toolBar = new CToolBar(this._mainContainer);
		this._toolBar = toolBar;

		var obj = this;
		this._oPrevActiveButton = toolBar.addItem(TOOLBAR_PREV_ACTIVE, function () { obj.showNearMessage(ViewMessage.oPrevMsg); }, false);
		this._oPrevInactiveButton = toolBar.addItem(TOOLBAR_PREV_INACTIVE, function () { }, true);
		this._oNextActiveButton = toolBar.addItem(TOOLBAR_NEXT_ACTIVE, function () { obj.showNearMessage(ViewMessage.oNextMsg); }, false);
		this._oNextInactiveButton = toolBar.addItem(TOOLBAR_NEXT_INACTIVE, function () { }, true);

		var isMySavedMsg = (ViewMessage.folderType == FOLDER_TYPE_SENT
			|| ViewMessage.folderType == FOLDER_TYPE_DRAFTS);
		if (WebMail.Settings.allowReplyMessage && !isMySavedMsg) {
			function CreateReplyClickFromReplyPane(obj, replyAction)
			{
				return function () {
					obj._replyPane.switchToFullForm(replyAction);
				};
			}
			var replyFunc = CreateReplyClickFromReplyPane(this, TOOLBAR_REPLY);
			var replyAllFunc = CreateReplyClickFromReplyPane(this, TOOLBAR_REPLYALL);
			var replyParts = toolBar.addReplyItem(WebMail.PopupMenus, true, replyFunc, replyAllFunc);
			if (ViewMessage.noReply) {
				replyParts.replyButton.disable();
				replyParts.replyPopupMenu.disable = true;
			}
			if (ViewMessage.noReplyAll) {
				replyParts.replyAllButton.disable();
			}
		}

		if (WebMail.Settings.allowForwardMessage && !isMySavedMsg) {
			function CreateForwardClick()
			{
				return function () {
				   WebMail.replyMessageClick(TOOLBAR_FORWARD, ViewMessage);
				};
			}
			var forwardButton = toolBar.addItem(TOOLBAR_FORWARD, CreateForwardClick(), true);
			if (ViewMessage.noForward) {
				forwardButton.disable();
			}
		}

		if (ViewMessage.printLink != '#') {
			this._printButton = toolBar.addItem(TOOLBAR_PRINT_MESSAGE, function () {
				PopupPrintMessage(ViewMessage.printLink);
			}, true);
		}

		if (ViewMessage.saveLink != '#') {
			this._saveButton = toolBar.addItem(TOOLBAR_SAVE_MESSAGE, function () {
				document.location = ViewMessage.saveLink;
			}, true);
		}

		toolBar.addClearDiv();
	},

	_build: function ()
	{
		var mainContainer = CreateChild(document.body, 'div');
		this._mainContainer = mainContainer;
		this._buildToolBar();
		this._picturesControl.build(mainContainer);
		this._sensivityControl.build(mainContainer);
		this._readConfirmationControl.build(mainContainer);
		this._previewPaneMessageHeaders.build(mainContainer);
		this._oAttachmentsPane = new CAttachmentsPane(mainContainer);
		this._msgViewer.build(mainContainer, 0);
		this._msgViewer.setSwitcher(this._previewPaneMessageHeaders.SwitcherCont, 'wm_message_right', this._previewPaneMessageHeaders.SwitcherObj);
		this._replyPane = new CMessageReplyPane(mainContainer, true);
	},

	_fill: function ()
	{
		this._previewPaneMessageHeaders.fill(ViewMessage, null);
		this._oAttachmentsPane.show(ViewMessage.attachments);
		this._msgViewer.fill(ViewMessage);
		var isMySavedMsg = (ViewMessage.folderType == FOLDER_TYPE_SENT
			|| ViewMessage.folderType == FOLDER_TYPE_DRAFTS);
		if (WebMail.Settings.allowReplyMessage && !isMySavedMsg) {
			this._replyPane.show(ViewMessage);
		}
		if (window.opener){
			var oNextMessages = window.opener.WebMail.getNearMessages(ViewMessage.id, ViewMessage.uid, window);
			this.setNextMessage(oNextMessages.oNextMsg);
			this.setPrevMessage(oNextMessages.oPrevMsg);
		}
	},

	show: function ()
	{
		this._mainContainer.className = '';
	},

	hide: function ()
	{
		this._mainContainer.className = 'wm_hide';
	},

	resetFlags: function ()
	{
		this._replyPane.resetFlags();
	},

	setMessageId: function (msgId, msgUid)
	{
		this._replyPane.setMessageId(msgId, msgUid);
	},

	endSlideReplyPane: function (sDir)
	{
		this._replyPane.endSlide(sDir);
	},

	slideAndShowReplyPane: function (bClear)
	{
		this._replyPane.slideAndShow(bClear);
	},

	resizeScreen: function ()
	{
		this.resize();
	},

	resize: function ()
	{
		this._msgFrameVertWidth = 0;
		this._resizeMessageHeight(GetHeight());

		var width = GetWidth();
		this._previewPaneMessageHeaders.resize(width);
		this._msgViewer.resizeWidth(width);
		this._picturesControl.resizeWidth(width);
		this._readConfirmationControl.resizeWidth(width);
		this._sensivityControl.resizeWidth(width);
		this._replyPane.resizeWidth(width);
	},

	switchToHtmlPlain: function ()
	{
		this._msgViewer.switchToHtmlPlainInNewWindow();
		this._msgViewer.fill(ViewMessage);
	}
};

CPreviewPaneInNewWindow.prototype._fillMessageInfo = MessageListPrototype._fillMessageInfo;
CPreviewPaneInNewWindow.prototype._resizeMessageHeight = CMessageListCentralPaneScreen.prototype._resizeMessageHeight;
