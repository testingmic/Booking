<?php
/*
App Name: Booking Api
App URI: https://www.myschoollearner.com/
Version: 1.0.1
Author: Emmanuel Obeng, Emmallen Networks Ltd.
Author URI: https://www.github.com/emmallob
*/
// ensure this file is being included by a parent file
if( !defined( 'SITE_URL' ) ) die( 'Restricted access' );

class Booking {

	/* A globl variable to set for the table to query */
	public $tableName;

	/* The edit url variable that will be used in the loadDetails class */
	public $editURL;
	public $permitPage;

	/* This is the global value for the browser and platform to use by all methods */
	public $browser;
	public $platform;
	public $baseUrl;
	public $client_guid;
	public $user_guid;
	public $clientData;
	public $global_limit = 1000;
	public $max_attachment_size = 25;
	public $permission_denied = "Sorry! You do not have the required permissions to perform this action.";

	public function __construct() {
		global $booking, $session, $config;

		$this->db = $booking;
		$this->booking = $booking;
		$this->config = $config;
		$this->baseUrl = $config->base_url();
		$this->session = $session;
		$this->client_guid = $this->session->client_guid;
		$this->ip_address = ip_address();
		$this->baseUrl = $this->config->base_url();

		$this->user_agent = load_class('user_agent', 'libraries');
		$this->platform = $this->user_agent->platform();
		$this->browser = $this->user_agent->browser();
		$this->clientData = $this->clientData($this->client_guid);

		$this->fake_files = [
            "files" => [],
            "files_count" => 0,
            "files_size" => 0,
            "raw_size_mb" => 0
        ];

		$this->favicon_array = [
			'jpg' => 'fa fa-file-image', 'png' => 'fa fa-file-image',
			'jpeg' => 'fa fa-file-image', 'gif' => 'fa fa-file-image',
            'pjpeg' => 'fa fa-file-image', 'webp' => 'fa fa-file-image',
			'pdf' => 'fa fa-file-pdf', 'doc' => 'fa fa-file-word',
			'docx' => 'fa fa-file-word', 'mp3' => 'fa fa-file-audio',
			'mpeg' => 'fa fa-file-video', 'mpg' => 'fa fa-file-video',
			'mov' => 'fa fa-file-video', 'movie' => 'fa fa-file-video',
			'webm' => 'fa fa-file-video', 'flv' => 'fa fa-file-video',
			'qt' => 'fa fa-file-video', 'zip' => 'fa fa-archive',
			'txt' => 'fa fa-file-alt', 'csv' => 'fa fa-file-csv',
			'rtf' => 'fa fa-file-alt', 'xls' => 'fa fa-file-excel',
			'xlsx' => 'fa fa-file-excel', 'php' => 'fa fa-file-alt',
			'css' => 'fa fa-file-alt', 'ppt' => 'fa fa-file-powerpoint',
			'pptx' => 'fa fa-file-powerpoint', 'sql' => 'fa fa-file-alt',
			'json' => 'fa fa-file-alt', 
		];

		$this->accepted_attachment_file_types = [
			'jpg', 'png', 'jpeg', 'txt', 'pdf', 'sql', 'docx', 'doc', 'xls', 'xlsx', 'mpeg',
			'ppt', 'pptx', 'php', 'css', 'csv', 'rtf', 'gif', 'pub', 'json', 'zip', 
			'mpg', 'flv', 'webm', 'movie', 'mov', 'qt', 'pjpeg', 'webp'
		];
	}

