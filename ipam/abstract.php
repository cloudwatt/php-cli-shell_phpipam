<?php
	require_once(__DIR__ . '/main.php');
	require_once(__DIR__ . '/interface.php');
	require_once(__DIR__ . '/connector/abstract.php');
	require_once(__DIR__ . '/connector/sql.php');
	require_once(__DIR__ . '/connector/rest.php');

	abstract class IPAM_Abstract extends IPAM_Main implements IPAM_Interface
	{
		protected $_aIPAM = array();
		protected $_oIPAM = null;


		public function __construct(array $servers, $printInfoMessages = true)
		{
			$config = CONFIG::getInstance()->IPAM;

			foreach($servers as $server)
			{
				$server = mb_strtoupper($server);

				if(!$config->servers->key_exists($server)) {
					throw new Exception("Unable to retreive IPAM server for '".$server."' from config", E_USER_ERROR);
				}
				elseif(!$config->contexts->key_exists($server)) {
					throw new Exception("Unable to retreive IPAM context for '".$server."' from config", E_USER_ERROR);
				}
				else
				{
					$login = getenv('IPAM_'.$server.'_LOGIN');
					$password = getenv('IPAM_'.$server.'_PASSWORD');

					if($login === false || $password === false) {
						throw new Exception("Unable to retreive IPAM credentials for '".$server."' from env", E_USER_ERROR);
					}

					$this->_aIPAM[$server] = new IPAM_Connector_Rest($server, $config->servers[$server], $config->contexts[$server], $login, $password, $printInfoMessages);
				}
			}

			if(count($this->_aIPAM) === 1) {
				$this->_oIPAM = current($this->_aIPAM);
			}
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
		}

		public static function __callStatic($name, array $arguments)
		{
			return forward_static_call_array(array('IPAM_Connector_Rest', $name), $arguments);
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
	}