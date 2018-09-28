<?php
	require_once(__DIR__ . '/abstract.php');

	abstract class Service_Abstract_Browser extends Service_Abstract
	{
		protected $_cdautocomplete = true;
		protected $_printBothObjectAndList = true;

		protected $_pathIds = null;
		protected $_pathApi = null;


		protected function _preLauchingShell($welcomeMessage = true)
		{
			parent::_preLauchingShell($welcomeMessage);
			$this->_moveToRoot();
		}

		protected function _routeShellCmd($cmd, array $args)
		{
			switch($cmd)
			{
				case 'ls':
				case 'll':
				{
					$isPrinted = $this->_Service_Shell->printObjectInfos($args, true);

					if(!$isPrinted || $this->_printBothObjectAndList)
					{
						if(!$isPrinted) {
							$this->deleteWaitingMsg(true);					// Fix PHP_EOL lié au double message d'attente successif lorsque la commande precedente n'a rien affichée
						}

						$path = (isset($args[0])) ? ($args[0]) : (null);
						$objects = $this->_Service_Shell->printObjectsList($path);
						$this->setLastCmdResult($objects);
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

					$this->print($currentPath, 'white');
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
					$this->print("L'autocomplétion de la commande CD est ".$cdAutoCompleteStatut, 'green');
					break;
				}
				default: {
					return parent::_routeShellCmd($cmd, $args);
				}
			}

			return false;
		}

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
	}