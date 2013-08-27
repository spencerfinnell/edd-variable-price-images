<?php
/**
 * Plugin Name: Easy Digital Downloads - Variable Price Images
 * Plugin URI:  https://github.com/Astoundify/edd-variable-price-images
 * Description: Assign a specific image for each variable price option.
 * Author:      Astoundify
 * Author URI:  http://astoundify.com
 * Version:     1.0
 * Text Domain: edd_vpi
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Astoundify_EDD_VPI {

	/**
	 * @var $instance
	 */
	private static $instance;

	/**
	 * Make sure only one instance is only running.
	 */
	public static function instance() {
		if ( ! isset ( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Start things up.
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 */
	public function __construct() {
		$this->setup_globals();
		$this->setup_actions();
	}

	/**
	 * Set some smart defaults to class variables. Allow some of them to be
	 * filtered to allow for early overriding.
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @return void
	 */
	private function setup_globals() {
		$this->file         = __FILE__;
		
		$this->basename     = apply_filters( 'edd_vpi_plugin_basenname', plugin_basename( $this->file ) );
		$this->plugin_dir   = apply_filters( 'edd_vpi_plugin_dir_path',  plugin_dir_path( $this->file ) );
		$this->plugin_url   = apply_filters( 'edd_vpi_plugin_dir_url',   plugin_dir_url ( $this->file ) );

		$this->lang_dir     = apply_filters( 'edd_vpi_lang_dir',     trailingslashit( $this->plugin_dir . 'languages' ) );

		$this->domain       = 'edd_vpi'; 
	}

	/**
	 * Setup the default hooks and actions
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @return void
	 */
	private function setup_actions() {
		add_action( 'edd_after_price_option', array( $this, 'price_option' ), 10, 3 );
		add_filter( 'edd_checkout_image', array( $this, 'checkout_image' ), 10, 2 );
		
		$this->load_textdomain();

		if ( ! is_admin() )
			return;

		add_filter( 'edd_settings_extensions', array( $this, 'settings' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 200 );

		add_action( 'edd_download_price_table_head', array( $this, 'download_price_table_head' ), 20 );
		add_action( 'edd_download_price_table_row', array( $this, 'download_price_table_row' ), 20, 3 );
	}

	/**
	 * Settings
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @param array $settings
	 * @return array $settings
	 */
	public function settings( $settings ) {
		$settings[ 'edd_vpi' ] = array(
			'id'   => 'edd_vpi',
			'name' => '<strong>' . __( 'Variable Price Images', 'edd_vpi' ) . '</strong>',
			'desc' => null,
			'type' => 'header'
		);

		$settings[ 'edd_vpi_style' ] = array(
			'id'   => 'edd_vpi_style',
			'name' => __( 'Output Style', 'edd_vpi' ),
			'desc' => null,
			'type' => 'select',
			'options' => array(
				'preview-link' => __( 'Preview Links', 'edd_vpi' ),
				'gallery'      => __( 'Image Gallery', 'edd_vpi' )
			)
		);

		return $settings;
	}

	/**
	 * 
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @return void
	 */
	public function admin_scripts( $hook ) {
		$screen = get_current_screen();

		if ( ! ( 'post' == $screen->base && 'download' == $screen->post_type ) )
			return;

		wp_enqueue_script( 'edd_vpi', $this->plugin_url . 'edd-variable-price-images.js', array( 'jquery', 'edd-admin-scripts' ), 20130901, true );
	}

	/**
	 * 
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @return void
	 */
	public function download_price_table_head() {
		echo '<th style="width: 80px;">' . __( 'Gallery', 'edd_vpi' ) . '</th>';
	}

	/**
	 * 
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @return void
	 */
	public function download_price_table_row( $post_id, $key, $args ) {
		$prices = edd_get_variable_prices( $post_id );
		$image  = $prices[ $key ][ 'image' ];
	?>
			<td>
				<a href="#" class="edd_vpi_assign button-secondary" style="margin: 3px 0 0; " data-price="<?php _e( 'Image for Price Option', 'edd_vpi' ); ?>" data-text="<?php _e( 'Choose Images', 'edd_vpi' ); ?>"><?php _e( 'Choose Images', 'edd_vpi' ); ?></a>
				<input type="hidden" name="edd_variable_prices[<?php echo esc_attr( $key ); ?>][image]" value="<?php echo esc_attr( $image ); ?>" />
			</td>
	<?php
	}

	/**
	 * 
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @return void
	 */
	public function price_option( $key, $price, $download_id ) {
		global $edd_options;

		$prices = edd_get_variable_prices( $download_id );

		if ( ! isset ( $prices[ $key ][ 'image' ] ) )
			return;

		$shortcode = $prices[ $key ][ 'image' ];

		if ( 'preview-link' == $edd_options[ 'edd_vpi_style' ] ) {
			$images = $this->get_gallery_ids( $shortcode );
			$output = array();

			foreach ( $images as $image ) {
				$image = get_post( $image );
				$src   = wp_get_attachment_image_src( $image->ID, 'fullsize' );

				$output[] = sprintf( '<a href="%s">' . $image->post_title . '</a>', $src[0] );
			}

			echo '<span class="edd-vpi"> &mdash; ';
			echo implode( ' &bull; ', $output );
			echo '</span>';

			return;
		} else if ( 'gallery' == $edd_options[ 'edd_vpi_style' ] ) {
			add_filter( 'shortcode_atts_gallery', array( $this, 'gallery_fullsize' ), 10, 3 );

			echo '<div class="edd-vpi">' . do_shortcode( $prices[ $key ][ 'image' ] ) . '</div>';

			remove_filter( 'shortcode_atts_gallery', array( $this, 'gallery_fullsize' ), 10, 3 );
		}
	}

	/**
	 * 
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @return void
	 */
	public function gallery_fullsize( $out, $pairs, $atts ) {
		$out[ 'size' ] = 'full';

		return $out;
	}

	/**
	 * 
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @return void
	 */
	public function checkout_image( $image, $item ) {
		global $edd_options;

		if ( ! is_page( $edd_options[ 'purchase_page' ] ) )
			return $image;

		$prices = edd_get_variable_prices( $item[ 'id' ] );

		if ( ! isset ( $prices[ $item[ 'options' ][ 'price_id' ] ][ 'image' ] ) )
			return $image;

		$images = $this->get_gallery_ids( $prices[ $item[ 'options' ][ 'price_id' ] ][ 'image' ] );

		return wp_get_attachment_link( $images[0], apply_filters( 'edd_checkout_image_size', array( 25, 25 ) ) );
	}

	/**
	 * 
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @return void
	 */
	public function get_gallery_ids( $shortcode ) {
		$pattern = get_shortcode_regex();
		preg_match( "/$pattern/s", $shortcode, $match );
		$atts = shortcode_parse_atts( $match[3] );
		
		if ( isset( $atts['ids'] ) )
			$images = explode( ',', $atts['ids'] );

		return $images;
	}

	/**
	 * Loads the plugin language files
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @return false
	 */
	public function load_textdomain() {
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/' . $this->domain . '/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			return load_plugin_textdomain( $this->domain, '', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			return load_plugin_textdomain( $this->domain, '', $mofile_local );
		}

		return false;
	}
}

/**
 * Start things up.
 *
 * Use this function instead of a global.
 *
 * $edd_vpi = edd_vpi();
 *
 * @since Easy Digital Downloads - Variable Price Images 1.0
 */
function edd_vpi() {
	return Astoundify_EDD_VPI::instance();
}

edd_vpi();