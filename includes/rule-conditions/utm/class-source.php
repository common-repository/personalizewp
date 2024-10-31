<?php
/**
 * UTM Source type condition Rules
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
 * Checks for the existence of 'utm_source' within URL Query string based conditions
 */
class Source extends Tag {

	/**
	 * Condition identifier
	 *
	 * @var string
	 */
	public string $identifier = 'utm_source';

	/**
	 * UTM Tag to look for
	 *
	 * @var string
	 */
	protected string $utm_tag = 'utm_source';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->description = __( 'UTM Source', 'personalizewp' );
	}
}
