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

		public static function cidrMaskToBinary($cidrMask, $IPv)
		{
			if($IPv === 4) {
				return (~((1 << (32 - $cidrMask)) - 1));
			}
			elseif($IPv === 6)
			{
				$netMask = str_repeat("f", $cidrMask / 4);

				switch($cidrMask % 4)
				{
					case 0:
						break;
					case 1:
						$netMask .= "8";
						break;
					case 2:
						$netMask .= "c";
						break;
					case 3:
						$netMask .= "e";
						break;
				}

				$netMask = str_pad($netMask, 32, '0');
				$binMask = pack("H*", $netMask);

				return $binMask;
			}

			return false;
		}

		public static function netMaskToCidr($netMask)
		{
			$longMask = ip2long($netMask);
			$longBase = ip2long('255.255.255.255');
			return 32 - log(($longMask ^ $longBase)+1, 2);
		}

		public static function firstSubnetIp($ip, $mask)
		{
			if(($isIPv4 = Tools::is('ipv4', $ip)) === true || ($isIPv6 = Tools::is('ipv6', $ip)) === true)
			{
				if(Tools::is('int&&>0', $mask)) {
					$IPv = ($isIPv4) ? (4) : (6);
					$mask = self::cidrMaskToBinary($mask, $IPv);
				}
				elseif(defined('AF_INET6')) {
					$mask = inet_pton($mask);
				}
				else {
					return false;
				}

				// IPv4 & IPv6 compatible
				if(defined('AF_INET6')) {
					$ip = inet_pton($ip);
					return inet_ntop($ip & $mask);
				}
				// IPv4 only
				elseif($isIPv4) {
					$netIp = (ip2long($ip) & $mask);
					return long2ip($netIp);
				}
			}

			return false;
		}

		public static function lastSubnetIp($ip, $mask)
		{
			if(($isIPv4 = Tools::is('ipv4', $ip)) === true || ($isIPv6 = Tools::is('ipv6', $ip)) === true)
			{
				if(Tools::is('int&&>0', $mask)) {
					$IPv = ($isIPv4) ? (4) : (6);
					$mask = self::cidrMaskToBinary($mask, $IPv);
				}
				elseif(defined('AF_INET6')) {
					$mask = inet_pton($mask);
				}
				else {
					return false;
				}

				// IPv4 et IPv6 compatible
				if(defined('AF_INET6')) {
					$ip = inet_pton($ip);
					return inet_ntop($ip | ~ $mask);
				}
				// IPv4 only
				elseif($isIPv4) {
					$bcIp = (ip2long($ip) | (~ $mask));
					return long2ip($bcIp);
				}
			}

			return false;
		}

		public static function networkIp($ip, $mask)
		{
			return self::firstSubnetIp($ip, $mask);
		}

		public static function broadcastIp($ip, $mask)
		{
			if(Tools::is('ipv4', $ip)) {
				return self::lastSubnetIp($ip, $mask);
			}
			elseif(Tools::is('ipv6', $ip)) {
				return 'ff02::1';
			}
			else {
				return false;
			}
		}

		public static function networkSubnet($cidrSubnet)
		{
			$subnetPart = explode('/', $cidrSubnet);

			if(count($subnetPart) === 2) {
				$networkIp = self::firstSubnetIp($subnetPart[0], $subnetPart[1]);
				return $networkIp.'/'.$subnetPart[1];
			}
			else {
				return false;
			}
		}
	}