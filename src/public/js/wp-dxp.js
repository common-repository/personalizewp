(() => {
	"use strict";

	const legacyPlaceholders = []; // Track all legacy placeholders indexed by their delay in showing

	class legacyWPDxpBlocks extends HTMLElement {
		// Instantiate our Web Component
		constructor() {
			// Inherit the parent class properties
			super();
		}

		connectedCallback() {
			if (this.post_id && this.block_id) {
				if (undefined === legacyPlaceholders[this.delayed]) {
					legacyPlaceholders[this.delayed] = {
						parsing: false,
						elements: [],
					};
				}
				// Placeholders are grouped by their delay before showing, defaulting to 0 i.e. immediate display
				legacyPlaceholders[this.delayed].elements.push(this);
			}
		}

		get post_id() {
			return this.getAttribute("post-id");
		}

		get block_id() {
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
	window.customElements.define("wp-dxp", legacyWPDxpBlocks);

	/**
	 * Parse Legacy DOM placeholders for block content
	 * Largely the same as for pwp-blocks
	 */
	const populateLegacyPlaceholders = async function () {
		const PersonalizeWP = window.PersonalizeWP;

		Object.keys(legacyPlaceholders).forEach(function (delay) {
			// a parse is already underway for this delay
			if (legacyPlaceholders[delay].parsing) {
				return;
			}
			legacyPlaceholders[delay].parsing = true;

			const elements = legacyPlaceholders[delay].elements;

			// Pre-process the placeholders data to simplify before send to the API
			let placeholdersData = [];
			for (let el of elements) {
				const { post_id, block_id } = el;
				placeholdersData.push({
					...(post_id && { post_id }),
					...(block_id && { block_id }),
				});
			}

			if (!placeholdersData.length) {
				legacyPlaceholders[delay].parsing = false;
				return;
			}

			// This'll be a single REST call per delay set of blocks, using the legacy URL.
			PersonalizeWP.getBlocks(placeholdersData, 'v1/blocks')
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
								let el = document.createElement("div");
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
								const blockEvent = new CustomEvent('PWP:parsePlaceholder', {
									bubbles: true,
									cancelable: true,
									detail: {
										container: placeholderContainer,
									},
								});
								placeholderContainer.dispatchEvent(blockEvent);
							});

							// Re-trigger standard browser event for any setup that plugins might be doing.
							document.dispatchEvent(new CustomEvent('DOMContentLoaded'));

							// Allow triggering functionality when batches of blocks are inserted.
							const batchEvent = new CustomEvent('PWP:parsedPlaceholders', {
								bubbles: true,
								cancelable: true,
							});
							document.dispatchEvent(batchEvent);

							// reparse after adding elements in case of further placeholders
							populateLegacyPlaceholders();
						}, delay * 1000);
					}

					// No longer parsing this set of placeholders
					legacyPlaceholders[delay].parsing = false;
				})
				.catch((err) => {
					console.log(err);
				});
		});
	};

	// Wrap up legacy placeholders as module of main PersonalizeWP script.
	PersonalizeWP.module("wp-dxp", {
		populateLegacyPlaceholders: populateLegacyPlaceholders,
		ready: function () {
			this.populateLegacyPlaceholders();
		},
	});
})();
