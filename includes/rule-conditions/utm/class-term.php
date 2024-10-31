<?php
/**
 * UTM Term type condition Rules
 *
 * @link       https://personalizewp.com/
 * @since      2.6.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Rule_Conditions/UTM
 */

namespace PersonalizeWP\Rule_Conditions\UTM;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Checks for the existence of 'utm_term' within URL Query string based conditions
 */
class Term extends Tag {

	/**
	 * Condition identifier
	 *
	 * @var string
	 */
	public string $identifier = 'utm_term';

	/**
	 * UTM Tag to look for
	 *
	 * @var string
	 */
	protected string $utm_tag = 'utm_term';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->description = __( 'UTM Term', 'personalizewp' );
	}
}
