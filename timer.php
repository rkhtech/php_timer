<?php

/**
 * Created by PhpStorm.
 * User: randy
 * Date: 3/31/16
 * Time: 7:54 AM
 */

/*

{
  "default": [],
  "rkh": {
    "archon": {
      "hourlyRate": 60.00,
      "timeBlock": 900,
      "currentStartTime": 0,
      "currentPauseTime": 0,
      "history": {
        "2018-03-20": {
          "totalTime": 8100,
          "hourlyRate": 60.00,
          "description": [
            "big long list of things I've done... pretty cool huh?"
          ]
        }
      }
    }
  }
}
 */

const DEFAULT_HOURLY_RATE = 60; // Hourly Rate
const DEFAULT_TIME_BLOCK = 15;  // TOTAL MINUTES

const EMPTY_TIMER = [
    "hourlyRate" => DEFAULT_HOURLY_RATE,
    "timeBlock" => DEFAULT_TIME_BLOCK,
    "currentStartTime" => 0,
    "currentPauseTime" => 0,
    "history" => [],
];

class Timer {
    private $_DataLocation = "/home/randy/Dropbox/git/php_timer/timer.json";
    private $_Data = [];
    private $_Now;

    function __construct()
    {
        $this->updateNow();
        $this->loadData();
        if (!isset($this->_Data['default'])) {
            $this->_Data['default'] = [];
        }
    }
    function __destruct()
    {
        $this->saveData();
    }

    function updateNow()
    {
        $this->_Now = new DateTime();
    }
    function parseTimerNameString($string) {
        $list = explode('/',$string);
        if (count($list) == 1) {
            array_unshift($list,'default');
        }
        $this->createTimer($list[0],$list[1]);
        return $list;
    }
    function today() {
        return $this->_Now->format("Y-m-d");
    }
    function createTimer($groupName,$timerName) {
        $this->createGroup($groupName);
        if (!isset($this->_Data[$groupName][$timerName])) {
            $this->_Data[$groupName][$timerName] = EMPTY_TIMER;
        }
    }
    function createGroup($groupName) {
        if (!isset($this->_Data[$groupName])) {
            $this->_Data[$groupName] = [];
        }
    }

    function loadData()
    {
        $data = @file_get_contents($this->_DataLocation);
        if ($data !== FALSE) {
            $this->_Data = json_decode($data,true);
        }
    }
    function saveData()
    {
        $data = json_encode($this->_Data,JSON_PRETTY_PRINT);
        file_put_contents($this->_DataLocation,$data);
    }

    function startCountdown()
    {

    }
    function makeToday($timerNameString) {
        list($groupName,$timerName) = $this->parseTimerNameString($timerNameString);
        if (!isset($this->_Data[$groupName][$timerName]['history'][$this->today()])) {
            $this->_Data[$groupName][$timerName]['history'][$this->today()] = [
                "totalTime" => 0,
                "hourlyRate" =>  $this->_Data[$groupName][$timerName]['hourlyRate'],
                "description" => [],
            ];
        }
    }

