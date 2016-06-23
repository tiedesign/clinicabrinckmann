(function (window, $) {

	function fDisableInput(sName, bIsDisabled)
	{
		var 
			oInput = $(sName),
			oInputLabel = $(sName + '_label'),

			oInputJs = (oInput && 0 < oInput.length) ? oInput : null,
			oInputLabelJs = (oInputLabel && 0 < oInputLabel.length) ? oInputLabel : null
		;

		if (oInputJs)
		{
			if (bIsDisabled)
			{
				oInputJs.addClass('disabled').attr('disabled', 'disabled');
			}
			else
			{
				oInputJs.removeClass('disabled').attr('disabled', '');
			}

			if (oInputLabelJs)
			{
				if (bIsDisabled)
				{
					oInputLabelJs.addClass('disabled');
				}
				else
				{
					oInputLabelJs.removeClass('disabled');
				}
			}
		}
	}

	function InitEnableMobileSyncCheckbox()
	{
		var bIsChecked = !$(this).is(':checked');

		fDisableInput('#txtMobileSyncUrl', bIsChecked);
		fDisableInput('#txtMobileSyncContactDatabase', bIsChecked);
		fDisableInput('#txtMobileSyncCalendarDatabase', bIsChecked);
	}


	$(function () {
		
		var oEnableMobileSync = $('#chEnableMobileSync');

		if (oEnableMobileSync && 0 < oEnableMobileSync.length)
		{
			oEnableMobileSync.change(InitEnableMobileSyncCheckbox);
			InitEnableMobileSyncCheckbox.call(oEnableMobileSync[0]);
		}

		$('#next-btn-server-check').click(function () {
			window.open('http://www.afterlogic.com/congratulations/webmail-lite-php');
		});
		
		$('#exit-btn-completed').click(function () {
			window.location = '../adminpanel/';
		});
	});

}(window, jQuery));