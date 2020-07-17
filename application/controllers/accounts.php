<?php 
// ensure this file is being included by a parent file

if( !defined( 'SITE_URL' ) && !defined( 'SITE_DATE_FORMAT' ) ) die( 'Restricted access' );

class Accounts extends Booking {

	public $userBrands;

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Update the Account details
	 * The $account parameter contains all the necessary information for this account
	 * It verifies if a valid email and contact number have been parsed before processing the user request
	 * 
	 * @param stdClass $account
	 * 
	 * @return Bool
	 */
	public function updateAccount(stdClass $account){

        // update directory
        $uploadDir = 'assets/img/accounts/';

		if(!filter_var($account->email, FILTER_VALIDATE_EMAIL)) {
			return "invalid-email";
		}

		if(!preg_match("/^[0-9+]+$/", $account->phone)) {
			return "invalid-phone";
		}

		$params = [
			$account->name
		];

        // confirm that a logo was parsed
        if(isset($account->logo)) {

            // File path config 
            $fileName = basename($account->logo["name"]); 
            $targetFilePath = $uploadDir . $fileName; 
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

            // Allow certain file formats 
            $allowTypes = array('jpg', 'png', 'jpeg'); 
            
            // check if its a valid image
            if(!empty($fileName) && in_array($fileType, $allowTypes)){
                
                // set a new filename
                $fileName = $uploadDir . random_string('alnum', 25).'.'.$fileType;

                // Upload file to the server 
                if(move_uploaded_file($account->logo["tmp_name"], $fileName)){ 
                    $uploadedFile = $fileName;
                }
            }
        }

		// insert the brand information
		$stmt = $this->db->prepare("
			UPDATE `users_accounts` 
			SET 
				name = ?
                ".(isset($uploadedFile) ? ", account_logo='{$uploadedFile}'" : null)."
				".(!empty($account->city) ? ", city='{$account->city}'" : null)."
                ".(!empty($account->country) ? ", country='{$account->country}'" : null)."
                ".(!empty($account->phone) ? ", phone='{$account->phone}'" : null)."
				".(!empty($account->email) ? ", email='{$account->email}'" : null)."
			WHERE client_guid='{$account->clientId}'
		");

		$this->userLogs("account", $account->clientId, "Updated the Account details.", $account->userId, $account->clientId);

        if(isset($uploadedFile)) {
            return "refresh";
        } else {
            return $stmt->execute($params);
        }

	}

}

?>