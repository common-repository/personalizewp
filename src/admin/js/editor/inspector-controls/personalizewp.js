/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { useEntityRecord } from '@wordpress/core-data';
import { select } from '@wordpress/data';
import { InspectorControls } from "@wordpress/block-editor";
import { withFilters, Spinner, PanelBody, SelectControl } from "@wordpress/components";
import { applyFilters } from "@wordpress/hooks";
import { useEffect } from "@wordpress/element";

/**
 * Internal dependencies
 */
import { migrateDXPAttributes, isEmpty, generateID } from '../utils';
import RulesControls from './rules-controls';
import { legacyPersonalizeWPAttributes } from '../legacy-attributes';

// Provides an entry point to slot in additional settings, before the action. Must be placed
// outside of function to avoid unnecessary rerenders.
const AdditionalControls = withFilters(
	'personalizeWP.addInspectorControls'
)( ( props ) => <></> ); // eslint-disable-line

// Provides an entry point to slot in additional settings, after the action. Must be placed
// outside of function to avoid unnecessary rerenders.
const AdditionalInspectorPanels = withFilters(
	'personalizeWP.addInspectorPanels'
)( ( props ) => <></> ); // eslint-disable-line

/**
 * Add the PersonalizeWP inspector control to each allowed block in the editor
 *
 * @since 2.6.0
 * @param {Object} props All the props passed to this function
 */
export default function PersonalizeWPInspectorControls( props ) {

	const {
		attributes,
		globallyRestricted,
		widgetAreaRestricted,
		name,
		setAttributes,
		isSelected,
		clientId
	} = props;

	if ( ! isSelected ) {
		return null;
	}

	// ******
	// Note: useEffect must exist before any returning of content. https://reactjs.org/link/rules-of-hooks
	// ******

	// Conversion of old DXP attributes to new personalizewp ones (as migration doesn't fire?!?)
	useEffect( () => {
		// Check to see whether the block is eligible to get the migration, i.e. non-empty legacy ID, no new attributes
		if ( attributes && Object.hasOwn( attributes, 'wpDxpId' ) && ! Object.hasOwn( attributes, 'personalizewp' ) ) {
			// Using legacy properties setup removal object.
			const nullifyLegacyAttributes = {};
			for ( const legacyAtt in legacyPersonalizeWPAttributes ) {
				// Ensure WP removes it.
				nullifyLegacyAttributes[legacyAtt] = undefined;
			}
			// Update the block to remove, and set the migrated data.
			const migratedAtts = Object.assign( nullifyLegacyAttributes, { personalizewp: migrateDXPAttributes( attributes ) } );
			setAttributes( migratedAtts );
		}
	}, [attributes] );

	// Setup data for all components
	const settingsData = useEntityRecord( 'personalizewp/v1', 'settings' );

	// Display a default panel with spinner when settings are loading.
	if ( settingsData.isResolving ) {
		return (
			<InspectorControls group="settings">
				<PanelBody title={ __( 'Personalize', 'personalizewp' ) }>
					<div className="pwp-panel rules">
						<Spinner />
					</div>
				</PanelBody>
			</InspectorControls>
		);
	}
	const settings = settingsData.record;

	const { getBlocks } = select( 'core/block-editor' );
	// Determine if we are in the Widget Editor (Not the best but all we got).
	const widgetAreas = getBlocks().filter(
		( block ) => block.name === 'core/widget-area'
	);

	// There are a few core blocks that are not compatible either globally or
	// specifically in the block-based Widget Editor.
	if (
		( widgetAreaRestricted.includes( name ) &&
			widgetAreas.length > 0 ) ||
		globallyRestricted.includes( name )
	) {
		return null;
	}

	let blockAtts = attributes?.personalizewp ?? {};

	/**
	 * Wrapper for setAttributes, validating and sanitising fields before saving.
	 * @param {array} personalizeWPAtts
	 */
	function setPersonalizeWPAtts( personalizeWPAtts ) {

		let updatedAttributes = Object.assign(
			// Use placeholder to both apply an order (blockID first) and set defaults.
			{ blockID: '', action: 'show' },
			// Merge existing attributes.
			{ ...blockAtts },
			// Merge any changes.
			{ ...personalizeWPAtts }
		);

		// Free validates we're using Rules only.
		const usesRules = ( atts ) => {
			let result = Object.hasOwn( atts, 'rules' ) && 0 < atts.rules.filter(Boolean).length;
			return result;
		}
		// Validate for required fields, allow Pro to extend.
		const hasRequired = applyFilters(
			'personalizeWP.hasRequiredFields',
			usesRules( updatedAttributes ),
			updatedAttributes
		);
		if ( hasRequired && '' === updatedAttributes.blockID ) {
			// We have the required attributes, so generate a new ID as not already set.
			updatedAttributes.blockID = generateID();
		} else if ( ! hasRequired ) {
			// We don't have the required attributes, so remove the blockID as not needed. This will suppress any BE/FE processing.
			// delete updatedAttributes.blockID;
			// Or simply cause the removal of personalizewp completely.
			updatedAttributes = undefined;
		}

		// Finally save the attributes
		setAttributes( {
			personalizewp: updatedAttributes
		} );
	}

	return (
		<InspectorControls group="settings">
			<PanelBody title={ __( 'Personalize', 'personalizewp' ) }>

				<RulesControls
					blockAtts={ blockAtts }
					setPersonalizeWPAtts={ setPersonalizeWPAtts }
					settings={ settings }
					{ ...props }
				/>

				<AdditionalControls
					blockAtts={ blockAtts }
					setPersonalizeWPAtts={ setPersonalizeWPAtts }
					settings={ settings }
					{ ...props }
				/>

				<div className="pwp-panel action">
					<SelectControl
						label={ __( 'THEN take the following action:', 'personalizewp' ) }
						labelPosition="top"
						value={ blockAtts?.action ?? 'show' }
						options={ [
							{ value: 'show', label: __( 'Show block', 'personalizewp' ) },
							{ value: 'hide', label: __( 'Hide block', 'personalizewp' ) },
						] }
						onChange={
							(value) => setPersonalizeWPAtts( { action: value } )
						}
						disabled={ isEmpty( blockAtts ) }
						__nextHasNoMarginBottom
					/>
				</div>
			</PanelBody>

			<AdditionalInspectorPanels
				blockAtts={ blockAtts }
				setPersonalizeWPAtts={ setPersonalizeWPAtts }
				settings={ settings }
				{ ...props }
			/>

		</InspectorControls>
	);
}
