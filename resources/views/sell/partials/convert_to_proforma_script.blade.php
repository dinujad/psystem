{{-- Shared: Convert quotation → proforma, with multi-option picker --}}
<script>
(function () {
	function convertToProforma(url) {
		function doConvert(optionGroup) {
			var reqUrl = url;
			if (optionGroup) {
				reqUrl += (url.indexOf('?') >= 0 ? '&' : '?') + 'option_group=' + encodeURIComponent(optionGroup);
			}
			$.ajax({
				method: 'GET',
				url: reqUrl,
				dataType: 'json',
				success: function (result) {
					if (result && result.need_option && result.options && result.options.length) {
						showOptionPicker(url, result.options, result.msg);
						return;
					}
					if (result && (result.success == true || result.success == 1)) {
						toastr.success(result.msg);
						if (typeof sell_table !== 'undefined' && sell_table) {
							sell_table.ajax.reload();
						}
					} else {
						toastr.error((result && result.msg) ? result.msg : LANG.something_went_wrong);
					}
				},
				error: function () {
					toastr.error(LANG.something_went_wrong);
				}
			});
		}

		function showOptionPicker(baseUrl, options, msg) {
			var wrap = document.createElement('div');
			wrap.style.textAlign = 'left';
			wrap.style.padding = '4px 2px';
			var hint = document.createElement('p');
			hint.style.margin = '0 0 10px 0';
			hint.style.fontSize = '13px';
			hint.style.color = '#374151';
			hint.textContent = msg || (LANG.select_quotation_option_for_proforma || 'Select which option should become the Proforma Invoice.');
			wrap.appendChild(hint);

			options.forEach(function (o, i) {
				var label = document.createElement('label');
				label.style.display = 'flex';
				label.style.alignItems = 'center';
				label.style.gap = '8px';
				label.style.margin = '0 0 8px 0';
				label.style.padding = '10px 12px';
				label.style.border = '1px solid #fecaca';
				label.style.borderRadius = '8px';
				label.style.cursor = 'pointer';
				label.style.background = i === 0 ? '#fff5f5' : '#fff';

				var radio = document.createElement('input');
				radio.type = 'radio';
				radio.name = 'quote_opt_for_proforma';
				radio.value = o.option;
				if (i === 0) radio.checked = true;

				var text = document.createElement('span');
				text.innerHTML = '<strong style="color:#E31E24">' + (o.label || ('OPTION ' + o.option)) + '</strong>' +
					' &nbsp;—&nbsp; ' + (o.total || '') +
					(o.items != null ? ' <span style="color:#6b7280">(' + o.items + ' items)</span>' : '');

				label.appendChild(radio);
				label.appendChild(text);
				label.addEventListener('click', function () {
					wrap.querySelectorAll('label').forEach(function (l) { l.style.background = '#fff'; });
					label.style.background = '#fff5f5';
				});
				wrap.appendChild(label);
			});

			swal({
				title: LANG.quotation_option || 'Quotation Option',
				content: wrap,
				buttons: {
					cancel: true,
					confirm: {
						text: LANG.convert_to_proforma || 'Convert',
						value: true,
						closeModal: true
					}
				}
			}).then(function (confirm) {
				if (!confirm) return;
				var checked = wrap.querySelector('input[name="quote_opt_for_proforma"]:checked');
				if (!checked) {
					toastr.error(LANG.invalid_quotation_option || 'Please select an option');
					return;
				}
				doConvert(checked.value);
			});
		}

		swal({
			title: LANG.sure,
			icon: 'warning',
			buttons: true,
			dangerMode: true,
		}).then(function (confirm) {
			if (confirm) {
				doConvert(null);
			}
		});
	}

	$(document).on('click', 'a.convert-to-proforma', function (e) {
		e.preventDefault();
		convertToProforma($(this).attr('href'));
	});
})();
</script>
