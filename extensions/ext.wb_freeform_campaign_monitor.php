<?php
if ( ! defined('EXT')) { exit('Invalid file request'); }

class Wb_freeform_campaign_monitor
{
	var $settings		= array();
	
	var $name           = 'WB Freeform Campaign Monitor';
	var $class_name     = 'Wb_freeform_campaign_monitor';
	var $version        = '1.3.1';
	var $description    = 'After receiving a form entry, sends it to Campaign Monitor';
	var $settings_exist = 'y';
	var $docs_url       = '';

	// --------------------------------
	//  Constructor
	// --------------------------------
	function __construct($settings='')
	{
		global $SESS;
		$this->settings = $settings;
		$this->_get_settings();
	}
	
	// --------------------------------
	//  Change Settings
	// --------------------------------  
	function settings()
	{
		global $LANG;
		$settings = array();
		$settings['api_key'] = '';
		$settings['list_id'] = '';
		$settings['switch_field'] = '';
		$settings['switch_field_value'] = '';
		$settings['name_field'] = 'name';
		$settings['email_field'] = 'email';
		$settings['custom_field_1'] = '';
		$settings['custom_field_1_tag'] = '';
		$settings['custom_field_2'] = '';
		$settings['custom_field_2_tag'] = '';
		$settings['custom_field_3'] = '';
		$settings['custom_field_3_tag'] = '';
		$settings['custom_field_4'] = '';
		$settings['custom_field_4_tag'] = '';
		return $settings;
	}
	