    function startClock($timerNameString)
    {
        list($groupName,$timerName) = $this->parseTimerNameString($timerNameString);
        $this->updateNow();

        if ($this->_Data[$groupName][$timerName]['currentStartTime'] === 0) {
            $this->_Data[$groupName][$timerName]['currentStartTime'] = intval($this->_Now->format('U'));
        }
        if ($this->_Data[$groupName][$timerName]['currentPauseTime'] !== 0) {
            $timePaused = $this->_Data[$groupName][$timerName]['currentPauseTime'] - $this->_Data[$groupName][$timerName]['currentStartTime'];
            $this->_Data[$groupName][$timerName]['currentStartTime'] = $this->_Now->format('U') - $timePaused;
            $this->_Data[$groupName][$timerName]['currentPauseTime'] = 0;
        }
    }
    function pauseClock($timerNameString) {
        list($groupName,$timerName) = $this->parseTimerNameString($timerNameString);
        $this->updateNow();

        if (($this->_Data[$groupName][$timerName]['currentPauseTime'] === 0) &&
            ($this->_Data[$groupName][$timerName]['currentStartTime'] !== 0) ) {
            $this->_Data[$groupName][$timerName]['currentPauseTime'] = intval($this->_Now->format('U'));
        }
    }
    function stopClock($timerNameString)  // this is where rounding comes into play...   This will always round UP
    {
        list($groupName,$timerName) = $this->parseTimerNameString($timerNameString);
        $this->updateNow();

        $elapsedTime = 0;
        if ($this->_Data[$groupName][$timerName]['currentPauseTime'] !== 0) {
            $elapsedTime = $this->_Data[$groupName][$timerName]['currentPauseTime'] - $this->_Data[$groupName][$timerName]['currentStartTime'];
        }

        if ($this->_Data[$groupName][$timerName]['currentStartTime'] !== 0) {
            $elapsedTime = $this->_Now->format('U') - $this->_Data[$groupName][$timerName]['currentStartTime'] ;
        }

        $chunks = ceil($elapsedTime / ($this->_Data[$groupName][$timerName]['timeBlock']*60));
        $this->makeToday($timerNameString);
        $this->_Data[$groupName][$timerName]['history'][$this->today()]['totalTime'] += $chunks * $this->_Data[$groupName][$timerName]['timeBlock'];

        $this->_Data[$groupName][$timerName]['currentStartTime'] = 0;
        $this->_Data[$groupName][$timerName]['currentPauseTime'] = 0;
    }
    function resetClock($timerNameString)  // this is where rounding comes into play...   This will always round UP
    {
        list($groupName,$timerName) = $this->parseTimerNameString($timerNameString);
        if ($this->_Data[$groupName][$timerName]['currentPauseTime'] === 0) {
            $this->_Data[$groupName][$timerName]['currentStartTime'] = 0;
        }
    }
    function addDescription($timerNameString,$description)  // this is where rounding comes into play...   This will always round UP
    {
        list($groupName,$timerName) = $this->parseTimerNameString($timerNameString);
        $this->makeToday($timerNameString);
        $this->_Data[$groupName][$timerName]['history'][$this->today()]['description'][] = $description;
    }
    function setRate($timerNameString,$newRate)
    {
        list($groupName,$timerName) = $this->parseTimerNameString($timerNameString);
        $this->makeToday($timerNameString);
        $this->_Data[$groupName][$timerName]['hourlyRate'] = $newRate;
    }
    function setTodayRate($timerNameString,$newRate)
    {
        list($groupName,$timerName) = $this->parseTimerNameString($timerNameString);
        $this->makeToday($timerNameString);
        $this->_Data[$groupName][$timerName]['history'][$this->today()]['hourlyRate'] = $newRate;
    }
    function setTimeBlock($timerNameString,$newTimeBlock)
    {
        list($groupName,$timerName) = $this->parseTimerNameString($timerNameString);
        $this->makeToday($timerNameString);
        $this->_Data[$groupName][$timerName]['timeBlock'] = $newTimeBlock; // time in minutes
    }

    function addTime($timerNameString,$minutes,$optional)
    {
        list($groupName,$timerName) = $this->parseTimerNameString($timerNameString);
        $this->makeToday($timerNameString);
        $time = $minutes;
        if ($optional === 'block') {
            $time = $minutes * $this->_Data[$groupName][$timerName]['timeBlock'];
        }
        if ($minutes === 'block') $time = $this->_Data[$groupName][$timerName]['timeBlock'];
        $this->_Data[$groupName][$timerName]['history'][$this->today()]['totalTime']  += $time;
    }
    function subtractTime($timerNameString,$minutes,$optional)
    {
        list($groupName,$timerName) = $this->parseTimerNameString($timerNameString);
        $this->makeToday($timerNameString);
        $time = $minutes;
        if ($optional === 'block') {
            $time = $minutes * $this->_Data[$groupName][$timerName]['timeBlock'];
        }
        if ($minutes === 'block') $time = $this->_Data[$groupName][$timerName]['timeBlock'];
        $this->_Data[$groupName][$timerName]['history'][$this->today()]['totalTime']  -= $time;
    }

    function displayTimeStamp($timestamp) {
        $local = localtime($timestamp,true);
        if ($timestamp === 0)
            return "0000-00-00 00:00:00";
        return sprintf("%04d-%02d-%02d %02d:%02d:%02d",
            ($local['tm_year']+1900),
            ($local['tm_mon']+1),
            $local['tm_mday'],$local['tm_hour'],$local['tm_min'],$local['tm_sec']);
    }

    function displayEllapsedTime($timeDiff) {
        $days = floor($timeDiff / (60*60*24));
        $timeDiff -= $days * (60*60*24);
        $hours = floor($timeDiff / (60*60));
        $timeDiff -= $hours * (60*60);
        $minutes = floor($timeDiff / 60);
        $timeDiff -= $minutes * (60);
        $seconds = $timeDiff;

        $timeString = null;
        if ($days) {
            $timeString = sprintf("%02d:%02d:%02d:%02d",$days,$hours,$minutes,$seconds);
        }
        if ($hours && !$timeString) {
            $timeString = sprintf("%02d:%02d:%02d",$hours,$minutes,$seconds);
        }
        if (!$timeString) {
            $timeString = sprintf("%02d:%02d",$minutes,$seconds);
        }

        return $timeString;
    }

