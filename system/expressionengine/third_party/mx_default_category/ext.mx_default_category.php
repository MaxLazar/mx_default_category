<?php

if ( ! defined( 'BASEPATH' ) ) exit( 'No direct script access allowed' );

require_once PATH_THIRD . 'mx_default_category/config.php';

/**
 * MX Default Category
 *
 * MX Default Category allows you to .
 *
 * @package  ExpressionEngine
 * @category Extension
 * @author    Max Lazar <max@eec.ms>
 * @copyright Copyright (c) 2013 Max Lazar (http://www.eec.ms)
 * @version 1.0
 */



class Mx_default_category_ext {
	var $settings        = array();

	var $addon_name      = MX_DEFAULT_CATEGORY_NAME;
	var $name            = MX_DEFAULT_CATEGORY_NAME;
	var $version         = MX_DEFAULT_CATEGORY_VER;
	var $description     = MX_DEFAULT_CATEGORY_DESC;
	var $settings_exist  = 'y';
	var $docs_url        = '';

	/**
	 * Defines the ExpressionEngine hooks that this extension will intercept.
	 *
	 * @since Version 1.0.0
	 * @access private
	 * @var mixed an array of strings that name defined hooks
	 * @see http://codeigniter.com/user_guide/general/hooks.html
	 * */

	private $hooks = array( 'cp_js_end' => 'cp_js_end' );

	// -------------------------------
	// Constructor
	// -------------------------------

	public function __construct( $settings=FALSE ) {
		$this->EE =& get_instance();

		// define a constant for the current site_id rather than calling $PREFS->ini() all the time
		if
		( defined( 'SITE_ID' ) == FALSE )
			define( 'SITE_ID', $this->EE->config->item( 'site_id' ) );

		// set the settings for all other methods to access
		$this->settings = ( $settings == FALSE ) ? $this->_getSettings() : $this->_saveSettingsToSession( $settings );
	}


	/**
	 * Prepares and loads the settings form for display in the ExpressionEngine control panel.
	 *
	 * @since Version 1.0.0
	 * @access public
	 * @return void
	 * */
	public function settings_form() {
		$channels = array();
		$categories =array();

		$this->EE->lang->loadfile( 'mx_default_category' );

		$this->EE->load->library( 'api' );
		$this->EE->api->instantiate( 'channel_structure' );
		$this->EE->api->instantiate( 'channel_categories' );

		$channel_q = $this->EE->api_channel_structure->get_channels();


		foreach ( $channel_q->result() as $c_row ) {
			$channels[$c_row->channel_id] = $c_row;

			$this->EE->api_channel_categories->category_tree( $c_row->cat_group );

			if ( count( $this->EE->api_channel_categories->categories ) > 0 ) {
				foreach ( $this->EE->api_channel_categories->categories as $val ) {
					$categories[$c_row->channel_id][$val['3']][] = $val;
				}
			}
		}


		// Create the variable array
		$vars = array(
			'addon_name' => $this->addon_name,
			'error' => FALSE,
			'input_prefix' => __CLASS__,
			'message' => FALSE,
			'settings_form' =>FALSE,
			'channel_data' => $channels,
			'categories' => $categories,
			'language_packs' => ''
		);



		$vars['settings'] = $this->settings;
		$vars['settings_form'] = TRUE;

		if ( $new_settings = $this->EE->input->post( __CLASS__ ) ) {
			$vars['settings'] = $new_settings;
			$this->_saveSettingsToDB( $new_settings );
			$this->_ee_notice( $this->EE->lang->line( 'extension_settings_saved_success' ) );
		}


		return $this->EE->load->view( 'form_settings', $vars, true );

	}
	// END

	/**
	 * _ee_notice function.
	 *
	 * @access private
	 * @param mixed   $msg
	 * @return void
	 */
	function _ee_notice( $msg ) {
		$this->EE->javascript->output( array(
				'$.ee_notice("'.$this->EE->lang->line( $msg ).'",{type:"success",open:true});',
				'window.setTimeout(function(){$.ee_notice.destroy()}, 3000);'
			) );
	}

