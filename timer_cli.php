<?php
require(__DIR__."/timer.php");
$timer = new Timer();

if ($argc < 2) {
    echo "usage: \n";
    echo "        timer group/timername action [parameters]\n\n";
    echo "action: \n";
    echo "        start      - Start a timer\n";
    echo "        pause      - Pause the timer if already started\n";
    echo "        stop       - Stops the timer, and rounds up to the currently set block\n";
    echo "        reset      - If started and not paused, it will reset the timer to 0\n";
    echo "        today-rate - Set's the timer's hourly rate for today.\n";
    echo "        rate       - Set's the timer's hourly rate\n";
    echo "        time-block - set the current time block in minutes\n";
    echo "        add        - Add specified minutes to today's clock\n";
    echo "        subtract   - Subtract specified minutes from today's clock\n";
    echo "        summary    - unknown\n";
//    echo "example:\n";
//    echo "        timer group/mytimer start\n";
//    echo "        timer group/mytimer stop\n";
    echo "\n";
    return;
}

$displayData = true;
switch (@$argv[2]) {
    case 'start':
        $timer->startClock($argv[1]);
        break;
    case 'stop':
        $timer->stopClock($argv[1]);
        break;
    case 'pause':
        $timer->pauseClock($argv[1]);
        break;
    case 'summary':
        $timer->displaySummary($argv[1]);
        $displayData = FALSE;
        break;
    case 'description':
        $timer->addDescription($argv[1],implode(" ",array_splice($argv,3)));
        break;
    case 'reset':
        $timer->resetClock($argv[1]);
        break;
    case 'today-rate':
        if ($argc !== 4) {
            echo "Error, you must include a new rate\n";
            break;
        }
        $timer->setTodayRate($argv[1],$argv[3]);
        break;
    case 'rate':
        if ($argc !== 4) {
            echo "Error, you must include a new rate\n";
            break;
        }
        $timer->setRate($argv[1],$argv[3]);
        break;
    case 'time-block':
        if ($argc !== 4) {
            echo "Error, you must include the amount of time in minutes\n";
            break;
        }
        $timer->setTimeBlock($argv[1],$argv[3]);
        break;
    case 'add':
        if ($argc < 4) {
            echo "Error, you must include the amount of time in minutes\n";
            break;
        }
        $timer->addTime($argv[1],$argv[3],@$argv[4]);
        break;
    case 'subtract':
        if ($argc < 4) {
            echo "Error, you must include the amount of time in minutes\n";
            break;
        }
        $timer->subtractTime($argv[1],$argv[3],@$argv[4]);
        break;
    case 'report':
        $timer->report($argv[1],@$argv[3]);
        break;
}
$timer->displaySummary($argv[1]);