    function displaySummary($timerNameString) {
        list($groupName,$timerName) = $this->parseTimerNameString($timerNameString);
        $this->updateNow();
        $status = "OFF";
        if ($this->_Data[$groupName][$timerName]['currentStartTime'] > 0) {
            $status = "RUNNING";
            printf("%25s: %s\n","Start Time",$this->displayTimeStamp($this->_Data[$groupName][$timerName]['currentStartTime']));
        }

        $timeDiff = 0;
        if ($this->_Data[$groupName][$timerName]['currentPauseTime'] > 0) {
            $status = "PAUSED";
            printf("%25s: %s\n","Paused Time",$this->displayTimeStamp($this->_Data[$groupName][$timerName]['currentPauseTime']));
            $timeDiff = $this->_Data[$groupName][$timerName]['currentPauseTime'] - $this->_Data[$groupName][$timerName]['currentStartTime'];
        } else {
            if ($status==='RUNNING')
                printf("%25s: %s\n","Current Time",$this->displayTimeStamp(time()));
            $timeDiff = time() - $this->_Data[$groupName][$timerName]['currentStartTime'];
            if ($this->_Data[$groupName][$timerName]['currentStartTime'] === 0) $timeDiff = 0;
        }
        if ($status !== "OFF") {
            printf("%25s: %s\n","Elapsed Time",$this->displayEllapsedTime($timeDiff));
        }
        if (@$this->_Data[$groupName][$timerName]['history'][$this->today()]['totalTime'] > 0) {
            printf("%25s: %s\n","Today's Total",$this->displayEllapsedTime($this->_Data[$groupName][$timerName]['history'][$this->today()]['totalTime']));
        }
        printf("%25s: %s\n","Current Status",$status);
    }
    function report($timerNameString,$selector) {
        list($groupName,$timerName) = $this->parseTimerNameString($timerNameString);
        $len = @strlen($selector);
        echo "selector: $selector\n";
        $totalTime = 0;
        if ($len > 4) {
           foreach ($this->_Data[$groupName][$timerName]['history'] as $date => $value)
           {
               $lineItems = [];

               foreach ($value['description'] as $description) {
                   $lineItems[] = [
                       $date,0,$description
                   ];
               }
               if ($lineItems === []) {
                   $lineItems[0] = [
                       $date,
                       $value['totalTime']/60,
                       "n/a"
                   ];
               } else {
                   $lineItems[0][1]=$value['totalTime']/60;
               }
               foreach ($lineItems as $value) {
                   if (stripos($value[0],$selector) === 0) {
                       $totalTime+= $value[1];
                       printf("%s,%.2f,%s\n",$value[0],$value[1],$value[2]);
                   }
               }
           }
        }
        printf("total hours: %.2f\n",$totalTime);
    }
    function displayTimerHumanReadable($timeData) {
        $this->updateNow();
        echo "TotalTimeSeconds: " . $timeData['totalTimeSeconds'] . "\n";
        echo "TotalTimeHours:   " ;
        printf("%.2f\n",$timeData['totalTimeSeconds']/3600);
        echo "currentStartTime: " . sprintf("%10d",$timeData['currentStartTime']) . " (".$this->displayTimeStamp($timeData['currentStartTime']).")\n";
        echo "currentPauseTime: " . sprintf("%10d",$timeData['currentPauseTime']) . " (".$this->displayTimeStamp($timeData['currentPauseTime']).")\n";
        if ($timeData['currentPauseTime'] > 0) {
            $elapsedTime = $timeData['currentPauseTime'] - $timeData['currentStartTime'];
            $H = floor($elapsedTime/3600);
            $elapsedTime %= 3600;
            $M = floor($elapsedTime / 60);
            $elapsedTime %= 60;
            $S = $elapsedTime;
            echo "elapsedPauseTime: ".sprintf("%02d:%02d:%02d\n",$H,$M,$S);
        }
        if ( $timeData['currentStartTime'] > 0 && $timeData['currentPauseTime'] === 0) {
            $elapsedTime = $this->_Now->format('U') - $timeData['currentStartTime'];
            $H = floor($elapsedTime/3600);
            $elapsedTime %= 3600;
            $M = floor($elapsedTime / 60);
            $elapsedTime %= 60;
            $S = $elapsedTime;
            echo "elapsedStartTime: ".sprintf("%02d:%02d:%02d\n",$H,$M,$S);
        }
    }
    function displayData($timerNameString=NULL,$onlyToday=TRUE)
    {
        if ($timerNameString) {
            list($groupName,$timerName) = $this->parseTimerNameString($timerNameString);
            print_r($this->_Data[$groupName][$timerName]);
        } else {
            var_dump($this->_Now);
            print_r($this->_Data);
        }
    }
}