	function cp_js_end() {

		$out = '';
		$json = '';

		if ( $this->EE->extensions->last_call !== FALSE ) {
			$out = $this->EE->extensions->last_call;
		}
		$out .= '$(function () {';
		$this->EE->load->helper( 'array' );

		parse_str( parse_url( @$_SERVER['HTTP_REFERER'], PHP_URL_QUERY ), $get );

		if ( element( 'D', $get ) == 'cp' && element( 'C', $get ) == 'content_publish' && element( 'M', $get ) == 'entry_form' && element( 'channel_id', $get ) ) {

			$settings =  $this->_getSettings();

			$channel_id = $this->EE->security->xss_clean( element( 'channel_id', $get ) );

			if  ( $channel_id != '' ) {

				if  ( $channel_id !== FALSE ) {
					$channel_id =  $channel_id;
				}
				if(isset($this->settings[$channel_id])) {

					foreach($this->settings[$channel_id] as $v => $k) {
							//$json .= $v.',';
							//$group .= $v.'|';
					}
					$json = implode(",", $this->settings[$channel_id]);
					$group = implode("|", $this->settings[$channel_id]);
				}

				$this->EE->load->library( 'api' );
				$this->EE->api->instantiate( 'channel_categories' );
			
				$this->EE->api_channel_categories->category_tree( implode("|", $this->settings[$channel_id]) );

				$category_tree = array();

				if ( count( $this->EE->api_channel_categories->categories ) > 0 ) {
					foreach ( $this->EE->api_channel_categories->categories as $val ) {
						 if($val[6] != '') {
							$category_tree[$val[6]][] = $val[0];
						 }
					}
				}
			}

			$json = trim($json, ',');
			$out .= 'var mx_default_category = [' . $json. '];';
			$out .= 'var mx_parents_child = \''.json_encode($category_tree).'\';';

			$out .= '$.each(mx_default_category, function(e){
						$("input[name=category[]][value=" + mx_default_category[e] + "]").prop("checked", true);	
					});

					$("#sub_hold_field_category").find("legend").each(function() {
						var old_ = $(this).html();
						$(this).html(old_ + \' <input type="checkbox" value="true" class="select_categories">\');
					});
					
			
					var result = $.parseJSON(mx_parents_child);

					$.each(result, function(k, v) {
					    $("input[name=category[]][value=" + k + "]").data("children", v).addClass("select_children");
					});	
					
					$("#sub_hold_field_category").on("click", ".select_children", function(e){
						var status = $(this).prop("checked");
						var children = $(this).data("children");
						$.each(children, function(k, v) {
							$("input[name=category[]][value=" + v + "]").prop("checked", status);		
						});
					});
					
	

					$(".select_categories").toggle(
								function(){
									$(this).parents("fieldset:first").find("input").each(function() {
										this.checked = true;
									});
								}, function (){
									$(this).parents("fieldset:first").find("input").each(function() {
										this.checked = false;
									});
								}
				   );
			';

		}



		$out .= '});';

