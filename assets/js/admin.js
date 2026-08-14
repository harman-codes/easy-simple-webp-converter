/**
 * Easy & Simple WebP Converter - admin scripts.
 */
(function ($) {
	'use strict';

	$(function () {
		var $range = $('#easy-webp-quality-range');
		var $number = $('#easy-webp-quality');
		var $button = $('#easy-webp-convert-button');
		var $wrap = $('#easy-webp-progress-wrap');
		var $bar = $('#easy-webp-progress-bar');
		var $status = $('#easy-webp-status');
		var $stats = $('#easy-webp-stats');

		var originalLabel = $button.length ? $button.text() : '';
		var totals = { converted: 0, failed: 0, skipped: 0, total: 0, known: false };
		var running = false;

		if ($range.length) {
			$range.on('input', function () {
				$number.val($range.val());
			});
			$number.on('input', function () {
				var val = Math.min(100, Math.max(0, parseInt($number.val(), 10) || 0));
				$range.val(val);
			});
		}

		if (!$button.length) {
			return;
		}

		$button.on('click', function (e) {
			e.preventDefault();
			if (running) {
				return;
			}
			running = true;
			$button.prop('disabled', true);
			$button.text(easyWebp.i18n.converting);
			$wrap.show();
			$status.text(easyWebp.i18n.starting);
			processBatch();
		});

		function processBatch() {
			$.post(easyWebp.ajaxUrl, { action: 'easy_webp_convert', nonce: easyWebp.nonce })
				.done(function (response) {
					if (!response || !response.success) {
						fail(response && response.data && response.data.message
							? response.data.message
							: easyWebp.i18n.error);
						return;
					}

					var data = response.data;

					if (data.starting || !totals.known) {
						totals.total = parseInt(data.total, 10) || 0;
						totals.known = true;
					}

					totals.converted += parseInt(data.converted, 10) || 0;
					totals.skipped += parseInt(data.skipped, 10) || 0;
					totals.failed += parseInt(data.failed, 10) || 0;

					if (data.done) {
						$bar.css('width', '100%');
						if (totals.total === 0) {
							$status.text(easyWebp.i18n.noImages);
						} else {
							$status.text(easyWebp.i18n.done);
						}
						updateStats();
						$button.prop('disabled', false).text(originalLabel);
						running = false;
						return;
					}

					var processed = totals.converted + totals.skipped + totals.failed;
					var pct = totals.total > 0 ? Math.round((processed / totals.total) * 100) : 0;
					pct = Math.min(100, pct);

					$bar.css('width', pct + '%');
					$status.text(easyWebp.i18n.converting + ' ' + pct + '% (' + data.remaining + ' ' + easyWebp.i18n.remaining + ')');
					updateStats();

					setTimeout(processBatch, 300);
				})
				.fail(function () {
					fail(easyWebp.i18n.error);
				});
		}

		function updateStats() {
			$stats.html(
				'<strong>' + easyWebp.i18n.converted + ':</strong> ' + totals.converted +
				' &middot; <strong>' + easyWebp.i18n.skipped + ':</strong> ' + totals.skipped +
				' &middot; <strong>' + easyWebp.i18n.failed + ':</strong> ' + totals.failed
			);
		}

		function fail(message) {
			$status.text(message);
			$button.prop('disabled', false).text(originalLabel);
			running = false;
		}
	});
})(jQuery);