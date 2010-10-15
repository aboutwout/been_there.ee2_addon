<?php

/**
* @package ExpressionEngine
* @author Wouter Vervloet
* @copyright  Copyright (c) 2010, Baseworks
* @license    http://creativecommons.org/licenses/by-sa/3.0/
* 
* This work is licensed under the Creative Commons Attribution-Share Alike 3.0 Unported.
* To view a copy of this license, visit http://creativecommons.org/licenses/by-sa/3.0/
* or send a letter to Creative Commons, 171 Second Street, Suite 300,
* San Francisco, California, 94105, USA.
* 
*/

if ( ! defined('EXT')) { exit('Invalid file request'); }

class Been_there_ext
{
  public $settings            = array();
  
  public $name                = 'Been There';
  public $version             = 0.9;
  public $description         = "Enforce the presence of only one sticky per weblog.";
  public $settings_exist      = '';
  public $docs_url            = '';

	// -------------------------------
	// Constructor
	// -------------------------------
	function Been_there_ext($settings='')
	{
	  $this->__construct($settings);
	}
	
	function __construct($settings='')
	{	  
	  
	  $this->EE =& get_instance();
	  
		$this->settings = $settings;	
	}
	// END Been_there_ext
  
  function set_global_variable($session)
  { 
    
    $viewed_entries_str = '';
    
    if(isset($session->userdata['member_id']))
    {
      $member_id = $session->userdata['member_id'];
      
  		$this->EE->db->select('viewed_entries');
  		$this->EE->db->from('member_data');
  		$this->EE->db->where('member_id', $member_id);
  		$query = $this->EE->db->get();
  		
  		if($query->num_rows() > 0)
  		{
  		  $viewed_entries_str = $query->row('viewed_entries');
  		}
  		
    }
    
    if( isset($_COOKIE["exp_been_there"]) ) {
      $cookie_entries = array_keys($_COOKIE["exp_been_there"]);
      $db_entries = explode('|', $viewed_entries_str);
      $viewed_entries_str = implode( '|', array_unique(array_filter(array_merge($cookie_entries, $db_entries))) );
    }
    
    $this->EE->config->_global_vars['viewed_entries'] = $viewed_entries_str;

  }
  
	// --------------------------------
	//  Activate Extension
	// --------------------------------
	function activate_extension()
	{
	  
    $sql = array();

    /**
    * @todo
    */

    // hooks array
    $hooks = array(
      'sessions_end' => 'set_global_variable'
    );

    // insert hooks and methods
    foreach ($hooks AS $hook => $method)
    {
      // data to insert
      $data = array(
        'class'		=> get_class($this),
        'method'	=> $method,
        'hook'		=> $hook,
        'priority'	=> 1,
        'version'	=> $this->version,
        'enabled'	=> 'y',
        'settings'	=> ''
      );

      // insert in database
      $sql[] = $this->EE->db->insert_string('exp_extensions', $data);
      $sql[] = "ALTER TABLE `exp_member_data` ADD COLUMN `viewed_entries` text";
    }

    // run all sql queries
    foreach ($sql as $query) {
      $this->EE->db->query($query);
    }

    return true;
	}
	// END activate_extension
	 
	 
	// --------------------------------
	//  Update Extension
	// --------------------------------  
	function update_extension($current='')
	{
		
    if ($current == '' OR $current == $this->version)
    {
      return FALSE;
    }
    
    if($current < $this->version) { }

    // init data array
    $data = array();

    // Add version to data array
    $data['version'] = $this->version;    

    // Update records using data array
    $sql = $this->EE->db->update_string('extensions', $data, "class = '".get_class($this)."'");
    $this->EE->db->query($sql);
  }
  // END update_extension

	// --------------------------------
	//  Disable Extension
	// --------------------------------
	function disable_extension()
	{	

    $this->EE->db->where('class', __CLASS__);
    $this->EE->db->delete('extensions');
	
    // Delete column in member_data table
	  $this->EE->db->query("ALTER TABLE `exp_member_data` DROP COLUMN `viewed_entries`");
  }
  // END disable_extension

	 
}
// END CLASS
?>