	public function settings_form()
	{
		global $DSP, $LANG, $IN, $PREFS;
	
		$settings = isset($this->settings[$PREFS->ini('site_id')]) ? $this->settings[$PREFS->ini('site_id')] : array();
	
		$DSP->crumbline = TRUE;
	
		$DSP->title	 = $LANG->line('extension_settings');
		$DSP->crumb	 = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities'));
		$DSP->crumb .= $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager', $LANG->line('extensions_manager')));
		$DSP->crumb .= $DSP->crumb_item($this->name);
	
		$DSP->right_crumb($LANG->line('disable_extension'), BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=toggle_extension_confirm'.AMP.'which=disable'.AMP.'name='.$IN->GBL('name'));
	
		$DSP->body = $DSP->form_open(
			array(
			'action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=save_extension_settings',
			'name'   => 'settings_wb_freeform_campaign_monitor',
			'id'     => 'settings_wb_freeform_campaign_monitor'
			),
			array('name' => get_class($this))
		);
		
		$DSP->body .= $DSP->table('tableBorder', '0', '', '100%');
		$DSP->body .= $DSP->tr();
		$DSP->body .= $DSP->td('tableHeadingAlt', '', '2');
		$DSP->body .= $this->name;
		$DSP->body .= $DSP->td_c();
		$DSP->body .= $DSP->tr_c();
	                
		$this->_build_row('api_key');
		$this->_build_row('list_id');
		$this->_build_row('switch_field');
		$this->_build_row('switch_field_value');
		$this->_build_row('name_field', 'name');
		$this->_build_row('email_field', 'email');
		$this->_build_row('custom_field_1');
		$this->_build_row('custom_field_1_tag');
		$this->_build_row('custom_field_2');
		$this->_build_row('custom_field_2_tag');
		$this->_build_row('custom_field_3');
		$this->_build_row('custom_field_3_tag');
		$this->_build_row('custom_field_4');
		$this->_build_row('custom_field_4_tag');
	                
		$DSP->body .= $DSP->table_c();
		$DSP->body .= $DSP->qdiv('itemWrapperTop', $DSP->input_submit());
		$DSP->body .= $DSP->form_c();
	}
	
	private function _build_row($item_name, $default = '')
	{
		global $DSP, $LANG, $PREFS;
		
		$settings = isset($this->settings[$PREFS->ini('site_id')]) ? $this->settings[$PREFS->ini('site_id')] : array();
		
		$DSP->body .= $DSP->tr();
			$DSP->body .= $DSP->td('tableCellOne', '45%');
				$DSP->body .= $DSP->qdiv('defaultBold', $LANG->line($item_name));
			$DSP->body .= $DSP->td_c();
	                
			$DSP->body .= $DSP->td('tableCellOne');
				$DSP->body .= $DSP->input_text($item_name, ( ! isset($settings[$item_name])) ? $default : $settings[$item_name]);
			$DSP->body .= $DSP->td_c();
		$DSP->body .= $DSP->tr_c();
	}
	
	public function save_settings()
	{
		global $DB, $PREFS;
		
		$this->settings[$PREFS->ini('site_id')] = array(
			'api_key'            => $this->_get_setting('api_key'),
			'list_id'            => $this->_get_setting('list_id'),
			'switch_field'       => $this->_get_setting('switch_field'),
			'switch_field_value' => $this->_get_setting('switch_field_value'),
			'name_field'         => $this->_get_setting('name_field', 'name'),
			'email_field'        => $this->_get_setting('email_field', 'email'),
			'custom_field_1'     => $this->_get_setting('custom_field_1'),
			'custom_field_1_tag' => $this->_get_setting('custom_field_1_tag'),
			'custom_field_2'     => $this->_get_setting('custom_field_2'),
			'custom_field_2_tag' => $this->_get_setting('custom_field_2_tag'),
			'custom_field_3'     => $this->_get_setting('custom_field_3'),
			'custom_field_3_tag' => $this->_get_setting('custom_field_3_tag'),
			'custom_field_4'     => $this->_get_setting('custom_field_4'),
			'custom_field_4_tag' => $this->_get_setting('custom_field_4_tag'),
		);
		
		$DB->query($DB->update_string('exp_extensions', array('settings' => serialize($this->settings)), 'class = "'.$this->class_name.'"'));
	}
	
	private function _get_setting($setting_name, $default = '')
	{
		return (isset($_POST[$setting_name]) AND $_POST[$setting_name]) ? $_POST[$setting_name] : $default;
	}
	
	private function _get_settings()
	{
		global $DB;
		
		$query =  $DB->query('SELECT settings FROM exp_extensions WHERE class = "' . $this->class_name . '";');
		$this->settings = unserialize($query->row['settings']);
	}
	
	
	// --------------------------------
	//  Activate Extension
	// --------------------------------
	function activate_extension()
	{
		global $DB, $PREFS;

		$hooks = array(
		  'freeform_module_insert_begin' => 'insert_new_entry'
		);
		
      foreach ($hooks as $hook => $method)
      {
         $sql[] = $DB->insert_string( 'exp_extensions', 
         array(
            'extension_id' => '',
            'class'        => get_class($this),
            'method'       => $method,
            'hook'         => $hook,
            'settings'     => "",
            'priority'     => 10,
            'version'      => $this->version,
            'enabled'      => "y"
            )
         );
      }

		// run all sql queries
		foreach ($sql as $query)
		{
			$DB->query($query);   
		}
		return TRUE;
	}
	
	
	// --------------------------------
	//  Disable Extension
	// -------------------------------- 
	function disable_extension()
	{
		global $DB;
		$DB->query("DELETE FROM exp_extensions WHERE class = '" . get_class($this) . "'");
	}
	
	// --------------------------------
	//  Update Extension
	// --------------------------------  
	function update_extension($current='')
	{
		global $DB;	
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		$DB->query("UPDATE exp_extensions
		            SET version = '".$DB->escape_str($this->version)."'
		            WHERE class = '".get_class($this)."'");
	}
	// END	
	// ============================================================================	

	public function insert_new_entry ($data)
	{		
		global $IN, $PREFS;
		
		$this->_get_settings();
		$settings = $this->settings[$PREFS->ini('site_id')];
		
		if ($IN->GBL($settings['switch_field'], 'POST') == $settings['switch_field_value'] &&
			$this->_valid_email($data[$settings['email_field']]) ) 
		{
			require_once('campaign-monitor/CMBase.php');

			$api_key = $settings['api_key'];
			$client_id = null;
			$campaign_id = null;
			$list_id = $settings['list_id'];
			$cm = new CampaignMonitor( $api_key, $client_id, $campaign_id, $list_id );

			$custom_field_values = array();
			$custom_fields = array('custom_field_1' => 'custom_field_1_tag', 
								   'custom_field_2' => 'custom_field_2_tag', 
								   'custom_field_3' => 'custom_field_3_tag', 
								   'custom_field_4' => 'custom_field_4_tag');
								
			foreach ($custom_fields as $field => $tag) {
				if (
					isset($settings[$field]) AND $settings[$field] != "" 
					AND isset($settings[$tag]) AND $settings[$tag] != ""
				) 
				{
					$custom_field_values[$settings[$tag]] = utf8_encode($data[$settings[$field]]);
				}
			}
			
			$result = $cm->subscriberAddAndResubscribeWithCustomFields(
				utf8_encode($data[$settings['email_field']]), 
				utf8_encode($data[$settings['name_field']]), 
				$custom_field_values
			);
		}
		
		return $data;
	}
	
	/**
	 * Validate an email address.
	 * Provide email address (raw input)
	 * Returns true if the email address has the email 
	 * address format and the domain exists.
	*/
	private function _valid_email($email)
	{
	   $isValid = true;
	   $atIndex = strrpos($email, "@");
	   if (is_bool($atIndex) && !$atIndex)
	   {
	      $isValid = false;
	   }
	   else
	   {
	      $domain = substr($email, $atIndex+1);
	      $local = substr($email, 0, $atIndex);
	      $localLen = strlen($local);
	      $domainLen = strlen($domain);
	      if ($localLen < 1 || $localLen > 64)
	      {
	         // local part length exceeded
	         $isValid = false;
	      }
	      else if ($domainLen < 1 || $domainLen > 255)
	      {
	         // domain part length exceeded
	         $isValid = false;
	      }
	      else if ($local[0] == '.' || $local[$localLen-1] == '.')
	      {
	         // local part starts or ends with '.'
	         $isValid = false;
	      }
	      else if (preg_match('/\\.\\./', $local))
	      {
	         // local part has two consecutive dots
	         $isValid = false;
	      }
	      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
	      {
	         // character not valid in domain part
	         $isValid = false;
	      }
	      else if (preg_match('/\\.\\./', $domain))
	      {
	         // domain part has two consecutive dots
	         $isValid = false;
	      }
	      else if
	(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
	                 str_replace("\\\\","",$local)))
	      {
	         // character not valid in local part unless 
	         // local part is quoted
	         if (!preg_match('/^"(\\\\"|[^"])+"$/',
	             str_replace("\\\\","",$local)))
	         {
	            $isValid = false;
	         }
	      }
	      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
	      {
	         // domain not found in DNS
	         $isValid = false;
	      }
	   }
	   return $isValid;
	}

/* END class */
}
/* End of file ext.wb_freeform_campaign_monitor.php */
/* Location: ./system/extensions/ext.wb_freeform_campaign_monitor.php */
