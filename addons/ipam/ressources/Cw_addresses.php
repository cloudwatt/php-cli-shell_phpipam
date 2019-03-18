<?php

require_once(__DIR__ . '/../Addresses.php');

class Cw_addresses_controller extends Addresses_controller  {

	public function GET()
	{
		if(@$this->_params->id == 'search' && !isset($this->_params->id2))
		{
			$where = array();
			$searchOptions = array(
				'ip_addr' => 'ip',
				'ip_version' => 'ip_version',
				'dns_name' => 'hostname',
				'description' => 'description',
				'port' => 'port',
				'note' => 'note',
				'subnetId' => 'subnetId'
			);

			foreach($searchOptions as $fieldName => $searchOption)
			{
				if(isset($this->_params->{$searchOption}))
				{
					$param = $this->_params->{$searchOption};
					$param = preg_replace('/^(##not##)/i', '', $param, 1, $not);

					switch($searchOption)
					{
						case 'ip':
							$ipAddrFound = preg_match('/(?:##[^#]+##)*(.*)/i', $param, $ipAddr);
							if($ipAddrFound && count($ipAddr) > 0) {
								$param = $this->Addresses->transform_address($ipAddr[1], "decimal");
								break;
							}
							else {
								continue(2);
							}
						case 'ip_version':
							if($param == 4) $operator = '<=';
							elseif($param == 6) $operator = '>';
							else continue(2);
							$where[] = 'length(ip_addr) '.$operator.' 10';
							continue(2);
					}

					switch(true)
					{
						case (preg_match('/^(##present##)$/i', $param)):
							if(!$not) {
								$where[] = $fieldName.' != ""';
								$where[] = $fieldName.' IS NOT NULL';
							}
							else {
								$where[] = '('.$fieldName.' = "" OR '.$fieldName.' IS NULL)';
							}
							continue(2);
						case (preg_match('/^(##like##)/i', $param)):
							$param = substr($param, 8);
							$operator = (!$not) ? ('LIKE') : ('NOT LIKE');
							break;
						case (preg_match('/^(##regexp##)/i', $param)):
							$param = substr($param, 10);
							$operator = (!$not) ? ('REGEXP') : ('NOT REGEXP');
							break;
						default:
							$operator = (!$not) ? ('=') : ('!=');
					}

					$where[] = $fieldName.' '.$operator.' ?';
					$values[] = $param;
				}
			}

			if(count($where) > 0) {
				$query = "SELECT * FROM ipaddresses WHERE ".implode(' AND ', $where)." ORDER BY ip_addr";
				$results = $this->Database->getObjectsQuery($query, $values);
			}
			else {
				$results = false;
			}

			if($results === false) {
				$this->Response->throw_exception(200, 'Addresses not found');
			}
			else {
				return array("code" => 200, "data" => $this->prepare_result($results, 'addresses', true, true));
			}
		}
		else {
			return parent::GET();
		}
	}
}