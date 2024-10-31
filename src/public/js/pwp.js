(() => {
	"use strict";

	class PWP {

		userData = {}

		/**
		 * PWP utility object
		 */
		utils = {
			/**
			 * Get a persistent value
			 * @param {String} k The key of storage
			 * @param {boolean} toJson To parse value to json ,default false
			 * @param {String} p The prefix of the store
			 * @return {string | json}
			 */
			pwpGet: (k, toJson = false, p = "personalizewp") => {
				const i = localStorage.getItem(`${p}_${k}`);
				if (i) {
					return toJson ? JSON.parse(i) : i;
				}
				return null;
			},

			/**
			 * Set a persistent value
			 * @param {String} k The key of storage
			 * @param {String} v The value to be save
			 * @param {String} p The prefix of the store
			 */
			pwpStore: (k, v, p = "personalizewp") => {
				localStorage.setItem(`${p}_${k}`, JSON.stringify(v));
			},

			/**
			 * Clear a persistent value
			 * @param {String} k The key of storage
			 * @param {String} p The prefix of the store
			 */
			pwpClear: (k, p = "personalizewp") => {
				localStorage.removeItem(`${p}_${k}`);
			},

			/**
			 * Call a PWP request api
			 * @param {String} u The url of the endpoint
			 * @param {String} b The parameter body
			 * @return {Promise<any | null>} The response body of request
			 */
			callApi: async (u, b) => {
				try {
					const loc = document.location;
					const response = await fetch(`${this.baseUrl}${u}`, {
						method: "POST", // Generally not 'creating' content, but POST allows a larger quantity of params via the body
						cache: "no-cache",
						headers: {
							"Accept": "application/json, */*;q=0.1",
							"Cache-Control": "no-cache, private",
							"Content-type": "application/json",
							"X-Requested-With": "XMLHttpRequest",
							...(window.pwpSettings &&
								// Safely check for nonce
								window.pwpSettings.nonce && {
									"X-WP-Nonce": window.pwpSettings.nonce,
								}),
						},
						body: JSON.stringify(b),
					});
					if (!response.ok) {
						throw new Error(`Response status: ${response.status}`);
					}
					return await response.json();
				} catch (error) {
					console.error("API Error:", error);
				}
				return null;
			},

			/**
			 * Workaround for setting innerHTML not processing encased <script>s
			 */
			nodeScriptReplace: (el) => {
				const nodeScriptIs = el.tagName === "SCRIPT";
				if (nodeScriptIs) {
					el.parentNode.replaceChild(this.utils.nodeScriptClone(el), el);
				} else {
					var i = -1,
						children = el.childNodes;
					while (++i < children.length) {
						this.utils.nodeScriptReplace(children[i]);
					}
				}
				return el;
			},
			nodeScriptClone: (el) => {
				var script = document.createElement("script");
				script.text = el.innerHTML;

				var i = -1,
					attrs = el.attributes,
					attr;
				while (++i < attrs.length) {
					script.setAttribute((attr = attrs[i]).name, attr.value);
				}
				return script;
			},
		}

		/**
		 * Determines what time of the day a user is visiting the site - core_users_visiting_time
		 * @return {String}
		 */
		#getTimeOfDay() {
			const currentHour = new Date().getHours();
			if (currentHour >= 0 && currentHour < 6) {
				return "nighttime";
			}
			if (currentHour >= 6 && currentHour < 12) {
				return "morning";
			}
			if (currentHour >= 12 && currentHour < 18) {
				return "afternoon";
			}
			if (currentHour >= 18 && currentHour <= 23) {
				return "evening";
			}
			return "";
		}

		/**
		 * Works out the difference between two timestamps in days
		 * @param {Int} timestamp1 The current/future timestamp, in seconds
		 * @param {Int} timestamp2 The past timestamp, in seconds
		 * @return {Int}
		 */
		#dateDayDiff(timestamp1, timestamp2) {
			const secsInDay = 60 * 60 * 24;
			// Validate timestamps
			timestamp1 = parseInt(timestamp1);
			timestamp2 = parseInt(timestamp2);
			if (isNaN(timestamp1) || isNaN(timestamp2)) {
				return 0;
			}
			// Check for past timestamp being greater than current, possible it's in milliseconds
			if (timestamp2 > timestamp1) {
				// Convert to nearest seconds
				timestamp2 = Math.round(timestamp2 / 1000);
			}
			return Math.round((timestamp1 - timestamp2 ) / secsInDay);
		}

		/**
		 * Estimates if mobile based on user agent string
		 * @returns {boolean}
		 */
		#isMobile() {
			let check = false;
			(function (a) {
				if (
					/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(
						a,
					) ||
					/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(
						a.substr(0, 4),
					)
				)
					check = true;
			})(navigator.userAgent || navigator.vendor || window.opera);
			return check;
		}

		/**
		 * Estimates if tablet based on user agent string
		 * @returns {boolean}
		 */
		#isTablet() {
			const userAgent = navigator.userAgent.toLowerCase();
			const isTablet =
				/(ipad|tablet|(android(?!.*mobile))|(windows(?!.*phone)(.*touch))|kindle|playbook|silk|(puffin(?!.*(IP|AP|WP))))/.test(
					userAgent,
				);

			return isTablet;
		}

		/**
		 * Estimates possible mobile/tablet operating system.
		 * This function returns one of 'iOS', 'Android', 'Windows Phone', or 'unknown'.
		 * @returns {String}
		 */
		#getMobileOperatingSystem() {
			const userAgent = navigator.userAgent || navigator.vendor || window.opera;

			// Windows Phone must come first because its UA also contains "Android"
			if (/windows phone/i.test(userAgent)) {
				return "windows";
			}

			if (/android/i.test(userAgent)) {
				return "android";
			}

			// iOS detection from: http://stackoverflow.com/a/9039885/177710
			if (/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream) {
				return "ios";
			}

			return "";
		}

		/**
		 * Determines what device the user is using
		 * @return {array} Array of device type, and if tablet/mobile the OS (windows/android/ios)
		 */
		#getDeviceType() {
			const os = this.#getMobileOperatingSystem();
			if (this.#isMobile()) {
				return ["mobile", os];
			}
			if (this.#isTablet()) {
				return ["tablet", os];
			}
			return ["desktop"];
		}

		/**
		 * Initialise PWP
		 */
		constructor() {
			// Define properties
			this.pwpLoaded = [];
			this.baseUrl =
				(window.pwpSettings ? window.pwpSettings.root : "/wp-json/") +
				"personalizewp/";
			this.readyQueue = [];
			this.readyExecuted = false;

			const currentTimestamp = Math.round(Date.now() / 1000); // Date.now() returns milliseconds, use nearest second
			const currentTimestring = new Date().toTimeString();

			// User data is sent with every blocks request
			this.userData = {
				timeOfDay: this.#getTimeOfDay(),
				currentTime: currentTimestring.substring(0, currentTimestring.indexOf(" ")), // Return just the local time, HH:mm:ss
				currentTimestamp: new Date().toISOString(), // Date/time, timezone always UTC
				isReturningVisitor: true, // assumed for now
				daysSinceLastVisit: 0,
				deviceType: this.#getDeviceType(),
				uid: this.utils.pwpGet("tracked_user", true, "pwp")?.id ?? '',
			}

			// SECTION DETERMINES IF THE USER IS A NEW USER OR AN EXISTING USER - 'core_new_visitor'
			const firstVisit = this.utils.pwpGet("first_visit");
			if ( null === firstVisit ) {
				// set local storage variable for when user first visited the site
				this.utils.pwpStore("first_visit", currentTimestamp);
				this.userData.isReturningVisitor = false;
			} else if ( firstVisit > currentTimestamp ) {
				// Patch for versions with firstVisit stored as milliseconds
				this.utils.pwpStore("first_visit", Math.round(firstVisit / 1000));
			} else if ( 1 > this.#dateDayDiff( currentTimestamp, parseInt( firstVisit ) ) ) {
				// calculate the number of days between current date and first visit
				// if user visited less than a day ago then they are still a new visitor
				this.userData.isReturningVisitor = false;
			}

			// SECTION DETERMINES WHEN THE USER LAST VISITED THE SITE - 'core_users_last_visit'
			// set session variable to hold when the user last visited the site - THIS SESSION VARIABLE WILL BE USED TO CALCULATE THE LAST VISIT
			var lastSession = sessionStorage.getItem("personalizewp_last_session");
			if ( null === lastSession || isNaN(parseInt(lastSession)) ) {
				// Store session from locals' last visit, if available, or use current time
				lastSession = parseInt(this.utils.pwpGet("last_visit") ?? currentTimestamp);
				sessionStorage.setItem("personalizewp_last_session", lastSession);
			} else if ( parseInt( lastSession ) > currentTimestamp ) {
				// Patch for versions with lastSession stored as milliseconds
				lastSession = Math.round(parseInt( lastSession ) / 1000);
				sessionStorage.setItem("personalizewp_last_session", lastSession);
			}

			// calculate the number of days between current date and the users last session, might be zero i.e. today
			this.userData.daysSinceLastVisit = this.#dateDayDiff(currentTimestamp, lastSession);

			// Update local storage variable everytime the user loads a page
			this.utils.pwpStore("last_visit", currentTimestamp);
		}

		/**
		 * Get blocks for base placeholder IDs
		 * @param {array} blocksData The array of blocks to check for
		 * @param {String} apiURL The API URL to call, optional
		 * @return {Promise<any | null>}
		 */
		async getBlocks(blocksData, apiURL = 'v2/blocks' ) {
			// Double check
			if (0 === blocksData.length) {
				return null;
			}

			// Merge the base userData with the current page data
			const body = { ...this.userData, ...{
				// Required args
				location: location.pathname, // Referrer may not contain the full path
				blocks: blocksData,
				// Rule Condition args in addition to userData
				urlQueryString: window.location.search + window.location.hash, // Normal fetch referrer doesn't include all this, separate to location for ease
				referrerURL: document.referrer,
			} };

			const blocks = await this.utils.callApi(apiURL, body);

			if (blocks && blocks.length) {
				return blocks;
			}

			return null;
		}

		/**
		 * Register a function to trigger after any user interaction
		 * @param {function} fn The function to trigger when there is an interaction
		 * @param {String} m The module name
		 */
		#registerOnInteraction(fn, m) {
			const interaction = () => {
				this.#triggerFunction(fn, m);
			};
			// Once run, remove for performance
			const runOnce = { once: true };

			document.body.addEventListener('mousemove', interaction, runOnce);
			document.body.addEventListener('scroll', interaction, runOnce);
			document.body.addEventListener('keydown', interaction, runOnce);
			document.body.addEventListener('click', interaction, runOnce);
			document.body.addEventListener('touchstart', interaction, runOnce);
		}

		/**
		 * Trigger a module function if it's not loaded yet. Otherwise don't do anything
		 * @param {function} fn The function to trigger
		 * @param {String} m The module name
		 */
		#triggerFunction(fn, m) {
			const loaded = this.pwpLoaded.indexOf(m) >= 0;
			if (!loaded) {
				fn();
				this.pwpLoaded.push(m);
			}
		}

		/**
		 * Trigger initialisation of a function and checks delayInit setting
		 * If delayInit is true, function will trigger after an interaction
		 * Else will trigger the function immediately
		 * @param {function} fn The function to trigger
		 * @param {String} m The module name
		 */
		initialiseOnLoad(fn, m) {
			if (window.pwpSettings.delayInit) {
				this.#registerOnInteraction(fn, m);
			} else {
				// Immediately run as DOM available
				this.#triggerFunction(fn, m);
			}
		}

		/**
		 * Set main object readyExecuted and execute module ready
		 */
		ready() {
			this.readyExecuted = true;
			for (let module of this.readyQueue) {
				module.ready(this);
			}
		}

		/**
		 * Execute a PWP module
		 * @param {String} name The name of the module
		 * @param {Object} module The module object
		 * @param {boolean} forceLoad Set this to true if you want to immediately trigger the event without checking the value of pwpSetting.delayInit
		 */
		module (name, module, forceLoad = false) {
			const runModule = () => {
				// run init method if available
				if (module.init) {
					module.init();
				}

				// run/queue ready method if available
				if (module.ready) {
					// ready already executed?
					// run immediately
					if (this.readyExecuted) module.ready();
					// otherwise add to queue
					else this.readyQueue.push(module);
				}
			};

			if (forceLoad) {
				runModule();
			} else {
				this.initialiseOnLoad(runModule, name);
			}
		}
	}

	// ------- VARIABLE DECLARATIONS
	// Initialise and register PersonalizeWP for add on module use.
	window.PersonalizeWP = new PWP();
	const placeholders = []; // Track all placeholders indexed by their delay in showing

	class PWPBlocks extends HTMLElement {
		// Instantiate our Web Component
		constructor() {
			// Inherit the parent class properties
			super();
		}

		connectedCallback() {
			if (this.blockId) {
				if (undefined === placeholders[this.delayed]) {
					placeholders[this.delayed] = {
						parsing: false,
						elements: [],
					};
				}
				// Placeholders are grouped by their delay before showing, defaulting to 0 i.e. immediate display
				placeholders[this.delayed].elements.push(this);
			}
		}
		get blockId() {
			return this.getAttribute("block-id");
		}

		get delayed() {
			let d = this.getAttribute("delayed");
			return d ? Number(d) : 0;
		}

		get lifetime() {
			let l = this.getAttribute("lifetime");
			return l ? Number(l) : 0;
		}
	}
	window.customElements.define("pwp-block", PWPBlocks);

	/**
	 * Parse DOM placeholders for block content
	 */
	const parsePlaceholders = () => {
		Object.keys(placeholders).forEach(function (delay) {
			// a parse is already underway for this delay
			if (placeholders[delay].parsing) {
				return;
			}
			placeholders[delay].parsing = true;

			const elements = placeholders[delay].elements;

			// Pre-process the placeholders data to simplify to just IDs before sending to the API
			let blockIDs = [];
			for (let el of elements) {
				blockIDs.push(el.blockId);
			}

			if (!blockIDs.length) {
				placeholders[delay].parsing = false;
				return;
			}

			// This'll be a single REST call per delay set of blocks.
			PersonalizeWP.getBlocks(blockIDs)
				.then((blocks) => {
					if (!Array.isArray(blocks)) {
						throw "No blocks to parse";
					}
					// Post-process the data to group the original element with the content to show
					const batch = [];
					blocks.forEach(function (content) {
						// process elements in order
						const el = elements.shift();
						// do we have respective element content? Assume 1 to 1 relationship
						if (content) {
							batch.push([el, content]);
						}
						// no content, remove the placeholder
						else {
							el.remove();
						}
					});

					if (batch.length) {
						setTimeout(function () {
							// replace web component placeholder element with real content
							batch.forEach(function (b) {
								const placeholderContainer = b[0].parentNode;
								// We don't know what the actual HTML is, so use a generic one to place everything within for now. This'll be removed later.
								let el = document.createElement('div');
								// Set the inner content of the div. This sets the API response as a string and is parsed as DOM elements, but doesn't process scripts.
								el.innerHTML = b[1];
								// Re-Process the element to restore any <script>s
								el = PersonalizeWP.utils.nodeScriptReplace(el);

								// If the block has a non-zero lifespan, then queue it up to be removed.
								if (b[0].lifetime) {
									// As there might be multiple nodes in the response, add a remove timeout to each individual one.
									const timeout = b[0].lifetime * 1000;
									const kids = el.childNodes;
									for (const kid of kids) {
										setTimeout(function () {
											kid.remove();
										}, timeout);
									}
								}

								// Finally replace the web component placeholder in the DOM with the new elements' children, as there could be many elements.
								b[0].replaceWith(...el.childNodes);

								// Allow triggering functionality when individual blocks are inserted.
								const blockEvent = new CustomEvent("PWP:parsePlaceholder", {
									bubbles: true,
									cancelable: true,
									detail: {
										container: placeholderContainer,
									},
								});
								placeholderContainer.dispatchEvent(blockEvent);
							});

							// Re-trigger standard browser event for any setup that plugins might be doing.
							document.dispatchEvent(new CustomEvent("DOMContentLoaded"));

							// Allow triggering functionality when batches of blocks are inserted.
							const batchEvent = new CustomEvent("PWP:parsedPlaceholders", {
								bubbles: true,
								cancelable: true,
							});
							document.dispatchEvent(batchEvent);

							// reparse after adding elements in case of further placeholders
							parsePlaceholders();
						}, delay * 1000);
					}

					// No longer parsing this set of placeholders
					placeholders[delay].parsing = false;
				})
				.catch((err) => {
					console.log(err);
				});
		});
	};

	// Initialise ourselves and setup our built in blocks
	PersonalizeWP.initialiseOnLoad(parsePlaceholders, "pwp");

	// Initialise any add on modules
	PersonalizeWP.ready();
})();
