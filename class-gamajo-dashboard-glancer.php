<?php
/**
 * Gamajo Dashboard Glancer
 *
 * @package   Gamajo\DashboardGlancer
 * @author    Gary Jones
 * @link      https://github.com/gamajo/dashboard-glancer
 * @copyright 2013 Gary Jones, Gamajo
 * @license   GPL-2.0-or-later
 * @version   1.0.5
 */

/**
 * Easily add items to the At a Glance Dashboard widget in WordPress 3.8+.
 *
 * @package Gamajo\DashboardGlancer
 * @author  Gary Jones
 */
class Gamajo_Dashboard_Glancer {
	/**
	 * Hold all of the items to show.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * Automatically show any registered items.
	 *
	 * With this, there's no need to explicitly call show() during the
	 * `dashboard_glance_items` hook, and items can be registered at any time
	 * before `dashboard_glance_items` priority 20 (including on earlier hooks).
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'dashboard_glance_items', array( $this, 'show' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'dashboard_css' ) );
	}

	/**
	 * Register one or more post type items to be shown on the dashboard widget.
	 *
	 * @since 1.0.0
	 *
	 * @param array|string $post_types Post type name, or array of post type names.
	 * @param array|string $statuses   Optional. Post status or array of
	 *                                 different post type statuses. Default is
	 *                                 `publish`.
	 * @return null Return early if action hook has already passed, or no valid
	 *              post types were given.
	 */
	public function add( $post_types, $statuses = null ) {
		if ( null === $statuses ) {
			$statuses = 'publish';
		}

		// If relevant output action hook has already passed, then no point in proceeding.
		if ( did_action( 'dashboard_glance_items' ) ) {
			_doing_it_wrong( __CLASS__, 'Trying to add At a Glance items to dashboard widget afterhook already fired', '1.0.0' );

			return;
		}

		$post_types = $this->unset_invalid_post_types( (array) $post_types );

		// If all given post types were invalid, bail now.
		if ( ! $post_types ) {
			return;
		}

		// Register each combination of given post type and status.
		foreach ( $post_types as $post_type ) {
			foreach ( (array) $statuses as $status ) {
				$this->items[] = array(
					'type'   => $post_type,
					'status' => $status, // No checks yet to see if status is valid.
				);
			}
		}
	}

	/**
	 * Show the items on the dashboard widget.
	 *
	 * @since 1.0.0
	 */
	public function show() {
		foreach ( $this->items as $item ) {
			echo wp_kses_post( $this->get_single_item( $item ) );
		}
		// Reset items, so items aren't shown again if show() is re-called.
		unset( $this->items );
	}

	/**
	 * Check one or more post types to see if they are valid.
	 *
	 * @since 1.0.0
	 *
	 * @param array $post_types Each of the post types to check.
	 * @return array List of the given post types that are valid.
	 */
	protected function unset_invalid_post_types( array $post_types ) {
		foreach ( $post_types as $index => $post_type ) {
			$post_type_object = get_post_type_object( $post_type );
			if ( null === $post_type_object ) {
				unset( $post_types[ $index ] );
			}
		}

		return $post_types;
	}

	/**
	 * Build and return the data and markup for a single item.
	 *
	 * If the item count is zero, return an empty string, to avoid visual clutter.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Registered item.
	 * @return string Markup, or empty string if item count is zero.
	 */
	protected function get_single_item( array $item ) {
		$num_posts = wp_count_posts( $item['type'] );
		$count     = (int) $num_posts->{$item['status']};

		if ( ! $count ) {
			return '';
		}

		$href = $this->get_link_url( $item );
		$text = number_format_i18n( $count ) . ' ' . $this->get_label( $item, $count );
		$text = $this->maybe_link( $text, $href );

		return $this->get_markup( $text, $item['type'] );
	}

	/**
	 * Get the singular or plural label for an item.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item  Registered item.
	 * @param int   $count Number of items present in WP.
	 * @return string
	 */
	protected function get_label( array $item, $count ) {
		$post_type_object = get_post_type_object( $item['type'] );
		$labels           = $post_type_object->labels;
		$label            = 1 === $count ? $labels->singular_name : $labels->name;

		// Append status for non-publish statuses for disambiguation.
		if ( 'publish' !== $item['status'] ) {
			$label .= ' (' . $item['status'] . ')';
		}

		return $label;
	}

	/**
	 * Build the URL that linked items use.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Registered item.
	 * @return string Admin URL to view the entries of the given post type with the given status
	 */
	public function get_link_url( array $item ) {
		return 'edit.php?post_status=' . $item['status'] . '&amp;post_type=' . $item['type'];
	}

	/**
	 * Wrap a glance item in a link, if the current user can edit posts.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Text to potentially wrap in a link.
	 * @param string $href Link target.
	 * @return string Text wrapped in a link if current user can edit posts, or original text otherwise.
	 */
	protected function maybe_link( $text, $href ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return '<a href="' . esc_url( $href ) . '">' . esc_html( $text ) . '</a>';
		}

		return '<span>' . esc_html( $text ) . '</span>';
	}

	/**
	 * Wrap number and text within list item markup.
	 *
	 * The extra work for populating classes is to provide dashicons support.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text      Text to display. May be wrapped in a link.
	 * @param string $post_type Post type.
	 * @return string Markup for list item.
	 */
	protected function get_markup( $text, $post_type ) {
		$classes[]        = $post_type . '-count';
		$post_type_object = get_post_type_object( $post_type );
		$menu_icon        = isset( $post_type_object->menu_icon ) ? $post_type_object->menu_icon : null;

		if ( 0 === strpos( $menu_icon, 'dashicons-' ) ) {
			$classes[] = 'dashicons-before';
			$classes[] = $menu_icon;
		}

		$class = implode( ' ', sanitize_html_class( $classes ) );

		return '<li class="' . esc_attr( $class ) . '">' . wp_kses_post( $text ) . '</li>' . "\n";
	}

	/**
	 * Add post types icon styling to Dashboard page.
	 *
	 * To override the overly-specific core styles, we apply styles to ignore
	 * the default circle, and with the classes added to the list item (not
	 * anchor), get dashicons showing there instead.
	 *
	 * @since 1.1.0
	 *
	 * @return null Return early if style already added, or the Dashboard page.
	 */
	public function dashboard_css() {
		static $added_style;

		if ( $added_style ) {
			return;
		}

		$screen = get_current_screen();

		if ( 'dashboard' !== $screen->base ) {
			return;
		}

		// Remove default circle, and style dashicons we add via classes.
		echo '<style type="text/css">#dashboard_right_now li.dashicons-before a:before, #dashboard_right_now li.dashicons span:before {content: none;}#dashboard_right_now li.dashicons-before:before {color: #82878c;margin: 0 5px 0 0;}</style>';

		$added_style = true;
	}
}
