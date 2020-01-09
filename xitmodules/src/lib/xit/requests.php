<?php   
    class XitRequests {
        public function cURLRequest($requestMethod = NULL, $requestData = NULL, $requestEndpointURL = NULL, $requestHeaders = NULL){
            $ch = curl_init($requestEndpointURL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            switch($requestMethod){
                case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                 if(!empty($requestData)){
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
                }
                break;
            }
            if (!empty($requestHeaders)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
            }
            $response = curl_exec($ch);
            if (curl_error($ch)) {
                return json_encode(array(null, "0", curl_error($ch)));
            }
            curl_close($ch);
            return $response;
        }

        public function pageContentHttp($requestMethod = NULL, $requestData = NULL, $requestEndpointURL = NULL, $requestHeaders = NULL){
            $ch = curl_init($requestEndpointURL);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestMethod); 
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                'Content-Type: application/json',                                                                                
                'Content-Length: ' . strlen(json_encode($requestData)))                                                                       
            );                                                                                                                   
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
        }
    }