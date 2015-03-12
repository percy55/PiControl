<?php
date_default_timezone_set('Europe/Amsterdam');

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'reboot':
            reboot();
            break;

        case 'getStats':
            getStats();
            break;

        case 'getProcesses':
            getProcesses();
            break;
    }
}

function reboot(){
    exec('sudo systemctl reboot');
    exit;
}

function getStats(){
    $uptime = new DateTime(exec('uptime -s'));
    $now = new DateTime("now");
    $uptime = $uptime->diff($now);

    $fs = array();
    $format = array(
            '%s' => 'seconds',
            '%i' => 'minutes',
            '%h' => 'hours',
            '%d' => 'days',
            '%m' => 'months',
            '%Y' => 'years'
        );

    foreach ($format as $f => $v){
        $t = $uptime->format($f);
        if($t > 0){
            $ident = ($v == 1 ? substr($v, 0, -1) : $v);
            array_push($fs, $f . ' ' . $ident);
        }
    }

    $uptime = $uptime->format(join(array_reverse($fs), ', '));

    $cpu_temp = exec('/opt/vc/bin/vcgencmd measure_temp');
    $cpu_temp = str_replace('temp=', '', $cpu_temp);

    $freemem = exec('free -m | grep Mem:');
    $freemem = str_replace('Mem:', '', $freemem);
    $freemem = preg_replace('!\s+!', ' ', $freemem);
    $freemem = split(' ', trim($freemem));

    $tasks = exec('ps aux | grep -v root | wc -l') - 1;

    $cpu_usage = exec('top -bn 1 -d 0.01 | grep \'^%Cpu\' | tail -n 1 | gawk \'{print $2+$4+$6}\'');

    $data = array(
        'uptime'    => $uptime,
        'tasks'     => $tasks,
        'cpu_temp'  => $cpu_temp,
        'cpu_usage' => $cpu_usage,
        'free_mem'  => $freemem
    );

    $json = json_encode($data, JSON_PRETTY_PRINT);
    echo $json;
}

function getProcesses(){
    $output = array();
    $processes = array();
    $proc = exec('ps aux | grep -v \'root\' |  grep -v \'ps aux\' | grep -v \'awk NR>1\' | awk \'NR>1\'', $output);
    $tasks = count($proc);

    foreach ($output as $p){
        $process = preg_split('/\s+/', $p);
        array_splice($process, 4, 6);
        $user = $process[0];
        $pid = $process[1];
        $cpu = $process[2];
        $ram = $process[3];
        array_splice($process, 0, 4);
        $command = '';
        foreach ($process as $cp) {
            $command = $command . $cp . ' ';
        }

        array_push($processes, array($user, $pid, $cpu, $ram, $command));
    }

    $json = json_encode($processes, JSON_PRETTY_PRINT);
    echo $json;
}
?>