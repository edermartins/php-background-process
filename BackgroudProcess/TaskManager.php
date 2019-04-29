<?php
namespace EderMartins\BackgroudProcess;

class TaskManager {
	private $taskList = array();
	
	const ERROR_NO_PID = 1;
	
	
	public function __construct(){
		
	}
	
	/**
	 * Create a new Task and put it in the queue to be checked
	 * @param string $cmd
	 * @param string $params
	 * @param string $name
	 * @throws \Exception If is not possible to run the 'cmd' with the 'params'
	 */
	public function add($cmd, $params='', $name=null){
		/*
		 * The task do not stats automatcally 
		 */
	    $task = new Task($cmd, $params);
		/*
		 * Start id 
		 */
		$pid = $task->start();
		if(!$pid){
			throw new \Exception("The command did not return a PID: '$cmd' with this params '$params'", self::ERROR_NO_PID);
		}
		//Add one task in the list
		$this->addTask($pid, $task, $name);
	}
	
	/**
	 * Check if the task is running or not. If not remove it from the list
	 * @param string $pid
	 * @return array|boolean array('pid' => 1010, 'status' => 'RUNNING'|'KILLED', 'output' => 'OS output to be analysed','name' => <Name or null>)
	 * <br>or <b>false</b> if not found the PID
	 */
	public function check($pid){
		$taskLine = $this->getTask($pid);
		$result = false;
		if($taskLine){
			/* @var Task */
			$task = $taskLine['task'];
			$name = $taskLine['name'];
			$osOutput = '';
			$status = $task->status($osOutput);
			if($status == Task::PROCESS_KILLED){
				//Remove from the list
				unset($this->taskList[$pid]);
			}
			$result = array('pid' => $pid, 'status' => $status, 'output' => $osOutput,'name' => $name);
		}
		return $result;
	}
	
	/**
	 * Check all process
	 * @return array with array elements when process is in the list or false if is not
	 * <br>array( array(see check()), false, ...)
	 * @see TaskManager::check()
	 */
	public function checkAll(){
		$result = array();
		foreach ($this->taskList as $pid => $task){
			$result[$pid] = $this->check($pid);
		}
		return $result;
	}
	
	/**
	 * Kill the task and remove it from the List if it was successfully killed
	 * @param string $PID
	 * @param string $force
	 * @return boolean <b>true</b> if the process was killed or <b>false</b> if not. Will be necessy check again, some process
	 * take time to be killed
	 */
	public function remove($pid, $force=false){
		$taskLine = $this->getTask($pid);
		$result = true;
		if($taskLine){
			/* @var Task */
			$task = $taskLine['task'];
			$task->kill($force);
			
			$result = ($task->status() == Task::PROCESS_KILLED);
			if($result){
				//Remove from the list
				unset($this->taskList[$pid]);
			}
		}
		return $result;
	}
	
	/**
	 * List of tasks. Get the status of wich one in real time, so it can take some time to retun
	 * @return <b>array</b>(
	 * <br><b>array</b>('pid' => $pid, 'status' => $status,'cmd' => cmd, 'params' => $params,'name' => $name, 'output' => $output)
	 * <br>)
	 */
	public function listAll(){
		$result = array();
		foreach ($this->taskList as $pid => $taskLine){
			$task = $taskLine['task'];
			$output = '';
			$name = $taskLine['name'];
			$cmd = $task->getCmd();
			$params = $task->getParams();
			$status = $task->status($output);
			$result[] = array('pid' => $pid, 'status' => $status,'cmd' => $cmd, 'params' => $params,'name' => $name, 'output' => $output);
		}
		return $result;
	}
	
	/**
	 * Number of tasks in the list
	 * @return int Number of the tasks
	 */
	public function count(){
		return count($this->taskList);		
	}
	
	/**
	 * Get an Task in the list by pid
	 * @param string $pid PID
	 * @return array|boolean array('pid' => 1010, 'task' => Task class, 'name' => name) or false if not found the pid
	 */
	private function getTask($pid){
		return (isset($this->taskList[$pid]) ? $this->taskList[$pid] : false);
	}
	
	/**
	 * Add a class Taks in the list
	 * @param string $pid PID
	 * @param Task $task Class Task
	 * @param string $name Easy name to be shown
	 */
	private function addTask($pid, $task, $name=null){
		if(!$this->getTask($pid)){
			$this->taskList[$pid] = array('pid' => $pid, 'task' => $task, 'name' => $name);
		}
	}
	
}