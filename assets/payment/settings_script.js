/* global settings_object */
/* exported redirectToCallback */
let BuyLicenseWindow = null;
let UpdatePlanWindow = null;
let CancelSubscriptionWindow = null;
let wait = false;
/* eslint-disable */
function redirectToCallback(param) {
	window.location.href += "&" + param;
}
function cancelPayment(){
	let CurrentWindow;
	let template;
	if (BuyLicenseWindow && !BuyLicenseWindow.closed) {
		CurrentWindow = BuyLicenseWindow;
		template = 'payment_template';
	} else if (CancelSubscriptionWindow && !CancelSubscriptionWindow.closed) {
		CurrentWindow = CancelSubscriptionWindow;
		template = 'cancel_template';
	}
	wp.ajax
	.send(template, {
		type: 'POST',
		data: {
			_ajax_nonce: settings_object.ajaxnonce,
			priceinfo: JSON.stringify(settings_object.infoplans),
		},
	})
	.done((response) => {
		CurrentWindow.document.body.innerHTML = '';
		CurrentWindow.document.write(response);
		wait = false;
	});
}
function confirmPlanChange(wantedPlanRank){
	let CurrentWindow;
	if (BuyLicenseWindow && !BuyLicenseWindow.closed) {
		CurrentWindow = BuyLicenseWindow;
	} else if (CancelSubscriptionWindow && !CancelSubscriptionWindow.closed) {
		CurrentWindow = CancelSubscriptionWindow;
	}
	confirmPlanChangeWindow(CurrentWindow, wantedPlanRank);
}
//this function is used by the dialog CustomFormWindow 
function customStripeForm(planRank){
	let CurrentWindow;
	if (BuyLicenseWindow && !BuyLicenseWindow.closed) {
		CurrentWindow = BuyLicenseWindow;
	} else if (CancelSubscriptionWindow && !CancelSubscriptionWindow.closed) {
		CurrentWindow = CancelSubscriptionWindow;
	}
	showCustomFormWindow(planRank, CurrentWindow);
}
//this function is used by the dialog CustomFormWindow 
function thankYouPage(mode){
	let CurrentWindow;
	if (BuyLicenseWindow && !BuyLicenseWindow.closed) {
		CurrentWindow = BuyLicenseWindow;
	} else if (CancelSubscriptionWindow && !CancelSubscriptionWindow.closed) {
		CurrentWindow = CancelSubscriptionWindow;
	} else if (UpdatePlanWindow && !UpdatePlanWindow.closed) {
		CurrentWindow = UpdatePlanWindow;
	}
	showThankYouPageWindow(CurrentWindow, mode);
}
/* eslint-enable */

