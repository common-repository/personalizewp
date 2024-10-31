<?php
/**
 * Custom List Table for displaying data in a table format in WordPress plugin.
 *
 * @link       https://personalizewp.com
 * @since      1.2.0
 *
 * @package    PersonalizeWP
 * @subpackage PersonalizeWP/Admin
 */

namespace PersonalizeWP\Admin;

use PersonalizeWP_Rule;
use PersonalizeWP_Category;
use PersonalizeWP_Rule_Types;
use PersonalizeWP_Block;


defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Custom List Table for displaying data in a table format in WordPress plugin.
 */
class ListTableRules extends \WP_List_Table {

	/**
	 * The screen name.
	 *
	 * @var string
	 */
	public $screen_id = 'pwp_rules';

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
				'singular' => 'rule',
				'plural'   => 'rules',
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
			'name'         => __( 'Rule name', 'personalizewp' ),
			'category'     => __( 'Category', 'personalizewp' ),
			'type'         => __( 'Type', 'personalizewp' ),
			'created_by'   => __( 'Created by', 'personalizewp' ),
			'usage_blocks' => __( 'Used by', 'personalizewp' ),
		];
	}

	/**
	 * Get sortable columns
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return [
			'name'       => 'name',
			'category'   => [ 'category_id', true, __( 'Category', 'personalizewp' ), __( 'Table ordered by category.', 'personalizewp' ), 'asc' ],
			'type'       => 'type',
			'created_by' => [ 'created_by', false ],
		];
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @return string Name of the default primary column, in this case, 'name'.
	 */
	protected function get_default_primary_column_name() {
		return 'name';
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

		$actions = array();

		$actions['edit'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_url( PERSONALIZEWP_ADMIN_RULES_EDIT_URL . $item['id'] ),
			/* translators: %s: Item name. */
			esc_attr( sprintf( _x( 'Edit &#8220;%s&#8221;', 'Item name', 'personalizewp' ), $item['name'] ) ),
			esc_html__( 'Edit', 'personalizewp' )
		);

		$actions['duplicate'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			wp_nonce_url( PERSONALIZEWP_ADMIN_RULES_DUPLICATE_URL . $item['id'], 'rule-duplicate-' . $item['id'] ),
			/* translators: %s: Item name. */
			esc_attr( sprintf( _x( 'Duplicate &#8220;%s&#8221;', 'Item name', 'personalizewp' ), $item['name'] ) ),
			esc_html__( 'Duplicate', 'personalizewp' )
		);

		if ( 0 >= PersonalizeWP_Block::getUsagePostsCount( $item['id'] ) ) {
			$actions['delete'] = sprintf(
				'<a href="%s" data-show-modal="#deleteRuleModal" aria-label="%s">%s</a>',
				wp_nonce_url( PERSONALIZEWP_ADMIN_RULES_DELETE_URL . $item['id'], 'rule-delete-' . $item['id'] ),
				/* translators: %s: Item name. */
				esc_attr( sprintf( _x( 'Delete &#8220;%s&#8221;', 'Item name', 'personalizewp' ), $item['name'] ) ),
				esc_html__( 'Delete', 'personalizewp' )
			);
		}

		return $this->row_actions( $actions );
	}

	/**
	 * Default column renderer
	 *
	 * @param array  $item        Rule
	 * @param string $column_name List table column
	 *
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			case 'name':
				return sprintf(
					'<a href="%s" aria-label="%s">%s</a>',
					esc_url( PERSONALIZEWP_ADMIN_RULES_EDIT_URL . $item['id'] ),
					/* translators: %s: Item name. */
					esc_attr( sprintf( _x( 'Edit &#8220;%s&#8221;', 'Item name', 'personalizewp' ), $item['name'] ) ),
					esc_html( $item['name'] )
				);
				break;

			case 'category':
				$category = PersonalizeWP_Category::find( $item['category_id'] );
				return $category ? esc_html( $category->name ) : esc_html_x( 'Unknown', 'Category name', 'personalizewp' );
				break;

			case 'type':
				return esc_html( PersonalizeWP_Rule_Types::getName( $item['type'] ) );
				break;

			case 'created_by':
				if ( ! empty( $item['created_by'] ) ) {
					$user     = get_userdata( $item['created_by'] );
					$username = empty( $user->display_name ) ? _x( 'Unknown', 'Created by user name', 'personalizewp' ) : $user->display_name;
					return sprintf(
						'<a href="%1$s">%2$s</a>',
						admin_url( '/user-edit.php?user_id=' . (int) $item['created_by'] ),
						esc_html( $username )
					);
				} else {
					return esc_html_x( 'Plugin', 'Created by user name', 'personalizewp' );
				}

				break;

			case 'usage_blocks':
				$blocks_count = PersonalizeWP_Block::getUsageBlocksCount( $item['id'] );
				/* translators: 1: %d number of blocks. */
				return esc_html( sprintf( _n( '%d block', '%d blocks', $blocks_count, 'personalizewp' ), $blocks_count ) );
				break;

			default:
				return '';
		}
	}

	/**
	 * Displays a categories drop-down for filtering on the Rules list table.
	 *
	 * @return void
	 */
	protected function categories_dropdown() {

		$category_id = isset( $_REQUEST['id'] ) ? (int) wp_unslash( $_REQUEST['id'] ) : false;
		if ( $category_id ) {
			// Already restricted by Category
			return;
		}
		$category = isset( $_REQUEST['cat'] ) ? (int) wp_unslash( $_REQUEST['cat'] ) : false;

		echo '<label class="screen-reader-text" for="filter-by-category">' . esc_html__( 'Filter by category', 'personalizewp' ) . '</label>';

		$categories = PersonalizeWP_Category::all();
		?>
		<select id="filter-by-category" class="postform category-dropdown" name="cat">
			<option value="0"><?php esc_html_e( 'All categories', 'personalizewp' ); ?></option>
			<?php
			foreach ( $categories as $cat ) :
				?>
				<option <?php selected( $cat->id, $category ); ?> value="<?php echo esc_attr( $cat->id ); ?>"><?php echo esc_attr( $cat->name ); ?></option>
				<?php
			endforeach;
			?>
		</select>
		<?php
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
		$this->extra_tablenav( $which );
		$this->pagination( $which );
		?>

	</div>
		<?php
	}

	/**
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		?>
		<div class="actions">
		<?php
		if ( 'top' === $which ) {
			ob_start();

			$this->categories_dropdown();

			$output = ob_get_clean();

			if ( ! empty( $output ) ) {
				echo $output;
				printf(
					'<input type="submit" name="filter_action" id="rule-query-submit" class="secondary btn" value="%s" />',
					esc_html__( 'Filter rules', 'personalizewp' )
				);
			}
		}
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

		$page        = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : false;
		$action      = isset( $_REQUEST['personalizewp_action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['personalizewp_action'] ) ) : false;
		$search      = isset( $_REQUEST['search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['search'] ) ) : false;
		$cat_name    = 'cat';
		$category_id = isset( $_REQUEST['cat'] ) ? (int) wp_unslash( $_REQUEST['cat'] ) : false;
		if ( ! $category_id ) {
			$cat_name    = 'id';
			$category_id = isset( $_REQUEST['id'] ) ? (int) wp_unslash( $_REQUEST['id'] ) : false;
		}
		?>
		<p class="search-box">
			<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>" />
			<input type="hidden" name="personalizewp_action" value="<?php echo esc_attr( $action ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( $cat_name ); ?>" value="<?php echo esc_attr( $category_id ); ?>" />

			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
			<input type="text" class="search-icon" id="<?php echo esc_attr( $input_id ); ?>" name="search" value="<?php echo esc_attr( $search ); ?>"
				placeholder="<?php esc_html_e( 'Search name', 'personalizewp' ); ?>" />
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

		$where = '';
		// Category filtering, cat on Rules, id on Category screen.
		$category_id = isset( $_REQUEST['cat'] ) ? (int) wp_unslash( $_REQUEST['cat'] ) : false;
		if ( ! $category_id ) {
			$category_id = isset( $_REQUEST['id'] ) ? (int) wp_unslash( $_REQUEST['id'] ) : false;
		}
		if ( ! empty( $category_id ) ) {
			$where .= $wpdb->prepare( ' AND `category_id` = %d ', $category_id );
		}
		// Process Searching
		$search = isset( $_REQUEST['search'] ) ? sanitize_text_field( $_REQUEST['search'] ) : false;
		if ( $search ) {
			$search_cols    = array(
				'name',
				'type',
			);
			$search_clauses = array();

			foreach ( $search_cols as $col ) {
				$search_clauses[] = $wpdb->prepare( '%i LIKE %s', $col, '%' . $wpdb->esc_like( $search ) . '%' );
			}
			$where .= ' AND (' . implode( ' OR ', $search_clauses ) . ')';
		}

		// Process Ordering
		$orderby = 'category_id DESC, name '; // Default when not custom, 2nd order is added below
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
			"SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->prefix}pwp_rules WHERE 1=1 {$where} {$orderby} {$limits} ",
			ARRAY_A
		);

		// Convert plain array into array of Rules to support the backwards compatibility of the model structure.
		// $this->items = array_map( function( $item ) {
		// 	return new PersonalizeWP_Rule( $item );
		// }, $this->items );

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
			return 'ASC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}
}
