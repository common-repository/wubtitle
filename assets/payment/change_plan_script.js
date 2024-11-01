(function (Stripe, document) {
	const { adminAjax, nonce, stripeKey } = WP_GLOBALS;
	let stripe = null;
	let isUpgrade;

	const paymentSuccessfull = () => {
		const message = isUpgrade ? 'upgrade' : 'downgrade';
		window.opener.thankYouPage(message);
	};

	const confirmPayment = (clientSecret, paymentMethod) => {
		stripe
			.confirmCardPayment(clientSecret, {
				payment_method: paymentMethod,
				setup_future_usage: 'off_session',
			})
			.then((response) => {
				if (response.paymentIntent.status === 'succeeded') {
					paymentSuccessfull();
				}
				document.getElementById('error-message').innerHTML =
					response.data;
			});
	};

	const handleConfirm = () => {
		fetch(adminAjax, {
			method: 'POST',
			credentials: 'include',
			headers: new Headers({
				'Content-Type': 'application/x-www-form-urlencoded',
			}),
			body: `action=change_plan&_ajax_nonce=${nonce}`,
		})
			.then((resp) => resp.json())
			.then((response) => {
				if (response.success) {
					isUpgrade = response.data.isUpgrade;
					if (
						response.data &&
						response.data.status === 'requires_action'
					) {
						confirmPayment(
							response.data.clientSecret,
							response.paymentMethod
						);
					} else {
						paymentSuccessfull();
					}
				}
			});
	};

	const init = () => {
		const confirmButton = document.querySelector('#confirm_changes');
		const closeButton = document.querySelector('#forget');
		stripe = Stripe(stripeKey);

		if (confirmButton) {
			confirmButton.addEventListener('click', () => {
				handleConfirm();
			});
		}
		if (closeButton) {
			closeButton.addEventListener('click', () => {
				window.opener.cancelPayment();
			});
		}
	};

	return {
		init,
	};
})(Stripe, document).init();