document.addEventListener('DOMContentLoaded', function () {
	const buyButton = document.querySelector('#buy-license-button');
	if (buyButton) {
		buyButton.addEventListener('click', (e) => {
			e.preventDefault();
			showBuyLicenseWindow();
		});
	}

	const modifyPlan = document.querySelector('#modify-plan');
	if (modifyPlan) {
		modifyPlan.addEventListener('click', (e) => {
			e.preventDefault();
			showBuyLicenseWindow();
		});
	}

	const cancelButton = document.querySelector('#cancel-license-button');
	if (cancelButton) {
		cancelButton.addEventListener('click', (e) => {
			e.preventDefault();
			showCancelSubscriptionWindow();
		});
	}

	const updateButton = document.querySelector('#update-plan-button');
	if (updateButton) {
		updateButton.addEventListener('click', (e) => {
			e.preventDefault();
			showUpdatePlanWindow();
		});
	}

	const reactivateButton = document.querySelector('#reactivate-plan-button');
	if (reactivateButton) {
		reactivateButton.addEventListener('click', (e) => {
			e.preventDefault();
			reactivateFunction();
		});
	}
});
const showCustomFormWindow = (planRank, CurrentWindow) => {
	wp.ajax
		.send('custom_form_template', {
			type: 'POST',
			data: {
				_ajax_nonce: settings_object.ajaxnonce,
				planRank,
				priceinfo: JSON.stringify(settings_object.infoplans),
			},
		})
		.done((response) => {
			CurrentWindow.document.body.innerHTML = '';
			CurrentWindow.document.write(response);
		});
};
const showThankYouPageWindow = (CurrentWindow, mode) => {
	wp.ajax
		.send('thankyou_page', {
			type: 'POST',
			data: {
				_ajax_nonce: settings_object.ajaxnonce,
				mode,
			},
		})
		.done((response) => {
			CurrentWindow.document.body.innerHTML = '';
			CurrentWindow.document.write(response);
		});
};
const showUpdatePlanWindow = () => {
	if (CancelSubscriptionWindow && !CancelSubscriptionWindow.closed) {
		CancelSubscriptionWindow.close();
	}
	if (BuyLicenseWindow && !BuyLicenseWindow.closed) {
		BuyLicenseWindow.close();
	}
	if (UpdatePlanWindow === null || UpdatePlanWindow.closed) {
		const windowFeatures = `
            left=500,
            top=200,
            width=1200,
            height=700,
            scrollbars=yes,
        `;
		wp.ajax
			.send('update_template', {
				type: 'POST',
				data: {
					_ajax_nonce: settings_object.ajaxnonce,
					priceinfo: JSON.stringify(settings_object.infoplans),
				},
			})
			.done((response) => {
				UpdatePlanWindow = window.open(
					'',
					'Update-Plan',
					windowFeatures
				);
				UpdatePlanWindow.document.write(response);
			});
	} else if (UpdatePlanWindow) {
		UpdatePlanWindow.focus();
		return UpdatePlanWindow;
	}
};
const showBuyLicenseWindow = () => {
	if (CancelSubscriptionWindow && !CancelSubscriptionWindow.closed) {
		CancelSubscriptionWindow.close();
	}
	if (UpdatePlanWindow && !UpdatePlanWindow.closed) {
		UpdatePlanWindow.close();
	}
	if (!wait && (BuyLicenseWindow === null || BuyLicenseWindow.closed)) {
		const windowFeatures = `
            left=500,
            top=200,
            width=1200,
            height=700,
            scrollbars=yes,
		`;
		wait = true;
		wp.ajax
			.send('payment_template', {
				type: 'POST',
				data: {
					_ajax_nonce: settings_object.ajaxnonce,
					priceinfo: JSON.stringify(settings_object.infoplans),
				},
			})
			.done((response) => {
				BuyLicenseWindow = window.open(
					'',
					'Buy-license',
					windowFeatures
				);
				BuyLicenseWindow.document.write(response);
				wait = false;
			});
	} else if (BuyLicenseWindow) {
		BuyLicenseWindow.focus();
		return BuyLicenseWindow;
	}
};

const confirmPlanChangeWindow = (CurrentWindow, wantedPlanRank) => {
	if (wait) {
		return;
	}
	wait = true;
	wp.ajax
		.send('change_plan_template', {
			type: 'POST',
			data: {
				_ajax_nonce: settings_object.ajaxnonce,
				priceinfo: JSON.stringify(settings_object.infoplans),
				wantedPlanRank,
			},
		})
		.done((response) => {
			CurrentWindow.document.body.innerHTML = '';
			CurrentWindow.document.write(response);
			wait = false;
		});
};

const showCancelSubscriptionWindow = () => {
	if (BuyLicenseWindow && !BuyLicenseWindow.closed) {
		BuyLicenseWindow.close();
	}
	if (UpdatePlanWindow && !UpdatePlanWindow.closed) {
		UpdatePlanWindow.close();
	}
	if (
		!wait &&
		(CancelSubscriptionWindow === null || CancelSubscriptionWindow.closed)
	) {
		const windowFeatures = `
			left=500,
			top=200,
			width=1200,
			height=700,
			scrollbars=yes,
		`;
		wait = true;
		wp.ajax
			.send('cancel_template', {
				type: 'POST',
				data: {
					_ajax_nonce: settings_object.ajaxnonce,
					priceinfo: JSON.stringify(settings_object.infoplans),
				},
			})
			.done((response) => {
				CancelSubscriptionWindow = window.open(
					'',
					'Cancel subscription',
					windowFeatures
				);
				CancelSubscriptionWindow.document.write(response);
				wait = false;
			});
	} else if (CancelSubscriptionWindow) {
		CancelSubscriptionWindow.focus();
	}
};
const reactivateFunction = () => {
	fetch(settings_object.ajax_url, {
		method: 'POST',
		credentials: 'include',
		headers: new Headers({
			'Content-Type': 'application/x-www-form-urlencoded',
		}),
		body: `action=reactivate_plan&_ajax_nonce=${settings_object.ajaxnonce}`,
	})
		.then((resp) => resp.json())
		.then((response) => {
			if (response.success) {
				redirectToCallback('notices-code=reactivate');
			}
		});
};
window.onunload = function () {
	if (BuyLicenseWindow !== null) {
		BuyLicenseWindow.close();
	}
	if (UpdatePlanWindow !== null) {
		UpdatePlanWindow.close();
	}
	if (CancelSubscriptionWindow !== null) {
		CancelSubscriptionWindow.close();
	}
};
