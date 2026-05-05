<?php
namespace apptable;
use \lib\StdLib as lib;
class UserTable extends \fw\database\table\MySQLTable
{
	private $trace = false;
	public function init($db,$user_id="null") {
		if ($this->trace ) { echo 'Enter '.__METHOD__.'<br>'; }
		parent::init($db,$user_id);
		$this->fields = array(
			"id"=>"",
			"given_name"=>"",
			"family_name"=>"",
			"display_name"=>"",
			"email"=>"",
			"mobile"=>"",
			"username"=>"",
			"receives_stock_alerts"=>"",
			"password"=>"",
			"isadmin"=>"",
			"hide_my_account"=>"",
			"menu_number"=>"",
		);
		if ( $this->trace ) { echo 'Leave '.__METHOD__.'<br>'; }
	}
	protected function beforeinsert(&$fnames,&$fvalues){
        $fnames .= ",registered_by";
        $fvalues .= ",".$this->user_id;
	}
	protected function beforeupdate(&$set){
		if (strpos($set,"modified_by") === false  && !empty($this->user)) {
			$set .= ", `modified_by` = ".$this->user_id;
		}
	}
}
