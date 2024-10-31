// All supported properties of the PersonalizeWP attribute(s).
export const personalizeWPAttributes = {
	blockID: {
		type: 'string',
	},
	rules: {
		type: 'array',
		items: {
			type: 'integer',
		},
	},
	action: {
		enum: [ 'show', 'hide' ],
		default: 'show',
	},
};
