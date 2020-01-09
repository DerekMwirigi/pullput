<?php   
    class XitDates {
        public $monthNames = array(
            "", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
        );
        private $timeZones = array("Africa/Nairobi");
        public function __construct($debug = NULL, $timeZoneIndex = NULL){
            $timeZoneIndex = 0;
            date_default_timezone_set($this->timeZones[$timeZoneIndex]);
        }

        public function getDateTimeNow(){
            return date('Y-m-d H:i:s');
        }

        public function getDateNow(){
            return date('Y-m-d');
        }

        public function timeStamp (){
            $date = date('Y-m-d H:i:s');
            $date = str_replace('-', '', $date);
            $date = str_replace(' ', '', $date);
            $date = str_replace(':', '', $date);
            return $date;
        }

        public function getDateTimeDiff ($laterDate, $earlierDate){
            $datetime1 = new DateTime($laterDate);
            $datetime2 = new DateTime($earlierDate);
            $interval = $datetime1->diff($datetime2);
            return $interval->format('%Y-%m-%d %H:%i:%s');
        }

        public function subDays ($dateP, $days, $format = 'Y-m-d') {
            $date = date_create($dateP);
            date_sub($date, date_interval_create_from_date_string($days . ' days'));
            return date_format($date, $format);
        }

        public function addDays ($dateP, $days, $format = 'Y-m-d') {
            $date = date_create($dateP);
            date_add($date, date_interval_create_from_date_string($days));
            return date_format($date, $format);
        }

        public function getMonthDates ($month, $year) {
            $dates = array();
            $num_of_days = date('t', mktime (0,0,0,$month,1,$year));
            for( $i=1; $i <= $num_of_days; $i++) {
                if($i < 10) $i = '0' . $i;
                array_push($dates, $year . '-' . $month . '-' . $i);
            }
            return $dates;
        }

        public function getMonthName ($month_num) {
            return $this->monthNames[intval($month_num)];
        }

        function getDatesInRange($start, $end, $format = 'Y-m-d') { 
            $array = array(); 
            $startDate = strtotime($start); 
            $endDate = strtotime($end); 
            // Use for loop to store dates into array 
            // 86400 sec = 24 hrs = 60*60*24 = 1 day 
            for ($currentDate = $startDate; $currentDate <= $endDate; $currentDate += (86400)) {     
                $Store = date($format, $currentDate); 
                $array[] = $Store; 
            } 
            return $array; 
        } 
    }