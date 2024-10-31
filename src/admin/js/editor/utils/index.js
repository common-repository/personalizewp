/**
 * Native friendly way of checking for empty.
 *
 * @param {mixed} obj
 * @returns
 */
export const isEmpty = ( obj ) => {
	return [Object, Array].includes((obj || {}).constructor) && !Object.entries((obj || {})).length;
}

/**
 * Converts a comma separated string of IDs into an array of Int IDs
 * @param {string} idString
 * @returns {array}
 */
export const convertStringToIntArray = ( idString ) => {
	// Sanitize the string, removing excess commas, whitespace etc
	idString = idString.replace(/^,+|,+(?=,)/g, "");
	// Convert to array
	idString = idString.split(",");
	// Remove empty, null, 0 values
	idString = idString.filter(Boolean);
	// Conversion to actual Ints and remove any duplicate IDs
	return [...new Set( idString.map( num => parseInt(num, 10) ) )];
}

/**
 * Migrates incoming DXP attributes, returning a valid `personalizewp` attribute object
 * @param {object} attributes
 * @returns {object}
 */
export const migrateDXPAttributes = ( attributes ) => {
	// get the old attribute values from the existing attributes
	const { wpDxpId, wpDxpRule, wpDxpAction, wpDxpSegment, pwpMinScore, pwpMaxScore, pwpPassword, pwpUserRoles, pwpUsers } = attributes;

	// clone the attributes object
	const newAttributes = { ...attributes };

	// remove the old attributes from the new object
	delete newAttributes.wpDxpId;
	delete newAttributes.wpDxpRule;
	delete newAttributes.wpDxpAction;
	delete newAttributes.wpDxpSegment;
	delete newAttributes.pwpMinScore;
	delete newAttributes.pwpMaxScore;
	delete newAttributes.pwpPassword;
	delete newAttributes.pwpUserRoles;
	delete newAttributes.pwpUsers;

	let hasRules = false;
	const baseAttributes = {
		blockID: generateID(), // Use new format UUID
		action: ( wpDxpAction && '' !== wpDxpAction ) ? wpDxpAction : 'show',
	}
	if ( wpDxpRule && '' !== wpDxpRule ) {
		hasRules = true;
		baseAttributes.rules = convertStringToIntArray( wpDxpRule ) // Convert to array
	}

	// Process the Pro attrs, reformating and deleting as we go
	const proAttributes = {};
	if ( wpDxpSegment && '' !== wpDxpSegment ) {
		proAttributes.segments = convertStringToIntArray( wpDxpSegment ) // Convert to array
	}
	if ( pwpMinScore && '' !== pwpMinScore ) {
		proAttributes.minScore = pwpMinScore
	}
	if ( pwpMaxScore && '' !== pwpMaxScore  ) {
		proAttributes.maxScore = pwpMaxScore
	}
	if ( pwpPassword && '' !== pwpPassword ) {
		proAttributes.password = pwpPassword
	}
	if ( pwpUserRoles && ! isEmpty( pwpUserRoles ) ) {
		proAttributes.userRoles = pwpUserRoles
	}
	if ( pwpUsers && ! isEmpty( pwpUsers ) ) {
		proAttributes.users = pwpUsers
	}
	if ( ! hasRules && isEmpty(proAttributes) ) {
		// Not passed required fields, simply return.
		return undefined;
	}
	return Object.assign( baseAttributes, proAttributes );
}

/**
 * Generates a new UUID. Wrapper for crypto with fallback if not available.
 * @link https://developer.mozilla.org/en-US/docs/Web/API/Crypto/randomUUID
 * @returns {string}
 */
export const generateID = () => {
	return crypto.randomUUID ? crypto.randomUUID() : broofa_uuid();
}
/**
 * A compact, though not performant, RFC4122v4 solution to UUID.
 * @returns {string}
 */
const broofa_uuid = () => {
	return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
		var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
		return v.toString(16);
	});
}
