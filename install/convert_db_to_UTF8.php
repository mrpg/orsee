<?php
// upgrade database to UTF-8 and orsee-2.2.0

// table fields to be updated. Add any custom fields if needed to the first two rows.
$table_fields=array(
'participants'=>array('id'=>'participant_id','fields'=>array('phone_number','lname','fname','remarks','address_street','address_zip','address_city','address_country')),
'participants_temp'=>array('id'=>'participant_id','fields'=>array('phone_number','lname','fname','remarks','address_street','address_zip','address_city','address_country')),
'participants_log'=>array('id'=>'log_id','fields'=>array('target')),
'lang'=>array('id'=>'lang_id','fields'=>'languages'),
'admin'=>array('id'=>'admin_id','fields'=>array('fname','lname')),
'admin_log'=>array('id'=>'log_id','fields'=>array('target')),
'admin_types'=>array('id'=>'type_id','fields'=>array('type_name')),
'bulk_mail_texts'=>array('id'=>'bulktext_id','fields'=>array('bulk_subject','bulk_text')),
'cron_log'=>array('id'=>'log_id','fields'=>array('target')),
'experiment_types'=>array('id'=>'exptype_id','fields'=>array('exptype_name','exptype_description')),
'experiments'=>array('id'=>'experiment_id','fields'=>array('experiment_name','experiment_public_name','experiment_description')),
'lab_space'=>array('id'=>'space_id','fields'=>array('reason')),
'options'=>array('id'=>'option_id','fields'=>array('option_value')),
'sessions'=>array('id'=>'session_id','fields'=>array('session_remarks')),
'subpools'=>array('id'=>'subpool_id','fields'=>array('subpool_name','subpool_description')),
'uploads'=>array('id'=>'upload_id','fields'=>array('upload_name'))
);



include("../admin/cronheader.php");

// DROP SOME OBSOLETE TABLES;
$drop_tables=array('os_data_form','os_items_checkbox','os_items_radio',
'os_items_select_numbers','os_items_select_text','os_items_textarea',
'os_items_textline','os_page_content','os_playerdata','os_pre_answers',
'os_properties','os_questions','os_results','participants_os');

foreach($drop_tables as $t) {
	$query="DROP TABLE IF EXISTS ".table($t);
	$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
}

// PREPARE or_lang TABLE FOR UTF-8 
/// (indexes are limited to 1000 chars, 
// index needs 3xfield size under UTF-8)
$query="ALTER TABLE ".table('lang')." CHANGE content_type content_type VARCHAR(250) default NULL";
$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
$query="ALTER TABLE ".table('lang')." change content_name content_name VARCHAR(250) default NULL";
$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));

// CONVERT ALL TABLES TO UTF-8
$conv_tables=array('admin','admin_log','admin_types','bulk_mail_texts','cron_jobs',
'cron_log','experiment_types','experiments','faqs','http_sessions','lab_space',
'lang','mail_queue','options','participants','participants_log','participants_temp',
'participate_at','sessions','subpools','uploads','uploads_data');

foreach($conv_tables as $t) {
	$query="ALTER TABLE ".table($t)." CONVERT TO CHARSET utf8 COLLATE utf8_unicode_ci";
	$done=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
}

// UPDATE TABLE CONTENT AND ENCODE IN UTF-8

function detectUTF8($string)
{
        return preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
        )+%xs', $string);
}

	$langs=get_languages();
	foreach ($table_fields as $table=>$tabdata) {
		if(is_array($tabdata['fields'])) $fs=$tabdata['fields'];
		else $fs=$langs;
		$idvar=$tabdata['id'];
		
		$i=0; $j=0;
		$query="SELECT * FROM ".table($table);
		$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
		while ($line = mysqli_fetch_assoc($result)) {
    		foreach($fs as $f) {
    			if (!detectUTF8(stripslashes($line[$f]))) {
    				$utf=utf8_encode(stripslashes($line[$f]));
    				$query2="UPDATE ".table($table)." 
    					SET ".$f."='".mysqli_real_escape_string($GLOBALS['mysqli'],$utf)."' 
    					WHERE ".$idvar."='".$line[$idvar]."'";
    				$done=mysqli_query($GLOBALS['mysqli'],$query2) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
    				//echo $query2."\n\n";
    				$i++;
    			}
    		$j++;
    		}
    	}
		echo "Converted ".$i." out of ".$j." terms in ".count($fs)." fields in table ".table($table)." to UTF-8.\n";
	}

?>
