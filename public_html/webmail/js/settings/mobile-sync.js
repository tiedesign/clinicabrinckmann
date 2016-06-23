/*
 * Classes:
 *  CMobileSyncSettingsPane(oParent)
 */

function CMobileSyncSettingsPane(oParent)
{
	this._oParent = oParent;
	
	this._mainForm = null;
	
	this._settings = null;
	this._newSettings = null;
	
	this.hasChanges = false;
	this._shown = false;
	
	this._enableObj = null;
	this._urlObj = null;
	this._loginObj = null;
	this._contactDataBaseObj = null;
	this._calendarDataBaseObj = null;
}

CMobileSyncSettingsPane.prototype = {
	show: function()
	{
		this.hasChanges = false;
		this._mainForm.className = (window.UseDb || window.UseLdapSettings) ? '' : 'wm_hide';
		this._shown = true;
		if (this._settings == null) {
			GetHandler(TYPE_MOBILE_SYNC, { }, [], '');
		}
        else {
			this.fill();
		}
	},
	
	hide: function()
	{
		if (this.hasChanges) {
			if (confirm(Lang.ConfirmSaveSettings)) {
				this.saveChanges();
			}
            else {
				this.fill();
			}
		}
		this._mainForm.className = 'wm_hide';
		this.hasChanges = false;
		this._shown = false;
	},
	
	SetSettings: function (settings)
	{
		this._settings = settings;
		this.fill();
	},
	
	UpdateSettings: function ()
	{
		this._settings = this._newSettings;
		this.fill();
	},

	fill: function ()
	{
		if (!this._shown) return;

        this.hasChanges = false;
        var mobileSync = this._settings;
        this._enableObj.checked = mobileSync.userEnable;
        this._urlObj.value = mobileSync.url;
        this._loginObj.value = mobileSync.login;
        this._contactDataBaseObj.value = mobileSync.contactDataBase;
        this._calendarDataBaseObj.value = mobileSync.calendarDataBase;

        this._oParent.resizeBody();
	},
	
	saveChanges: function ()
	{
		var newSettings = new CMobileSyncData();
        newSettings.copy(this._settings);
		newSettings.userEnable = this._enableObj.checked;

		var xml = newSettings.getInXml();
		RequestHandler('update', 'mobile_sync', xml);

		this._newSettings = newSettings;
		this.hasChanges = false;
	},

	build: function(container)
	{
		var obj = this;
		this._mainForm = CreateChild(container, 'form');
		this._mainForm.onsubmit = function () { return false; };
		this._mainForm.className = 'wm_hide';
		var tbl = CreateChild(this._mainForm, 'table');
		tbl.className = 'wm_settings_common';

		var rowIndex = 0;
		var tr = tbl.insertRow(rowIndex++);
		tr.className = '';
		var td = tr.insertCell(0);
		td.className = '';
		td.colSpan = 3;
		td.innerHTML = '<br />';

		tr = tbl.insertRow(rowIndex++);
		td = tr.insertCell(0);
		td = tr.insertCell(1);
		td.colSpan = 2;
		var inp = CreateChild(td, 'input', [['class', 'wm_checkbox'], ['type', 'checkbox'], ['id', 'enable_mobile_sync']]);
		var lbl = CreateChild(td, 'label', [['for', 'enable_mobile_sync']]);
		lbl.innerHTML = Lang.MobileSyncEnableLabel;
		WebMail.langChanger.register('innerHTML', lbl, 'MobileSyncEnableLabel', '');
		inp.onchange = function ()  { obj.hasChanges = true; };
		this._enableObj = inp;

		tr = tbl.insertRow(rowIndex++);
		td = tr.insertCell(0);
		td.className = 'wm_settings_title';
		td.innerHTML = Lang.MobileSyncUrlTitle;
		WebMail.langChanger.register('innerHTML', td, 'MobileSyncUrlTitle', '');
		td = tr.insertCell(1);
		td.colSpan = 2;
		inp = CreateChild(td, 'input', [['class', 'wm_input'], ['type', 'text'], ['size', '50'], ['readonly', 'readonly']]);
		this._urlObj = inp;

		tr = tbl.insertRow(rowIndex++);
		td = tr.insertCell(0);
		td.className = 'wm_settings_title';
		td.innerHTML = Lang.MobileSyncLoginTitle;
		WebMail.langChanger.register('innerHTML', td, 'MobileSyncLoginTitle', '');
		td = tr.insertCell(1);
		td.colSpan = 2;
		inp = CreateChild(td, 'input', [['class', 'wm_input'], ['type', 'text'], ['size', '25'], ['readonly', 'readonly']]);
		this._loginObj = inp;

		tr = tbl.insertRow(rowIndex++);
		td = tr.insertCell(0);
		td.className = 'wm_settings_title';
		td.innerHTML = Lang.MobileSyncContactDataBaseTitle;
		WebMail.langChanger.register('innerHTML', td, 'MobileSyncContactDataBaseTitle', '');
		td = tr.insertCell(1);
		td.colSpan = 2;
		inp = CreateChild(td, 'input', [['class', 'wm_input'], ['type', 'text'], ['size', '15'], ['readonly', 'readonly']]);
		this._contactDataBaseObj = inp;

		tr = tbl.insertRow(rowIndex++);
		td = tr.insertCell(0);
		td.className = 'wm_settings_title';
		td.innerHTML = Lang.MobileSyncCalendarDataBaseTitle;
		WebMail.langChanger.register('innerHTML', td, 'MobileSyncCalendarDataBaseTitle', '');
		td = tr.insertCell(1);
		td.colSpan = 2;
		inp = CreateChild(td, 'input', [['class', 'wm_input'], ['type', 'text'], ['size', '15'], ['readonly', 'readonly']]);
		this._calendarDataBaseObj = inp;

		tr = tbl.insertRow(rowIndex++);
		td = tr.insertCell(0);
		td.className = '';
		td = tr.insertCell(1);
		td.colSpan = 2;
		var div = CreateChild(td, 'div', [['class', 'syncTextTitleClass']]);
		div.innerHTML = Lang.MobileSyncTitleText;
		WebMail.langChanger.register('innerHTML', div, 'MobileSyncTitleText', '');

		var eButtonsPane = CreateChild(this._mainForm, 'div', [['class', 'wm_settings_buttons']]);
		inp = CreateChild(eButtonsPane, 'input', [['class', 'wm_button'], ['type', 'button'], ['value', Lang.Save]]);
		WebMail.langChanger.register('value', inp, 'Save', '');
		inp.onclick = function () {
			obj.saveChanges();
		};
	}//build
};

if (typeof window.JSFileLoaded != 'undefined') {
	JSFileLoaded();
}