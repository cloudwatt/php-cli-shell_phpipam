<?php
	namespace Addon\Ipam;

	use mysqli;
	use ReflectionClass;

	use Core as C;

	class Connector_Sql extends Connector_Abstract
	{
		protected $_mysqli;
		protected $_mysqliStmt;


		public function __construct($server, $login, $password)
		{
			C\Tools::e(PHP_EOL."Connection SQL à l'IPAM @ ".$server." veuillez patienter ... ", 'blue');

			$this->_mysqli = new mysqli($server, $login, $password, 'phpipam');

			if($this->_mysqli->connect_error) {
				throw new Exception("Impossible de se connecter au serveur SQL de l'IPAM", E_USER_ERROR);
			}

			C\Tools::e("[OK]", 'green');
		}

		protected function _getPortVlanIpMaskByEquipLabel(&$equipLabel, array $where = null, array $args = null, $limit = null, $IPv = 4)
		{
			$whereArray = array(
				'ip.description = ?',
			);

			C\Tools::merge($whereArray, $where);
			$whereArray[] = $this->_getWherePartForIPv($IPv);
			$where = implode(' AND ', $whereArray);

			$argArray = array('s', &$equipLabel);

			if($args !== null) {
				$argArray[0] .= array_shift($args);
				C\Tools::merge($argArray, $args);
			}

			$limit = (is_int($limit)) ? ('LIMIT '.$limit) : ('');

			$this
			->prepare(
				'SELECT ip.port as port, vl.number as vlanId, vl.name as vlan, ip.ip_addr as ip, sn.mask as mask, sn.id as subnetId '
				. 'FROM ipaddresses ip '
				. 'LEFT JOIN subnets sn ON ip.subnetId = sn.id '
				. 'LEFT JOIN vlans vl ON sn.vlanId = vl.vlanId '
				. 'WHERE '.$where.' '
				. $limit
			)
			->bindArgs($argArray)
			->execute();

			return $this;
		}

		public function getByEquipLabel($equipLabel, $IPv = 4)
		{
			$vars = array();

			$whereArray = array(
				'ip.port != ""',
				'ip.port IS NOT NULL'
			);

			$this->_getPortVlanIpMaskByEquipLabel($equipLabel, $whereArray, null, null, $IPv);
			$this->bindVars(array(&$portName, &$vlanId, &$vlanName, &$ip, &$cidrMask, &$subnetId));

			while($this->fetch())
			{
				$ip = long2ip((float) $ip);
				$netMask = long2ip(-1 << (32 - (int) $cidrMask));

				$vars[] = array(
					'portName' => $portName,
					'vlanId' => $vlanId,
					'vlanName' => $vlanName,
					'address' => $ip,
					'netMask' => $netMask,
					'cidrMask' => $cidrMask,
					'subnetId' => $subnetId
				);
			}

			$this->close();
			return $vars;
		}

		public function getByEquipLabelVlanName($equipLabel, $vlanName, $IPv = 4)
		{
			$vlanName = $this->_mysqli->escape_string($vlanName);
			return $this->getByEquipLabelVlanRegex($equipLabel, $vlanName, $IPv);
		}

		public function getByEquipLabelVlanRegex($equipLabel, $vlanRegex, $IPv = 4)
		{
			// REGEXP is not case sensitive, except when used with binary strings

			$whereArray = array('vl.name REGEXP ?');
			$argArray = array('s', &$vlanRegex);
			$limit = 1;

			$this->_getPortVlanIpMaskByEquipLabel($equipLabel, $whereArray, $argArray, $limit, $IPv);
			$this->bindVars(array(&$portName, &$vlanId, &$vlanRegex, &$ip, &$cidrMask, &$subnetId));

			if($this->fetch())
			{
				$ip = long2ip((float) $ip);
				$netMask = long2ip(-1 << (32 - (int) $cidrMask));

				$vars = array(
					'portName' => $portName,
					'vlanId' => $vlanId,
					'vlanName' => $vlanRegex,
					'address' => $ip,
					'netMask' => $netMask,
					'cidrMask' => $cidrMask,
					'subnetId' => $subnetId
				);
			}

			$this->close();

			return (isset($vars)) ? ($vars) : (false);
		}

		public function getByEquipLabelPortLabel($equipLabel, $portLabel, $IPv = 4)
		{
			$whereArray = array('ip.port = ?');
			$argArray = array('s', &$portLabel);
			$limit = 1;

			$this->_getPortVlanIpMaskByEquipLabel($equipLabel, $whereArray, $argArray, $limit, $IPv);
			$this->bindVars(array(&$portName, &$vlanId, &$vlanName, &$ip, &$cidrMask, &$subnetId));

			if($this->fetch())
			{
				$ip = long2ip((float) $ip);
				$netMask = long2ip(-1 << (32 - (int) $cidrMask));

				$vars = array(
					'portName' => $portName,
					'vlanId' => $vlanId,
					'vlanName' => $vlanName,
					'address' => $ip,
					'netMask' => $netMask,
					'cidrMask' => $cidrMask,
					'subnetId' => $subnetId
				);
			}

			$this->close();

			return (isset($vars)) ? ($vars) : (false);
		}

		/*public function getEquipLabelByIp($IP, $IPv = 4)
		{
			$rows = array();
			$IPAM = $this->_IPAM['SEC'];

			$whereArray = array(
				'ip.ip_addr = ?',
			);

			$whereArray[] = $IPAM->getWherePartForIPv($IPv);
			$where = implode(' AND ', $whereArray);

			$longIP = self::IPv4ToLong($IP);
			$argArray = array('i', &$longIP);

			$IPAM
			->prepare(
				'SELECT ip.ip_addr as ip, ip.dns_name as hostname, ip.description as description, vl.name as interface '
				. 'FROM ipaddresses ip '
				. 'JOIN subnets sn ON ip.subnetId = sn.id '
				. 'LEFT JOIN vlans vl ON sn.vlanId = vl.vlanId '
				. 'WHERE '.$where.' '
			)
			->bindArgs($argArray)
			->execute()
			->bindVars(array(&$ip, &$hostname, &$description, &$interface));

			while($IPAM->fetch())
			{
				$ip = $IPAM::longIpToIPv4($ip);

				if($ip !== $IP) {
					throw new Exception("Les IPs ne correspondent pas entre l'Arkoon et l'IPAM", E_USER_ERROR);
				}

				$rows[] = array(
					'ip' => $ip,
					'hostname' => $hostname,
					'description' => $description,
					'interface' => $interface,
				);
			}

			$IPAM->close();
			return (count($rows) > 0) ? ($rows) : (false);
		}*/

		/*public function getSubnetLabelByNetworkIpMask($netIp, $netMask, $IPv = 4)
		{
			$rows = array();
			$IPAM = $this->_IPAM['SEC'];

			$whereArray = array(
				'sn.subnet = ?',
				'sn.mask = ?',
				'sn.sectionId != 1',		// Overwiew = sectionId 1
			);

			$whereArray[] = $IPAM->getWherePartForIPv($IPv, 'sn', 'subnet');
			$where = implode(' AND ', $whereArray);

			$longNetIp = self::IPv4ToLong($netIp);
			$argArray = array('ii', &$longNetIp, &$netMask);

			$IPAM
			->prepare(
				'SELECT sn.subnet as ip, sn.mask as mask, sn.description as description, vl.name as interface '
				. 'FROM subnets sn '
				. 'LEFT JOIN vlans vl ON sn.vlanId = vl.vlanId '
				. 'WHERE '.$where.' '
			)
			->bindArgs($argArray)
			->execute()
			->bindVars(array(&$ip, &$mask, &$name, &$interface));

			while($IPAM->fetch())
			{
				$ip = $IPAM::longIpToIPv4($ip);

				if($ip !== $netIp) {
					throw new Exception("Les IPs ne correspondent pas entre l'Arkoon et l'IPAM", E_USER_ERROR);
				}
				elseif($mask !== $netMask) {
					throw new Exception("Les masques ne correspondent pas entre l'Arkoon et l'IPAM", E_USER_ERROR);
				}

				$rows[] = array(
					'ip' => $ip,
					'mask' => $mask,
					'name' => $name,
					'interface' => $interface,
				);
			}

			$IPAM->close();
			return (count($rows) > 0) ? ($rows) : (false);
		}*/

		public function getGatewayBySubnetId($subnetId)
		{
			if(is_array($subnetId) && array_key_exists('subnetId', $subnetId)) {
				$subnetId = $subnetId['subnetId'];
			}

			if(C\Tools::is('int&&>0', $subnetId))
			{
				$this
					->prepare("SELECT ip_addr as gateway FROM ipaddresses WHERE subnetId=? AND is_gateway = 1 LIMIT 1")
					->bindArgs(array('i', &$subnetId))
					->execute()
					->bindVars(array(&$gateway))
					->fetch();

				$this->close();

				if(isset($gateway)) {
					return long2ip((float) $gateway);
				}
			}

			return false;
		}

		/*public function getVlanNameByVlanId($vlanId)
		{
			$this
				->prepare("SELECT name FROM vlans WHERE number=? LIMIT 1")
				->bindArgs(array('i', &$vlanId))
				->execute()
				->bindVars(array(&$vlanLabel))
				->fetch();

			$this->close();

			return (isset($vlanLabel)) ? ($vlanLabel) : (false);
		}*/

		public function getVlanNamesByVlanIds(array $vlanIds, array $environments)
		{
			$vlanNames = array();

			$this
				->prepare("SELECT name FROM vlans WHERE number=?")
				->bindArgs(array('i', &$vlanId));

			foreach($vlanIds as $vlanId)
			{
				$this
					->execute()
					->bindVars(array(&$vlanName));

				while($this->fetch())
				{
					foreach($environments as $environment)
					{
						if(preg_match('#^('.preg_quote($environment).')[-_]#i', $vlanName)) {
							$vlanNames[$vlanName] = $vlanId;
							break(2);
						}
					}
				}
			}

			return $vlanNames;
		}

		public function getMcLagRowset($hostName, $portName, $IPv = 4)
		{
			$rowset = array();
			$IPv = $this->_getWherePartForIPv($IPv);

			$this
				->prepare(
					  'SELECT ip.subnetId '
					. 'FROM ipaddresses ip '
					. 'JOIN subnets sn ON ip.subnetId = sn.id '
					. 'WHERE ip.description = ? AND ip.port = ? AND '
						. ' LOWER(sn.description) LIKE "%iccp%" AND '.$IPv.' '
					. 'LIMIT 1'
				)
				->bindArgs(array('ss', &$hostName, &$portName))
				->execute()
				->bindVars(array(&$subnetId))
				->fetch();

			$this->close();

			$this
			->prepare(
				'SELECT ip.description as description, ip.ip_addr as ip, sn.mask as mask '
				. 'FROM ipaddresses as ip '
				. 'JOIN subnets sn ON ip.subnetId = sn.id '
				. 'WHERE ip.subnetId = ? AND '.$IPv
			)
			->bindArgs(array('i', &$subnetId))
			->execute()
			->bindVars(array(&$description, &$longIp, &$cidrMask));

			while($this->fetch())
			{
				$ip = self::longIpToIPv4($longIp);
				$netMask = self::cidrMaskToNetMask($cidrMask);

				$rowset[] = array(
					'hostName' => $description,
					'iccp' => $ip, 'address' => $ip,
					'cidrMask' => $cidrMask, 'netMask' => $netMask
				);
			}

			$this->close();
			return $rowset;
		}

		/**
		  * /!\ Dans l'IPAM le champ is_gateway peut être à TRUE pour seulement une entrée par subnet
		  * On ne peut donc pas avoir deux VIP VRRP dans le même subnet retournées si on test ce champ
		  **/
		public function getVrrpRowset($hostName, $subnetId, $IPv = 4)
		{
			$rowset = array();
			$hostName = substr($hostName, 0, 17);
			$IPv = $this->_getWherePartForIPv($IPv);

			$this
			->prepare(
				'SELECT ip.ip_addr as ip, sn.mask as mask, ip.note as note '
				. 'FROM ipaddresses ip '
				. 'LEFT JOIN subnets sn ON ip.subnetId = sn.id '
				. 'WHERE ip.subnetId = ? AND SUBSTRING(ip.description FROM 1 FOR 17) LIKE ? '
				. 'AND ip.note LIKE "%vrrp%" AND '.$IPv
			)
			->bindArgs(array('is', &$subnetId, &$hostName))
			->execute()
			->bindVars(array(&$ip, &$cidrMask, &$note));

			while($this->fetch())
			{
				$ip = long2ip((float) $ip);
				$netMask = long2ip(-1 << (32 - (int) $cidrMask));

				$rowset[] = array(
					'note' => $note,
					'address' => $ip,
					'netMask' => $netMask,
					'cidrMask' => $cidrMask
				);
			}

			$this->close();
			return $rowset;
		}

		protected function prepare($sql)
		{
			$this->_mysqliStmt = $this->_mysqli->stmt_init();

			if($this->_mysqliStmt->prepare($sql)) {
				return $this;
			}
			else {
				print_r($this->_mysqliStmt->error_list);
				throw new Exception("Impossible de préparer la requête SQL pour l'IPAM @ ".$sql, E_USER_ERROR);
			}
		}

		protected function bindArgs(array $args)
		{
			$ReflectionClass = new ReflectionClass('mysqli_stmt');
			$ReflectionMethod = $ReflectionClass->getMethod('bind_param');
			$ReflectionMethod->invokeArgs($this->_mysqliStmt, $args);
			return $this;
		}

		protected function execute()
		{
			if($this->_mysqliStmt->execute()) {
				return $this;
			}
			else {
				throw new Exception("Impossible d'exécuter la requête SQL pour l'IPAM", E_USER_ERROR);
			}
		}

		protected function bindVars(array $args)
		{
			$ReflectionClass = new ReflectionClass('mysqli_stmt');
			$ReflectionMethod = $ReflectionClass->getMethod('bind_result');
			$ReflectionMethod->invokeArgs($this->_mysqliStmt, $args);
			return $this;
		}

		protected function fetch()
		{
			return $this->_mysqliStmt->fetch();
		}

		/*public function result($resultType = MYSQLI_ASSOC)
		{
			$mysqliResult = $this->_mysqli->store_result();
			$result = $mysqliResult->fetch_all($resultType);
			$mysqliResult->free();
			return $result;
		}*/

		public function close()
		{
			if($this->_mysqliStmt !== null) {
				$this->_mysqliStmt->close();
				$this->_mysqliStmt = null;
			}

			return $this;
		}

		protected function _getWherePartForIPv($IPv, $table = 'ip', $field = 'ip_addr')
		{
			switch($IPv)
			{
				case 6:
				case 'v6':
				case 'ipv6': {
					$operator = '>';
					break;
				}

				case 4:
				case 'v4':
				case 'ipv4':
				default: {
					$operator = '<=';
				}
			}

			return 'length('.$table.'.'.$field.') '.$operator.' 10';
		}

		public function __destruct()
		{
			$this->close();
		}
	}