<?php   
    class XitUtils {
        public function encryptString($string){
            return md5(sha1(sha1($string)));
        }
        
        public function createToken (){
            return sha1(sha1(md5(uniqid())));
        }

        public function generateRandom($Start, $End, $Length){
            $Number = rand($Start, $End);
            if(strlen($Number) != $Length){
                $Number = $this->generateRandom($Start, $End, $Length); 
            }
            return $Number;
        }
    }