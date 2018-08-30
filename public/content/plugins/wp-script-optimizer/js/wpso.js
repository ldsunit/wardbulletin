// function currently not in use
var updateQueryStringParam = function (key, value) {
    var baseUrl = [location.protocol, '//', location.host, location.pathname].join(''),
        urlQueryString = document.location.search,
        newParam = key + '=' + value,
        params = '?' + newParam;

    if (urlQueryString) {
        keyRegex = new RegExp('([\?&])' + key + '[^&]*');

        if (urlQueryString.match(keyRegex) !== null) {
            params = urlQueryString.replace(keyRegex, "$1" + newParam);
        } else {
            params = urlQueryString + '&' + newParam;
        }
    }
    window.history.replaceState({}, "", baseUrl + params);
};


function clickTab( tab ) {
	tab.addClass('nav-tab-active');
	tab.siblings().removeClass('nav-tab-active');

	tab.closest('.nav-tab-wrapper').next('.wpso-tabs-wrapper').find('[data-tab="' + tab.attr('data-tab') + '"]').fadeIn();
	tab.closest('.nav-tab-wrapper').next('.wpso-tabs-wrapper').find('[data-tab="' + tab.attr('data-tab') + '"]').siblings().hide();

	set_tab_cookies();
}


function set_tab_cookies() {
	var tab_sc 	 	 = jQuery('#wpso-tabs-scripts > a.nav-tab-active').attr('data-tab'),
		tab_st 	 	 = jQuery('#wpso-tabs-styles > a.nav-tab-active').attr('data-tab'),
		query_string = wpso_ajax_object.query_string;

	document.cookie = 'wpso_tab_sc_' + encodeURIComponent( query_string ) + '=' + tab_sc;
	document.cookie = 'wpso_tab_st_' + encodeURIComponent( query_string ) + '=' + tab_st;
}


jQuery(function($) {
	$('#wpso-tabs-styles > a').on('click', function(e) {
		e.preventDefault();
		clickTab( $(this) );
	});

	$('#wpso-tabs-scripts > a').on('click', function(e) {
		e.preventDefault();
		clickTab( $(this) );
	});
});


jQuery(function($) {
	$('#wpso-search-page-result').on('click', '#wpso-clear-search-page-input', function(e) {
		e.preventDefault();
		$('#wpso-search-page-input').val('').focus();
	});
});


jQuery(function($) {
	$('#wpso-saved-urls-list').on('click', '.wpso-saved-url', function() {
		var queryString = $(this).find('.wpso-saved-url-link').attr('data-querystring');
		location.replace( wpso_ajax_object.screen_url + '&qs=' + btoa(queryString) );
	});
});










jQuery(function($) {

	$('.wpso-item-checkbox-master').on('change', 'input', function(e) {
		$(this).closest('.wpso-tab').find('.wpso-item-checkbox input:not([disabled])').prop('checked', $(this).prop("checked"));
	});


	$('.wpso-item-actions').on('click', '.wpso-item-edit-toggle', function(e) {
		e.preventDefault();
		toggleItemEdit( $(this).closest('.wpso-item-wrapper').find('.hidden') );
	});


	$( ".wpso-conditions" ).on('click', '.add-condition-group', function(e) {
		e.preventDefault();

		var toClonedElement   = $(this).closest('.wpso-conditions').children('.wpso-condition-group').last(),
			oldConditionValue = toClonedElement.find('td.condition select').val(),
			oldOperatorValue  = toClonedElement.find('td.operator select').val(),
			oldValueValue 	  = toClonedElement.find('td.value select').val();

		var	clonedElement 		= toClonedElement.clone(),
			clonedElementEdited = clonedElement.find('.wpso-condition-single').eq(0).siblings().remove().end().end().end(),
			inserted 			= clonedElementEdited.insertBefore( $(this) );

		inserted.find('td.condition select').val( oldConditionValue );
		inserted.find('td.operator select').val( oldOperatorValue );
		inserted.find('td.value select').val( oldValueValue );

		updateConditionIds( $(this) );
	});


	$( ".wpso-conditions" ).on('click', '.add-condition-single', function(e) {
		e.preventDefault();

		var toClonedElement	  = $(this).closest('.wpso-condition-single'),
			oldConditionValue = toClonedElement.find('td.condition select').val(),
			oldOperatorValue  = toClonedElement.find('td.operator select').val(),
			oldValueValue 	  = toClonedElement.find('td.value select').val();

		var clonedElement = toClonedElement.clone().insertAfter( toClonedElement ).effect( 'highlight', '', 800 );

		clonedElement.find('td.condition select').val( oldConditionValue );
		clonedElement.find('td.operator select').val( oldOperatorValue );
		clonedElement.find('td.value select').val( oldValueValue );

		updateConditionIds( $(this) );
	});


	$( ".wpso-conditions" ).on('click', '.remove-condition-single', function(e) {
		e.preventDefault();

		if ( $(this).closest('.wpso-condition-group').find('.wpso-condition-single').length <= 1 ) {
			$(this).closest('.wpso-condition-group').fadeOut(function() {
				updateConditionIds( $(this) );
				$(this).remove();
			});
		} else {
			$(this).closest('.wpso-condition-single').fadeOut(function() {
				updateConditionIds( $(this) );
				$(this).remove();
			});
		}
	});

});


function toggleItemEdit( element ) {
	element.closest('.wpso-item-wrapper').toggleClass('toggle-open');
	element.toggle( "blind", '', 200 );
};


function updateConditionIds( element ) {
	var groups = element.closest('.wpso-item-edit').find('.wpso-condition-group'),
		i = 0;

	groups.each(function() {
		jQuery(this).attr( 'data-condition-group', ++i );

		var singles = jQuery(this).find('.wpso-condition-single'),
			j = 0;

		singles.each(function() {
			jQuery(this).attr( 'data-condition-single', ++j );
		});
	});
};
