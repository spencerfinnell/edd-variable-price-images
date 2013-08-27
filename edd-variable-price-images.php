<?php
/**
 * Plugin Name: Easy Digital Downloads - Variable Price Image Galleries
 * Plugin URI:  https://easydigitaldownloads.com/extensions/variable-price-images
 * Description: Create image galleries for each variable price option. Can either be displayed as a list of links, or a gallery.
 * Author:      Spencer Finnell
 * Author URI:  http://spencerfinnell.com
 * Version:     1.0
 * Text Domain: edd_vpi
 * Domain Path: languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * License
 */
if( ! class_exists( 'EDD_License' ) )
	include( dirname( __FILE__ ) . '/EDD_License_Handler.php' );

$license = new EDD_License( __FILE__, 'Variable Price Images', '1.0', 'Spencer Finnell' );

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
		add_action( 'edd_checkout_table_header_first', array( $this, 'table_header_first' ) );
		add_action( 'edd_checkout_table_body_first', array( $this, 'table_body_first' ) );
		add_filter( 'get_post_metadata', array( $this, 'force_no_thumbnail' ), 10, 4 );
		
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
	 * If we are editing a download, output our custom scripts.
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @param $hook The current page hook
	 * @return void
	 */
	public function admin_scripts( $hook ) {
		$screen = get_current_screen();

		if ( ! ( 'post' == $screen->base && 'download' == $screen->post_type ) )
			return;

		wp_enqueue_script( 'edd_vpi', $this->plugin_url . 'edd-variable-price-images.js', array( 'jquery', 'edd-admin-scripts' ), 20130901, true );
	}

	/**
	 * Add a new heading column to the variable price options table.
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @return void
	 */
	public function download_price_table_head() {
		echo '<th style="width: 80px;">' . __( 'Gallery', 'edd_vpi' ) . '</th>';
	}

	/**
	 * Add a new column to the variable price options table row.
	 * Outputs a button to edit/create a gallery of images for that price point. 
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @param int $post_id The ID of the current download we are editing.
	 * @param int $key The price option ID of the download.
	 * @param array $args The default arguments.
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
	 * Modify the output of the variable price options. Depending on the setting
	 * selected, either output a list of image links, or a gallery of images.
	 *
	 * The gallery will respect the columns and link options set in the admin.
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @param int $key The price option ID of the download.
	 * @param stirng $price The current price.
	 * @param int $download_id The ID of the current download.
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
	 * When outputting a gallery, always load the full size images. This way
	 * a single column gallery will look decent. 
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @param array $out
	 * @param unknown $pairs
	 * @param array $atts
	 * @return array $out The array or attributes for the shortcode.
	 */
	public function gallery_fullsize( $out, $pairs, $atts ) {
		$out[ 'size' ] = 'full';

		return $out;
	}

	public function table_header_first() {
		$width = apply_filters( 'edd_checkout_image_size', array( 25,25 ) );

		echo '<th style="width: ' . $width[0] . 'px"></th>';
	}

	/**
	 * If there is a custom image for their chosen variation, show a link to it.
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @param $item The current line item we are viewing.
	 * @return mixed
	 */
	public function table_body_first( $item ) {
		global $edd_options;

		if ( ! is_page( $edd_options[ 'purchase_page' ] ) )
			return $image;

		$prices = edd_get_variable_prices( $item[ 'id' ] );

		if ( ! isset ( $prices[ $item[ 'options' ][ 'price_id' ] ][ 'image' ] ) )
			return;

		$images = $this->get_gallery_ids( $prices[ $item[ 'options' ][ 'price_id' ] ][ 'image' ] );
	
		echo '<td>' . wp_get_attachment_link( $images[0], apply_filters( 'edd_checkout_image_size', array( 25,25 ) ) ) . '</td>';
	}

	public function force_no_thumbnail( $metadata, $object_id, $meta_key, $single ) {
		global $edd_options;

		if ( is_page( $edd_options[ 'purchase_page' ] ) && '_thumbnail_id' == $meta_key )
			return false;

		return $metadata;
	}

	/**
	 * Get the IDs from a specific gallery shortcode. 
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
	 *
	 * @param string $shortcode The gallery shortcode to search.
	 * @return array $images An array of image IDs.
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