<?php
if ( ! defined('EXT')) { exit('Invalid file request'); }

class Wb_freeform_campaign_monitor
{
	var $settings		= array();
	
	var $name           = 'WB Freeform Campaign Monitor';
	var $class_name     = 'Wb_freeform_campaign_monitor';
	var $version        = '1.0';
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
		return $settings;
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
		global $IN;
		
		// Array
		// (
		//     [author_id] => 0
		//     [group_id] => 3
		//     [ip_address] => 127.0.0.1
		//     [entry_date] => 1274812392
		//     [edit_date] => 1274812392
		//     [status] => open
		//     [form_name] => contest
		//     [name] => Wes Baker
		//     [email] => wcbaker@gmail.com
		//     [review_date] => 5/20/2010
		//     [apple_store_nickname] => wesbaker
		// )
		
		if ($IN->GBL($this->settings['switch_field'], 'POST') == $this->settings['switch_field_value'] &&
			$this->_valid_email($data[$this->settings['email_field']]) ) 
		{
			require_once('campaign-monitor/CMBase.php');

			$api_key = $this->settings['api_key'];
			$client_id = null;
			$campaign_id = null;
			$list_id = $this->settings['list_id'];
			$cm = new CampaignMonitor( $api_key, $client_id, $campaign_id, $list_id );

			$result = $cm->subscriberAdd($data[$this->settings['email_field']], $data[$this->settings['name_field']]);
		}
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