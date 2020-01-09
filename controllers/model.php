<?php
include 'data-magic.php';
class Model extends EndPoint
{
    private $debug, $errors, $audit_trail;
    public function __construct($debug = NULL)
    {
        $this->debug = $debug;
        parent::__construct($this->debug);
        $this->flagRequest($_SERVER);
        $this->audit_trail = new AuditTrail($this->debug);
        $this->errors = array();
    }

    protected function validateModel($model, $valid_model)
    {
        $errors = array();
        if (isset($valid_model) && isset($model)) {
            foreach ($valid_model as $vKey => $vValue) {
                $fAttrib = explode("|", $vValue);
                if (isset($model[$vKey])) {
                    if (!empty($fAttrib[1]) && strlen($model[$vKey]) < $fAttrib[1]) {
                        array_push($errors, $vKey . " is too short.");
                    }
                    if (!empty($fAttrib[2]) &&  strlen($model[$vKey]) > $fAttrib[2]) {
                        array_push($errors, $vKey . " is too long.");
                    }
                } else {
                    array_push($errors, $vKey . " should not be blank.");
                }
            }
            if (count($errors) > 0) {
                return array(
                    "success" => false,
                    "status_code" => 0,
                    "status_message" => 'Failed.',
                    "errors" => $errors,
                    "message" => "Error in inputs."
                );
            } else {
                return array(
                    "success" => true,
                    "status_code" => 1,
                    "status_message" => 'Success.',
                    "errors" => null,
                    "message" => "Valid inputs."
                );
            }
        } else {
            array_push($errors, "Invalid inputs.");
            return array(
                "success" => false,
                "status_code" => 0,
                "status_message" => 'Failed.',
                "errors" => $errors,
                "message" => "Invalid inputs."
            );
        }
    }

    protected function create($table, $model)
    {
        $model["code"] = $this->utils->generateRandom(11111, 99999, 5) . ':' . $this->dates->timeStamp();
        $model["createdOn"] = $this->dates->getDateTimeNow();
        $model["token"] = $this->utils->createToken();
        if(isset($model["password"])){
            $model["password"] = $this->utils->encryptString($model["password"]);
        }
        if(isset($this->userModel) && count($this->userModel) > 0) {
            $model["createdById"] = $this->userModel["token"];
        }
        $dbRes = $this->db->insert($table, $model);
        if($dbRes[0] == 1){
            $this->audit_trail->createLog($this->userModel, 'INSERT', 'Created new user.', $model);
            return array(
                "success" => true,
                "status_code" => 1,
                "status_message" => 'Success.',
                "errors" => null,
                "message" => "Record created."
            );
        }
        array_push($this->errors, $dbRes[1]);
        $this->audit_trail->createLog($this->userModel, 'INSERT', 'Tried to create a new user, but failed with error. ' . $dbRes[1], $model);
        return array(
            "success" => true,
            "status_code" => 0,
            "status_message" => 'Failed.',
            "errors" => $this->errors,
            "message" => "Failed to create a record."
        );
    }

    protected function update($table, $model, $keys)
    {
    }

    protected function view($table, $payLoad)
    {
    }

    protected function fetch($table, $payLoad)
    {
    }

    protected function authDetails($payLoad)
    {

    }
}

class AuditTrail extends EndPoint {

    public function __construct($debug = NULL)
    {
        $this->debug = $debug;
        parent::__construct($this->debug);
    }
    public function createLog ($userModel, $actionType, $description, $model) {
        if(isset($userModel) && count($userModel) > 0) {
            $userModel = $userModel;
        }else{
            $userModel = array(
                "token"=>"0000_alesto_maps_8743543"
            );
        }

        $logStamp = array(
            "code"=>$this->utils->generateRandom(11111, 99999, 5) . ':' . $this->dates->timeStamp(),
            "userId"=>$userModel["token"],
            "actionType"=>$actionType,
            "description"=>$description,
            "model"=>$model,
            "createdOn"=>$this->dates->getDateTimeNow()
        );
        $this->db->insert('audit_trail', $logStamp);
    }
}
