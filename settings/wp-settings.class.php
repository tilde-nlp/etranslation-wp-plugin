<?php
namespace WP_Settings;

if( !class_exists( 'WP_Settings\WP_Settings' ) ) {
class WP_Settings {
	public $settingsStructure = array();
	public $defaultActions = array();
	public $extendedActions = array();

	public $plugin_id;
	public $menu_order = 20;
	public $minimum_capability = 'manage_options';
	public $option_page = '';
	public $defaultSettingsTab = '';
	public $parent_menu = '';
	public $post_type = false;

	public function __construct() {
		// Register settings

		add_action( 'admin_init', array( $this, 'loadSettings' ) );
		add_action( 'admin_init', array( $this, 'registerSettings' ) );

		$this->plugins_paths = apply_filters( 'settings_etranslate_paths', array() );
		
		// Add link to menu

		global $wp_filter;
		$real_order = $this->menu_order;
		while( isset( $wp_filter['admin_menu']->callbacks[$real_order] ) ) {
			$real_order++;
		}
		add_action( 'admin_menu', array( $this, 'addToMenu' ), $real_order );

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

		if( !class_exists( 'WP_Settings\WC_Settings_API' )) {
			require_once( dirname( __FILE__ ) . '/wp-settings-api.class.php' );
		}
		$this->WC_Settings_API = new WC_Settings_API( $this->getPluginID(), $this->getSettingsStructure() );


		$key_name = $this->plugin_id . '_options_save';
		if( isset( $_REQUEST['save'] ) && isset( $_REQUEST[$key_name] ) && $_REQUEST[$key_name] ) {
			$this->saveSettings();
			if( method_exists( $this, 'on_save' ) ) {
				$this->on_save();
			}

			add_action( 'admin_notices', array( $this, 'print_saved_notice' ) );
		}

		add_action( 'admin_notices', array( $this, 'maybe_print_notices' ) );


		$this->defaultActions['prune_logs'] =array( $this, 'pruneLogs' );
	}

	function getPluginID() {
		return $this->plugin_id;
	}

	function getCurrentPluginPage() {
		if( !is_admin( ) ) {
			return false;
		}
		if( !isset( $_GET['page'] ) ) {
			return false;
		}

		return sanitize_title_with_dashes( $_GET['page'] );
	}

	function getOptionPage() {
		return $this->option_page;
	}

	function getMinimumCapability() {
		return $this->minimum_capability;
	}

	function saveSettings() {
		$this->WC_Settings_API->process_admin_options();
	}

	function maybe_print_notices() {
	}

	function print_saved_notice() {
		?>
		<div id="message" class="updated notice is-dismissible"><p><strong><?php _e( 'Settings saved.' ); ?></strong></p></div>
		<?php
	}

	public function loadSettings() {
		$this->settingsStructure = $this->getSettingsStructure();
	}

	/**
	 * Add Settings link to menu
	 *
	 * @access public
	 * @return voidaddToMenu
	 */
	public function addToMenu() {
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

		// Print header
		$this->printHeader();

		$this->printFields();

		$possibleActions = array_merge( $this->defaultActions, $this->extendedActions );

		if( count( $possibleActions ) ) foreach( $possibleActions as $action => $function ) {
			if( isset( $_REQUEST[$action] ) ) {
				if( is_array( $function ) ) {
					list($object, $method) = $function;
					if( method_exists( $object, $method ) ) {
						$object->$method();
					}
					
				}
				elseif( function_exists( $function) ) {
					$function();
				}
				else {
					printf( __( 'Attention, fonction non définie %s' ), $function );
				}	
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
		$extended_url = '';
		if( isset( $parsed_url['query'] ) && strlen( $parsed_url['query'] ) ) {
			$extended_url = '&' . $parsed_url['query'];
		}

		if( property_exists($this, 'post_type' ) && $this->post_type ) {
			$action = 'edit.php';
		}
		else {
			$action = 'admin.php';
		}
		$action .= '?';

		if( $this->post_type ) {
			$action .= 'post_type=' . $this->post_type .'&';
		}
		$action .= 'page=' . $this->getOptionPage();
		if($active_tab) {
			$action .= '&tab=' . $active_tab;
		}

		$page_link = '?';
		if( $this->post_type ) {
			$page_link .= 'post_type=' . $this->post_type .'&';
		}
		$page_link .= 'page=' . $this->getOptionPage();

		

		?>
		<form method="post" id="mainform" action="<?php echo $action; ?>" enctype="multipart/form-data">
			<input type="hidden" name="<?php echo $this->plugin_id; ?>_options_save" value="1">
			<input type="hidden" name="tab" value="<?php echo $active_tab; ?>">

		 <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
			<?php foreach( $tabs as $tab => $label ) :
			$nav_tab_id = $this->getOptionPage() . '-' . $tab; ?>
			<a id="<?php echo $nav_tab_id; ?>" href="<?php echo $page_link .  '&tab=' . $tab . $extended_url; ?>" class="nav-tab <?php echo $active_tab == $tab ? 'nav-tab-active' : ''; ?>"><?php echo $label; ?></a>
			<?php endforeach; ?>
		 </nav>

		 <?php
		if( !$active_tab = $this->getActiveTab() ) {
			return false;
		}
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
				'html'			=> false,
			);
			$section_data = wp_parse_args( $section, $defaults );

			?>
			<h3 class="wc-settings-sub-title <?php echo esc_attr( $section_data['class'] ); ?>" id="<?php echo esc_attr( $section_id ); ?>"><?php echo wp_kses_post( $section_data['title'] ); ?></h3>
			<?php if ( ! empty( $section_data['description'] ) ) : ?>
					<p><?php echo wp_kses_post( $section_data['description'] ); ?></p>
			<?php endif; ?>

			<table class="form-table <?php echo esc_attr( $section_data['class'] ); ?>">

			<?php

			$this->WC_Settings_API->id = $active_tab;

			$section_fields = $section_data['fields'];

			$fields = array();
			foreach( $section_fields as $field ) {
				$fields[$field['id']] = $field;
			}

			$this->WC_Settings_API->generate_settings_html( $fields );
			?>
			</table>
			<?php
			 if( isset( $section['html'] ) && $section['html'] ) {
				echo $section['html']; 
			}
			?>

			<?php if( isset( $section['actions'] ) && $section['actions'] ) foreach( $section['actions'] as $action ) {
					$param = false;
					if( is_array( $action ) ) {
						list($action, $param) = $action;
						if( function_exists( $action ) ) {
							$action( $param );
						}
					}
					elseif( function_exists( $action ) ) {
						$action( $param );
					}
					else {
						printf( __( 'Attention, fonction non définie %s' ), $action );
					}
			}
			
				?>


			<?php if( count( $fields ) ) : ?>

			<p class="submit">
				<?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
					<button name="save" class="button-primary" type="submit" value="<?php esc_attr_e( 'Update' ); ?>"><?php _e( 'Update' ); ?></button>
				<?php endif; ?>
			</p>
			<?php endif; ?>

		<?php
		}
		?>

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
			if( isset( $tab_data['footer']['actions'] ) ) {
				echo '<hr />';
				foreach( $tab_data['footer']['actions'] as $action ) {
					$param = false;

					if( is_array( $action ) ) {
						list($object, $method) = $action;
						if( method_exists( $object, $method ) ) {
							$object->$method();
						}
					}
					elseif( function_exists( $action ) ) {
						$action( $param );
					}
					else {
						printf( __( 'Attention, fonction non définie %s' ), $action );
					}
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


	public function showServerInfo() {
		echo '<h2>' . __('Informations serveur', $this->plugin_text_domain ) . '</h2>';

		$ini_values = ini_get_all();

		$bytes = memory_get_usage();
		$s = array('o', 'Ko', 'Mo', 'Go', 'To', 'Po');
	    $e = floor(log($bytes)/log(1024));
	    $memory_usage = sprintf('%.2f '.$s[$e], ($bytes/pow(1024, floor($e))));
	  

	    $timeout = $ini_values['max_execution_time']['local_value'];
	    if($timeout > 1000 ) {
	    	$timeout = round($timeout /1000,1);
	    }

		$informations = array(
			'Real path'		=> get_home_path(),
			'PHP version'	=> phpversion(),
			'Timeout'		=> $timeout .' s',
			'Memory usage'	=> $memory_usage,
		);

		if( function_exists( 'sys_getloadavg') && is_array(sys_getloadavg() ) ) {
			$informations['Load'] = implode(', ', sys_getloadavg() );
		}

		foreach($informations as $label => $value) {
			echo '<p><strong>' . $label . '</strong> ' . $value .'</p>';
		}


	}

	function pruneLogs() {
		if( !current_user_can( 'manage_options' ) ) {
			return false;
		}

		$currentPlugin = $this->getCurrentPluginPage();
		$path = $this->plugins_paths[$currentPlugin]['files'];
		
		$logs = glob( trailingslashit( $path ) . '*.log');

		if($logs) foreach($logs as $log_file) {
			$file_name = basename( $log_file );
			if(preg_match('#(\d+)-(\d+)-(\w+)\.log#', $file_name, $match)) {
				$date = $match[2] . '/' . $match[1];
				$log_time = mktime(0, 0, 0, $match[2], 1, $match[1] );

				$first_day_of_the_month = new \DateTime('first day of this month');
				$first_day_of_the_month->modify('- 1 day');
		 		$first_day_time = $first_day_of_the_month->getTimestamp();

		 		if( $log_time < $first_day_time ) {
		 			unlink( $log_file );
		 		}
			}
			echo "<br />\n" . sprintf( __('Log file %s deleted', 'etranslate' ), basename( $file_name ) );
		}
		echo "<br />\n";
	}

	public function showLogs() {
		
		if( !is_admin() ) {
			return false;
		}

		$currentPlugin = $this->getCurrentPluginPage();
		$path = $this->plugins_paths[$currentPlugin]['files'];
		
		$logs = glob( trailingslashit( $path ) . '*.log');
		if($logs) foreach($logs as $log_file) {
			$file_name = basename( $log_file );
			$contents = file_get_contents( $log_file );
			if(preg_match('#(\d+)-(\d+)-(\w+)\.log#', $file_name, $match)) {
				$date = $match[2] . '/' . $match[1];
				echo '<h3>';
				printf( 
					__("Fichier '%s' pour %s" ),
					$match[3],
					$date
				);
				echo '</h3>';
				plouf($contents);

			}
		}

	}	
}
}