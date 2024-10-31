<?php
/**
 * UTM Campaign type condition Rules
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
 * Checks for the existence of 'utm_campaign' within URL Query string based conditions
 */
class Campaign extends Tag {

	/**
	 * Condition identifier
	 *
	 * @var string
	 */
	public string $identifier = 'utm_campaign';

	/**
	 * UTM Tag to look for
	 *
	 * @var string
	 */
	protected string $utm_tag = 'utm_campaign';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->description = __( 'UTM Campaign', 'personalizewp' );
	}
}
