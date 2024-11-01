/* global wubtitle_object_modal */
document.addEventListener('DOMContentLoaded', function () {
	const pattern = new RegExp(
		'^(https?:\\/\\/)?' + // protocol
		'((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,})' + // domain name
		'(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
			'(\\#[-a-z\\d_]*)?', // fragment locator
		'i'
	);
	let isOpened = false;
	let videoTitle;
	const myNotice = document.getElementById('wubtitle-notice');
	const button = document.getElementById('insert-my-media');
	if (button) {
		button.addEventListener('click', () => {
			wp.media.editor.remove();
			openMediaWindow();
		});
	}

	const windowTrascriptions = wp.media({
		frame: 'post',
		state: 'embed',
		library: {
			type: ['video'],
		},
	});

	windowTrascriptions.on('insert', () => {
		const media = windowTrascriptions
			.state()
			.get('selection')
			.first()
			.toJSON();
		wp.ajax
			.send('get_transcript_internal_video', {
				type: 'POST',
				data: {
					id: media.id,
					_ajax_nonce: wubtitle_object_modal.ajaxnonce,
					from: 'classic_editor',
				},
			})
			.then((response) => {
				myNotice.style.display = 'none';
				wp.media.editor.insert(
					`<p> ${wp.i18n.__(
						'Transcription of the video',
						'wubtitle'
					)} ${response.post_title} </p> <p> ${
						response.post_content
					} </p>`
				);
			})
			.fail((response) => {
				myNotice.style.display = '';
				myNotice.innerHTML = `<p>  ${response} </p>`;
			});
	});

	windowTrascriptions.on('select', () => {
		const embedUrl = document.getElementById('embed-url-field').value;
		const languageSelect = document.getElementById(
			'transcript-select-lang'
		);
		const subtitle = languageSelect.value;
		if (subtitle === '') {
			myNotice.style.visibility = 'visible';
			myNotice.innerHTML = `<p>  ${wp.i18n.__(
				'Error, language not selected',
				'wubtitle'
			)}  </p>`;
			return;
		}
		wp.ajax
			.send('get_transcript_embed', {
				type: 'POST',
				data: {
					_ajax_nonce: wubtitle_object_modal.ajaxnonce,
					urlVideo: embedUrl,
					subtitle,
					videoTitle,
				},
			})
			.then((response) => {
				myNotice.style.display = 'none';
				wp.media.editor.insert(
					`[embed]  ${embedUrl} [/embed]
					<p> ${response} </p>`
				);
			})
			.fail((response) => {
				myNotice.style.display = '';
				myNotice.innerHTML = `<p>  ${response} </p>`;
			});
	});

	const getLanguages = (inputUrl) => {
		const selectInput = document.getElementById('transcript-select-lang');
		const errorMessage = document.getElementById(
			'error-message-transcript'
		);
		wp.ajax
			.send('get_video_info', {
				type: 'POST',
				data: {
					_ajax_nonce: wubtitle_object_modal.ajaxnonce,
					url: inputUrl,
				},
			})
			.then((response) => {
				selectInput.innerHTML = `<option value="">${wp.i18n.__(
					'Select language',
					'wubtitle'
				)}</option>`;
				videoTitle = response.title;
				if (!response.languages) {
					errorMessage.innerHTML = wp.i18n.__(
						'Error: this video does not contain subtitles. Select a video with subtitles to generate the transcript',
						'wubtitle'
					);
					selectInput.disabled = true;
					return;
				}
				errorMessage.innerHTML = '';
				selectInput.disabled = false;
				response.languages.forEach((subtitle) => {
					let value;
					let label;
					if (response.source === 'youtube') {
						value = subtitle.baseUrl;
						label = subtitle.name.simpleText;
					}
					if (response.source === 'vimeo') {
						value = subtitle.code;
						label = subtitle.name;
					}
					document.getElementById(
						'transcript-select-lang'
					).innerHTML += `<option value=${value}>${label}</option>`;
				});
			})
			.fail((response) => {
				errorMessage.innerHTML = response;
				document.getElementById(
					'transcript-select-lang'
				).innerHTML = `<option value="">${wp.i18n.__(
					'Select language',
					'wubtitle'
				)}</option>`;
			});
	};

	const insertSelect = () => {
		if (!document.getElementById('transcript-select-lang')) {
			const divModal = document.getElementsByClassName(
				'embed-link-settings'
			);
			const header = document.createElement('h2');
			const textHeader = document.createTextNode(
				wp.i18n.__('Language of trascription', 'wubtitle')
			);
			header.appendChild(textHeader);
			const select = document.createElement('SELECT');
			select.setAttribute('id', 'transcript-select-lang');
			const errorMessage = document.createElement('p');
			errorMessage.setAttribute('id', 'error-message-transcript');
			select.innerHTML = `<option value="">${wp.i18n.__(
				'Select language',
				'wubtitle'
			)}</option>`;
			if (divModal.length > 0) {
				divModal[0].appendChild(header);
				divModal[0].appendChild(select);
				divModal[0].appendChild(errorMessage);
			}
			select.disabled = true;
		}
	};

	const addListenerFunction = () => {
		const inputUrl = document.getElementById('embed-url-field');
		if (inputUrl) {
			if (pattern.test(inputUrl.value)) {
				getLanguages(inputUrl.value);
			}
			inputUrl.addEventListener('input', () => {
				if (inputUrl.value === '') {
					const select = document.getElementById(
						'transcript-select-lang'
					);
					select.innerHTML = `<option value="">${wp.i18n.__(
						'Select language',
						'wubtitle'
					)}</option>`;
					select.disabled = true;
				}
				if (pattern.test(inputUrl.value)) {
					getLanguages(inputUrl.value);
				}
			});
		}
	};

	const openMediaWindow = () => {
		windowTrascriptions.open();
		const modalEmbed = document.getElementById('menu-item-embed');
		modalEmbed.addEventListener('click', () => {
			insertSelect();
			addListenerFunction();
		});
		insertSelect();
		if (!isOpened) {
			addListenerFunction();
			document.getElementById('menu-item-gallery').remove();
			document.getElementById('menu-item-playlist').remove();
			document.getElementById('menu-item-video-playlist').remove();
			document.getElementById('menu-item-featured-image').remove();
			isOpened = true;
		}
	};
	return false;
});
