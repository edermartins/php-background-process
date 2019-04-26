<?php
/**
 * Simple test to run processes in backgroud
 * @author Eder Jani Martins <zetared@gmail.com>
 */
require_once(__DIR__.'/../vendor/autoload.php');

use EderMartins\BackgroudProcess\TaskManager;

$tasks = new TaskManager();

/*
 * Command to be executed in background
 */
$cmd = "php";
/*
 * OPTIONAL: Params to be sent.
 * In case of php is the name of php file.
 * If the script needs his own parameters put them separeted with space, 
 * like you do in the command line
 * eg.: $params = "child-script.php subparam1 subparam2"
 */
$params = "child-script.php";

/*
 * Create 10 test children with random time to finish
 */
for ($i = 0; $i < 10; $i++){
    /*
     * Add the process in the internal list and start it
     */
	$tasks->add($cmd, $params, "nome$i");
}

/*
 * Logic to stay in loop checking and waiting for the end of the processes
 */
while ($tasks->count() > 0){
    echo "\nThe are '" . $tasks->count() . "' processes in the list";
    
    /*
     * Gell the list of all process in the class TaskManager list
     */
	foreach ($tasks->listAll() as $task){
		$pid = $task['pid'];
		/*
		 * Check the status of processes
		 */
		$check = $tasks->check($pid);
		echo "\nProcess: $pid >> " . var_export($check, true);
	}
	echo "\nWating 10 seconds";
	sleep(10);
}
echo "\nFinished with success";