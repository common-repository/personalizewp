<?php
/**
 * Custom List Table for displaying Rules used across Blocks in a table format.
 *
 * @link       https://personalizewp.com
 * @since      1.2.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin
 */

namespace PersonalizeWP\Admin;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Custom List Table for displaying Rules used across Blocks in a table format.
 */
class ListTableRuleUsage extends \WP_List_Table {

	/**
	 * The screen name.
	 *
	 * @var string
	 */
	public $screen_id = 'pwp_ruleusage';

	/**
	 * Default number of items per page
	 */
	const ITEMS_PER_PAGE = 20;

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'rule_usage',
				'plural'   => 'rule_usage',
				'ajax'     => false,
				'screen'   => $this->screen_id,
			]
		);
	}

	/**
	 * Retrieve the table columns.
	 *
	 * @return array Associative array of table columns with column name as key and column title as value.
	 */
	public function get_columns() {
		return [
			'post_id' => __( 'Page', 'personalizewp' ),
			'block'   => __( 'Block', 'personalizewp' ),
		];
	}

	/**
	 * Get sortable columns
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return [
			'post_id' => [ 'post_id', true, __( 'Page ID', 'personalizewp' ), __( 'Table ordered by Page.', 'personalizewp' ), 'asc' ],
			'block'   => 'name',
		];
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @return string Name of the default primary column, in this case, 'post_id'.
	 */
	protected function get_default_primary_column_name() {
		return 'post_id';
	}

	/**
	 * Generates and displays row action links.
	 *
	 * @param array  $item        Item being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 *
	 * @return string Row actions output for Contact, or an empty string if the current column is not the primary column.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$title = get_the_title( $item['post_id'] );
		if ( empty( $title ) ) {
			$title = __( '- Unknown -', 'personalizewp' );
		}

		$actions = array();

		$actions['edit'] = sprintf(
			'<a href="%1$s" aria-label="%2$s">%3$s</a>',
			esc_url( get_edit_post_link( $item['post_id'] ) ),
			/* translators: %s: Page name. */
			esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'personalizewp' ), $title ) ),
			__( 'Edit', 'personalizewp' )
		);

		return $this->row_actions( $actions );
	}

	/**
	 * Default column renderer
	 *
	 * @param array  $item        Usage Entry
	 * @param string $column_name List table column
	 *
	 * @return mixed
	 */
	protected function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			case 'post_id':
				$title = get_the_title( $item['post_id'] );
				if ( empty( $title ) ) {
					$title = __( '- Unknown -', 'personalizewp' );
				}
				return sprintf(
					'<a href="%1$s" aria-label="%2$s">%3$s</a>',
					esc_url( get_edit_post_link( $item['post_id'] ) ),
					/* translators: %s: Page name. */
					esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'personalizewp' ), $title ) ),
					$title
				);
				break;

			case 'block':
				return esc_html( $item['name'] );
				break;

			default:
				return '';
		}
	}

	/**
	 * Generates the table navigation above or below the table
	 *
	 * Overridden to remove bulk actions and nonces
	 *
	 * @param string $which Top or bottom of table
	 *
	 * @return void
	 */
	protected function display_tablenav( $which ) {
		?>
	<div class="tablenav <?php echo esc_attr( $which ); ?>">

		<?php
		$this->pagination( $which );
		?>

	</div>
		<?php
	}

	/**
	 * Displays the search box.
	 *
	 * @param string $text     The 'submit' button label.
	 * @param string $input_id ID attribute value for the search input field.
	 */
	public function search_box( $text, $input_id ) {

		// Duplicate all the current URL args used to load the page.
		$page    = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : false;
		$action  = isset( $_REQUEST['personalizewp_action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['personalizewp_action'] ) ) : false;
		$rule_id = isset( $_REQUEST['id'] ) ? (int) wp_unslash( $_REQUEST['id'] ) : false;
		$nonce   = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : false;
		$search  = isset( $_REQUEST['search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['search'] ) ) : false;
		?>
		<p class="search-box">
			<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>" />
			<input type="hidden" name="personalizewp_action" value="<?php echo esc_attr( $action ); ?>" />
			<input type="hidden" name="id" value="<?php echo esc_attr( $rule_id ); ?>" />
			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>" />

			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
			<input type="text" class="search-icon" id="<?php echo esc_attr( $input_id ); ?>" name="search" value="<?php echo esc_attr( $search ); ?>"
				placeholder="<?php esc_html_e( 'Search block name', 'personalizewp' ); ?>" />
			<input type="submit" class="secondary btn" value="<?php echo esc_html( $text ); ?>" />
		</p>
		<?php
	}

	/**
	 * Prepare the table items. Retrieves and sets the data for the table.
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		$rule_id = isset( $_REQUEST['id'] ) ? (int) wp_unslash( $_REQUEST['id'] ) : false;

		$where = '';
		// Process Searching
		$search = isset( $_REQUEST['search'] ) ? sanitize_text_field( $_REQUEST['search'] ) : false;
		if ( $search ) {
			$search_cols    = array(
				'name',
			);
			$search_clauses = array();

			foreach ( $search_cols as $col ) {
				$search_clauses[] = $wpdb->prepare( '%i LIKE %s', $col, '%' . $wpdb->esc_like( $search ) . '%' );
			}
			$where .= ' AND (' . implode( ' OR ', $search_clauses ) . ')';
		}

		// Process Ordering
		$orderby = 'post_id DESC, name '; // Default when not custom, 2nd order is added below
		if ( ! empty( $_GET['orderby'] ) ) {
			$_orderby = sanitize_text_field( urldecode( $_GET['orderby'] ) );
			$parsed   = $this->parse_orderby( $_orderby );
			if ( $parsed ) {
				$orderby = $parsed;
			}
		}
		$order   = ! empty( $_GET['order'] ) ? sanitize_text_field( urldecode( $_GET['order'] ) ) : '';
		$order   = $this->parse_order( $order );
		$orderby = 'ORDER BY ' . $orderby . ' ' . $order;

		// Process pagination
		$per_page = $this->get_items_per_page( "edit_{$this->screen->id}_per_page", self::ITEMS_PER_PAGE );
		$page     = $this->get_pagenum();
		$offset   = absint( ( $page - 1 ) * $per_page ) . ', ';
		$limits   = 'LIMIT ' . $offset . $per_page;

		// Get the results from the options above
		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->prefix}pwp_active_blocks WHERE `rule_id` = %s $where $orderby $limits ",
				$rule_id,
			),
			ARRAY_A
		);

		$total_items = (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Validates that the 'orderby' is valid
	 *
	 * @param string $orderby The 'orderby' query variable.
	 * @return string The sanitized 'orderby' query variable.
	 */
	protected function parse_orderby( $orderby ) {

		$orderby = strtolower( $orderby );

		// Used to filter values.
		$allowed_keys = array_keys( $this->get_sortable_columns() );

		if ( ! in_array( $orderby, $allowed_keys, true ) ) {
			return false;
		}

		return $orderby;
	}

	/**
	 * Parse an 'order' query variable and cast it to ASC or DESC as necessary.
	 *
	 * @param string $order The 'order' query variable.
	 * @return string The sanitized 'order' query variable.
	 */
	protected function parse_order( $order ) {
		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}
}