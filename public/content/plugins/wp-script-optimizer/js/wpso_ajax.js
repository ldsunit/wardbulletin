jQuery(function($) {

	$( ".wpso-conditions" ).on('change', 'td.condition select', function(e) {
		e.preventDefault();

		var valueContainer = $(this).parent().siblings( 'td.value' );

		var ajaxData = {
			'action': 'get_value_select_ajax',
			'ajax_nonce': wpso_ajax_object.ajax_nonce,
			'param': $(this).val()
		};

		$.ajax({
			type: 'POST',
			url: wpso_ajax_object.ajax_url,
			data: ajaxData,
			beforeSend: function ()	{
				valueContainer.html( '<span class="spinner is-active"></span>' );
			},
			success: function(data)	{
				valueContainer.html( data );
			},
			error: function(xhr) {
				alert("An Error is occured: " + xhr.status + " " + xhr.statusText);
			}
		});
	});


	$( "input[id*='remove-conditions']" ).click(function(e) {
		e.preventDefault();

		var button 	 = $(this),
			handleId = button.data('handle-id');

		var ajaxData = {
			'action': 'remove_conditions_ajax',
			'ajax_nonce': wpso_ajax_object.ajax_nonce,
			'handle_id': handleId
		};

		$.ajax({
			type: 'POST',
			url: wpso_ajax_object.ajax_url,
			data: ajaxData,
			beforeSend: function ()	{
				button.prop('disabled', true);
				button.before( '<span class="spinner is-active"></span>' );
			},
			success: function(data)	{
				toggleItemEdit( button.closest('.wpso-item-wrapper').find('.hidden') );
				button.prop('disabled', false);
				button.siblings('.spinner').remove();
				// location.replace( wpso_ajax_object.screen_url );
				location.reload();
			},
			error: function(xhr) {
				alert("An Error is occured: " + xhr.status + " " + xhr.statusText);
			}
		});
	});


	$( "input[id*='save-conditions']" ).click(function(e) {
		e.preventDefault();

		var button 	 		= $(this),
			handleId 		= button.data('handle-id'),
			conditionsArray = {};

		updateConditionIds( button );

		var conditionGroups = button.closest('.wpso-item-edit').find('.wpso-condition-group');
		conditionGroups.each(function() {
			var groupId 		 = $(this).data('condition-group'),
				conditionSingles = $(this).find('.wpso-condition-single');

			conditionsArray['condition_group_' + groupId] = {};

			conditionSingles.each(function() {
				var singleId = $(this).data('condition-single');

				conditionsArray['condition_group_' + groupId]['condition_single_' + singleId] = {};

				conditionsArray['condition_group_' + groupId]['condition_single_' + singleId]['condition'] = $(this).find('td.condition select').val();
				conditionsArray['condition_group_' + groupId]['condition_single_' + singleId]['operator']  = $(this).find('td.operator select').val();
				conditionsArray['condition_group_' + groupId]['condition_single_' + singleId]['value'] 	   = $(this).find('td.value select').val();
			});
		});

		var ajaxData = {
			'action': 'save_conditions_ajax',
			'ajax_nonce': wpso_ajax_object.ajax_nonce,
			'handle_id': handleId,
			'conditions': conditionsArray
		};

		$.ajax({
			type: 'POST',
			url: wpso_ajax_object.ajax_url,
			data: ajaxData,
			beforeSend: function ()	{
				button.prop('disabled', true);
				button.before( '<span class="spinner is-active"></span>' );
			},
			success: function(data)	{
				toggleItemEdit( button.closest('.wpso-item-wrapper').find('.hidden') );
				button.prop('disabled', false);
				button.siblings('.spinner').remove();
				// location.replace( wpso_ajax_object.screen_url + '&qs=' + wpso_ajax_object.query_string );
				location.reload();
			},
			error: function(xhr) {
				alert("An Error is occured: " + xhr.status + " " + xhr.statusText);
			}
		});
	});


	$( ".wpso-update-list" ).on('click', function(e) {
		e.preventDefault();

		var button 		= $(this),
			queryString = button.attr('data-querystring');

		if ( queryString == 'global' ) {
			urlVar = wpso_ajax_object.home_url + '?wpso=check';
		} else {
			urlVar = wpso_ajax_object.home_url + '?' + queryString + '&wpso=check';
		}

		$.ajax({
			type: 'POST',
			url: urlVar,
			beforeSend: function ()	{
				button.prop('disabled', true);
				button.find('i.fa').addClass('fa-spin');
			},
			success: function(data)	{
				location.replace( wpso_ajax_object.screen_url + '&qs=' + btoa( queryString ) );
			},
			error: function(xhr) {
				alert("An Error is occured: " + xhr.status + " " + xhr.statusText);
			}
		});
	});


	$( ".wpso-delete-list" ).on('click', function(e) {
		e.preventDefault();

		var button 		= $(this),
			queryString = button.attr('data-querystring');

		var ajaxData = {
			'action': 'delete_handle_list_ajax',
			'ajax_nonce': wpso_ajax_object.ajax_nonce,
			'query_string': queryString
		};

		var r = confirm( wpso_ajax_object.delete_all_confirm );

		if ( r == true ) {
			$.ajax({
				type: 'POST',
				url: wpso_ajax_object.ajax_url,
				data: ajaxData,
				beforeSend: function ()	{
					button.prop('disabled', true);
					button.find('i.fa').removeClass('fa-trash').addClass('fa-spinner fa-spin');
				},
				success: function(data)	{
					location.replace( wpso_ajax_object.screen_url );
				},
				error: function(xhr) {
					alert("An Error is occured: " + xhr.status + " " + xhr.statusText);
				}
			});
		}
	});


	$( ".wpso-sync-list" ).on('click', function(e) {
		e.preventDefault();

		var button 		= $(this),
			queryString = button.attr('data-querystring');

		var ajaxData = {
			'action': 'sync_handle_list_ajax',
			'ajax_nonce': wpso_ajax_object.ajax_nonce,
			'query_string': queryString
		};

		var r = confirm( wpso_ajax_object.sync_page_confirm );

		if ( r == true ) {
			$.ajax({
				type: 'POST',
				url: wpso_ajax_object.ajax_url,
				data: ajaxData,
				beforeSend: function ()	{
					button.prop('disabled', true);
					button.find('i.fa').removeClass('fa-cloud-download').addClass('fa-spinner fa-spin');
				},
				success: function(data)	{
					location.reload();
				},
				error: function(xhr) {
					alert("An Error is occured: " + xhr.status + " " + xhr.statusText);
				}
			});
		}
	});


	$( "#wpso-search-page-result" ).on('click', '#wpso-get-search-page-input', function(e) {
		e.preventDefault();

		var button 		= $(this);
		var queryString = button.attr('data-querystring');
		var check 		= '';

		if ( $('#wpso-get-search-page-sync').prop('checked') == true ) {
			check = '&wpso=synccheck';
		} else {
			check = '&wpso=check';
		}

		$.ajax({
			type: 'POST',
			url: wpso_ajax_object.home_url + '?' + atob(queryString) + check,
			beforeSend: function ()	{
				button.prop('disabled', true);
				button.find('i.fa').removeClass('fa-arrow-right').addClass('fa-spinner fa-spin');
			},
			success: function(data)	{
				location.replace( wpso_ajax_object.screen_url + '&qs=' + queryString );
			},
			error: function(xhr) {
				alert("An Error is occured: " + xhr.status + " " + xhr.statusText);
			}
		});
	});


	var delayTimer;
	$('#wpso-search-page-input').on('keyup', function() {
		var ajaxData = {
			'action': 'process_page_request',
			'ajax_nonce': wpso_ajax_object.ajax_nonce,
			'page_request': $(this).val()
		};

		var input   	 = $(this),
			inputOverlay = $(this).next('.wpso-overlay'),
			results 	 = $('#wpso-search-page-result');

		if ( input.val() == '' || input.val().length < 3 ) {
			inputOverlay.html('');
			results.hide();
		} else {
			clearTimeout(delayTimer);
		    delayTimer = setTimeout(function() {
				$.ajax({
					type: 'POST',
					dataType: 'json',
					url: wpso_ajax_object.ajax_url,
					data: ajaxData,
					beforeSend: function ()	{
						inputOverlay.html('<i class="fa fa-spinner fa-spin"></i>');
					},
					success: function(data)	{
						if ( ! data.error ) {
							inputOverlay.html('<i class="fa fa-check" style="color:green;"></i>');
							results.slideDown('fast');
							results.html( data.html );
						} else {
							inputOverlay.html('<i class="fa fa-times" style="color:red;"></i>');
							results.slideDown('fast');
							results.html( '<p><b>' + data.error + '</b></p>' + data.html );
						}
					},
					error: function(xhr) {
						alert("An Error is occured: " + xhr.status + " " + xhr.statusText);
					}
				});
		    }, 1000);
		}
	});


	if ( $('#wpso-saved-urls-list').length ) {
		$.ajax({
			type: 'POST',
			url: wpso_ajax_object.ajax_url,
			data: { 'action': 'get_saved_urls_list', 'ajax_nonce': wpso_ajax_object.ajax_nonce, 'query_string': wpso_ajax_object.query_string },
			beforeSend: function ()	{
				$('#wpso-saved-urls-list').html('<center><i class="fa fa-spinner fa-pulse fa-lg"></i></center>');
			},
			success: function(data)	{
				$('#wpso-saved-urls-list').html( data );
			},
			error: function(xhr) {
				alert("An Error is occured: " + xhr.status + " " + xhr.statusText);
			}
		});
	}


	$( "#wpso-update-all-lists" ).on('click', function(e) {
		e.preventDefault();

		var savedUrls = $('#wpso-saved-urls-list .wpso-saved-url');

		savedUrls.each( function( key, value ) {
			var link 		= $(this).find('.wpso-saved-url-link'),
				stats 		= $(this).find('.wpso-saved-url-stats'),
				queryString = link.attr('data-querystring'),
				urlVar 		= wpso_ajax_object.home_url + '?' + queryString + '&wpso=check',
				spinner 	= $('<i class="fa fa-refresh fa-spin fa-lg" style="margin-right:4px;"></i>'),
				check 		= $('<i class="fa fa-check fa-lg" style="margin-right:4px; color:green;"></i>'),
				error 		= $('<i class="fa fa-times fa-lg" style="margin-right:4px; color:red;"></i>');

			$.ajax({
				type: 'POST',
				url: urlVar,
				beforeSend: function ()	{
					spinner.prependTo(stats);
				},
				success: function(data)	{
					spinner.remove();
					check.prependTo(stats);
				},
				error: function(xhr) {
					spinner.remove();
					error.prependTo(stats);

					if ( xhr.status ) {
						var text = wpso_ajax_object.page_not_exist;
						alert( xhr.status + " " + xhr.statusText + "\n\n" + text.replace( '%s', queryString ) );

						var ajaxData = {
							'action': 'delete_handle_list_ajax',
							'ajax_nonce': wpso_ajax_object.ajax_nonce,
							'query_string': queryString
						};

						$.ajax({
							type: 'POST',
							url: wpso_ajax_object.ajax_url,
							data: ajaxData,
							beforeSend: function ()	{},
							success: function(data)	{},
							error: function(xhr) {
								alert("An Error is occured: " + xhr.status + " " + xhr.statusText);
							}
						});
					}
				}
			});
		});

		$( document ).ajaxStop(function() {
			location.reload();
		});
	});


	$( "#wpso-delete-all-lists" ).on('click', function(e) {
		e.preventDefault();

		var ajaxData = {
			'action': 'delete_handle_list_ajax',
			'ajax_nonce': wpso_ajax_object.ajax_nonce,
			'query_string': 'all'
		};

		var link 	= $(this),
			spinner = $('<i class="fa fa-refresh fa-spin" style="margin-left:4px;"></i>');

		var r = confirm( wpso_ajax_object.delete_all_confirm );

		if ( r == true ) {
			$.ajax({
				type: 'POST',
				url: wpso_ajax_object.ajax_url,
				data: ajaxData,
				beforeSend: function ()	{
					spinner.appendTo(link);
				},
				success: function(data)	{
					location.replace( wpso_ajax_object.screen_url );
				},
				error: function(xhr) {
					alert("An Error is occured: " + xhr.status + " " + xhr.statusText);
				}
			});
		}
	});

});
