/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, Flex, FlexItem, Button, Tooltip, Icon } from "@wordpress/components";

/**
 * Render the Rules inspector control panel.
 *
 * @since 2.6.0
 * @param {Object} props All the props passed to this function
 */
export default function RulesControls( props ) {
	const {
		blockAtts,
		setPersonalizeWPAtts,
		settings
	} = props;

	// Check for rules to use, awaiting loading.
	if ( ! settings?.rules || 0 >= settings.rules.length ) {
		return null;
	}

	// Process the rules into options we can use.
	const ruleOptions = [
			{
				value: 0,
				label: __( '-- Select a rule --', 'personalizewp' ),
			},
		].concat(
			...Array.from(settings.rules).map(
				function(rule) {
					return {
						value: parseInt( rule.id, 10 ),
						label: rule.name,
						disabled: !rule.is_usable,
					}
				} )
		);

	/**
	 * Sanitizes a group of Rules, ensuring only valid, unique options.
	 * @param {Array} rules Array of Rules
	 */
	const sanitizeRules = (rules) => {
		// Internal use of ints
		rules = rules.map( num => parseInt(num, 10) );
		// Remove empty rules
		// rules = rules.filter(Boolean);
		// And remove any duplicate Rules
		return [...new Set(rules)];
	};

	/**
	 * Regenerate the Rules attribute, when a Rule select control changes.
	 * @param {String} index Position of Rule to update
	 * @param {String} rule  Value of RuleID to update
	 */
	const updateBlockRules = (index, rule) => {
		selectedRules[ index ] = rule;
		setPersonalizeWPAtts( {
			rules: sanitizeRules( selectedRules ),
		} );
	};

	/**
	 * Trigger the removal of another Rule select control, and updating the Rule attribute.
	 * @param {String} index Position of Rule to remove
	 */
	const removeBlockRule = (index) => {
		// Remove this rule from the block
		selectedRules.splice( index, 1 );
		setPersonalizeWPAtts( {
			rules: sanitizeRules( selectedRules ),
		} );
	};

	/**
	 * Trigger the addition of another Rule select control, at the end, and updating the Rule attribute.
	 */
	const addBlockRule = () => {
		// Add a rule to the end of the rules
		selectedRules.splice( selectedRules.length, 0, 0 );
		setPersonalizeWPAtts( {
			rules: sanitizeRules( selectedRules ),
		} );
	};

	let selectedRules = blockAtts?.rules ?? [ 0 ];
	// This is required to ensure the first SelectControl appears
	if ( ! selectedRules || 0 === selectedRules.length ) {
		selectedRules = [ 0 ];
	}

	return (
		<div className="pwp-panel rules">
			<Flex direction="column" align="flex-start">
				<FlexItem>
				<p>{ __( 'IF the user meets the following:', 'personalizewp' ) }</p>
				</FlexItem>
			</Flex>
			<Flex direction="row" align="flex-start">
				<FlexItem>
					<h3>{ __( 'Rules', 'personalizewp' ) }</h3>
				</FlexItem>
				<FlexItem>
					<Tooltip
						delay={200}
						text={__("If you add multiple rules these will be calculated using AND and return true if ALL rules are met.", "personalizewp")}
						className="pwp-tooltip"
						placement="bottom-end">
						<div>
							<Icon icon="editor-help" />
						</div>
					</Tooltip>
				</FlexItem>
			</Flex>

			{ selectedRules.map( (ruleID, index ) => {
				return (
					<Flex
						direction="row"
						data-key={"i" + index}
						key={"pwp-rule-" + index}
						className="select-rule"
					>
						<SelectControl
							label={ 0 === index ? __( 'Rules', 'personalizewp' ) : '' }
							hideLabelFromVision={true}
							value={ String(ruleID) }
							options={ ruleOptions }
							onChange={ ( value ) => {
								updateBlockRules( index, value );
							} }
						/>

						{
							// Only show this when there are at least 2 rules
							selectedRules.length > 1 && (
								<Button
									style={{
										padding: "0",
										minWidth: "min-content",
									}}
									icon="remove"
									onClick={ () => {
										removeBlockRule( index );
									} }
								/>
							)
						}

						{
							// Only show the plus if this is the last item, and it's not "no rule"
							0 !== ruleID && index === selectedRules.length - 1 && (
								<Button
									style={{
										padding: "0",
										minWidth: "min-content",
									}}
									icon="insert"
									onClick={ () => {
										addBlockRule();
									} }
								/>
							)
						}
					</Flex>
				);
			} ) }
		</div>
	);
}
