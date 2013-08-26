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
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 200 );

		add_action( 'edd_download_price_table_head', array( $this, 'download_price_table_head' ), 20 );
		add_action( 'edd_download_price_table_row', array( $this, 'download_price_table_row' ), 20, 3 );
		
		$this->load_textdomain();
	}

	public function admin_scripts( $hook ) {
		$screen = get_current_screen();

		if ( ! ( 'post' == $screen->base && 'download' == $screen->post_type ) )
			return;

		wp_enqueue_script( 'edd_vpi', $this->plugin_url . 'edd-variable-price-images.js', array( 'jquery', 'edd-admin-scripts' ), 20130901, true );
	}

	public function download_price_table_head() {
		echo '<th style="width: 140px;">' . __( 'Image', 'edd_vpi' ) . '</th>';
	}

	public function download_price_table_row( $post_id, $key, $args ) {
		$prices = edd_get_variable_prices( $post_id );
		$image  = $prices[ $key ][ 'image' ];
		$attachment = wp_get_attachment_image_src( $image, array( 25, 25 ) );
	?>
			<td>
				<a href="#" class="edd_vpi_assign button-secondary" style="margin: 3px 0 0; float: left;" data-price="<?php _e( 'Image for Price Option', 'edd_vpi' ); ?>" data-text="<?php _e( 'Assign Image', 'edd_vpi' ); ?>"><?php _e( 'Assign Image', 'edd_vpi' ); ?></a>
				<img src="<?php echo esc_url( $attachment[ 0 ] ); ?>" width="28" height="28" style="margin: 3px 0 0 5px" />
				<input type="hidden" name="edd_variable_prices[<?php echo esc_attr( $key ); ?>][image]" value="<?php echo esc_attr( $image ); ?>" />
			</td>
	<?php
	}

	/**
	 * Loads the plugin language files
	 *
	 * @since Easy Digital Downloads - Variable Price Images 1.0
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