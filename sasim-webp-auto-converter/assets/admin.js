(function () {
	'use strict';

	const config = window.saswacAdmin;
	if (!config) {
		return;
	}

	const range = document.getElementById('saswac-quality-range');
	const number = document.getElementById('saswac-quality-number');

	if (range && number) {
		const clamp = (value) => Math.max(0, Math.min(100, value));

		range.addEventListener('input', function () {
			number.value = range.value;
		});

		number.addEventListener('input', function () {
			const value = clamp(parseInt(number.value, 10) || 0);
			range.value = String(value);
			number.value = String(value);
		});

		number.addEventListener('change', function () {
			const value = clamp(parseInt(number.value, 10) || 0);
			range.value = String(value);
			number.value = String(value);
		});
	}

	const btn = document.getElementById('saswac-batch-start');
	const progress = document.getElementById('saswac-batch-progress');
	const progressBar = document.getElementById('saswac-batch-progress-bar');
	const status = document.getElementById('saswac-batch-status');

	if (!btn || !progress || !progressBar || !status) {
		return;
	}

	let running = false;

	const setProgress = (percent, indeterminate) => {
		progress.hidden = false;
		progress.classList.toggle('saswac-progress--indeterminate', indeterminate);

		if (indeterminate) {
			progress.removeAttribute('aria-valuenow');
			return;
		}

		const safePercent = Math.max(0, Math.min(100, percent));
		progressBar.style.width = safePercent + '%';
		progress.setAttribute('aria-valuenow', String(Math.round(safePercent)));
	};

	const setStatus = (text, isError) => {
		status.textContent = text;
		status.classList.toggle('is-error', Boolean(isError));
	};

	const formatProgress = (processed, total, convertedTotal) => {
		return config.i18n.progress
			.replace('%1$s', String(processed))
			.replace('%2$s', String(total))
			.replace('%3$s', String(convertedTotal));
	};

	const formatDone = (convertedTotal, processed) => {
		return config.i18n.done
			.replace('%1$s', String(convertedTotal))
			.replace('%2$s', String(processed));
	};

	btn.addEventListener('click', async function () {
		if (running || !config.converterAvailable) {
			return;
		}

		running = true;
		let offset = 0;
		let total = 0;
		let convertedTotal = 0;

		btn.disabled = true;
		btn.setAttribute('aria-busy', 'true');
		btn.textContent = config.i18n.running;
		setStatus(config.i18n.starting, false);
		setProgress(0, true);

		try {
			while (true) {
				const body = new URLSearchParams();
				body.set('action', 'saswac_batch');
				body.set('offset', String(offset));
				body.set('_ajax_nonce', config.nonce);

				let res;
				try {
					res = await fetch(config.ajaxUrl, {
						method: 'POST',
						body,
						credentials: 'same-origin',
					});
				} catch (error) {
					setStatus(config.i18n.networkError, true);
					break;
				}

				let data;
				try {
					data = await res.json();
				} catch (error) {
					setStatus(config.i18n.error, true);
					break;
				}

				if (!data.success) {
					setStatus(data.data?.message || config.i18n.error, true);
					break;
				}

				const payload = data.data;
				if (typeof payload.total === 'number') {
					total = payload.total;
				}

				convertedTotal += payload.converted_batch || 0;
				offset = payload.next_offset;

				const processed = payload.processed || 0;

				if (total > 0) {
					setProgress((processed / total) * 100, false);
				} else if (payload.done) {
					setProgress(100, false);
				} else {
					setProgress(0, true);
				}

				if (payload.done) {
					if (total === 0) {
						setStatus(config.i18n.noImages, false);
						setProgress(0, false);
						progress.hidden = true;
					} else {
						setStatus(formatDone(convertedTotal, processed), false);
						setProgress(100, false);
					}
					break;
				}

				setStatus(formatProgress(processed, total, convertedTotal), false);
			}
		} finally {
			btn.disabled = !config.converterAvailable;
			btn.removeAttribute('aria-busy');
			btn.textContent = config.i18n.start;
			running = false;
		}
	});
})();
