<?php
	namespace Addon\Ipam;

	use Core as C;

	class Connector extends Connector_Abstract
	{
		protected static $_defaultConnector = __NAMESPACE__ .'\Connector_Rest';
		
		protected $_currentConnector;

		/**
		  * IPAM server configuration
		  * @var Core\Config
		  */
		protected $_config = null;

		protected $_aIPAM = array();
		protected $_oIPAM = null;


		public function __construct(array $servers, $printInfoMessages = true, $connector = null, $debug = false)
		{
			$config = C\Config::getInstance()->IPAM;
			$this->_setCurrentConnector($connector);

			foreach($servers as $server)
			{
				$server = mb_strtoupper($server);

				if(!$config->servers->key_exists($server)) {
					throw new Exception("Unable to retrieve IPAM server '".$server."' configuration", E_USER_ERROR);
				}
				elseif(!$config->contexts->key_exists($server)) {
					throw new Exception("Unable to retrieve IPAM server '".$server."' context configuration", E_USER_ERROR);
				}
				else
				{
					$this->_config = $config->servers[$server];

					if($this->_config->key_exists('serverLocation')) {
						list($loginCredential, $passwordCredential) = $this->_getCredentials($this->_config, $server);
						$this->_aIPAM[$server] = new $this->_currentConnector($server, $this->_config->serverLocation, $config->contexts[$server], $loginCredential, $passwordCredential, $printInfoMessages, $debug);
					}
					else {
						throw new Exception("Unable to retrieve 'serverLocation' configuration for IPAM server '".$server."'", E_USER_ERROR);
					}
				}
			}

			if(count($this->_aIPAM) === 1) {
				$this->_oIPAM = current($this->_aIPAM);
			}
		}

		protected function _setCurrentConnector($connector)
		{
			if($connector !== null && static::_isValidConnector($connector)) {
				$this->_currentConnector = $connector;
			}
			else {
				$this->_currentConnector = static::$_defaultConnector;
			}
		}

		protected function _getCredentials(C\MyArrayObject  $serverConfig, $server)
		{
			if($serverConfig->key_exists('loginCredential') && C\Tools::is('string&&!empty', $serverConfig->loginCredential)) {
				$loginCredential = $serverConfig->loginCredential;
			}
			elseif($serverConfig->key_exists('loginEnvVarName') && C\Tools::is('string&&!empty', $serverConfig->loginEnvVarName))
			{
				$loginEnvVarName = $serverConfig->loginEnvVarName;
				$loginCredential = getenv($loginEnvVarName);

				if($loginCredential === false) {
					throw new Exception("Unable to retrieve login credential for IPAM server '".$server."' from environment with variable name '".$loginEnvVarName."'", E_USER_ERROR);
				}
			}
			else {
				throw new Exception("Unable to retrieve 'loginCredential' or 'loginEnvVarName' configuration for IPAM server '".$server."'", E_USER_ERROR);
			}

			if($serverConfig->key_exists('passwordCredential') && C\Tools::is('string&&!empty', $serverConfig->passwordCredential)) {
				$passwordCredential = $serverConfig->passwordCredential;
			}
			elseif($serverConfig->key_exists('passwordEnvVarName') && C\Tools::is('string&&!empty', $serverConfig->passwordEnvVarName))
			{
				$passwordEnvVarName = $serverConfig->passwordEnvVarName;
				$passwordCredential = getenv($passwordEnvVarName);

				if($passwordCredential === false) {
					throw new Exception("Unable to retrieve password credential for IPAM server '".$server."' from environment with variable name '".$passwordEnvVarName."'", E_USER_ERROR);
				}
			}
			else {
				throw new Exception("Unable to retrieve 'passwordCredential' or 'passwordEnvVarName' configuration for IPAM server '".$server."'", E_USER_ERROR);
			}

			return array($loginCredential, $passwordCredential);
		}

		public function getByEquipLabel($equipLabel, $IPv = 4)
		{
			$IPAM = $this->getIpam($equipLabel);
			return $IPAM->getByEquipLabel($equipLabel, $IPv);
		}

		public function getByEquipLabelVlanName($equipLabel, $vlanName, $IPv = 4)
		{
			$IPAM = $this->getIpam($equipLabel);
			return $IPAM->getByEquipLabelVlanName($equipLabel, $vlanName, $IPv);
		}

		public function getByEquipLabelVlanRegex($equipLabel, $vlanRegex, $IPv = 4)
		{
			$IPAM = $this->getIpam($equipLabel);
			return $IPAM->getByEquipLabelVlanName($equipLabel, $vlanRegex, $IPv);
		}

		public function getByEquipLabelPortLabel($equipLabel, $portLabel, $IPv = 4)
		{
			$IPAM = $this->getIpam($equipLabel);
			return $IPAM->getByEquipLabelPortLabel($equipLabel, $portLabel, $IPv);
		}

		public function getGatewayByEquipLabelSubnetId($equipLabel, $subnetId)
		{
			$IPAM = $this->getIpam($equipLabel);
			return $IPAM->getGatewayBySubnetId($subnetId);
		}

		public function getGatewayBySubnetId($subnetId)
		{
			throw new Exception('Impossible de déterminer sur quel IPAM effectuer la requête', E_USER_ERROR);
		}

		public function getVlanNamesByVlanIds(array $vlanIds, array $environments)
		{
			$vlanNames = array();

			foreach($this->_aIPAM as $IPAM) {
				$result = $IPAM->getVlanNamesByVlanIds($vlanIds, $environments);
				$vlanNames = array_merge($vlanNames, $result);
				// @todo array_unique ??
			}

			return $vlanNames;
		}

		public function getMcLagRowset($equipLabel, $portName, $IPv = 4)
		{
			$IPAM = $this->getIpam($equipLabel);
			return $IPAM->getMcLagRowset($equipLabel, $portName, $IPv);
		}

		/**
		  * /!\ Dans l'IPAM le champ is_gateway peut être à TRUE pour seulement une entrée par subnet
		  * On ne peut donc pas avoir deux VIP VRRP dans le même subnet retournées si on test ce champ
		  **/
		public function getVrrpRowset($equipLabel, $subnetId, $IPv = 4)
		{
			$IPAM = $this->getIpam($equipLabel);
			return $IPAM->getVrrpRowset($equipLabel, $subnetId, $IPv);
		}

		public function getIpam($equipLabel = null)
		{
			if($this->_oIPAM !== null) {
				return $this->_oIPAM;
			}
			elseif($equipLabel !== null)
			{
				if(preg_match('#^(.*-[ps]-.*)$#i', $equipLabel)) {
					return $this->_aIPAM['SEC'];
				}
				elseif(preg_match('#^(.*-[il]-.*)$#i', $equipLabel)) {
					return $this->_aIPAM['CORP'];
				}
				elseif(preg_match('#^(.*-[d]-.*)$#i', $equipLabel)) {
					return $this->_aIPAM['DEV'];
				}
			}

			throw new Exception('Impossible de retourner le service IPAM adapté', E_USER_ERROR);
		}

		public function getAllIpam()
		{
			return $this->_aIPAM;
		}

		public function getConfig()
		{
			return $this->_config;
		}

		/*public function __call($name, array $arguments)
		{
			if($this->_oIPAM !== null) {
				return call_user_func_array(array($this->_oIPAM, $name), $arguments);
			}
			else
			{
				$results = array();

				foreach($_aIPAM as $ipam) {
					$result[] = call_user_func_array(array($ipam, $name), $arguments);
				}

				return $results;
			}
		}*/

		public function __get($name)
		{
			if($this->_oIPAM !== null) {
				return $this->_oIPAM;
			}
			else
			{
				switch(mb_strtolower($name))
				{
					case 'sec':
						return $this->_aIPAM['SEC'];
					case 'corp':
						return $this->_aIPAM['CORP'];
					case 'dev':
						return $this->_aIPAM['DEV'];
					case 'all':
						return $this->_aIPAM;
				}
			}

			throw new Exception("L'IPAM ".$name." n'existe pas", E_USER_ERROR);
		}

		public function __destruct()
		{
			foreach($this->_aIPAM as $IPAM) {
				$IPAM->close();
			}
		}

		/*public static function __callStatic($name, array $arguments)
		{
			$callable = array(static::$_defaultConnector, $name);
			$Closure = \Closure::fromCallable($callable);
			return forward_static_call_array($Closure, $arguments);
		}*/

		public static function setDefaultConnector($connector)
		{
			if(static::_isValidConnector($connector)) {
				static::$_defaultConnector = $connector;
			}
		}

		protected static function _isValidConnector($connector)
		{
			$ReflectionClass = new \ReflectionClass($connector);
			return $ReflectionClass->isSubclassOf(__NAMESPACE__ .'\Connector_Abstract');
		}
	}