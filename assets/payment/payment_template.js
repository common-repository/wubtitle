(function (document) {
	const { adminAjax, nonce } = WP_GLOBALS;

	const handleChoice = (planRank) => {
		fetch(adminAjax, {
			method: 'POST',
			credentials: 'include',
			headers: new Headers({
				'Content-Type': 'application/x-www-form-urlencoded',
			}),
			body: `action=check_plan_change&plan_rank=${planRank}`,
		})
			.then((resp) => resp.json())
			.then((response) => {
				if (response.success) {
					if (response.data === 'change_plan') {
						window.opener.confirmPlanChange(planRank);
						return;
					}
					window.opener.customStripeForm(planRank);
				}
			});
	};

	const handleUnsubscription = () => {
		fetch(adminAjax, {
			method: 'POST',
			credentials: 'include',
			headers: new Headers({
				'Content-Type': 'application/x-www-form-urlencoded',
			}),
			body: `action=cancel_subscription&_ajax_nonce=${nonce}`,
		}).then(() => {
			window.opener.redirectToCallback('notices-code=delete');
		});
	};

	const init = () => {
		const buttons = document.querySelectorAll('.button-choose-plan');
		buttons.forEach((button) => {
			button.addEventListener('click', () => {
				const planRank = button.getAttribute('plan');
				if (planRank === '0') {
					handleUnsubscription();
				} else {
					handleChoice(planRank);
				}
			});
		});

		const unsubscribeButton = document.querySelector('#unsubscribeButton');
		const closeButton = document.querySelector('#close');
		const successButton = document.querySelector('#success-button');
		if (successButton) {
			successButton.addEventListener('click', () => {
				window.opener.redirectToCallback('notices-code=payment');
			});
		}
		if (unsubscribeButton) {
			unsubscribeButton.addEventListener('click', () => {
				handleUnsubscription();
			});
		}
		if (closeButton) {
			closeButton.addEventListener('click', () => {
				window.close();
			});
		}
	};

	return {
		init,
	};
})(document).init();
