( function () {
	'use strict';

	var config = window.CookieSettingYesDataConsent;
	if ( ! config || ! config.endpoint ) {
		return;
	}

	function recordConsent( container, id, token ) {
		if ( container.dataset.csyLogged === 'true' || ! window.fetch ) {
			return;
		}

		window.fetch( config.endpoint, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify( { visitorId: id, consentToken: token } )
		} ).then( function ( response ) {
			if ( response.ok ) {
				container.dataset.csyLogged = 'true';
			}
		} ).catch( function () {} );
	}

	document.querySelectorAll( '[data-csy-data-consent]' ).forEach( function ( container ) {
		var input = container.querySelector( '[data-csy-data-consent-visitor]' );
		var checkbox = container.querySelector( '[data-csy-data-consent-checkbox]' );
		var id = input ? input.value : '';
		var token = container.dataset.csyConsentToken || '';

		if ( checkbox && id && token && container.dataset.csyLogOnChange === 'true' ) {
			checkbox.addEventListener( 'change', function () {
				if ( checkbox.checked ) {
					recordConsent( container, id, token );
				}
			} );
		}
	} );
}() );
