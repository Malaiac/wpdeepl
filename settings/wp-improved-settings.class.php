<?php
/**
 * Improved Settings
 *
 *
 * @package WP_Improved_Settings
 * @version 20190124
 */

namespace WP_Improved_Settings;

if( !class_exists( 'WP_Improved_Settings\WP_Improved_Settings' ) ) {
class WP_Improved_Settings {
	// loosely based on wc-dynamic-pricing-and-discounts/classes/rp-wcdpd-settings.class.php
	public $settingsStructure = array();
	public $extendedActions = array();

	public $plugin_id;
	public $menu_order = 20;
	public $minimum_capability = 'manage_options';
	public $option_page = '';
	public $defaultSettingsTab = '';
	public $parent_menu = '';
/*Dashboard: 'index.php'
Posts: 'edit.php'
Media: 'upload.php'
Pages: 'edit.php?post_type=page'
Comments: 'edit-comments.php'
Custom Post Types: 'edit.php?post_type=your_post_type'
Appearance: 'themes.php'
Plugins: 'plugins.php'
Users: 'users.php'
Tools: 'tools.php'
Settings: 'options-general.php'
Network Settings: 'settings.php'
WooCommerce : 'woocommerce'
*/

	public function __construct() {
		// Register settings

		add_action( 'admin_init', array( $this, 'loadSettings' ) );
		add_action( 'admin_init', array( $this, 'registerSettings' ) );

		// Add link to menu

		global $wp_filter;
		$real_order = $this->menu_order;
		while( isset( $wp_filter['admin_menu']->callbacks[$real_order] ) ) {
			$real_order++;
		}
		add_action( 'admin_menu', array( $this, 'addToMenu' ), $real_order );

		// Pass configuration to Javascript
		//add_action( 'admin_enqueue_scripts', array( $this, 'configuration_to_javascript' ), 999 );

		// Enqueue templates to be rendered in footer
		//add_action( 'admin_footer', array( $this, 'render_templates_in_footer' ) );

		// Settings export call
		if ( !empty( $_REQUEST['export_settings'] ) ) {
			add_action( 'wp_loaded', array( $this, 'export' ) );
		}

		// Settings import call
		if ( !empty( $_FILES[$this->option_page]['name']['import'] ) ) {
			add_action( 'wp_loaded', array( $this, 'import' ) );
		}

		// Print settings import notice
		if ( isset( $_REQUEST[$this->option_page .'_imported'] ) ) {
			add_action( 'admin_notices', array( $this, 'print_import_notice' ) );
		}

		if( !class_exists( 'WP_Improved_Settings\WC_Improved_Settings_API' )) {
			require_once( dirname( __FILE__ ) . '/wp-improved-settings-api.class.php' );
		}
		$this->WC_Improved_Settings_API = new WC_Improved_Settings_API( $this->getPluginID(), $this->getSettingsStructure() );

		$key_name = $this->plugin_id . '_options_save';
		if( isset( $_REQUEST['save'] ) && isset( $_REQUEST[$key_name] ) && $_REQUEST[$key_name] ) {
			$this->saveSettings();
//			echo " saving";
			if( method_exists( $this, 'on_save' ) ) {
//				echo "on save update";
				$this->on_save();
			}

			add_action( 'admin_notices', array( $this, 'print_saved_notice' ) );
		}

		add_action( 'admin_notices', array( $this, 'maybe_print_notices' ) );

		// Migration notices
		//add_action( 'admin_notices', array( $this, 'maybe_display_migration_notice' ), 1 );

		// Delete migration notice
		//$this->hide_migration_notice();
	}

	function getPluginID() {
		return $this->plugin_id;
	}

	function getOptionPage() {
		return $this->option_page;
	}

	function getMinimumCapability() {
		return $this->minimum_capability;
	}

	function saveSettings() {
		$this->WC_Improved_Settings_API->process_admin_options();
		//$this->process_admin_options();
	}
/*
	function me() {
		$this->loadSettings();
				$this->WC_Improved_Settings_API = WC_Improved_Settings_API( $this->getPluginID(), $this->getSettingsStructure() );
		return $this->WC_Improved_Settings_API->process_admin_options();
	}*/

	function maybe_print_notices() {
	}

	function print_saved_notice() {
		?>
		<div id="message" class="updated notice is-dismissible"><p><strong><?php _e( 'Settings saved.' ); ?></strong></p></div>
		<?php
	}

	public function loadSettings() {
		// load settings into $this sttings ?
		$this->settingsStructure = $this->getSettingsStructure();
		//plouf( $this->settingsStructure );		die( 'ok' );
	}

	/**
	 * Add Settings link to menu
	 *
	 * @access public
	 * @return voidaddToMenu
	 */
	public function addToMenu() {
		//die( 'menu to '.$this->parent_menu . ' page title = ' . $this->getPageTitle() .' menu = ' . 	$this->getMenuTitle() . ' cap ' . 	'manage_options' . ' option page = ' .			$this->getOptionPage() );
		add_submenu_page(
			$this->parent_menu,
			$this->getPageTitle(),
			$this->getMenuTitle(),
			$this->getMinimumCapability(),
			$this->getOptionPage(),
			array( $this, 'settingsPage' )
		);
	}

	/**
	 * Print settings page
	 *
	 * @access public
	 * @return void
	 */
	public function settingsPage() {
		

		// Get current tab
		$current_tab = ( isset( $_GET['tab'] ) ) ? $_GET['tab'] : $this->defaultSettingsTab;

//		plouf( $_POST );

		// Print header
		$this->printHeader();

		$this->printFields();

		if( count( $this->extendedActions ) ) foreach( $this->extendedActions as $action => $function ) {
			if( isset( $_REQUEST[$action] ) ) {
				$this->$function();
			}
		}
		$this->printFooter();
	}

	public function registerSettings() {
		// Check if current user can manage plugin settings
		if ( !is_admin() ) {
			return;
		}

		// Iterate over tabs
		foreach ( $this->settingsStructure as $tab_key => $tab ) {
			// Register tab
			register_setting(
				$this->option_page .'_group_' . $tab_key,
				$this->option_page,
				array( $this, 'validateSettings' )
			);

			// Iterate over sections
			foreach ( $tab['sections'] as $section_key => $section ) {
				$settings_page_id = $this->plugin_id . '-admin-' . str_replace( '_', '-', $tab_key );

				// Register section
				add_settings_section(
					$section_key,
					$section['title'],
					array( $this, 'print_section_info' ),
					$settings_page_id
				);

				// Iterate over fields
				foreach ( $section['fields'] as $field_key => $field ) {
					// Register field
					add_settings_field(
						$this->plugin_id . '_' . $field_key,
						$field['title'],
						array( $this, 'print_field_' . $field['type'] ),
						$settings_page_id,
						$section_key,
						array(
							'field_key'			 => $field_key,
							'field'				 => $field,
							'data-' . $this->plugin_id . '-setting-hint'	=> !empty( $field['hint'] ) ? $field['hint'] : null,
						)
					);
				}
			}
		}
	}

	function validateSettings() {
		return true;
	}

	function getActiveTab() {
		if( isset( $_GET[ 'tab' ] ) ) {
			$active_tab = $_GET[ 'tab' ];
		}
		elseif( $this->defaultSettingsTab != '' ) {
			$active_tab = $this->defaultSettingsTab;
		}

		if( !isset( $this->settingsStructure[$active_tab] ) ) {
			return false;
		}
		return $active_tab;
	}

	function printHeader() {
		$tabs = array();
		foreach( $this->settingsStructure as $setting_tab_slug => $setting_data ) {
			$tabs[$setting_tab_slug] = $setting_data['title'];
		}
		//echo '<div class="wrap woocommerce"><form method="post" action="options.php" enctype="multipart/form-data">';
		?>

			<div class="wrap">

		<div id="icon-themes" class="icon32"></div>
		<h2><?php echo $this->getPageTitle(); ?></h2>
		<?php
		settings_errors();

		$active_tab = $this->getActiveTab();

		$parent_menu = $this->parent_menu;
		$parsed_url = parse_url( $parent_menu );
		if( isset( $parsed_url['query'] ) && strlen( $parsed_url['query'] ) ) {
			$extended_url = '&' . $parsed_url['query'];
		}
		else {
			$extended_url = '';
		}

		?>
		<form method="post" id="mainform" action="admin.php?page=<?php echo $this->getOptionPage(); ?><?php echo ( $active_tab ) ?'&tab=' . $active_tab : ''; ?>" enctype="multipart/form-data">
			<input type="hidden" name="<?php echo $this->plugin_id; ?>_options_save" value="1">
			<input type="hidden" name="tab" value="<?php echo $active_tab; ?>">

		 <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
			<?php foreach( $tabs as $tab => $label ) :
			$nav_tab_id = $this->getOptionPage() . '-' . $tab; ?>
			<a id="<?php echo $nav_tab_id; ?>" href="?page=<?php echo $this->getOptionPage(); ?>&tab=<?php echo $tab; ?><?php echo $extended_url; ?>" class="nav-tab <?php echo $active_tab == $tab ? 'nav-tab-active' : ''; ?>"><?php echo $label; ?></a>
			<?php endforeach; ?>
		 </nav>

		 <?php
		if( !$active_tab = $this->getActiveTab() ) {
			return false;
		}

/*
		$tab_data = $this->settingsStructure[$active_tab];
		$defaults = array(
			'title'			=> '',
			'class'			=> '',
			'description'	=> '',
			'sections'		=> array(),
		);
		$tab_data = wp_parse_args( $tab_data, $defaults );

		$this->displayTabTitle( $active_tab, $tab_data );
*/
	}

	function printFields() {
		if(!$this->getActiveTab()) {
			return false;
		}
		$active_tab = $this->getActiveTab();
		$tab_data = $this->settingsStructure[$active_tab];

		foreach( $tab_data['sections'] as $section_id => $section ) {
			$defaults = array(
				'title'			=> '',
				'class'			=> '',
				'description'	=> '',
				'fields'		=> array(),
			);
			$section_data = wp_parse_args( $section, $defaults );

		//plouf( $section );
		?>
		<h3 class="wc-settings-sub-title <?php echo esc_attr( $section_data['class'] ); ?>" id="<?php echo esc_attr( $section_id ); ?>"><?php echo wp_kses_post( $section_data['title'] ); ?></h3>
		<?php if ( ! empty( $section_data['description'] ) ) : ?>
				<p><?php echo wp_kses_post( $section_data['description'] ); ?></p>
		<?php endif; ?>

		<table class="form-table">

		<?php

		$this->WC_Improved_Settings_API->id = $active_tab;

		$section_fields = $section_data['fields'];

		$fields = array();
		foreach( $section_fields as $field ) {
			$fields[$field['id']] = $field;
		}

		$this->WC_Improved_Settings_API->generate_settings_html( $fields );
		?>
		</table>

		<?php
		}
		?>

		<?php if( count( $fields ) ) : ?>

		<p class="submit">
			<?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
				<button name="save" class="button-primary" type="submit" value="<?php esc_attr_e( 'Update' ); ?>"><?php _e( 'Update' ); ?></button>
			<?php endif; ?>
			<?php
			// wp_nonce_field( 'woocommerce-settings' );
			?>
		</p>
		<?php endif; ?>

		<?php
	}

	function displayTabTitle( $tab_id, $tab_data ) {
		?>
		<h2 class="wc-settings-sub-title <?php echo esc_attr( $tab_data['class'] ); ?>" id="<?php echo esc_attr( $tab_id ); ?>"><?php echo wp_kses_post( $tab_data['title'] ); ?></h2>
		<?php if ( ! empty( $tab_data['description'] ) ) : ?>
				<p><?php echo wp_kses_post( $tab_data['description'] ); ?></p>
		<?php endif;
	}

	function tabFooter( $tab_id, $tab_data ) {
		if( isset( $tab_data['footer'] ) ) {
			if( isset( $tab_data['footer']['html'] ) ) foreach( $tab_data['footer']['html'] as $raw_html ) {
				echo $raw_html;
			}
			if( isset( $tab_data['footer']['actions'] ) ) foreach( $tab_data['footer']['actions'] as $action ) {
//				echo " ACTION = $action";
				if( function_exists( $action ) ) {
					$action();
				}
				else {
					printf( __( 'Attention, fonction non définie %s' ), $action );
				}
			}
		}
	}

	function printFooter() {
?>
		<?php
		$active_tab = $this->getActiveTab();
		if( $active_tab ) {
			$tab_data = $this->settingsStructure[$active_tab];
			$this->tabFooter( $active_tab, $tab_data );
		}
			?>

		</form>

	</div>
	<?php
	}
}
}
/*
if( !function_exists( 'WP_Improved_Settings\WC_Improved_Settings_API' ) ) {
function WC_Improved_Settings_API( $plugin_id, $settingsStructure ) {
	//echo "function WC_Improved_Settings_API";	echo " class exists ? " ;	var_dump( class_exists( 'WP_Improved_Settings\WC_Improved_Settings_API' ) );

	if( !class_exists( 'WP_Improved_Settings\WC_Improved_Settings_API' ) ) {
		if( !class_exists( 'WP_Improved_Settings\WC_Improved_Settings_API' ) ) {
			require_once( dirname( __FILE__ ) . '/abstract-wc-settings-api.php' );
		}

		class WC_Improved_Settings_API extends WC_Improved_Settings_API {

		}
	}
	return new WC_Improved_Settings_API( $plugin_id, $settingsStructure );
}
}*/