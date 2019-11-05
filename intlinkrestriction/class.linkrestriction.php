<?php

class Linkrestriction {

	private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	/**
	 * Initializes WordPress hooks
	 */
	private static function init_hooks() {
		self::$initiated = true;

		add_action( 'pre_post_update', array( 'Linkrestriction','intercept_publishing'), 10, 2 );
		add_action( 'admin_notices',  array( 'Linkrestriction','my_plugin_notice') );
		add_action('admin_enqueue_scripts', array( 'Linkrestriction','my_enqueue'));

		add_action( 'admin_init', array( 'Linkrestriction','linkrestriction_register_settings') );
		add_action('admin_menu', array( 'Linkrestriction','register_options_page'));
	}

	public static function intercept_publishing($post_ID, $data){
		set_transient( 'intlinks_errors', array(array('message'=>'Not enough links')), 30 );
		//add_filter( 'redirect_post_location', array( 'Linkrestriction', 'add_notice_query_var' ), 99 );
		$post_content = $data['post_content'];
		$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
		$count = substr_count($post_content,'href="'.$actual_link);
		$count += substr_count($post_content,"href='".$actual_link);
		$count += substr_count($post_content,'href=\"'.$actual_link);
		$count += substr_count($post_content,"href=\'".$actual_link);
		$cnti = get_option('linkrestr_img_count');
		if (!$cnti || empty($cnti)) {
			$cnti = -1;
		}
		$cnt = get_option('linkrestr_count');
		if (!$cnt || empty($cnt)) {
			$cnt = -1;
		}
		if ($cnt != -1 && $count < $cnt) {
			wp_die( __('<b>Publishing Error: </b> Not enough internal links'), __('Publishing Error'), [ 'back_link' => true ] );
		}
		$count = substr_count($post_content,'src="'.$actual_link);
		$count += substr_count($post_content,"src='".$actual_link);
		$count += substr_count($post_content,'src=\"'.$actual_link);
		$count += substr_count($post_content,"src=\'".$actual_link);
		if ($cnti != -1 && $count < $cnti) {
			wp_die( __('<b>Publishing Error: </b> Not enough internal images'), __('Publishing Error'), [ 'back_link' => true ] );
		}
    }

	public static function my_enqueue($hook) {
		wp_register_script( 'linkrestriction.js', plugin_dir_url( __FILE__ ) . '/script.js', array('jquery'), LINKRESTRICTION_VERSION );
		wp_enqueue_script( 'linkrestriction.js' );
		$cnti = get_option('linkrestr_img_count');
		if (!$cnti || empty($cnti)) {
			$cnti = -1;
		}
		$cnt = get_option('linkrestr_count');
		if (!$cnt || empty($cnt)) {
			$cnt = -1;
		}
		$conf = array(
		    'count' => $cnt,
            'link_message' => __('Not enough internal links.'),
            'image_count' => $cnti,
		    'link_image_message' => __('Not enough internal images.')
        );
		wp_localize_script( 'linkrestriction.js', 'conf', $conf );
	}

	public static function my_plugin_notice() {
		if ( ! ( $errors = get_transient( 'intlinks_errors' ) ) ) {
			return;
		}
		//$errors = get_transient( 'intlinks_errors' );
		$message = '<div id="linkrestriction-message" class="error below-h2"><p><ul>';
		foreach ( $errors as $error ) {
			$message .= '<li>' . $error['message'] . '</li>';
		}
		$message .= '</ul></p></div><!-- #error -->';
		// Write them out to the screen
		echo $message;
		// Clear and the transient and unhook any other notices so we don't see duplicate messages
		delete_transient( 'intlinks_errors' );
	}

	public static function plugin_activation() {
		if ( version_compare( $GLOBALS['wp_version'], LINKRESTRICTION__MINIMUM_WP_VERSION, '<' ) ) {
			load_plugin_textdomain( 'linkrestriction' );

			$message = '<strong>'.sprintf(esc_html__( 'Link Restriction %s requires WordPress %s or higher.' , 'linkrestriction'), LINKRESTRICTION_VERSION, LINKRESTRICTION__MINIMUM_WP_VERSION ).'</strong> '.sprintf(__('Please <a href="%1$s">upgrade WordPress</a> to a current version.', 'linkrestriction'), 'https://codex.wordpress.org/Upgrading_WordPress');

			Linkrestriction::bail_on_activation( $message );
		}
	}

	private static function bail_on_activation( $message, $deactivate = true ) {
		?>
		<!doctype html>
		<html>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>" />
			<style>
				* {
					text-align: center;
					margin: 0;
					padding: 0;
					font-family: "Lucida Grande",Verdana,Arial,"Bitstream Vera Sans",sans-serif;
				}
				p {
					margin-top: 1em;
					font-size: 18px;
				}
			</style>
		</head>
		<body>
		<p><?php echo esc_html( $message ); ?></p>
		</body>
		</html>
		<?php
		if ( $deactivate ) {
			$plugins = get_option( 'active_plugins' );
			$linkrestrict = plugin_basename( LINKRESTRICTION__PLUGIN_DIR . 'intlinkrestriction.php' );
			$update  = false;
			foreach ( $plugins as $i => $plugin ) {
				if ( $plugin === $linkrestrict ) {
					$plugins[$i] = false;
					$update = true;
				}
			}

			if ( $update ) {
				update_option( 'active_plugins', array_filter( $plugins ) );
			}
		}
		exit;
	}

	public static function plugin_deactivation( ) {

	}

	public static function linkrestriction_register_settings() {
		add_option( 'myplugin_option_name', 'This is my option value.');
		register_setting( 'linkrestr_options_group', 'linkrestr_count', 'linkrestr_callback' );
		register_setting( 'linkrestr_options_group', 'linkrestr_img_count', 'linkrestr_callback' );
    }

	public static function register_options_page() {
		add_options_page(__('Internal link restriction settings'), 'Link Restriction Menu', 'manage_options', 'linkrestriction', array( 'Linkrestriction','options_page'));
	}

	public static function options_page() {
		?>
        <div>
			<?php screen_icon(); ?>
            <h1><?php echo __('Internal link restriction settings'); ?></h1>
            <form method="post" action="options.php">
				<?php settings_fields( 'linkrestr_options_group' ); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr valign="top">
                            <th scope="row"><label for="linkrestr_count"><?php echo __('Internal link count'); ?></label></th>
                            <td><input type="text" id="linkrestr_count" name="linkrestr_count" value="<?php echo get_option('linkrestr_count'); ?>" /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="linkrestr_img_count"><?php echo __('Internal link image count'); ?></label></th>
                            <td><input type="text" id="linkrestr_img_count" name="linkrestr_img_count" value="<?php echo get_option('linkrestr_img_count'); ?>" /></td>
                        </tr>
                    </tbody>
                </table>
				<?php  submit_button(); ?>
            </form>
        </div>
		<?php
    }
}