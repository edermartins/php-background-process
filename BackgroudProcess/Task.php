<?php
namespace EderMartins\BackgroudProcess;

class Task {
	
	private $os;
	private $cmd;
	private $params;
	private $pid = null;
	
	const OS_WINDOWS = 'WINDOWS';
	const OS_LINUX = 'LINUX';
	const PROCESS_RUNNING = 'RUNNING';
	const PROCESS_KILLED = 'KILLED';
	
	/**
	 * Executa um comando/script em segundo plano. No caso do windows abre janelas cmd, que se fecham quando o 
	 * comando/script termina. As janelas são abertas para conseguir recuperar PID, pois só conseguir buscar o
	 * PID através no "title" da janela (esta classe gera uma por janela).
	 * Esta classe não retorna o resultado da execução, apenas executa
	 * TODO Tentar retonar se a execução terminou ou não com erro
	 * @param string $cmd Comando a ser executado. Ex.: 'php', 'notepad', etc.
	 * @param string $params Parâmetros separados por expaço. Ex.: "10", "Param1 Param2"
	 */
	function __construct($cmd, $params=''){
		$this->os = $this->checkOS();
		$this->cmd = $cmd;
		$this->params = $params;
	}
	
	/**
	 * Check if the system is Windows or Linux
	 */
	private function checkOS(){
		$result = self::OS_LINUX;
		$uname = php_uname();
		if( strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ){
			$result = self::OS_WINDOWS;
		}
		return $result;
	}
	
	/**
	 * Start the task on Windows or Linux
	 * @return mixed The PID or null if fail
	 */
	public function start(){
		$result = null;
		if(!$this->pid){
			if($this->getOS() == self::OS_WINDOWS ){
				$result = $this->startWindows();
			}else{
				$result = $this->startLinux();
			}
		}
		return $result;
	}

	/**
	 * Start the task on Windows. It opens an windows to allow get the PID by unique title of it
	 * @return mixed The PID or null if fail
	 */
	private function startWindows(){
		/*
		 * Gera o título da janela único para poder recuperar o PID
		 */
		$process_name = 'task_bg_' . date('YmdHms') . rand(10000, 99999);
		
		/*
		 * Abre o processo em uma janelo e não em backgroud
		 * Se abrir em backgroud o processo simplesmente aparece como "php" e não dá pra pegar o PID se
		 * rodar mais de um
		 */
		$completeCmd = "start \"{$process_name}\" \"{$this->cmd}\" \"{$this->params}\""; 
		echo "\n$completeCmd\n";
		pclose(popen($completeCmd, "r"));
		
		/*
		 * Lê o PID do processo através to "title" da janela
		 */
		$out = exec("tasklist /v /fo csv | findstr /i \"$process_name\"");
		try{
			$out_array = explode(',', $out);
			$this->pid = str_replace('"', '', $out_array[1]);//PID
		}catch (\Exception $ex){
			$this->pid = null;
		}
		return $this->pid;
	}
	
	/**
	 * Start the task on Linux
	 * @return mixed The PID or null if fail
	 */
	private function startLinux(){
		$this->pid = trim(exec("{$this->cmd} {$this->params} > dev/null 2>&1 & echo $!"));
	}
	
	/**
	 * Get the OS status
	 * @param string optionally return the OS result line to be checked if necessary
	 * <br><b>Windows Sample:</b> "chrome.exe","4212","Console","1","36.336 K","Unknown","ADMUSER","0:00:00","N/A"
	 * <br><b>Linux Sample:</b> apache    2946 34032  0 166001 36900  0 Apr21 ?        00:00:20 /usr/sbin/httpd -DFOREGROUND
	 * @return string Constants Task::PROCESS_RUNNING or Task::PROCESS_KILLED
	 */
	public function status(&$output=null){
		if($this->pid){
			if($this->getOS() == self::OS_WINDOWS ){
				$output = $this->statusWindows();
			}else{
				$output = $this->statusLinux();
			}
			if($output){
				$result = self::PROCESS_RUNNING;
			}else{
				$result = self::PROCESS_KILLED;
			}
		}
		return $result;
	}
	
	/**
	 * Get the status of the backgroud process.
	 * Like: "chrome.exe","4212","Console","1","36.336 K","Unknown","ADMUSER","0:00:00","N/A"
	 * @param string String with the OS status
	 */
	private function statusWindows(){
		/*
		 * The findstr is used to remove the header and get only the PID line
		 */
		return trim(exec("tasklist /v  /fo csv /fi \"PID eq {$this->pid}\" | findstr \"{$this->pid}\""));
	}
	
	/**
	 * Get the status of the backgroud process.
	 * Like: apache    2946 34032  0 166001 36900  0 Apr21 ?        00:00:20 /usr/sbin/httpd -DFOREGROUND
	 * @param string String with the OS status
	 */
	private function statusLinux(){
		/*
		 * The grep is used to remove the header and get only the PID line
		 */
		return trim(exec("ps -p {$this->pid} -F | grep {$this->pid}"));
	}
	
	/**
	 * Kill the process in backgroud
	 * @param string $force Kill the backgroud process normaly. If <b>true</b> force it '-9' for linux or '/f' for windows.
	 * <br><b>false</b> is the default value. You need to get the status to check if was killed with success
	 */
	public function kill($force=false){
		if($this->pid){
			if($this->os == self::OS_WINDOWS ){
				$this->killWindows($force);
			}else{
				$this->killLinux($force);
			}
		}
	}

	/**
	 * Kill the process in backgroud
	 * @param string $force Kill the backgroud process normaly. If <b>true</b> force it with '/f' for windows.
	 * <br><b>false</b> is the default value. You need to get the status to check if was killed with success
	 */
	private function killWindows($force=false){
		$force = ($force ? "/f" : '' );
		exec("taskkill $force /PID {$this->pid}");
	}
	
	/**
	 * Kill the process in backgroud
	 * @param string $force Kill the backgroud process normaly. If <b>true</b> force it with '-9' for linux.
	 * <br><b>false</b> is the default value. You need to get the status to check if was killed with success
	 */
	private function killLinux($force=false){
		$force = ($force ? '-9' : '');
		exec("kill $force {$this->pid} > dev/null 2>&1 & echo $!");
	}
	
	/**
	 * Return the OS type
	 * @return string Task::OS_WINDOWS or Task::OS_LINUX
	 */
	public function getOS(){
		return $this->os;
	}
	
	/**
	 * Return the PID (Process IDentification)
	 * @return string PID
	 */
	public function getPID(){
		return $this->pid;
	}
	
	/**
	 * Return the command
	 * @return string
	 */
	public function getCmd(){
		return $this->cmd;
	}
	
	/**
	 * Return the parameters
	 * @return string
	 */
	public function getParams(){
		return $this->params;
	}
}