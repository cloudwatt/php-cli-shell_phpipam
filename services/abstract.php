<?php
	include_once(__DIR__ . '/../classes/tools.php');
	include_once(__DIR__ . '/../classes/config.php');
	include_once(__DIR__ . '/../shell/abstract.php');

	abstract class Service_Abstract
	{
		const PHP_MIN_VERSION = '7.1.14';

		protected $_CONFIG;

		protected $_SHELL;
		protected $_Service_Shell;

		protected $_commands = array();

		/**
		  * Arguments ne commencant pas par - mais étant dans le flow de la commande
		  *
		  * ls mon/chemin/a/lister
		  * cd mon/chemin/ou/aller
		  * find ou/lancer/ma/recherche
		  */
		protected $_inlineArgCmds = array();

		/**
		  * Arguments commencant pas par - ou -- donc hors flow de la commande
		  *
		  * find ... -type [type] -name [name]
		  */
		protected $_outlineArgCmds = array();

		protected $_manCommands = array();

		protected $_cdautocomplete = true;
		protected $_waitingMsgFeature = true;

		protected $_isOneShotCall = null;
		protected $_lastCmdResult = null;

		protected $_pathIds = null;
		protected $_pathApi = null;

		protected $_debug = false;


		public function __construct($configFilename)
		{
			set_error_handler(array(static::class, 'errorHandler'));

			if(version_compare(PHP_VERSION, self::PHP_MIN_VERSION) === -1) {
				throw new Exception("Version PHP inférieure à ".self::PHP_MIN_VERSION.", PHP ".self::PHP_MIN_VERSION." min requis", E_USER_ERROR);
			}

			$debug = getenv('PHPCLISHELL_DEBUG');

			if(mb_strtolower($debug) === "true") {
				$this->_debug = (bool) $debug;
			}

			$this->_CONFIG = CONFIG::getInstance($configFilename);

			$this->_SHELL = new SHELL($this->_commands, $this->_inlineArgCmds, $this->_outlineArgCmds, $this->_manCommands);
			$this->_SHELL->debug($this->_debug)->setHistoryFilename(static::SHELL_HISTORY_FILENAME);
		}

		public function test($cmd, array $args = array())
		{
			$this->_isOneShotCall = true;
			$this->waitingMsgFeature(false);
			$this->_preLauchingShell(false);

			$this->_preRoutingShellCmd($cmd, $args);
			$exit = $this->_routeShellCmd($cmd, $args);
			$this->_postRoutingShellCmd($cmd, $args);

			echo json_encode($this->_lastCmdResult);

			$this->_postLauchingShell(false);
		}

		protected function _init()
		{
			$this->_oneShotCall();
			$this->_launchShell();
			return $this;
		}

		public function isOneShotCall()
		{
			if($this->_isOneShotCall === null) {
				$this->_isOneShotCall = ($_SERVER['argc'] > 1);
			}

			return $this->_isOneShotCall;
		}

		protected function _oneShotCall()
		{
			if($this->isOneShotCall())
			{
				$cmd = $_SERVER['argv'][1];
				$this->waitingMsgFeature(false);
				$this->_preLauchingShell(false);

				$Shell_Autocompletion = new Shell_Autocompletion($this->_commands, $this->_inlineArgCmds, $this->_outlineArgCmds, $this->_manCommands);
				$Shell_Autocompletion->debug($this->_debug);

				$status = $Shell_Autocompletion->_($cmd);

				if($status)
				{
					$cmd = $Shell_Autocompletion->command;
					$args = $Shell_Autocompletion->arguments;

					$this->_preRoutingShellCmd($cmd, $args);
					$exit = $this->_routeShellCmd($cmd, $args);
					$this->_postRoutingShellCmd($cmd, $args);

					echo json_encode($this->_lastCmdResult);

					$exitCode = 0;
				}
				else {
					Tools::e("Commande invalide", 'red', false, 'bold');
					$this->_SHELL->help();
					$exitCode = 1;
				}

				$this->_postLauchingShell(false);
				exit($exitCode);
			}
		}

		protected function _preLauchingShell($welcomeMessage = true)
		{
			$this->_moveToRoot();

			if($welcomeMessage) {
				Tools::e(PHP_EOL.PHP_EOL."CTRL+C ferme le shell, utilisez ALT+C à la place", 'blue', false, 'italic');
				Tools::e(PHP_EOL."Utilisez UP et DOWN afin de parcourir votre historique de commandes", 'blue', false, 'italic');
				Tools::e(PHP_EOL."Utilisez TAB pour l'autocomplétion et ? afin d'obtenir davantage d'informations", 'blue', false, 'italic');
			}
		}

		abstract protected function _launchShell($welcomeMessage = true, $goodbyeMessage = true);

		protected function _postLauchingShell($goodbyeMessage = true)
		{
			if($goodbyeMessage) {
				Tools::e(PHP_EOL.PHP_EOL."Merci d'avoir utilisé TOOLS-CLI by NOC", 'blue', false, 'italic');
			}
		}

		protected function _preRoutingShellCmd($cmd, array &$args)
		{
			foreach($args as &$arg) {
				$arg = preg_replace('#^("|\')|("|\')$#i', '', $arg);
			}

			$this->displayWaitingMsg();
		}

		protected function _routeShellCmd($cmd, array $args)
		{
			switch($cmd)
			{
				case '': {
					$this->deleteWaitingMsg();
					Tools::e("Tape help for help !", 'blue');
					break;
				}
				case 'ls':
				case 'll':
				{
					$isPrinted = $this->_Service_Shell->printObjectInfos($args);

					if(!$isPrinted) {
						$path = (isset($args[0])) ? ($args[0]) : (null);
						$this->_Service_Shell->printObjectsList($path);
					}
					break;
				}
				case 'cd':
				{
					if(isset($args[0]))
					{
						$path = $args[0];
						$path = explode('/', $path);

						if($path[0] === "" || $path[0] === '~') {
							array_shift($path);
							$this->_moveToRoot();
						}

						$this->_moveToPath($path);
					}
					else {
						$this->_moveToRoot();
					}

					$this->deleteWaitingMsg();
					break;
				}
				case 'pwd':
				{
					$currentPath = $this->_getCurrentPath();

					$this->deleteWaitingMsg();
					$this->e($currentPath, 'white');
					$this->setLastCmdResult($currentPath);
					break;
				}
				case 'cdautocomplete':
				{
					if(isset($args[0]))
					{
						switch($args[0])
						{
							case 'en':
							case 'enable':
								$this->_cdautocomplete = true;
								break;
							case 'dis':
							case 'disable':
								$this->_cdautocomplete = false;
								break;
						}
					}
					else {
						$this->_cdautocomplete = !$this->_cdautocomplete;
					}

					if(!$this->_cdautocomplete) {
						$this->_SHELL->setInlineArg('cd', $this->_inlineArgCmds['cd']);
					}

					$cdAutoCompleteStatut = ($this->_cdautocomplete) ? ('activée') : ('désactivée');

					$this->deleteWaitingMsg();
					Tools::e("L'autocomplétion de la commande CD est ".$cdAutoCompleteStatut, 'green');
					break;
				}
				case 'history': {
					$this->deleteWaitingMsg();
					$this->_SHELL->history();
					break;
				}
				case 'help': {
					$this->deleteWaitingMsg();
					$this->_SHELL->help();
					break;
				}
				case 'exit':
				case 'quit': {
					return true;
				}
				default: {
					$this->deleteWaitingMsg();
					Tools::e("Commande inconnue... [".$cmd."]", 'red');
				}
			}

			return false;
		}

		protected function _postRoutingShellCmd($cmd, array $args) {}

		protected function _setObjectAutocomplete(array $fields = null)
		{
			if($this->_cdautocomplete && count($fields) > 0) {
				$options = $this->_Service_Shell->getOptions();
				$this->_SHELL->setInlineArg('cd', array(0 => $options));
			}
			else {
				$this->_SHELL->setInlineArg('cd', $this->_inlineArgCmds['cd']);
			}
			return $this;
		}

		protected function _moveToRoot()
		{
			array_splice($this->_pathIds, 1);
			array_splice($this->_pathApi, 1);

			$this->_Service_Shell->updatePath($this->_pathIds, $this->_pathApi);

			$this->_setObjectAutocomplete();
			$this->_SHELL->setShellPrompt('/');
			return $this->_pathApi[0];
		}

		protected function _moveToPath($path)
		{
			$this->browser($this->_pathIds, $this->_pathApi, $path);
			$this->_Service_Shell->updatePath($this->_pathIds, $this->_pathApi);

			$this->_setObjectAutocomplete();
			$currentPath = $this->_getCurrentPath();
			$this->_SHELL->setShellPrompt($currentPath);

			return end($this->_pathApi);
		}

		protected function _getCurrentPath()
		{
			$pathname = '/';

			for($i=1; $i<count($this->_pathApi); $i++) {
				$pathname .= $this->_pathApi[$i]->getObjectLabel().'/';
			}

			return $pathname;
		}

		public function displayWaitingMsg()
		{
			if($this->waitingMsgFeature()) {
				$message = Tools::e("Veuillez patienter ...", 'orange', false, 'bold', true);
				$this->_SHELL->printMessage($message);
				return true;
			}
			else {
				return false;
			}
		}

		public function deleteWaitingMsg()
		{
			if($this->waitingMsgFeature()) {
				$this->_SHELL->deleteMessage();
				return true;
			}
			else {
				return false;
			}
		}

		public function waitingMsgFeature($status = null)
		{
			if($status === true || $status === false) {
				$this->_waitingMsgFeature = $status;
			}
			return $this->_waitingMsgFeature;
		}

		public function setLastCmdResult($result)
		{
			$this->_lastCmdResult = $result;
			return $this;
		}

		public function e($text, $textColor = false, $bgColor = false, $textStyle = false, $doNotPrint = false)
		{
			return ($this->_isOneShotCall) ? ($text) : (Tools::e($text, $textColor, $bgColor, $textStyle, $doNotPrint));
		}

		protected function _throwException(Exception $exception)
		{
			Tools::e(PHP_EOL.PHP_EOL."Exception --> ".$exception->getMessage()." [".$exception->getFile()."] {".$exception->getLine()."}", 'red');
		}

		public static function errorHandler($errno, $errstr, $errfile, $errline)
		{
			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		}
	}