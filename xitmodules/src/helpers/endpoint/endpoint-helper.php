<?php
require_once('../../xitmodules/src/helpers/db/db-helper.php');
require_once('../../xitmodules/src/lib/xit/dates.php');
require_once('../../xitmodules/src/lib/xit/requests.php');
require_once('../../xitmodules/src/lib/xit/utils.php');

class EndPoint
{
    private $debug, $errors, $apiconfig;
    protected $userModel;
    public $db, $dates, $requests, $utils;
    
    public function __construct($debug = NULL)
    {
        $this->debug = $debug;
        $this->errors = array();
        $this->userModel = array();
        $this->apiconfig = json_decode(file_get_contents("../../config/api.config.json"), true);
        $this->db = new DatabaseHelper($this->debug, json_decode(file_get_contents("../../config/db.config.json"), true));
        $this->dates = new XitDates();
        $this->requests = new XitRequests();
        $this->utils = new XitUtils();
    }

    protected function flagRequest($server)
    {
        $scriptDirectories = explode('/', $server["SCRIPT_NAME"]);
        if (isset($this->apiconfig["endpoints"][$scriptDirectories[count($scriptDirectories) - 2]][basename("/" . $server["SCRIPT_NAME"], ".php")])) {
            $apiModel = $this->apiconfig["endpoints"][$scriptDirectories[count($scriptDirectories) - 2]][basename("/" . $server["SCRIPT_NAME"], ".php")];
            if ($apiModel["method"] == $server["REQUEST_METHOD"]) {
                if (isset($apiModel["oauthToken"]) && $apiModel["oauthToken"] == 1) {
                    $authorization = $this->getAuthorization($server);
                    if ($authorization != null || !empty($authorization)) {
                        $authorization = explode(" ", $authorization)[1];
                        $dbRes = $this->verifyAuthorization($authorization);
                        if ($dbRes[0] == 1) {
                            $this->userModel = array(
                                "id" => $dbRes[2]["id"],
                                "token" => $dbRes[2]["token"],
                                "roleId" => $dbRes[2]["roleId"],
                                "accountType" => $dbRes["accountType"]
                            );
                            return array(
                                "success" => true,
                                "errors" => null,
                                "status_code" => 1,
                                "status_message" => 'Successful.',
                                "message" => "request flagged through.",
                                "data" => $dbRes[2]
                            );
                        } else {
                            array_push($this->errors, "Token is invalid or exipred.");
                        }
                    } else {
                        array_push($this->errors, "Token is missing.");
                    }
                } else {
                    return array(
                        "success" => true,
                        "errors" => null,
                        "status_code" => 1,
                        "status_message" => 'Sucess.',
                        "message" => "request flagged through.",
                        "data" => null
                    );
                }
            } else {
                array_push($this->errors, "Bad request method.");
            }
        } else {
            array_push($this->errors, "Bad request api not configured.");
        }
        echo json_encode(array(
            "success" => false,
            "errors" => $this->errors,
            "status_code" => 0,
            "status_message" => 'Failed.',
            "message" => "request not flagged through.",
            "data" => null
        ));
        exit();
    }

    protected function getAuthorization($server)
    {
        $authorization = 'null';
        if (isset($server['Authorization'])) {
            $authorization = trim($server["Authorization"]);
        } else if (isset($server['HTTP_AUTHORIZATION'])) {
            $authorization = trim($server["HTTP_AUTHORIZATION"]);
        } else if (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $authorization = trim($requestHeaders['Authorization']);
            }
        }
        return $authorization;
    }

    protected function verifyAuthorization($authorization)
    {
        foreach (json_decode(file_get_contents("../../models/auth.json"), true)["authorization_models"] as $accountType) {
            $dbRes = $this->db->fetchRow($accountType, array("token" => $authorization));
            if ($dbRes[0] == 1 && count($dbRes[2]) > 0) {
                $dbRes["accountType"] = $accountType;
                return $dbRes;
            }
        }
        return null;
    }
}
