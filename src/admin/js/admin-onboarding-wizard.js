window.addEventListener('DOMContentLoaded', function() {

	const onboarding = document.querySelector('#pwp-onboarding-wizard');
	if ( ! onboarding ) return;

	/**
	 * Setup properies
	 */
	const closeOnboarding = onboarding.querySelector('.dialog-close');
	const progressBar = onboarding.querySelector('aside .wizard-progress');
	const progressDots = onboarding.querySelector('footer .wizard-progress');
	const nextButtons = onboarding.querySelectorAll('.next-step button[value=next]');
	const allStepsContent = onboarding.querySelectorAll('section[data-step]');
	let currentStep = 1;

	/**
	 * Setup the various actions across the dialog
	 */
	onboarding.addEventListener('close', function(e) {
		// Dialog is closing, send data, assume ok
		sendAjax( new FormData( closeOnboarding ) );
		e.stopPropagation();
		// Restore non-fixed
		document.querySelector('body').classList.remove('pwp-fixed');
		return;
	});

	if ( progressBar ) {
		// Trigger returning to a previous named step
		progressBar.addEventListener('click', function(e) {
			const but = e.target.closest('button');
			if ( ! but ) return;
			const val = parseInt(but.value, 10);
			if ( 0 < val && val < 10 ) {
				stepChange(val);
			}
			e.stopPropagation();
		});
	}
	if ( 0 !== nextButtons.length ) {
		// Trigger changing to the next step
		nextButtons.forEach( btn => {
			btn.addEventListener('click', function (e) {
				submitStepForm( currentStep );
				stepChange( currentStep + 1 );
				e.stopPropagation();
			})
		});
	}

	/**
	 * Submits any form in a step that is marked as interstitial when changing to the next step.
	 * @param {int} step Step to process
	 */
	function submitStepForm( step ) {
		// Submit possible step form
		const stepContent = getStepContent( step );
		if ( stepContent ) {
			const stepForm = stepContent.querySelector( 'form[data-type=interstitial]' );
			if ( stepForm ) {
				const formData = new FormData( stepForm );
				// Add current step
				formData.set('step', step);
				// Send data, ignore response.
				sendAjax( formData );
			}
		}
	}

	/**
	 * Returns the dom of the current step
	 * @param {int} step Step to retrieve
	 */
	function getStepContent( step ) {
		return onboarding.querySelector( `section[data-step="${step}"]`);
	}

	/**
	 * Trigger a change in the step shown
	 * @param {int} step Step to change to
	 */
	function stepChange(step) {
		// Assign to ensure it's set
		currentStep = step;
		if ( ! allStepsContent || ! progressBar || ! progressDots ) return;

		// Show the current Step content.
		allStepsContent.forEach( stepContent => {
			const thisStep = parseInt(stepContent.getAttribute('data-step'), 10);
			// Update all hidden, only show that which matches the current
			stepContent.hidden = (thisStep !== currentStep);
		});
		// - Advance progress sidebar, adding 'is-complete' to next <li>, make <button> undisabled
		const icons = progressBar.querySelectorAll('[data-step]');
		icons.forEach( icon => {
			const thisStep = parseInt(icon.getAttribute('data-step'), 10);
			const but = icon.querySelector('button');
			if ( thisStep < currentStep ) {
				icon.classList.add('is-complete');
				icon.classList.remove('is-active');
				if ( but ) {
					but.removeAttribute('disabled');
				}
			} else {
				icon.classList.remove('is-complete', 'is-active');
				if ( thisStep === currentStep ) {
					icon.classList.add('is-active');
				}
				if ( but ) {
					but.setAttribute('disabled', '');
				}
			}
		});
		// - Advance progress footer, adding 'is-complete' to next <li>
		const dots = progressDots.querySelectorAll('[data-step]');
		dots.forEach( dot => {
			const thisStep = parseInt(dot.getAttribute('data-step'), 10);
			if ( thisStep < currentStep ) {
				dot.classList.add('is-complete');
				dot.classList.remove('is-active');
			} else {
				dot.classList.remove('is-complete', 'is-active');
				if ( thisStep === currentStep ) {
					dot.classList.add('is-active');
				}
			}
		});
	}

	/**
	 * Customise the YT play button
	 */
	const onboardingVideo = onboarding.querySelector('#onboarding-video');
	if ( onboardingVideo ) {
		const videoButton = onboardingVideo.querySelector('.overlay button');
		const videoIframe = onboardingVideo.querySelector('iframe');
		if (videoButton && videoIframe) {
			videoButton.addEventListener('click', () => {
				onboardingVideo.querySelector('.overlay').hidden = true;
				videoIframe.src += '?autoplay=1';
			}, {once:true});
		}
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

	// Stop overflow on normal page
	document.querySelector('body').classList.add('pwp-fixed');
	// Finally trigger to show.
	onboarding.showModal();
});
