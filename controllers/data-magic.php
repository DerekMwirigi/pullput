<?php
    require_once('../../xitmodules/src/helpers/endpoint/endpoint-helper.php');
    class DataMagic extends EndPoint
    {
        private $debug, $configModel, $errors;
        
        public function __construct($debug = NULL)
        {
            $this->debug = $debug;
            parent::__construct($this->debug);
            $this->errors = array();
            $this->configModel = json_decode(file_get_contents("../../models/data-magic.json"), true);
        }

        public function magicView () {
            return $this->db->insert('test', array("fg"=>1));
        }
    }
