<?php
class Magic
{
    private $debug, $errors;
    public function __construct($debug = NULL)
    {
        $this->debug = $debug;
        $this->errors = array();
    }

    public function doMagic($chunk)
    {
        if ($this->debug == 1) {
            Logger::log('chunk', $chunk);
        }
        if (!empty($chunk) && isset($chunk["models"])) {
            $models = $chunk["models"];
            $success = 0;
            $fail = count($models);
            $modelCount = 0;
            foreach ($models as $table => $model) {
                $db = new DB();
                $dbRes = $db->createModelDB($this->modelCreateTableSQL($model));
                if ($dbRes[0] == 1) {
                    /* insert default data */
                    $db->insertModelData($model["meta"]["entityName"], $model["data"]);
                    $this->createFiles($model);
                    $success++;
                } else {
                    array_push($this->errors, 'Failed to create ' . $table . '. ' . $dbRes[1]);
                    $fail--;
                }
                $modelCount++;
            }

            if ($success == $modelCount) {
                $status = true;
                $status_code = 1;
                $status_message = 'Success';
                $message = $success . ' models created and database successfuly set. Check <a href="docs/" target="_blank">documentation</a>';
                $data = array();
            } else {
                $status = false;
                $status_code = 0;
                $status_message = 'Failed';
                $message = $fail . ' models failed and database not successfuly set.';
                $data = array();
            }
        } else {
            $status = false;
            $status_code = 0;
            $status_message = 'Failed';
            $message = 'Nothig to set up';
            $data = array();
        }
        return array("status" => $status, "status_code" => $status_code, "status_message" => $status_message, "message" => $message, "errors" => $this->errors, "data" => $data);
    }

    private function modelCreateTableSQL($model)
    {
        $queryString = 'CREATE TABLE IF NOT EXISTS ' . $model["meta"]["entityName"] . ' (';
        $fields = $model["fields"];
        $cf = 1;
        $hasPK = false;
        $pkField = '';
        foreach ($fields as $field) {
            $queryString .= '`' . $field["name"] . '` ' . $field["type"];
            if (isset($field["length"]) && isset($field["decimals"])) {
                $queryString .= '(' . $field["length"] . ',' . $field["decimals"] . ') ';
            } else if (isset($field["length"])) {
                $queryString .= '(' . $field["length"] . ') ';
            }
            if (isset($field["null"]) && $field["null"] == 0) {
                $queryString .= 'NOT NULL' . ' ';
            }
            if (isset($field["pk"]) && $field["pk"] == 1) {
                $hasPK = true;
                $pkField = $field["name"];
            }
            if (isset($field["autoIncreament"]) && $field["autoIncreament"] == 1) {
                $queryString .= 'AUTO_INCREMENT' . ' ';
            }
            if (isset($field["default"])) {
                $queryString .= "DEFAULT " . $field["default"] . " ";
            }
            if (isset($field["comment"])) {
                $queryString .= 'COMMENT ' . $field["comment"] . ' ';
            }
            if ($cf < count($fields)) {
                $queryString .= ',';
            }
            $cf++;
        }
        if ($hasPK) {
            $queryString .= ', PRIMARY KEY (`' . $pkField . '`)';
        }
        $queryString .= ');';
        if ($this->debug == 1) {
            Logger::log('queryString', $queryString);
        }
        return $queryString;
    }

    private function createFiles($model)
    {
        /* create model file */
        $this->createModelModelFiles($model);
        /* create controller file */
        $this->createModelControllerFiles($model);
        /* create endpoint files */
        $this->createModelEndpointFiles($model);
        /* create authorization files */
        $this->createModelForAuthorization($model);
    }

    private function createModelForAuthorization($model)
    {
        $auth_model = json_decode(file_get_contents('../../models/auth.json'), true);
        if (isset($model["meta"]["authorization"]) && $model["meta"]["authorization"] == 1) {
            if (isset($auth_model["authorization_models"])) {
                $authorization_models = $auth_model["authorization_models"];
                $found = false;
                foreach ($authorization_models as $authorization_model) {
                    if ($authorization_model == $model["meta"]["entityName"]) {
                        $found = true;
                    }
                }
                if (!$found) {
                    array_push($auth_model["authorization_models"], $model["meta"]["entityName"]);
                }
            } else {
                $auth_model["authorization_models"] = array($model["meta"]["entityName"]);
            }
        }
        Files::createFile('../../models/auth.json', json_encode($auth_model), $this->debug);
    }

    private function createModelControllerFiles($model)
    {
        $controllerFileTemplate = $this->createControllerFileFromTemplate($model);
        Files::createFile('../../controllers/' . $model["meta"]["entityId"] . '-' . $model["meta"]["entityName"] . '.php', $controllerFileTemplate, $this->debug);
    }

    private function createModelModelFiles($model)
    {
        /* models config */
        $model["valid_create_model"] = $this->configCreateModel($model);
        $model["valid_update_model"] = $this->configCreateModel($model);
        /* view model config */
        $model["view_model"] = $this->configViewModel($model);
        Files::createFile('../../models/' . $model["meta"]["entityId"] . '-' . $model["meta"]["entityName"] . '.json', json_encode($model), $this->debug);
    }

