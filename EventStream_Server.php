<?php
/**
 * @author QTI3E
 * @license MIT
 */
include 'EventStream_Client.php';

/**
 * Class EventStream_Server
 */
abstract class EventStream_Server{
	/**
	 * @var string
	 */
	protected $client_class   = 'EventStream_Client';
	/**
	 * @var string
	 */
	protected $appName;
	/**
	 * @var string
	 */
	private $appFile;
	/**
	 * @var EventStream_Client
	 */
	private $user;
	/**
	 * @var int
	 */
	private $lastCount;

	/**
	 * EventStream_Server constructor.
	 */
	public function __construct() {
		session_start();
		if($this->appName == null){
			$this->appName  = get_class($this);
		}
		$this->appFile  = $this->appName.'.es';
		$this->user     = new $this->client_class(session_id());
		if(!file_exists($this->appFile)){
			file_put_contents($this->appFile,'');
		}
		if(isset($_GET['s'])){
			$this->write_command(['r',$_GET['s'],$this->user->get_sessionId()]);
		}else{
			set_time_limit(0);
			header('Content-Type: text/event-stream');
			header('X-Powered-By: XZBox/PHP-EventStream');
			$this->connected();
			$file               = file($this->appFile);
			$this->lastCount    = count($file);
			while(true){
				$file   = file($this->appFile);
				$count  = count($file);
				$diff   = $count - $this->lastCount;
				for($i  = $diff;$i > 0;$i--){
					$command = json_decode($file[-$i],true);
					if($command[0] == 'r'){
						if($command[2] == $this->user->get_sessionId()){
							$this->onMessage($command[1]);
						}
					}elseif($command[0] == 's'){
						if($command[3] == $this->user->get_sessionId() || $command[3] == '*'){
							echo "\n\n".'event: '.$command[1].implode("\ndata: ",explode("\n",$command[2]));
						}
					}
				}
			}
		}
	}

	/**
	 * @param $command
	 *
	 * @return void
	 */
	private function write_command($command){
		$fp = fopen($this->appFile,'a');
		fwrite($fp,json_encode($command)."\n");
		fclose($fp);
	}

	/**
	 * @return mixed
	 */
	abstract protected function connected();

	/**
	 * @param $message
	 *
	 * @return mixed
	 */
	abstract protected function onMessage($message);
}