		return $out;
	}

	// --------------------------------
	//  Activate Extension
	// --------------------------------

	function activate_extension() {
		$this->_createHooks();
	}

	/**
	 * Saves the specified settings array to the database.
	 *
	 * @since Version 1.0.0
	 * @access protected
	 * @param array   $settings an array of settings to save to the database.
	 * @return void
	 * */
	private function _getSettings( $refresh = FALSE ) {
		$settings = FALSE;
		if
		( isset( $this->EE->session->cache[$this->addon_name][__CLASS__]['settings'] ) === FALSE || $refresh === TRUE ) {
			$settings_query = $this->EE->db->select( 'settings' )
			->where( 'enabled', 'y' )
			->where( 'class', __CLASS__ )
			->get( 'extensions', 1 );

			if
			( $settings_query->num_rows() ) {
				$settings = unserialize( $settings_query->row()->settings );
				$this->_saveSettingsToSession( $settings );
			}
		}
		else {
			$settings = $this->EE->session->cache[$this->addon_name][__CLASS__]['settings'];
		}
		return $settings;
	}

	/**
	 * Saves the specified settings array to the session.
	 *
	 * @since Version 1.0.0
	 * @access protected
	 * @param array   $settings an array of settings to save to the session.
	 * @param array   $sess     A session object
	 * @return array the provided settings array
	 * */
	private function _saveSettingsToSession( $settings, &$sess = FALSE ) {
		// if there is no $sess passed and EE's session is not instaniated
		if
		( $sess == FALSE && isset( $this->EE->session->cache ) == FALSE )
			return $settings;

		// if there is an EE session available and there is no custom session object
		if
		( $sess == FALSE && isset( $this->EE->session ) == TRUE )
			$sess =& $this->EE->session;

		// Set the settings in the cache
		$sess->cache[$this->addon_name][__CLASS__]['settings'] = $settings;

		// return the settings
		return $settings;
	}


	/**
	 * Saves the specified settings array to the database.
	 *
	 * @since Version 1.0.0
	 * @access protected
	 * @param array   $settings an array of settings to save to the database.
	 * @return void
	 * */
	private function _saveSettingsToDB( $settings ) {
		$this->EE->db->where( 'class', __CLASS__ )
		->update( 'extensions', array( 'settings' => serialize( $settings ) ) );
	}
	/**
	 * Sets up and subscribes to the hooks specified by the $hooks array.
	 *
	 * @since Version 1.0.0
	 * @access private
	 * @param array   $hooks a flat array containing the names of any hooks that this extension subscribes to. By default, this parameter is set to FALSE.
	 * @return void
	 * @see http://codeigniter.com/user_guide/general/hooks.html
	 * */
	private function _createHooks( $hooks = FALSE ) {
		if ( !$hooks ) {
			$hooks = $this->hooks;
		}

		$hook_template = array(
			'class' => __CLASS__,
			'settings' =>'',
			'priority' => '1',
			'version' => $this->version,
		);


		foreach ( $hooks as $key => $hook ) {
			if ( is_array( $hook ) ) {
				$data['hook'] = $key;
				$data['method'] = ( isset( $hook['method'] ) === TRUE ) ? $hook['method'] : $key;

				$data = array_merge( $data, $hook );
			}
			else {
				$data['hook'] = $data['method'] = $hook;
			}

			$hook = array_merge( $hook_template, $data );
			//$hook['settings'] = serialize($hook['settings']);
			$this->EE->db->query( $this->EE->db->insert_string( 'exp_extensions', $hook ) );
		}
	}

	/**
	 * Removes all subscribed hooks for the current extension.
	 *
	 * @since Version 1.0.0
	 * @access private
	 * @return void
	 * @see http://codeigniter.com/user_guide/general/hooks.html
	 * */
	private function _deleteHooks() {
		$this->EE->db->query( "DELETE FROM `exp_extensions` WHERE `class` = '".__CLASS__."'" );
	}


	// END




	// --------------------------------
	//  Update Extension
	// --------------------------------

	function update_extension( $current='' ) {


		if ( $current == '' or $current == $this->version ) {
			return FALSE;
		}

		$this->EE->db->query( "UPDATE exp_extensions SET version = '".$this->EE->db->escape_str( $this->version )."' WHERE class = '".get_class( $this )."'" );
	}
	// END

	// --------------------------------
	//  Disable Extension
	// --------------------------------

	function disable_extension() {
		$this->EE->db->delete( 'exp_extensions', array( 'class' => get_class( $this ) ) );
	}
	// END
}

/* End of file ext.mx_default_category.php */
/* Location: ./system/expressionengine/third_party/mx_default_category/ext.mx_default_category.php */
