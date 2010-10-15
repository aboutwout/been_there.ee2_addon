<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
	'pi_name'			=> 'Been There',
	'pi_version'		=> '2.0',
	'pi_author'			=> 'Wouter Vervloet',
	'pi_author_url'		=> '',
	'pi_description'	=> 'Tell a user if he has already viewed this particular entry.',
	'pi_usage'			=> Been_there::usage()
);

/**
* Been There Plugin class
*
* @package		  been_there-ee2_addon
* @version			2.0
* @author			  Wouter Vervloet <wouter@baseworks.nl>
* @license			http://creativecommons.org/licenses/by-sa/3.0/
*/
class Been_there {

	/**
	* Plugin return data
	*
	* @var	string
	*/
	var $return_data;

	/**
	* Mark the checked entry as viewed
	*
	* @var	string
	*/
  var $set = true;
  
	/**
	* Expire the cookie after a number of months. default = 3 months
	*
	* @var	integer
	*/
  var $expire = 3;

	/**
	* Data to return when plugin returns true
	*
	* @var	string
	*/
  var $yes = '';

	/**
	* Data to return when plugin returns false
	*
	* @var	string
	*/
  var $no = '';

	/**
	* PHP4 Constructor
	*
	* @see	__construct()
	*/
	function Been_there($entry_id=false)
	{
		$this->__construct($date);
	}


	/**
	* PHP5 Constructor
	*
	* @param	string	$date
	* @return	string
	*/
	function __construct($entry_id=false)
	{
	  $this->EE =& get_instance();
    

    if($entry_id === false)
    {
      $entry_id = $this->EE->TMPL->fetch_param('entry_id') ? $this->_entry_exists($this->EE->TMPL->fetch_param('entry_id')) : $this->_entry_exists();
    }
    
    if($entry_id === false) return;
    
    foreach ($this->EE->TMPL->var_pair as $key => $val)
    {
      if (preg_match("/yes|no/", $key)) {
        $this->$key = $this->EE->TMPL->fetch_data_between_var_pairs($this->EE->TMPL->tagdata, $key);
      }
    }
    
    if($this->yes == '')
    {
      $this->yes = $this->EE->TMPL->tagdata;
    }
    
    $this->expire = ( is_numeric($this->EE->TMPL->fetch_param('expire')) ) ? intval($this->EE->TMPL->fetch_param('expire')) : $this->expire;
    $this->set = ( $this->EE->TMPL->fetch_param('set') == 'no' ) ? false : true;



    if( $this->_get($entry_id) )
    {
      $this->return_data = $this->yes;
      return;
    }

    $this->_save($entry_id);

    $this->return_data = $this->no;

    return;
    
	}
	// END __construct

	/**
	* Check if an entry exists for the given parameter
	*
	* @param	string	$in entry_id or url_title of a weblog entry
	* @return	mixed [integer|boolean]
	*/
  function _entry_exists($in = false) {
    
    if($in === false)
    {
      $in = $this->EE->uri->query_string;
    }

    $query = "SELECT entry_id, url_title FROM exp_channel_titles WHERE entry_id = '$in' OR url_title = '$in'";
 	  $results = $this->EE->db->query($query);
    
    return ($results->num_rows() > 0) ? $results->row('entry_id') : false;    
  }
  // END _entry_exists
  
  function _get($entry_id=false)
  {
    if($entry_id)
    { 
      
      $cookie_entries = $db_entries = array();
      
      if( isset($_COOKIE["exp_been_there"]) ) {
        $cookie_entries = array_keys($_COOKIE["exp_been_there"]);
      }
      
      if(isset($this->EE->session->userdata['member_id']) && class_exists('Been_there_ext'))
      {
      
        $query = $this->EE->db->select('viewed_entries')->where('member_id', $this->EE->session->userdata['member_id'])->from('member_data')->get();
      
        if($query->num_rows() > 0)
        {
          $db_entries = array_filter( explode( '|', $query->row('viewed_entries') ) );        
        }
      }
      
      $viewed_entries = array_merge($cookie_entries, $db_entries);
            
      return in_array($entry_id, $viewed_entries);
    }
    
    return false;
  }
  
  function _save($entry_id=false)
  {

    if($entry_id && $this->set === true)
    {

      if(isset($this->EE->session->userdata['member_id']))
      {
        
        if(class_exists('Been_there_ext')) {
          
          $this->EE->db->select('viewed_entries')
                        ->where('member_id', $this->EE->session->userdata['member_id'])
                        ->from('member_data');
          $viewed_entries = $this->EE->db->get()->row('viewed_entries');
        
          $viewed_entries_arr = explode('|', $viewed_entries);        
        
          array_push($viewed_entries_arr, $entry_id);
        
          $viewed_entries_new = implode('|', array_filter($viewed_entries_arr));
        
          $sql = $this->EE->db->update_string('member_data', array('viewed_entries' => $viewed_entries_new), 'member_id = '.$this->EE->session->userdata['member_id']);
          $this->EE->db->query($sql);
        }
      }

      // Set a cookie with an expiration date 3 months in the future
      $this->EE->functions->set_cookie("been_there[$entry_id]", true, time() + ($this->expire * 2678400));
    }

  }

	/**
	* Plugin Usage
	*
	* @return	string
	*/    
	function usage()
	{
		ob_start(); 
		?>
		
			{exp:been_there entry_id='36' set='no'}
			  {yes}You have already seen this entry.{/yes}
			  {no}This is the first time seeing this entry.{/no}
			{/exp:been_there}
			
			or
			
			{exp:been_there expire='1'}
			  This entry_id has been auto discovered and you have already seen this entry. This setting will be reset after 1 month.
			{/exp:been_there}
			
		<?php
		$buffer = ob_get_contents();
  
		ob_end_clean(); 

		return $buffer;
	}
	// END usage

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file pi.been_there.php */