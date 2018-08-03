<?php
	abstract class IPAM_Tools
	{
		public static function filter(array $values, array $filters)
		{
			$result = array();

			$filters = array_map('mb_strtolower', $filters);
			$filters = array_unique($filters);

			$fields = array(
				'ip' => array('address', 'netMask', 'cidrMask'),
				'vlan' => array('vlanId', 'vlanName'),
				'subnet' => array('subnetId'),
				'physical' => array('portName'),
			);

			foreach($filters as $filter)
			{
				switch($filter)
				{
					case 'ip':
					case 'vlan':
					case 'subnet':
					case 'physical':
						$fValues = array_intersect_key($values, array_flip($fields[$filter]));
						$result = array_merge($result, $fValues);
						break;
				}
			}

			return $result;
		}

		public static function cidrMatch($ip, $subnet)
		{
			list($subnet, $mask) = explode('/', $subnet);
			$ip = ip2long($ip);
			$subnet = ip2long($subnet);
			$mask = -1 << (32 - (int) $mask);
			$subnet &= $mask; // in case the supplied subnet was not correctly aligned
			return ($ip & $mask) == $subnet;
		}

		public static function subnetInSubnet($a, $b)
		{
			list($ip, $mask) = explode('/', $a);
			$ip = ip2long($ip);
			$mask = -1 << (32 - (int) $mask);
			$ip = $ip & $mask;
			$ip = self::longIpToIPv4($ip);
			return self::cidrMatch($ip, $b);
		}

		public static function IPv4ToLong($ip)
		{
			return ip2long($ip);
		}

		public static function longIpToIPv4($longIp)
		{
			return long2ip((float) $longIp);
		}

		public static function cidrMaskToNetMask($cidrMask)
		{
			return long2ip(-1 << (32 - (int) $cidrMask));
		}

		public static function netMaskToCidr($netMask)
		{
			$longMask = ip2long($netMask);
			$longBase = ip2long('255.255.255.255');
			return 32 - log(($longMask ^ $longBase)+1, 2);
		}

		public static function networkIp($ip, $mask)
		{
			if(Tools::is('int&&>0', $mask)) {
				$mask = self::cidrMaskToNetMask($mask);
			}

			$netIp = (ip2long($ip) & ip2long($mask));
			return long2ip($netIp);
		}

		public static function broadcastIp($ip, $mask)
		{
			if(Tools::is('int&&>0', $mask)) {
				$mask = self::cidrMaskToNetMask($mask);
			}

			$bcIp = (ip2long($ip) | (~ ip2long($mask)));
			return long2ip($bcIp);
		}

		public static function networkSubnet($cidrSubnet)
		{
			$subnetPart = explode('/', $cidrSubnet);

			if(count($subnetPart) === 2) {
				$networkIp = self::networkIp($subnetPart[0], $subnetPart[1]);
				return $networkIp.'/'.$subnetPart[1];
			}
			else {
				return false;
			}
		}
	}