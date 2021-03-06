<?php
	namespace Addon\Ipam;

	class Api_Universal
	{
		/**
		  * @var Addon\Ipam\Orchestrator
		  */
		protected $_orchestrator;


		public function __construct()
		{
			$this->_orchestrator = Orchestrator::getInstance();
		}

		public function getByEquipLabelPortPresents($equipLabel, $portPresents = true, $IPv = 4, $strict = false)
		{
			$adapter = $this->_selectAdapter($equipLabel);
			return $adapter->getByEquipLabelPortPresents($equipLabel, $portPresents, $IPv, $strict);
		}

		public function getByEquipLabelPortLabel($equipLabel, $portLabel, $IPv = 4, $strict = false)
		{
			$adapter = $this->_selectAdapter($equipLabel);
			return $adapter->getByEquipLabelPortLabel($equipLabel, $portLabel, $IPv, $strict);
		}

		public function getByEquipLabelVlanId($equipLabel, $vlanId, $IPv = 4)
		{
			$adapter = $this->_selectAdapter($equipLabel);
			return $adapter->getByEquipLabelVlanId($equipLabel, $vlanId, $IPv);
		}

		public function getByEquipLabelVlanName($equipLabel, $vlanName, $IPv = 4)
		{
			$adapter = $this->_selectAdapter($equipLabel);
			return $adapter->getByEquipLabelVlanName($equipLabel, $vlanName, $IPv);
		}

		public function getByEquipLabelVlanRegex($equipLabel, $vlanRegex, $IPv = 4)
		{
			$adapter = $this->_selectAdapter($equipLabel);
			return $adapter->getByEquipLabelVlanRegex($equipLabel, $vlanRegex, $IPv);
		}

		public function getGatewayByEquipLabelSubnetId($equipLabel, $subnetId)
		{
			$adapter = $this->_selectAdapter($equipLabel);
			return $adapter->getGatewayBySubnetId($subnetId);
		}

		public function getVlanNamesByVlanIds(array $vlanIds, array $environments)
		{
			$vlanNames = array();

			foreach($this->_orchestrator as $service) {
				$result = $service->adapter->getVlanNamesByVlanIds($vlanIds, $environments);
				$vlanNames = array_merge($vlanNames, $result);
				// @todo array_unique ??
			}

			return $vlanNames;
		}

		public function getMcLagRowset($equipLabel, $portName, $IPv = 4)
		{
			$adapter = $this->_selectAdapter($equipLabel);
			return $adapter->getMcLagRowset($equipLabel, $portName, $IPv);
		}

		/**
		  * /!\ Dans l'IPAM le champ is_gateway peut être à TRUE pour seulement une entrée par subnet
		  * On ne peut donc pas avoir deux VIP VRRP dans le même subnet retournées si on test ce champ
		  **/
		public function getVrrpRowset($equipLabel, $subnetId, $IPv = 4)
		{
			$adapter = $this->_selectAdapter($equipLabel);
			return $adapter->getVrrpRowset($equipLabel, $subnetId, $IPv);
		}

		/**
		  * @param string $hostName
		  * @param bool $gateway
		  * @param int $IPv IP version 4 or 6
		  * @return false|array
		  */
		public function getNmi($hostName, $gateway = true, $IPv = 4)
		{
			$adapter = $this->_selectAdapter($hostName);

			$vlanName = '[-_](nmi)$';	// /!\ Arg 2 doit être passé par référence
			$mgmtDatas = $adapter->getByEquipLabelVlanRegex($hostName, $vlanName, $IPv);

			if(count($mgmtDatas) > 0)
			{
				$mgmtDatas = current($mgmtDatas);	// Compatible adressage unique

				if($gateway === true)
				{
					$mgmtDatas['gateway'] = $adapter->getGatewayBySubnetId($mgmtDatas);

					if($mgmtDatas['gateway'] === false) {
						throw new Exception("Impossible de récupérer l'IP de la Gateway depuis l'IPAM pour '".$hostName."'", E_USER_ERROR);
					}

					unset($mgmtDatas['subnetId']);
				}

				return $mgmtDatas;
			}
			else {
				return false;
			}
		}

		protected function _selectService($hostName)
		{
			$service = $this->_orchestrator->selectService($hostName);

			if($service !== false) {
				return $service;
			}
			else {
				throw new Exception("Unable to get IPAM service from orchestrator, no selector matches hostname '".$hostName."'", E_USER_ERROR);
			}
		}

		protected function _selectAdapter($hostName)
		{
			$service = $this->_selectService($hostName);
			return $service->adapter;
		}
	}