	public function clientData($client_guid = null) {
		try {

			$client_guid = (!empty($client_guid)) ? $client_guid : $this->session->client_guid;

			$stmt = $this->db->prepare(
				"SELECT a.*, b.country_name
				FROM users_accounts a
				LEFT JOIN country b ON b.id = a.id WHERE a.client_guid='{$client_guid}'
			");
			$stmt->execute();

			$result = $stmt->fetch(PDO::FETCH_OBJ);

			$clientData = !empty($result) ? $result : null;

			return $clientData;
		} catch(PDOException $e) {
			return $e->getMessage();
		}
	}

	/**
	 * @method recordDetails($recordId, $whereClause)
	 * @desc This call returns the details of a single / multiple records in the database that meets the filter
	 * @return array
	 *
	 **/
	public function recordDetails($recordId=null, $columnNames = 'mn.*', $whereClause = 1, $joinClause = null) {

		global $config;

		try {

			// call the access level object
			$accessObject = load_class('accesslevel', 'controllers');
			$accessObject->user_guid = $this->user_guid;


			$filter = (!empty($recordId)) ? "mn.unique_id = '{$recordId}'" : null;
			$clientLimit = ($this->tableName == "countries") ? null : "mn.client_guid = '{$this->client_guid}' AND ";

			$stmt = $this->db->prepare("
				SELECT {$columnNames} FROM {$this->tableName} mn $joinClause WHERE $clientLimit {$filter} {$whereClause}
			");
			$stmt->execute();

			$results = [];
			$row = 0;

			if($stmt->rowCount() > 0) {
				while($result = $stmt->fetch(PDO::FETCH_OBJ)) {

					$row++;

					$result->editButton = '';
					$result->deleteButton = '';

					/* If the result set contains a unique_id then set the action, delete and edit buttons */
					if(isset($result->unique_id) && !empty($this->editURL)) {

						// confirm if the user has the permission to edit this item
						if($accessObject->hasAccess('view', $this->permitPage)) {
							$result->editButton = "-";
						}

						if($accessObject->hasAccess('update', $this->permitPage)) {
							$result->editButton = "<a class=\"btn btn-outline-success\" href=\"".$config->base_url("{$this->editURL}/{$result->unique_id}")."\"><i class=\"fa fa-edit\"></i></a>";
						}

						// confirm if the user has the permission to delete this item
						if($accessObject->hasAccess('delete', $this->permitPage)) {
							$result->deleteButton = " <a data-content=\"{$this->permitPage}\" data-value=\"{$result->unique_id}\" data-msg=\"Are you sure you want to delete this ".substr((ucfirst($this->permitPage)), 0, -1)."?\" class=\"btn btn-outline-danger delete-button\" href=\"javascript:void(0)\"><i class=\"fa fa-trash\"></i></a>";
						}

						$result->actionButton = $result->editButton . $result->deleteButton;
					}
					
					/* Set the row id for the result set that has been retrieved from the database */
					$result->row = $row;

					$results[] = $result;
				}
			}

			return $results;

		} catch(PDOException $e) {
			return [];
		}
	}

	/**
	 * @method lastRowId()
	 * @param $tableName The user needs to specify the table name for the query
	 * @return $rowId
	 **/
	public function lastRowId($tableName) {

		$stmt = $this->db->prepare("
				SELECT id AS rowId FROM {$tableName} WHERE 1
		");
		$stmt->execute();

		return ($stmt->rowCount() > 0) ? $stmt->fetch(PDO::FETCH_OBJ)->rowId : 0;
	}

	/**
	 * @method itemsCount($whereClause)
	 * @desc This method counts the number of rows found
	 * @return int
	 *
	 **/
	public function itemsCount($tableName, $whereClause = 1) {
		
		try {

			$stmt = $this->db->prepare("
				SELECT * FROM {$tableName} WHERE $whereClause AND client_guid = ?
			");
			$stmt->execute([$this->client_guid]);

			return $stmt->rowCount();

		} catch(PDOException $e) {
			return false;
		}

	}

	/**
	 * @method pushQuery($columns, $table, $whereClause)
	 * @desc Receives user query and returns the full data array
	 * 
	 * @return Object
	 **/
	public function pushQuery($columns = "*", $tableName, $whereClause = 1) {

		try {
			
			$stmt = $this->db->prepare("SELECT {$columns} FROM {$tableName} WHERE $whereClause");
			$stmt->execute();

			return $stmt->fetchAll(PDO::FETCH_OBJ);

		} catch(PDOException $e) {
			return [];
		}

	}

	public function justExecute($queryString) {
		try {

			$stmt = $this->db->prepare("$queryString");
			return $stmt->execute();

		} catch(PDOException $e) {
			return [];
		}
	}

	/**
	 * @method userLogs
	 * @param $page 	This is the page that the user is managing
	 * @param $item_guid	This relates to the item that is being managed
	 * @param $description This is the full description of what is being done
	 * @return null
	 *
	 **/
	final function userLogs($page, $item_guid, $description, $user_guid = null, $client_guid = null) {
		
		try {

			$ur_agent = $this->platform .' | '.$this->browser . ' | '.ip_address();

			$stmt = $this->db->prepare("
				INSERT INTO 
					users_activity_logs 
				SET 
					user_guid = ?, page = ?, item_guid = ?, description = ?, user_agent = ?, client_guid = ?
			");
			return $stmt->execute([($user_guid ?? $this->user_guid), $page, $item_guid, $description, $ur_agent, ($client_guid ?? $this->client_guid)]);

		} catch(PDOException $e) {
			return false;
		}

	}

	/**
	 * @method dataMonitoring
	 * @param string $data_type	This is the data that the user is updating (employee, leave)
	 * @param string $item_guid	This is the unique id that defines a recordset
	 * @param json $data_set 	This is a json encoded data of the initial record before update
	 * @return bool
	 **/
	final function dataMonitoring($data_type, $uniqueId, $data_set) : bool {

		try {

			$ur_agent = $this->platform .' | '.$this->browser . ' | '.ip_address();

			$stmt = $this->db->prepare("
				INSERT INTO 
					users_data_monitoring 
				SET 
					data_type = ?, item_guid = ?, data_set = ?, 
					user_guid = ?, user_agent = ?, client_guid = ?
			");
			return $stmt->execute([
				$data_type, $uniqueId, $data_set, $this->user_guid, $ur_agent, $this->client_guid
			]);

		} catch(PDOException $e) {
			return false;
		}

	}

	/**
	 * @method percentageCalculator
	 * @param totalAmount	This is the Amount that the percentage is to be calculated on
	 * @param percentageValue	This is the percentage value that is to be used for the calculation
	 * @return number_format of the value result
	 **/
	public function percentageCalculator($totalAmount, $percentageValue) {

		return number_format(
			(
				($percentageValue / 100 ) * $totalAmount), 2
			);

	}

	/**
	 * @method dateFormater
	 * @param string $dateParam
	 * @return date
	 **/
	public function dateFormater(array $dateParam) {
		$dateParam = (object)$dateParam;
		$date = ($dateParam->date) ?? date("Y-m-d");
		$period = ($dateParam->period) ?? "+1 days";
		$format = ($dateParam->format) ?? "Y-m-d";

		return date("$format", strtotime($date . " $period "));
	}

	/**
	 * @method allowedTime
	 * @desc Check if the User is within the time frame for logging an attendance
	 * @return bool
	 */
	final function allowedTime($openingHour = "5:00", $closinghour = "23:59") {
		
	    $currentTime = DateTime::createFromFormat('H:i', date("H:i"));
		$fromTime = DateTime::createFromFormat('H:i', $openingHour);
		$endTime = DateTime::createFromFormat('H:i', $closinghour);

		if ($currentTime > $fromTime && $currentTime < $endTime) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * @method listDays
	 * @desc It lists dates between two specified dates
	 * @param string $startDate 	This is the date to begin query from
	 * @param string $endDate	This is the date to end the request query
	 * @param string $format 	This is the format that will be applied to the date to be returned
	 * @return array
	 **/
	public function listDays($startDate, $endDate, $format='Y-m-d', $weekends = false) {

		$period = new DatePeriod(
		  new DateTime($startDate),
		  new DateInterval('P1D'),
		  new DateTime(date('Y-m-d',strtotime($endDate. '+1 days')))
		);

		$days = array();
		$sCheck = (array) $period->start;

		// check the date parsed
		if(date("Y-m-d", strtotime($sCheck['date'])) == "1970-01-01") {
			
			// set a new start date and call the function again
			return $this->listDays(date("Y-m-d", strtotime("first day of this month")), date("Y-m-d", strtotime("yesterday")));

			// exit the query
			exit;
		}
		
		// fetch the days to display
		foreach ($period as $key => $value) {

			if(!$weekends && (!in_array(date("l", strtotime($value->format($format))), ['Saturday', 'Sunday']))) {
				$days[] = $value->format($format);
			}

		}
		
		return $days;
	}

	/**
	 * @method stringToArray
	 * 
	 * @desc Converts a string to an array
	 * @param $string The string that will be converted to the array
	 * @param $delimeter The character for the separation
	 * 
	 * @return Array
	 */
	final function stringToArray($string, $delimiter = ",") {
		if(is_array($string)) {
			return $string;
		}

		$array = [];
		$expl = explode($delimiter, $string);
		foreach($expl as $each) {
			if(!empty($each)) {
				$array[] = trim($each);
			}
		}
		return $array;
	}

	/**
	 * Loop through the session and populate the total temporary email attachment size
	 * Convert the size to a much more readable version and parse back
	 * 
	 * @return String
	 */
	public function tempAttachmentsSize() {

		//: Process the email attachments
		if(!empty($this->session->tempAttachment) && is_array($this->session->tempAttachment)) {
			
			// calculate the file size
			$totalFileSize = 0;
			
			// using foreach loop to get the list of attached documents
			foreach($this->session->tempAttachment as $key => $values) {
				
				//: get the file size
				$n_FileSize = file_size_convert("assets/emails/tmp/{$values['item_id']}");
				$n_FileSize_KB = file_size("assets/emails/tmp/{$values['item_id']}");
				$totalFileSize += $n_FileSize_KB;
			}

			return round(($totalFileSize / 1024), 2);
		}

	}

	/**
	 * @method cleanLimit
	 * @desc This method takes the limit clause parsed in the query and formats it correctly
	 * @param string $limit 	This is the limit string that has been parsed
	 * @return string
	 **/
	final function cleanLimit($limit) {

		// process the string
		$limit = explode(',', $limit);
		$fPart = (isset($limit[0]) && ($limit[0] > -1)) ? (int) $limit[0] : 0;
		$lPart = (isset($limit[1]) && ($limit[1] > -1)) ? (int) $limit[1] : 25;

		$lPart = ($lPart != 0) ? $lPart : 25;

		$fPart = ($fPart > 100) ? 100 : $fPart;
		$lPart = ($lPart > 100) ? 100 : $lPart;

		return (!isset($limit[1])) ? $fPart : "$fPart,$lPart";
	}

	/**
	 * Verify if a string parsed is a valid date
	 * @param string $date 		This is the date string that has been parsed by the user
	 * @param string $format 	This is the format for that date to use
	 * @return bool
	 */
	public function validDate($date, $format = 'Y-m-d') {
	    $d = DateTime::createFromFormat($format, trim($date));
	    return $d && $d->format($format) === trim($date);
	}

	/**
	 * This method prepares a string to be used in a query
	 * This will format the user parameters to for a valid IN query
	 * 
	 * @param String $param 	This is the string that the user has parsed
	 * @param Array $compare 	This is the string to test the user's own against
	 * @param String $colum 	This is the column name
	 * 
	 * @return String
	 */
	public function inList($param) {

		if(empty($param)) {
			return $param;
		}

		$params = (is_array($param)) ? $param : $this->stringToArray($param);

		$string = '(';
		foreach($params as $item) {
			$string .= "'{$item}',";
		}
		$string = substr($string, 0, -1);
		$string .= ')';

		return $string; 
	}

	/**
	 * This method prepares a string to be used in a query
	 * This will format the user parameters to for a valid IN query
	 * 
	 * @param String $params 	This is the string that the user has parsed
	 * @param Array $compare 	This is the string to test the user's own against
	 * @param String $column 	This is the column name
	 * 
	 * @return String
	 */
	public function formatInQuery($param, array $compare, $column) {

		$params = (is_array($param)) ? $param : $this->stringToArray($param);

		if(count($params) > count($compare)) {
			return;
		}

		$string = '(';
		foreach($params as $item) {
			if(!in_array($item, $compare)) {
				return null;
				break;
			}

			$string .= "'{$item}',";
		}
		$string = substr($string, 0, -1);
		$string .= ')';

		return " AND $column IN $string"; 
	}

	/**
	 * Get the column value
	 * 
	 * @return Object
	 **/
	final function columnValue($column = "*", $tableName, $whereClause = 1) {

		try {

			$stmt = $this->db->prepare("SELECT {$column} FROM {$tableName} WHERE $whereClause LIMIT 1");
			$stmt->execute();

			return $stmt->fetch(PDO::FETCH_OBJ);

		} catch(PDOException $e) {
			return $e->getMessage();
		}

	}

	/**
	 * @method cleanDateRange
	 * @desc This method prepares and submits a clean date for processing
	 * @param string $date This is the date range that has been parsed
	 * @param string $prefix This is the SQL Query placeholder
	 **/
	final function cleanDateRange($date, $prefix) {

		// process the string
		$date = explode(':', $date);
		$fPart = (isset($date[0]) && $this->validDate($date[0])) ? $date[0] : '2020-05-01';
		$lPart = (isset($date[1]) && $this->validDate($date[1])) ? $date[1] : date('Y-m-d');

		if(!empty($date[1])) {
			return " AND (DATE($prefix.created_date) >= '{$fPart}' AND DATE($prefix.created_date) <= '{$lPart}')";
		} else {
			return " AND (DATE($prefix.created_date) = '{$fPart}')";
		}
	}

	/**
	 * This logs the user activity for trying to perform a suspected activity
	 *
	 * @param string $endpoint 		This is the activity that the user wants to perform
	 * @param string $tableName 	This is the name of the table that the activity was to be carried on
	 * @param array $invalids		The content of the data to be parsed that does not exist
	 * @param array $itemIds		This is the entire ids that have been parsed.
	 * @return bool
	 **/
	final function deleteBreach($endpoint, $tableName, array $invalids = [], array $itemIds = []) {
		
		try {

			// algorithm for severity
			$itemCount = count($itemIds);
			$invalidCount = count($invalids);

			$diff = $itemCount - $invalidCount;

			// find 30 percent of the entire list
			$thirtyPercent = round($itemCount * 0.3);

			// severity range
			if($diff >= $thirtyPercent) {
				$severity = "high";
			} else {
				$severity = "low";
			}

			// insert the record
			$stmt = $this->db->prepare("
				INSERT INTO breach_notifications
				SET request_method = ?, client_guid = ?, table_name = ?, severity = ?, suspected_ids = ?
			");
			return $stmt->execute([
				$endpoint, $this->client_guid, $tableName, $severity, json_encode($invalids)
			]);

		} catch(PDOException $e) {
			return false;
		}

	}

	/**
	 * Delete an item from the system. 
	 *
	 * @param String $itemId 	This is the item id that was parsed
	 * @param String $tableName This is the name of the table to effect the change on
	 * @param Array $itemIds	This is the array to filter
	 * 
	 * @return Bool
	 **/
	public function deleteItem($itemId, $tableName) {

		// convert the user string to an array
		$itemIds = $this->stringToArray($itemId, ',');

		$queryData = '';
		$error = false;
		$invalids = [];

		// loop through the array
		foreach($itemIds as $eachItem) {

			// confirm that the item already exists
			$prevData = $this->pushQuery("id, guid", $tableName, "guid='{$eachItem}' AND client_guid='{$this->client_guid}' AND status='1'");

			// confirm that the request returned some dataset
			if(!empty($prevData)) {

				// update the row information
				$queryData .= "UPDATE $tableName SET status = '0' WHERE guid = '{$eachItem}' AND client_guid = '{$this->client_guid}';";

			} else {
				$error = true;
				$invalids[] = $eachItem;
			}

		}

		// if at least one id parsed is invalid
		if($error) {
			// notify the admins of a possible breach of data
			$this->deleteBreach('delete', $tableName, $invalids, $itemIds);

			// return the invalid ids
			return $invalids;
		} else {
			$request = (!empty($queryData)) ? $this->db->query($queryData) : null;

			return !(empty($request)) ? 'successful' : null;
		}

	}

	public function statusChecker($status) {

		$status = strtolower($status);
		if(($status == 'active') || ($status == 1)) {
			return 1;
		} elseif(($status == 'inactive') || ($status == 0)) {
			return 0;
		}
	}

	/**
	 * This formats the post media properly
	 * 
	 * @param array $media 	This is the array of the media string
	 * @param int $quantity	This is the number of items to return
	 * @param string $type This is the type of data to return
	 * 
	 * @return $postImage
	 */
	public function postImage(array $media, $quantity = null, $type = 'all') {

		$i = 0;
		$limit = empty($quantity) ? 10000: (int) $quantity;
		$mediaData = [];

		foreach($media as $eachItem) {

			if(($type != 'all') && ($eachItem['type'] == $type)) {
				$mediaData[] = $eachItem['url'];
			} else {
				$mediaData[] = $eachItem;
			}
			
			$i++;
			if($i == $limit) {
				break;
			}
		}
		
		return $mediaData;
	}	

	/**
	 * Compare array and remove item from the list
	 * 
	 * @param String $arrayList		The list to loop through
	 * @param String $item			The value to find in the array list
	 * @param String $delimeter		The delimiter to use for converting the string to array
	 * 
	 * @return Array
	 */
	public function removeArrayValue($arrayList, $item, $delimeter = ",") {

		$arrayVariables = !is_array($arrayList) ? $this->stringToArray($arrayList, $delimeter) : $arrayList;
		$arrayKey = array_search($item, $arrayVariables);

		/** Remove the value from the array list */
		if(!empty($arrayKey)) {
			unset($arrayVariables[$arrayKey]);
		}

		return $arrayVariables;
	}

	/**
	 * Remove a record from the database table
	 * 
	 * @param stdClass 	$params				This object contains the item and its id to delete
	 * 					$params->item 		This refers to either a brand or user or any other item to remove
	 * 					$params->item_id	This is the unique id of the item to remove
	 * 					$params->client_guid	This is the unique id for the user account
	 * 
	 * @return String | Bool
	 */
	public function removeRecord(stdClass $params) {
		return;
	}

	/**
	 * Quick mode to add data to the database
	 * 
	 * @param String $table				This is the name of the table
	 * @param String $columnValues		This is a composite of the column names and their respective values
	 * 
	 * @return Bool
	 */
	public function addData($table, $columnValues) {
		$stmt = $this->db->prepare("INSERT INTO {$table} SET {$columnValues}");
		return $stmt->execute();
	}

	/**
	 * Quick mode to update data to the database
	 * 
	 * @param String $table				This is the name of the table
	 * @param String $columnValues		This is a composite of the column names and their respective values
	 * 
	 * @return Bool
	 */
	public function updateData($table, $columnValues, $where_clause) {
		$stmt = $this->db->prepare("UPDATE {$table} SET {$columnValues} WHERE {$where_clause}");
		return $stmt->execute();
	}

	/**
	 * Order Id format by adding zeros to the begining
	 * 
	 * @param String $requestId		This is the id to format
	 * 
	 * @return String
	 */
	public function serialIdFormat($requestId, $number = 6) {
		$preOrder = str_pad($requestId, $number, '0', STR_PAD_LEFT);
		return $preOrder;
	}

	/**
	 * Count the number of accounts that an email address is connected to
	 * 
	 * @param String $email 		The email address of the user
	 * 
	 * @return Int
	 */
	public function userAccountsCount($email) {

		try {

			$stmt = $this->db->prepare("SELECT COUNT(*) AS rows_count FROM users WHERE email= ? AND deleted='0'");
			$stmt->execute([$email]);

			return ($stmt->rowCount() > 0) ? $stmt->fetch(PDO::FETCH_OBJ)->rows_count : 0;
			
		} catch(PDOException $e) {
			return 0;
		}
	}

	/**
	 * Confirm that the user is online by checking the difference between the last_seen and the current time
	 * If the difference is 5 minutes or less then, the user is online if not then the user is offline
	 */
	public function user_is_online($last_seen) {
		// online algorithm (user is online if last activity is at most 3 minutes ago)
        return (bool) (raw_time_diff($last_seen) < 0.05);
	}

}
?>