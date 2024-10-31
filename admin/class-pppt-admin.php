<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://xuxu.fr
 * @since      1.0.0
 *
 * @package    Pppt
 * @subpackage Pppt/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Pppt
 * @subpackage Pppt/admin
 * @author     Xuan NGUYEN <xuxu.fr@gmail.com>
 */
class Pppt_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// add archive option for plugins dropdown
		add_filter('bulk_actions-plugins', array(&$this, 'add_option_plugins_dropdown'));

		// process archive option
		add_filter('handle_bulk_actions-plugins', array(&$this, 'process_option_plugins_dropdown'), 10, 3);

		// add action link
		add_filter('plugin_action_links', array(&$this, 'add_action_links'), 10, 2);

		//
		// add_filter('show_advanced_plugins', array(&$this, 'add_advanced_plugins'), 10, 1);

		// process manage archives
		add_action('load-plugins.php', array(&$this, 'process_manage_archives'), 10);

		// display notices
		add_action('pre_current_active_plugins', array(&$this, 'display_admin_notices'), 10);

		// ajax
		add_action('wp_ajax_show_archives', array(&$this, 'show_archives'));

		// allow thickbox
		add_action('admin_footer', array(&$this, 'pppt_admin_footer'));
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Pppt_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Pppt_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/pppt-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Pppt_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Pppt_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/pppt-admin.js', array( 'jquery' ), $this->version, false );

		//
		$translation_array = array(
			'checked_required' => __('Please select at least one plugin.', 'pppt'),
			'loading' => __('Loading...', 'pppt'),
		);
		wp_localize_script($this->plugin_name, 'pppt_translate', $translation_array);
	}

	/**
	 * add option for plugins dropdown
	 *
	 * @since    1.0.0
	 */
	public function pppt_admin_footer() {

		/**
		 * 
		 */

		$screen = get_current_screen();
		// var_dump($screen->id);
		//
 		if (isset($screen->id) && $screen->id == 'plugins') {
	    //
		add_thickbox();
?>
		<div id="pppt-popin-details" class="pppt-popin"></div>
		<a href="#TB_inline?width=600&height=400&inlineId=pppt-popin-details" class="thickbox pppt-thickbox-launcher"><span><?php _e('Launch Thickbox', 'pppt');?></span></a>
<?php
		echo '<form id="farchiveslink" name="farchiveslink" method="post" action="'.self_admin_url('plugins.php').'">';
		wp_nonce_field('pppt_show_archives', 'pppt_show_archives_nonce');
		echo '</form>';
 		}
	}

	/**
	 * add option for plugins dropdown
	 *
	 * @since    1.0.0
	 */
	public function add_option_plugins_dropdown($actions) {

		/**
		 * 
		 */

		$actions['archive-selected'] = __('Archive', 'pppt');

		return $actions;
	}

	/**
	 * add action links
	 *
	 * @since    1.0.0
	 */
	public function add_action_links($links, $plugin) {
		//
		$plugin_status = !empty($_REQUEST['plugin_status']) ? esc_attr($_REQUEST['plugin_status']) : "";
		$paged = !empty($_REQUEST['paged']) && is_numeric($_REQUEST['paged']) ? sanitize_key($_REQUEST['paged']) : "";
		$s = !empty($_REQUEST['s']) ? esc_html($_REQUEST['s']) : "";
		//
		$link_archive = esc_url(wp_nonce_url('plugins.php?type_action=archive&amp;plugin='.urlencode($plugin).'&amp;plugin_status='.$plugin_status.'&amp;paged='.$page.'&amp;s='.$s, 'pppt_action_archives', 'pppt_action_archives_nonce'));
		//		
		$archive_link = array(
			'<a href="' . $link_archive.'">'.__('Archive', 'pppt').'</a>',
		);
		return array_merge($links, $archive_link);
	}

	/**
	 * add advanced plugins
	 *
	 * @since    1.0.0
	 */
	public function add_advanced_plugins($bool) {

		/**
		 * 
		 */

		global $plugins;

		remove_filter('show_advanced_plugins', array(&$this, 'add_advanced_plugins'), 10, 1);
		// var_dump($plugins);

		if (!isset($plugins['archive'])) {
			$destination = wp_upload_dir();
			$destination_path = $destination['basedir']."/pppt/";
			// echo $destination_path."<hr />";
			// exit;

			// if (apply_filters('show_advanced_plugins', true, 'active')) {
				$plugins['archive'] = get_archive_plugins($destination_path);
			// }


			// $plugins['archive'] = array(1);
		}

		return $bool;
	}

	/**
	 * display admin notices post
	 *
	 * @since    1.0.0
	 */
	public function display_admin_notices() {
		global $user_ID;
		//
		$admin_notices = get_transient('plugins_archive_result_post_notice_'.$user_ID);
		// Delete it once we're done.
		delete_transient('plugins_archive_result_post_notice_' . $user_ID);
		//
		if (is_array($admin_notices) && sizeof($admin_notices)) {
			foreach ($admin_notices as $admin_notices) {
				echo $admin_notices;
			}
		}

		//
		$archive_plugins = get_archive_plugins();
		// var_dump($archive_plugins);
		if (is_array($archive_plugins) && sizeof($archive_plugins) > 0) {
			$count = sizeof($archive_plugins);
			$s = (sizeof($archive_plugins) > 1) ? "s" : "";
			//
			echo '
				<div class="notice notice-info is-dismissible">
					<p>'.sprintf(__('You have <strong>%s</strong> plugin%s archived. <a href="javascript:pppt_show_archives();">Click here</a> to manage them.', 'pppt'), $count, $s).'</p>
				</div>
			';
		}
	}

	/**
	 * show archives
	 *
	 * @since    1.0.0
	 */
	public function show_archives() {
		global $user_ID;

		//
		$error = 1;
		$message = "";
		$debug = "";
		//
		$plugin_status = !empty($_REQUEST['plugin_status']) ? esc_attr($_REQUEST['plugin_status']) : "";
		$paged = !empty($_REQUEST['paged']) && is_numeric($_REQUEST['paged']) ? sanitize_key($_REQUEST['paged']) : "";
		$s = !empty($_REQUEST['s']) ? esc_html($_REQUEST['s']) : "";
		//
		ob_start();
		//
		if (!wp_verify_nonce($_REQUEST['pppt_show_archives_nonce'], 'pppt_show_archives')) {
		    echo '<p>'.__('You are not allowed to make this.', 'pppt').'<p>'; 
		}
		else {
			if (current_user_can('activate_plugins') && current_user_can('deactivate_plugins') && current_user_can('delete_plugins')) {
				//
				$wp_list_table = _get_list_table('WP_Plugins_List_Table');

				$archives = get_archive_plugins();
				if (is_array($archives) && sizeof($archives) == 0) {
					echo "<p>".__('You do not have plugins archived yet.', 'pppt')."</p>";
				}
				else {
					echo '<form id="farchives" name="farchives" action="'.self_admin_url('plugins.php').'">';
					echo '<input type="hidden" id="type_action" name="type_action" value="" />';
					wp_nonce_field('pppt_action_archives', 'pppt_action_archives_nonce');
					echo '
						<div class="buttons">
							<input type="button" class="button selectall" name="btn-selectall" value="'.__('check / uncheck all', 'pppt').'" />
							<input type="button" class="button button-primary restore" name="btn-restore" value="'.__('restore', 'pppt').'" />
							<input type="button" class="button delete" name="btn-delete" value="'.__('delete', 'pppt').'" />					
						</div>
					';
					echo '<ul class="archives">';
					foreach($archives as $plugin=>$data) {
						//
						$link_restore = esc_url(wp_nonce_url('plugins.php?type_action=restore&amp;plugin='.urlencode($plugin).'&amp;plugin_status='.$plugin_status.'&amp;paged='.$page.'&amp;s='.$s, 'pppt_action_archives', 'pppt_action_archives_nonce'));
						$link_delete = esc_url(wp_nonce_url('plugins.php?type_action=delete&amp;plugin='.urlencode($plugin).'&amp;plugin_status='.$plugin_status.'&amp;paged='.$page.'&amp;s='.$s, 'pppt_action_archives', 'pppt_action_archives_nonce'));
						//
						echo '<li>';
						echo '
							<div class="actions">
								<a href="'.$link_restore.'">'.__('Restore', 'pppt').'</a>
								|
								<a href="'.$link_delete.'" class="delete">'.__('Delete', 'pppt').'</a>
							</div>
						';
						echo '<div class="title"><label for="'.$plugin.'"><input type="checkbox" id="'.$plugin.'" name="archive[]" value="'.$plugin.'" />'.$data['Name'].'</label></div>';
						echo '<div class="description">'.$data['Description'].'</div>';
						echo '<div class="author">'.__('Version', 'pppt').' '.$data['Version'].' | <a href="'.$data['AuthorURI'].'" target="_blank">'.$data['Author'].'</a> | <a href="'.$data['PluginURI'].'" target="_blank">'.__('Plugin site', 'pppt').'</a></div>';
						echo '</li>';
					}
					echo '</ul>';
					echo '
						<div class="buttons">
							<input type="button" class="button selectall" name="btn-selectall" value="'.__('check / uncheck all', 'pppt').'" />
							<input type="button" class="button button-primary restore" name="btn-restore" value="'.__('restore', 'pppt').'" />
							<input type="button" class="button delete" name="btn-delete" value="'.__('delete', 'pppt').'" />					
						</div>
					';
					echo '</form>';
				}
			}
			else {
			    echo '<p>'.__('You are not allowed to activate / deactivate / delete plugins.', 'pppt').'<p>'; 
			}
		}
		//
		$content = ob_get_contents();
		ob_end_clean();
		//
		$return = array(
			"error" => $error,
			"message" => $content,
			"debug" => $debug,
		);
		//
		wp_send_json($return);
	}

	/**
	 * process option for plugins dropdown
	 *
	 * @since    1.0.0
	 */
	public function process_option_plugins_dropdown($redirect_url, $doaction, $items) {
		global $user_ID;
		//
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function
		if (empty($wp_filesystem)) {
			require_once (ABSPATH.'/wp-admin/includes/file.php');
			WP_Filesystem();
		}
		//
		$plugin_status = !empty($_REQUEST['plugin_status']) ? esc_attr($_REQUEST['plugin_status']) : "";
		$paged = !empty($_REQUEST['paged']) && is_numeric($_REQUEST['paged']) ? sanitize_key($_REQUEST['paged']) : "";
		$s = !empty($_REQUEST['s']) ? esc_html($_REQUEST['s']) : "";
		//
		$admin_notices = array();
		//
		if (!empty($doaction) == 'archive-selected') {
			//
			if (!current_user_can('deactivate_plugins')) {
				wp_die(__('Sorry, you are not allowed to deactivate plugins for this site.'));
			}
			if (!current_user_can('delete_plugins')) {
				wp_die(__('Sorry, you are not allowed to delete plugins for this site.'));
			}
			//
			// check_admin_referer('bulk-plugins'); // check for bulk actions
			//
			$plugins = isset($items) ? (array) wp_unslash($items) : array();
			//
			// Do not deactivate plugins which are already deactivated.
			if (is_network_admin()) {
				$plugins = array_filter($plugins, 'is_plugin_active_for_network');
			}
			else {
				// $plugins = array_filter($plugins, 'is_plugin_active'); // filter only plugin active
				// $plugins = array_diff($plugins, array_filter($plugins, 'is_plugin_active_for_network'));
				// var_dump($plugins);
				// exit;
				//
				foreach ($plugins as $i => $plugin) {
					// Only deactivate plugins which the user can deactivate.
					if (!current_user_can('deactivate_plugin', $plugin)) {
						unset($plugins[$i]);
					}
				}
			}
			//
			if (empty($plugins)) {
				wp_redirect(self_admin_url("plugins.php?plugin_status=$plugin_status&paged=$page&s=$s"));
				exit;
			}
			//
			$destination = wp_upload_dir();
			$destination_path = $destination['basedir']."/pppt/";
			if (!file_exists($destination_path)) {
				if (!wp_mkdir_p($destination_path)) {
					$admin_notices[] = '
						<div class="notice notice-warning is-dismissible">
							<p>'.__('Sorry, the archive destination path is not writable.', 'pppt').'</p>
						</div>
					';
				}
				else {
					$admin_notices[] = '
						<div class="notice notice-success is-dismissible">
							<p>'.__('The archive destination path was created with success.', 'pppt').'</p>
						</div>
					';
				}
			}

			// security fix v1.0.1
			// prevent directory listing
			if (!file_exists($destination_path.'.htaccess')) {
				$htaccess_content = '<IfModule mod_autoindex.c>
Options -Indexes
</IfModule>';
				file_put_contents($destination_path.'.htaccess', $htaccess_content);
			}

			//
			deactivate_plugins($plugins, false, is_network_admin());
			//
			$deactivated = array();
			foreach ($plugins as $plugin) {
				$deactivated[$plugin] = time();
			}
			//
			if (!is_network_admin()) {
				update_option('recently_activated', $deactivated+(array) get_option('recently_activated'));
			}
			else {
				update_site_option('recently_activated', $deactivated+(array) get_site_option('recently_activated'));
			}
			//
			// $admin_notices[] = '
			// 	<div class="notice notice-success is-dismissible">
			// 		<p>'.__('Selected plugins <strong>deactivated</strong>.').'</p>
			// 	</div>
			// ';			
			//
			foreach($plugins as $plugin) {
				$plugin_current_dir_path = WP_PLUGIN_DIR."/".plugin_dir_path($plugin);
				// $plugin_destination_dir_path = $destination_path.basename(plugin_dir_path($plugin)).".zip";
				$plugin_destination_dir_path = $destination_path.plugin_dir_path($plugin);
				if (!wp_mkdir_p($plugin_destination_dir_path)) {
					$admin_notices[] = '
						<div class="notice notice-error is-dismissible">
							<p>'.sprintf(__('Sorry, the archive destination path for <strong>%s</strong> is not writable.', 'pppt'), basename(plugin_dir_path($plugin))).'</p>
						</div>
					';
				}
				else {
					//
					// $result = $this->zip_dir($plugin_current_dir_path, $plugin_destination_dir_path);
					copy_dir($plugin_current_dir_path, $plugin_destination_dir_path);
					// if (is_array($result)) {
					// 	$admin_notices[] = '
					// 		<div class="notice notice-error is-dismissible">
					// 			<p>'.current($result).'</p>
					// 		</div>
					// 	';
					// }
					// else {
						$admin_notices[] = '
							<div class="notice notice-success is-dismissible">
								<p>'.sprintf(__('Plugin <strong>%s</strong> has been <strong>archived</strong>.', 'pppt'), basename(plugin_dir_path($plugin))).'</p>
							</div>
						';
					// }
				}
			}
			//
			$delete_result = delete_plugins($plugins);
			// $admin_notices[] = '
			// 	<div class="notice notice-success is-dismissible">
			// 		<p>'.__('Selected plugins <strong>deleted</strong>.', 'pppt').'</p>
			// 	</div>
			// ';
			//
			$admin_notices = array_unique($admin_notices);
			//
			set_transient('plugins_archive_result_post_notice_'.$user_ID, $admin_notices);
			//
			wp_redirect(self_admin_url("plugins.php?plugin_status=$plugin_status&paged=$page&s=$s"));
			exit;
		}
		//
		return $redirect_url;
	}

	/**
	 * zip directory plugin and copy to another dir
	 *
	 * function based on https://lampjs.wordpress.com/2016/01/29/php-create-zip-from-directory-recursively/
	 *
	 * @since    1.0.0
	 */
	public function process_manage_archives() {
		global $user_ID;
		//
		global $wp_filesystem;
		// Initialize the WP filesystem, no more using 'file-put-contents' function
		if (empty($wp_filesystem)) {
			require_once (ABSPATH.'/wp-admin/includes/file.php');
			WP_Filesystem();
		}
		//
		if (isset($_REQUEST['type_action']) && in_array($_REQUEST['type_action'], array('archive', 'restore', 'delete'))) {
			//
			if ($_REQUEST['type_action'] == 'archive') {
				$this->process_option_plugins_dropdown(null, 'archive-selected', array($_REQUEST['plugin']));
				exit;
			}
		}
		//
		$plugin_status = !empty($_REQUEST['plugin_status']) ? esc_attr($_REQUEST['plugin_status']) : "";
		$paged = !empty($_REQUEST['paged']) && is_numeric($_REQUEST['paged']) ? sanitize_key($_REQUEST['paged']) : "";
		$s = !empty($_REQUEST['s']) ? esc_html($_REQUEST['s']) : "";
		//
		if (!isset($_REQUEST['archive']) && isset($_REQUEST['plugin'])) {
			$_REQUEST['archive'] = array(esc_html($_REQUEST['plugin']));
		}
		//
		$admin_notices = array();
		//
		$destination = wp_upload_dir();
		$destination_path = $destination['basedir']."/pppt/";
		//
		if (isset($_REQUEST['pppt_action_archives_nonce'])) {
			//
			if (!wp_verify_nonce($_REQUEST['pppt_action_archives_nonce'], 'pppt_action_archives')) {
			     wp_die(__('You are not allowed to make this.', 'pppt')); 

			}
			else {
				//
				if (is_array($_REQUEST['archive']) && sizeof($_REQUEST['archive']) > 0) {
					foreach($_REQUEST['archive'] as $plugin) {
						//
						$plugin = esc_html($plugin);
						$_REQUEST['type_action'] = sanitize_key($_REQUEST['type_action']);
						//
						if ($_REQUEST['type_action'] == 'restore') {
							$plugin_current_dir_path = WP_PLUGIN_DIR."/".plugin_dir_path($plugin);
							$plugin_destination_dir_path = $destination_path.plugin_dir_path($plugin);
							if (!wp_mkdir_p($plugin_current_dir_path)) {
								$admin_notices[] = '
									<div class="notice notice-error is-dismissible">
										<p>'.sprintf(__('Sorry, the plugin destination path for <strong>%s</strong> is not writable.', 'pppt'), basename(plugin_dir_path($plugin))).'</p>
									</div>
								';
							}
							else {
								//
								copy_dir($plugin_destination_dir_path, $plugin_current_dir_path);
								//
								$admin_notices[] = '
									<div class="notice notice-success is-dismissible">
										<p>'.sprintf(__('Plugin <strong>%s</strong> has been <strong>restored</strong>.', 'pppt'), basename(plugin_dir_path($plugin))).'</p>
									</div>
								';
								//
								require_once (ABSPATH.'/wp-admin/includes/class-wp-filesystem-direct.php');
								$fileSystemDirect = new WP_Filesystem_Direct(false);
								$fileSystemDirect->rmdir($plugin_destination_dir_path, true);
							}
						}
						//
						if ($_REQUEST['type_action'] == 'delete') {
							$plugin_destination_dir_path = $destination_path.plugin_dir_path($plugin);
							//
							$admin_notices[] = '
								<div class="notice notice-success is-dismissible">
									<p>'.sprintf(__('Plugin <strong>%s</strong> has been <strong>deleted</strong>.', 'pppt'), basename(plugin_dir_path($plugin))).'</p>
								</div>
							';
							//
							require_once (ABSPATH.'/wp-admin/includes/class-wp-filesystem-direct.php');
							$fileSystemDirect = new WP_Filesystem_Direct(false);
							$fileSystemDirect->rmdir($plugin_destination_dir_path, true);
						}
					}
				}
				//
				$admin_notices = array_unique($admin_notices);
				//
				set_transient('plugins_archive_result_post_notice_'.$user_ID, $admin_notices);
				//
				wp_redirect(self_admin_url("plugins.php?plugin_status=$plugin_status&paged=$page&s=$s"));
				exit;
			}
		}
	}

	/**
	 * zip directory plugin and copy to another dir
	 *
	 * function based on https://lampjs.wordpress.com/2016/01/29/php-create-zip-from-directory-recursively/
	 *
	 * @since    1.0.0
	 */
	public function zip_dir($source, $destination) {
		//
		$basename_plugin = basename($source);
		//
		$admin_notices = array();
	    //
	    if (!extension_loaded('zip')) {
	        $admin_notices[] = __('Zip extension not loaded in PHP.', 'pppt');
	        return $admin_notices;
	    }
		//
	    if (!file_exists($source)) {
	        $admin_notices[] = sprintf(__('Plugin directory source <strong>%s</strong> does not exist.', 'pppt'), $basename_plugin);
	        return $admin_notices;
	    } 
	    //
	    $destination_dir = dirname($destination);
	    if (!file_exists($destination_dir)) {
	        $admin_notices[] = sprintf(__('Destination directory <strong>%s</strong> does not exist.', 'pppt'), $destination_dir);
	        return $admin_notices;
	    }
	  	//
	    $zip = new ZipArchive();
	    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
	        $admin_notices[] = sprintf(__('Failed to create zip file on destination : <strong>%s</strong>.', 'pppt'), $destination);
	        return $admin_notices;
	    }
	  	//
	    $source = str_replace('\\', '/', realpath($source));
	  	//
	    if (is_dir($source) === true) {
	        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
	  		//
	        foreach ($files as $file) {
	            $file = str_replace('\\', '/', $file);
	            // Ignore "." and ".." folders
	            if (in_array(substr($file, strrpos($file, '/')+1), array('.', '..')))
	                continue;
				//	  
	            $file = realpath($file);
	  			//
	            if (is_dir($file) === true) {
	                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
	            }
	            else if (is_file($file) === true) {
	                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
	            }
	        }
	    }
	    else if (is_file($source) === true) {
	        $zip->addFromString(basename($source), file_get_contents($source));
	    } 
	    //
	    $zip->close();
	    //
	    return 1;
	}
}
