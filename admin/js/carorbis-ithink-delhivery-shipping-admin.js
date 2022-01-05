jQuery( document ).ready( function( $ ) {
	'use strict';

	/**
	 * Request the order manifestation.
	 */
	$( document ).on( 'click', '.cids-order-manifest-actions button', function() {
		var this_button        = $( this );
		var selected_logistics = $( 'input[name="cids-order-logistics-service"]:checked' ).val();

		// Exit, if the logistics is not selected.
		if ( -1 === is_valid_string( selected_logistics ) ) {
			return false;
		}

		// Block the button now.
		// block_element( this_button );

		// Fire the AJAX now.
		$.ajax( {
			dataType: 'JSON',
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'manifest_carorbis_order',
				selected_logistics: selected_logistics,
				order_id: $( '#post_ID' ).val(),
			},
			success: function ( response ) {
				// In case of invalid AJAX call.
				if ( 0 === response ) {
					console.warn( 'easy reservations: invalid AJAX call' );
					return false;
				}

				// If user already exists.
				if ( 'ersrv-user-exists' === response.data.code ) {
					// Unblock the button.
					unblock_element( this_button );

					// Activate loader.
					this_button.html( this_button_text );

					// Paste the error message.
					$( '.ersrv-form-error' ).text( response.data.error_message );

					return false;
				}
			}
		} );
	} );

	/**
	 * Check if a string is valid.
	 *
	 * @param {string} $data
	 */
	function is_valid_string( data ) {
		if ( '' === data || undefined === data || ! isNaN( data ) || 0 === data ) {
			return -1;
		} else {
			return 1;
		}
	}

	/**
	 * Check if a number is valid.
	 *
	 * @param {number} $data
	 */
	function is_valid_number( data ) {
		if ( '' === data || undefined === data || isNaN( data ) || 0 === data ) {
			return -1;
		} else {
			return 1;
		}
	}

	/**
	 * Block element.
	 *
	 * @param {string} element
	 */
	function block_element( element ) {
		element.addClass( 'non-clickable' );
	}

	/**
	 * Unblock element.
	 *
	 * @param {string} element
	 */
	function unblock_element( element ) {
		element.removeClass( 'non-clickable' );
	}
} );
