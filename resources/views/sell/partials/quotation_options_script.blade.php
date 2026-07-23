@if((!empty($status) && $status === 'quotation') || (!empty($transaction) && (int) ($transaction->is_quotation ?? 0) === 1))
<script>
(function () {
	function activeOption() {
		return parseInt($('#quote_active_option').val() || '1', 10) || 1;
	}

	function optionCount() {
		return Math.max(1, parseInt($('#quote_option_count').val() || '1', 10) || 1);
	}

	function setOptionCount(n) {
		$('#quote_option_count').val(Math.max(1, n));
	}

	function padOption(n) {
		return String(n).padStart(2, '0');
	}

	function renderTabs() {
		var count = optionCount();
		var active = activeOption();
		var html = '';
		for (var i = 1; i <= count; i++) {
			html += '<button type="button" class="quote-opt-tab' + (i === active ? ' active' : '') + '" data-option="' + i + '">OPTION ' + padOption(i) + '</button>';
		}
		$('#quoteOptTabs').html(html);
		filterRows();
		updateOptionSubtotal();
	}

	function filterRows() {
		var active = activeOption();
		$('#pos_table tbody tr.product_row').each(function () {
			var g = parseInt($(this).find('.row_option_group').val() || $(this).attr('data-option-group') || '1', 10) || 1;
			$(this).toggle(g === active);
		});
		// renumber visible #
		var n = 1;
		$('#pos_table tbody tr.product_row:visible').each(function () {
			$(this).find('td').first().find('.serial_no, .row_number').text(n);
			var firstTd = $(this).children('td').eq(0);
			if (firstTd.find('input').length === 0 && firstTd.find('button').length === 0) {
				// leave product cell alone; # column is first if serial_no
			}
			n++;
		});
	}

	function updateOptionSubtotal() {
		var total = 0;
		$('#pos_table tbody tr.product_row:visible').each(function () {
			var line = __read_number($(this).find('input.pos_line_total'));
			if (!isNaN(line)) total += line;
		});
		if (typeof __currency_trans_from_en === 'function') {
			$('#quoteOptSubtotal').text(__currency_trans_from_en(total, true));
		} else {
			$('#quoteOptSubtotal').text(total.toFixed(2));
		}
	}

	function assignRowToActiveOption($row) {
		var g = activeOption();
		$row.attr('data-option-group', g);
		var $input = $row.find('.row_option_group');
		if (!$input.length) {
			$row.find('td').first().prepend(
				'<input type="hidden" class="row_option_group" name="products[' + $row.data('row_index') + '][option_group]" value="' + g + '">'
			);
		} else {
			$input.val(g);
			// keep name index in sync if present
			var name = $input.attr('name');
			if (!name || name.indexOf('option_group') === -1) {
				$input.attr('name', 'products[' + $row.data('row_index') + '][option_group]');
			}
		}
		filterRows();
		updateOptionSubtotal();
	}

	$(document).on('click', '#quoteOptTabs .quote-opt-tab', function () {
		$('#quote_active_option').val($(this).data('option'));
		renderTabs();
	});

	$('#quoteOptAddBtn').on('click', function () {
		var next = optionCount() + 1;
		setOptionCount(next);
		$('#quote_active_option').val(next);
		renderTabs();
		toastr.success('OPTION ' + padOption(next) + ' added. Add products for this option.');
	});

	// After totals recalculate, refresh option subtotal
	$(document).on('change', '#pos_table input, #pos_table select', function () {
		setTimeout(updateOptionSubtotal, 50);
	});

	// Hook product insert: assign active option
	var _origInsert = window.pos_insert_product_row;
	if (typeof _origInsert === 'function') {
		window.pos_insert_product_row = function (result) {
			_origInsert(result);
			var $row = $('table#pos_table tbody').find('tr.product_row').last();
			assignRowToActiveOption($row);
		};
	}

	$(function () {
		// Ensure existing edit rows have option_group values
		$('#pos_table tbody tr.product_row').each(function () {
			var $row = $(this);
			if (!$row.find('.row_option_group').length) {
				var g = parseInt($row.attr('data-option-group') || '1', 10) || 1;
				$row.find('td').first().prepend(
					'<input type="hidden" class="row_option_group" name="products[' + $row.data('row_index') + '][option_group]" value="' + g + '">'
				);
			}
		});
		renderTabs();
	});
})();
</script>
@endif
