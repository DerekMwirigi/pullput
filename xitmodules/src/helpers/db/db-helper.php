<?php
    ini_set('display_errors', 1);
    ini_set('log_errors',true);

    require_once('adodb/adodb.inc.php');
    require_once('adodb/adodb-active-record.inc.php');

    class DatabaseHelper {
        private $debug, $db;

        public function __construct($debug = NULL, $conn_details){
            $this->debug = $debug;
            
            $db_type  = $conn_details['type'];
            $db_host  = $conn_details['host'].$conn_details['port'];
            $db_user  = $conn_details['user'];
            $db_pass  = $conn_details['pass'];
            $db_name  = $conn_details['name'];

            /* init database */
            $this->db = ADONewConnection($db_type);
            if(! @$this->db->Connect($db_host,$db_user,$db_pass,$db_name)){
                echo json_encode(array(
                    "success"=>false,
                    "errors"=>array("Could not connect to database."),
                    "status_code"=>0,
                    "status_message"=>'Failed.',
                    "message"=>"Database is gone.",
                    "data"=>null
                ));
                exit();
            }
            $this->db->debug = $this->debug;
            $this->db->SetFetchMode(ADODB_FETCH_ASSOC);
            ADODB_Active_Record::SetDatabaseAdapter( $this->db );
            /* end of iniit database */
        }

        private function sanitizePair ($pair){
            $model = array();
            foreach($pair as $key=>$value){
                if(is_array($value)) { $value_ = json_encode($value); }
                else if(is_object($value)) { $value_ = json_encode($value); }
                else { $value_ = $value; }
                $model[$key] = $value_;
            }
            return $model;
        }

        private function desanitizePair ($pair){
            $model = array();
            foreach($pair as $key=>$value){
                $value_ = json_decode($value, true);
                if($value_ == null || empty($value_)) { $value_ = $value; }
                $model[$key] = $value_;
            }
            return $model;
        }

        private function errorHandling (){
            $dbResModel = (json_decode(json_encode($this->db), true));
            $errorMessage = explode(' ', $this->db->errorMsg());
            switch($errorMessage[0]){
                case 'Duplicate':
                    return array(0, 'Seems like there is a similar record for ' . $errorMessage[count($errorMessage)-1]);
                default:
                    return array(0, $this->db->errorMsg(), null);
            }
        }

        public function insert ($entityName, $tableModel){
            try{
                unset($tableModel["id"]);
                if(empty($tableModel)) { return array(0, 'Missing values'); }
                $this->db->AutoExecute($entityName, $this->sanitizePair($tableModel), 'INSERT');
                if($this->db->errorMsg() == null){
                    return array(1, 'Inserted');
                }else{
                    return $this->errorHandling($this->db);
                }
            }catch(Exception $e){
                return array(500, $e->getMessage(), null);
            }
        }

        public function update ($entityName, $updateModel, $keyModel){
            try{
                if(empty($updateModel) || empty($keyModel)) { return array(0, 'Missing values'); }
                $sqlStatement = "";
                if(count($updateModel) > 0){
                    $currentItem = 0;
                    foreach($keyModel as $keyItem=>$valueItem)
                    {
                        $currentItem ++;
                        if($currentItem != count($keyModel) && count($keyModel) != 1){  $sqlStatement .= $keyItem . " = '". $valueItem . "' AND "; }else{  $sqlStatement .= $keyItem . " = '". $valueItem . "' "; }
                    }
                }
                $this->db->AutoExecute($entityName, $this->sanitizePair($updateModel), 'UPDATE', $sqlStatement);
                if($this->db->errorMsg() == null){
                    return array(1, 'Row updated');
                }else{
                    return $this->errorHandling();
                }
            }catch(Exception $e){
                return array(500, $e->getMessage(), null);
            }
        }

        public function delete ($entityName, $keyModel){
            if(!!empty($keyModel)) { return array(0, 'Missing values'); }
            try{
                $sqlStatement = "DELETE FROM $entityName WHERE ";
                if(!empty($keyModel)){
                    $currentItem = 0;
                    foreach($keyModel as $keyItem=>$valueItem)
                    {
                        $currentItem ++;
                        if($currentItem != count($keyModel) && count($keyModel) != 1){  $sqlStatement .= $keyItem . " = '". $valueItem . "' AND "; }else{  $sqlStatement .= $keyItem . " = '". $valueItem . "' "; }
                    }
                }
                $this->db->GetOne($sqlStatement);
                if($this->db->errorMsg() == null){
                    return array(1, 'Row updated');
                }else{
                    return $this->errorHandling();
                }
            }catch(Exception $e){
                return array(500, $e->getMessage(), null);
            }
        }

        public function executeNoQuery($sqlStatement){
            try{
                $this->db->GetOne($sqlStatement);
                if($this->db->errorMsg() == null){
                    return array(1, 'Query done.');
                }else{
                    return $this->errorHandling();
                }
            }catch(Exception $e){
                return array(500, $e->getMessage(), null);
            }
        }

        public function fetchItem ($entityName = NULL, $keyModel = NULL, $sqlStatement = NULL){
            try{
                if($entityName != NULL){
                    if($keyModel != NULL){
                        $sqlStatement = "SELECT * FROM " . $entityName . " WHERE ";
                        if(!empty($keyModel)){
                            $currentItem = 0;
                            foreach($keyModel as $keyItem=>$valueItem)
                            {
                                $currentItem ++;
                                if($currentItem != count($keyModel) && count($keyModel) != 1){  $sqlStatement .= $keyItem . " = '". $valueItem . "' AND "; }else{  $sqlStatement .= $keyItem . " = '". $valueItem . "' "; }
                            }
                        }else {
                            $sqlStatement = "SELECT * FROM " . $entityName;
                        }
                    }
                    else{
                        $sqlStatement = "SELECT * FROM " . $entityName;
                    }
                }
                $resultData = $this->db->GetOne($sqlStatement);
                if($this->db->errorMsg() == null){
                    if(!empty($resultData)){
                        return array(1, 'Found row.', $resultData);
                    }else{
                        return array(2, 'No items found.', null);
                    }
                }else{
                    return $this->errorHandling();
                }
            }catch(Exception $e){
                return array(500, $e->getMessage(), null);
            }
        }

        public function fetchRow ($entityName = NULL, $keyModel = NULL, $sqlStatement = NULL){
            try{
                if($entityName != NULL){
                    if($keyModel != NULL){
                        $sqlStatement = "SELECT * FROM " . $entityName . " WHERE ";
                        if(!empty($keyModel)){
                            $currentItem = 0;
                            foreach($keyModel as $keyItem=>$valueItem)
                            {
                                $currentItem ++;
                                if($currentItem != count($keyModel) && count($keyModel) != 1){  
                                    $sqlStatement .= $keyItem . " = '". $valueItem . "' AND "; 
                                }else{  
                                    $sqlStatement .= $keyItem . " = '". $valueItem . "' "; 
                                }
                            }
                        }else {
                            $sqlStatement = "SELECT * FROM " . $entityName;
                        }
                    }
                    else{
                        $sqlStatement = "SELECT * FROM " . $entityName;
                    }
                }
                $resultData = $this->db->GetRow($sqlStatement);
                if($this->db->errorMsg() == null){
                    if(!empty($resultData)){
                        return array(1, 'Found row.', $this->desanitizePair($resultData));
                    }else{
                        return array(2, 'No items found.', null);
                    }
                }else{
                    return $this->errorHandling();
                }
            }catch(Exception $e){
                return array(500, $e->getMessage(), null);
            }
        }

        public function fetchRows ($entityName = NULL, $keyModel = NULL, $likeliHood = NULL, $sqlStatementIn = NULL, $pageNo = NULL, $perPage = NULL){
            try{
                if($entityName != NULL){
                    if($keyModel != NULL){
                        $sqlStatement = "SELECT * FROM " . $entityName . " WHERE ";
                        if(!empty($keyModel)){
                            $currentItem = 0;
                            foreach($keyModel as $keyItem=>$valueItem)
                            {
                                $currentItem ++;
                                if($likeliHood == true){
                                    if($currentItem != count($keyModel) && count($keyModel) != 1){  $sqlStatement .= $keyItem . " LIKE ". $valueItem . ""; }else{  $sqlStatement .= $keyItem . " LIKE ". $valueItem . " "; }
                                }else{
                                    if($currentItem != count($keyModel) && count($keyModel) != 1){  $sqlStatement .= $keyItem . " = '". $valueItem . "' AND "; }else{  $sqlStatement .= $keyItem . " = '". $valueItem . "' "; }
                                }
                            }
                        }else {
                            $sqlStatement = "SELECT * FROM " . $entityName;
                        }
                    }
                    else{
                        $sqlStatement = "SELECT * FROM " . $entityName;
                    }
                    if($pageNo != NULL){
                        if($perPage != NULL){
                            $startPoint = ((($pageNo * $perPage) - $perPage));
                            $sqlStatement .= " LIMIT $startPoint, $perPage";
                        }else{
                            return array(0, 'No items found. An error in Statement', null);
                        }
                    }
                }
                if($sqlStatementIn != NULL){
                    $sqlStatement = $sqlStatementIn;
                }
                $resultData = $this->db->GetArray($sqlStatement);
                if($this->db->errorMsg() == null){
                    if(!empty($resultData)){
                        $resData = array();
                        foreach($resultData as $row){
                            array_push($resData, $this->desanitizePair($row));
                        }
                        return array(1, 'Found ' . count($resData) . ' rows.', $resData);
                    }else{
                        return array(2, 'No items found.', null);
                    }
                }else{
                    return $this->errorHandling();
                }
            }catch(Exception $e){
                return array(500, $e->getMessage(), null);
            }
        }

        public function executeStatement ($sqlStatement){
            try{
                $resultData = $this->db->GetArray($sqlStatement);
                if($this->db->errorMsg() == null){
                    if(!empty($resultData)){
                        $resData = array();
                        foreach($resultData as $row){
                            array_push($resData, $this->desanitizePair($row));
                        }
                        return array(1, 'Found ' . count($resData) . ' rows.', $resData);
                    }else{
                        return array(2, 'No items found.', null);
                    }
                }else{
                    return $this->errorHandling();
                }
            }catch(Exception $e){
                return array(500, $e->getMessage(), null);
            }
        }

    }