    private function createModelEndpointFiles($model)
    {
        $apiConfig = json_decode(file_get_contents('../../config/api.config.json'), true);
        /* check if endpoint - model - stack is registered */
        // if (!isset($apiConfig["endpoints"][$model["meta"]["entityId"] . '-' . $model["meta"]["entityName"]])) {
        //     $apiConfig["endpoints"][$model["meta"]["entityId"] . '-' . $model["meta"]["entityName"]] = array();
        //     foreach (array('create') as $file) {
        //         $apiConfig["endpoints"][$model["meta"]["entityId"] . '-' . $model["meta"]["entityName"]][$file] = array(
        //             "state" => 1,
        //             "url" => $model["meta"]["entityId"] . '-' . $model["meta"]["entityName"] . "/" . $file,
        //             "method" => "POST",
        //             "oauthToken" => 0
        //         );
        //     }
        // } else {
        foreach (array('create') as $file) {
            $oauthToken = 0;
            if ($file == 'create' || $file == 'update' || $file == 'delete') {
                $oauthToken = 1;
            }
            if (!isset($apiConfig["endpoints"][$model["meta"]["entityId"] . '-' . $model["meta"]["entityName"]][$file])) {
                $apiConfig["endpoints"][$model["meta"]["entityName"]][$file] = array(
                    "state" => 1,
                    "url" => $model["meta"]["entityName"] . "/" . $file,
                    "method" => "POST",
                    "oauthToken" => $oauthToken
                );
            }
            $dir = ('../../api/' . $model["meta"]["entityName"]);
            if (!is_dir($dir) && strlen($dir) > 0) {
                mkdir($dir, 0777, true);
            }
            $endpointFileTemplate = $this->createEndpointFileFromTemplate($file, $model);
            Files::createFile('../../api/' . $model["meta"]["entityName"] . '/' . $file . '.php', $endpointFileTemplate, $this->debug);
        }
        // }
        Files::createFile('../../config/api.config.json', json_encode($apiConfig), $this->debug);
    }

    private function createControllerFileFromTemplate($model)
    {
        $controllerFileTemplate =  file_get_contents("../src/templates/model.phpcontroller.template.xtmp");
        $controllerFileTemplate = str_replace('{entityName}', $model["meta"]["entityName"], $controllerFileTemplate);
        $controllerFileTemplate = str_replace('{entityId}', $model["meta"]["entityId"], $controllerFileTemplate);
        $controllerFileTemplate = str_replace('{tag}', $model["meta"]["tag"], $controllerFileTemplate);
        return $controllerFileTemplate;
    }

    private function createEndpointFileFromTemplate($file, $model)
    {
        $endpointFileTemplate =  file_get_contents("../src/templates/model." . $file . "-endpoint.template.xtmp");
        $endpointFileTemplate = str_replace('{entityName}', $model["meta"]["entityName"], $endpointFileTemplate);
        $endpointFileTemplate = str_replace('{entityId}', $model["meta"]["entityId"], $endpointFileTemplate);
        $endpointFileTemplate = str_replace('{tag}', $model["meta"]["tag"], $endpointFileTemplate);
        return $endpointFileTemplate;
    }

    private function configCreateModel($model)
    {
        $fields = $model["fields"];
        $columns = array();
        foreach ($fields as $field) {
            if (isset($field["null"]) && $field["null"] == 0 && $field["name"] != "id" && $field["name"] != "code") {
                if (isset($field["length"])) {
                    $columns[$field["name"]] = $field["type"] . "||" . $field["length"];
                } else {
                    $columns[$field["name"]] = $field["type"] . "||";
                }
            }
        }
        return $columns;
    }

    private function configViewModel($model)
    {
        $fields = $model["fields"];
        $columns = array();
        foreach ($fields as $field) {
            $columns[$field["name"]] = "";
        }
        return array(
            "entityId" => $model["meta"]["entityId"],
            "entityName" => $model["meta"]["entityName"],
            "columns" => $columns
        );
    }
}

class Files
{
    public static function createFile($fileName, $fileText, $debug = null)
    {
        if ($debug == 1) {
            Logger::log('queryString', array('fileName' => $fileName, 'fileText' => $fileText));
        }
        $myfile = fopen($fileName, "w") or die("Unable to open file!");
        fwrite($myfile, $fileText);
        fclose($myfile);
    }
}

class Logger
{
    public static function log($tag, $data)
    {
        echo $tag;
        print_r($data);
    }
}

require_once('../src/helpers/db/db-helper.php');
class DB extends DatabaseHelper
{
    public function __construct($debug = NULL)
    {
        $this->debug = $debug;
        parent::__construct($this->debug, json_decode(file_get_contents("../../config/db.config.json"), true));
    }

    public function createModelDB($queryString)
    {
        return $this->executeNoQuery($queryString);
    }

    public function insertModelData($entityName, $rows)
    {
        foreach ($rows as $row) {
            return $this->insert($entityName, $row);
        }
    }
}
