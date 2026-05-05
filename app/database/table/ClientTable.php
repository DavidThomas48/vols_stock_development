<?php
namespace apptable;
use \lib\StdLib as lib; 
class ClientTable extends \fw\database\table\MySQLTable
{
	private $trace = false;
	public function init($db,$user_id="null") {
		if ($this->trace ) { echo 'Enter '.__METHOD__.'<br>'; }
		parent::init($db,$user_id);
		$this->fields = array(
			'id' 							=>"",// 00		
			'given_name'				 	=>"",// 01
			'family_name' 					=>"",// 02			
			'email' 						=>"",// 03				
			'phone' 						=>"",// 04				
			'address_street' 				=>"",// 05	
			'address_street2' 				=>"",// 06		
			'address_townsuburb' 			=>"",// 07	
			'address_state' 				=>"",// 08		
			'address_postcode'				=>"",// 09	
			'residence' 					=>"",// 10 
			'gender' 						=>"",// 11  
			'month_of_birth' 				=>"",// 12		
			'year_of_birth' 				=>"",// 13		
			'interpreter' 					=>"",// 14			
			'language' 						=>"",// 15			
			'country_of_birth' 				=>"",// 16	
			'aborigine_TSislander' 			=>"",// 17
			'represented_by' 				=>"",// 18	
			'carer_name' 					=>"",// 19		
			'concession_card' 				=>"",// 20
			'dietary' 						=>"",// 21  
			'comments' 						=>"",// 22
			'office_comments'	 			=>"",// 23
			'has_read_tandc'				=>"",// 24
			'tandc_signature'				=>"",// 25
		);
		if ( $this->trace ) { echo 'Leave '.__METHOD__.'<br>'; }
	}
	protected function beforeinsert(&$fnames,&$fvalues){
        $fnames .= ",registered_by";
        $fvalues .= ",".$this->user_id;
	}
	protected function beforeupdate(&$set){
		$set .= ", `modified_by` = ".$this->user_id;
	}
}

