/**
 * WordPress Dependencies
 */
import { __ } from "@wordpress/i18n";
import { dispatch } from '@wordpress/data';
import { addFilter, applyFilters } from "@wordpress/hooks";
import { hasBlockSupport } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import PersonalizeWPInspectorControls from "./inspector-controls/personalizewp";
import { personalizeWPAttributes } from './attributes';
import { legacyPersonalizeWPAttributes } from './legacy-attributes';

/**
 * Add our custom entities for retrieving external data in the Block Editor.
 * @since 2.6.0
 */
dispatch( 'core' ).addEntities( [
	{
		label: __( 'PersonalizeWP Settings', 'personalizewp' ),
		kind: 'personalizewp/v1',
		name: 'settings',
		baseURL: '/personalizewp/v1/settings',
	},
] );

/**
 * Blocks that are not compatible at all with PWP controls.
 * @since 2.6.0
 */
const pwpGloballyRestricted = applyFilters(
	'personalizeWP.globallyRestrictedBlockTypes',
	[ 'core/freeform', 'core/legacy-widget', 'core/widget-area' ]
);

/**
 * Blocks that are not compatible with PWP controls when used as Widgets.
 * @since 2.6.0
 */
const pwpWidgetAreaRestricted = applyFilters(
	'personalizeWP.widgetAreaRestrictedBlockTypes',
	[ 'core/html' ]
);

/**
 * Add the PersonalizeWP setting attribute(s) to selected blocks.
 * Filter all registered block settings, extending attributes with our custom data.
 *
 * @param {Object} settings All original settings associated with a block type.
 * @return {Object} settings The updated array of settings.
 */
function addAttributes( settings ) {

	// Blocks such as the freeform (Classic Editor) are incompatible because
	// it does not support custom attributes.
	if ( pwpGloballyRestricted.includes( settings.name ) ) {
		return settings;
	}

	const pwpAttributes = {
		personalizewp: {
			type: 'object',
			// Filter registered PWP attributes to allow Pro additions
			properties: applyFilters( 'personalizeWP.attributes', personalizeWPAttributes ),
		}
	}

	// We don't want to enable PersonalizeWP for blocks that cannot be added via
	// the inserter or is a child block. Thus this excludes blocks such as reusable
	// blocks, individual column block, etc.
	if (
		hasBlockSupport( settings, 'inserter', true ) &&
			! settings.hasOwnProperty( 'parent' )
	) {
		// Merge existing attributes, with `personalizewp` attribute, and pull in legacy (to allow migration to occur).
		settings.attributes = Object.assign( settings.attributes, pwpAttributes, legacyPersonalizeWPAttributes );
		settings.supports = Object.assign( settings?.supports ?? {}, {
			personalizewp: true,
		} );
	}

	return settings;
}

addFilter(
	'blocks.registerBlockType',
	'personalizewp/add-attributes',
	addAttributes
);

/**
 * Filter the BlockEdit object to add PersonalizeWP controls to selected blocks
 *
 * @param {Object} BlockEdit Original component.
 */
function addInspectorControls( BlockEdit ) {
	return (props) => {

		// Either not selected or not a supported block for PWP.
		if ( ! props.isSelected ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<>
				<BlockEdit { ...props } />
				<PersonalizeWPInspectorControls
					globallyRestricted={pwpGloballyRestricted}
					widgetAreaRestricted={pwpWidgetAreaRestricted}
					{ ...props }
				/>
			</>
		);
	};
}

addFilter(
	'editor.BlockEdit',
	'personalizewp/add-inspector-controls',
	addInspectorControls,
	90 // Appear just above Advanced controls for blocks
);
