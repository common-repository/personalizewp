import Tabby from 'tabbyjs'; // https://github.com/cferdinandi/tabby

window.addEventListener('DOMContentLoaded', function() {

	if ( window.pwpSettings ) {
		// Mark admin menu item to open in new window
		document.querySelector(`ul#adminmenu a[href=\'${window.pwpSettings.kb}\']`).setAttribute("target", "_blank");
	}

	// Ensure any dialogs have close buttons that close.
	var dialogs = document.querySelectorAll( 'dialog.dialog' );
	if ( 0 !== dialogs.length ) {
		dialogs.forEach( dialog => {
			dialog.addEventListener('click', function(e) {
				// Clicking 'outside' of the dialog causes the target to be the dialog
				if ( e.target.matches( 'dialog' ) ) {
					dialog.close();
					e.stopPropagation()
					return;
				}
				// Track any button specifically marked to close the dialog
				const but = e.target.closest('button');
				if ( but && but.matches( 'button[value=close]' ) ) {
					dialog.close();
					e.stopPropagation()
					return;
				}
			});
		});
	}

	// Ensure any modal links open the corresponding modal.
	var buttonLinks = document.querySelectorAll( '[data-show-modal]' );
	if ( 0 !== buttonLinks.length ) {
		// Show any modal
		buttonLinks.forEach( btnLink => {
			btnLink.addEventListener('click', function (e) {
				e.stopPropagation();
				const target = btnLink.getAttribute( 'data-show-modal' );
				const dialog = document.querySelector( target );

				if ( window.HTMLDialogElement !== undefined && dialog ) {
					// Some modals have a dynamic action (delete etc), so update the forms' action first.
					if ( dialog.hasAttribute( 'data-url-action' ) ) {
						const url = btnLink.getAttribute( 'href' );
						dialog.querySelector( 'form' ).setAttribute( 'action', url );
					}
					// Category Edit modal form has additional inputs to update.
					if ( '#editCategoryModal' === target ) {
						// Update the edit modal with Category specific data
						const url = btnLink.getAttribute( 'href' ),
							catID = btnLink.getAttribute( 'data-id' ),
							catName = btnLink.getAttribute( 'data-name' );
						dialog.querySelector( 'form' ).setAttribute( 'action', url );
						dialog.querySelector( '[name="personalizewp_form[id]"]' ).value = catID;
						dialog.querySelector( '[name="personalizewp_form[name]"]' ).value = catName;
					}
					// Prevent any normal link
					e.preventDefault();
					dialog.showModal();
				}
			});
		});
	}

	/**
	 * Submit formdata via fetch API to ajax URL
	 * Fetch API is modern, but admin-ajax expects old-style url formatted data.
	 * @param {FormData} formdata
	 */
	const sendAjax = async function( formdata ) {

		// Create a new URLSearchParams object
		let params = new URLSearchParams();
		// Add the form data
		for (let [key, val] of formdata) {
			params.append(key, val);
		}

		const response = await fetch(pwpSettings.url, {
			method: "POST",
			headers: {
				'Accept': 'application/json, */*;q=0.1',
				'Content-Type' : 'application/x-www-form-urlencoded; charset=UTF-8',
				'X-Requested-With': 'XMLHttpRequest',
			},
			// Set the FormData instance as the request body
			body: params.toString(),
		});
		const data = await response.json();
		if ( response.ok ) {
			return data;
		}
		return false;
	}

	/**
	 * Processing ajax form submission (within settings and onboarding)
	 */
	const ajaxForms = document.querySelectorAll( '.ajax-form' );
	if ( 0 !== ajaxForms.length ) {
		ajaxForms.forEach( ajaxForm => {
			ajaxForm.addEventListener( 'submit', function(e) {
				e.preventDefault();
				let completed = false; // Used to disable the submitting button
				let isOnboarding = null !== ajaxForm.closest( 'dialog.onboarding' );
				const form = e.target,
					formData = new FormData( form );

				// Remove any existing error notes.
				const inputErrors = form.querySelectorAll('.wp-dxp-input-group__error');
				inputErrors.forEach( input => {
					input.remove();
				});

				sendAjax( formData ).then( function (response) {
					// Used to notify of errors and success
					if ( response.data.message ) {
						let icon;
						const message = ajaxForm.querySelector('.notice-message');
						if ( response.success ) {
							completed = true;
							message.classList.add( 'notice-success' );
							icon = 'yes-alt';
						} else {
							message.classList.add( 'notice-error' );
							icon = 'warning';
						}
						message.innerHTML = `<p><span class="dashicons dashicons-${icon}"></span>${response.data.message}</p>`;
					}
					if ( response.data.errors ) {
						// Display input specific error message
						response.data.errors.forEach( message => {
							if ( '' !== message.input ) {
								let inputGroup = form.querySelector( `input[name="${message.input}"]` ).parentNode;
								inputGroup.classList.add( 'wp-dxp-input-group--error' );
								let error = document.createElement( 'span' );
								error.classList.add( 'wp-dxp-input-group__error' );
								error.textContent = message.message;
								inputGroup.append( error );
							}
							else {
								let error = document.createElement( 'span' );
								error.classList.add( 'wp-dxp-input-group__error' );
								error.textContent = message.message;
								form.append( error );
							}
						} );
					}
					if ( response.success ) {
						completed = true;
						// Toggle all actions visibility, if used
						const buttons = ajaxForm.querySelectorAll('.actions button');
						buttons.forEach(button => {
							button.hidden = ! button.hidden;
						});
						// Check for onboarding button actions
						if ( isOnboarding ) {
							let stepButtons = ajaxForm.closest( 'section.step' ).querySelectorAll('footer.actions button');
							if ( 1 < stepButtons.length ) {
								// Toggle between buttons, if there is more than 1
								stepButtons.forEach( button => {
									button.hidden = ! button.hidden;
								});
							}
						}
						if ( response.data.confirmation ) {
							// Display confirmation message
							const confirmation = ajaxForm.querySelector('.confirmation');
							if (confirmation) {
								confirmation.hidden = false;
							}
						}
					}
					if (completed && e.submitter) {
						// Assuming submitted by button, mark the button as disabled, so no more submissions.
						e.submitter.setAttribute( 'disabled', '' );
					}
				}).catch(function (error) {
					// There was an error
					console.warn(error);
				});
			});
		});
	}

	/**
	 * Dismiss Permanent Notices
	 */
	const notices = document.querySelectorAll( '.notice[data-dismiss-type]' );
	if ( 0 !== notices.length ) {
		notices.forEach( notice => {
			notice.addEventListener( 'click', function(e) {
				if ( ! e.target.matches( 'button' ) ) {
					return;
				}
				e.stopPropagation();
				const type = notice.getAttribute( 'data-dismiss-type' );
				const data = new FormData();
				data.append( 'action', `pwp_dismiss_${type}_message` );
				data.append( '_ajax_nonce', pwpSettings.nonce );
				sendAjax( data );
				// Notice will remove due to WP JS.
			});
		});
	}

	/**
	 * Check and initialise tabs
	 */
	if (document.querySelector('.pwp-page [data-tabs]')) {
		const tabs = new Tabby('.pwp-page [data-tabs]', {
			idPrefix: 'pwp_', // The prefix to add to tab element IDs if they don't already have one
		});
		document.addEventListener('tabby', function (event) {
			event.detail.tab.classList.add('is-active');
			event.detail.previousTab.classList.remove('is-active');
		}, false);
	}

});
