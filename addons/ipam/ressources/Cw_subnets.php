<?php

require_once(__DIR__ . '/../Subnets.php');

class Cw_subnets_controller extends Subnets_controller {

	public function GET()
	{
		if(isset($this->_params->id) && $this->_params->id === 'search' && !isset($this->_params->id2))
		{
			$where = array();
			$searchOptions = array(
				'subnet' => 'subnet',
				'mask' => 'mask',
				'ip_version' => 'ip_version',
				'description' => 'description',
				'isFolder' => 'isFolder',
				'masterSubnetId' => 'subnetId',
				'sectionId' => 'sectionId'
			);

			foreach($searchOptions as $fieldName => $searchOption)
			{
				if(isset($this->_params->{$searchOption}))
				{
					$param = $this->_params->{$searchOption};
					$param = preg_replace('/^(##not##)/i', '', $param, 1, $not);

					switch($searchOption)
					{
						case 'subnet':
							$subnetFound = preg_match('/(?:##[^#]+##)*(.*)/i', $param, $subnet);
							if($subnetFound && count($subnet) > 0) {
								$param = $this->Subnets->transform_address($subnet[1], "decimal");
								break;
							}
							else {
								continue(2);
							}
							break;
						case 'ip_version':
							if($param == 4) $operator = '<=';
							elseif($param == 6) $operator = '>';
							else continue(2);
							$where[] = 'length(subnet) '.$operator.' 10';
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
				$query = "SELECT * FROM subnets WHERE ".implode(' AND ', $where)." ORDER BY subnet, mask";
				$results = $this->Database->getObjectsQuery($query, $values);
			}
			else {
				$results = false;
			}

			if($results === false) {
				$this->Response->throw_exception(200, 'No subnet found');
			}
			else
			{
				// /!\ method are private!
				/*foreach($results as $key => $result)
				{
					$ns = $this->read_subnet_nameserver($result->nameserverId);

					if($ns !== false) {
						$result->nameservers = $ns;
					}

					$gateway = $this->read_subnet_gateway($result->id);

					if($gateway !== false) {
						$result->gatewayId = $gateway->id;
						$gateway = $this->transform_address($gateway);
						$result->gateway = $gateway;
					}

					$result->permissions = $this->User->get_user_permissions_from_json($result->permissions);

					// location details
					if(!empty($result->location)) {
						$result->location = $this->Tools->fetch_object("locations", "id", $result->location);
					}
					else {
						$result->location = array();
					}

					// erase old values
					$results[$key] = $result;
				}*/

				return array("code" => 200, "data" => $this->prepare_result($results, 'subnets', true, true));
			}
		}
		else {
			return parent::GET();
		}
	}

	protected function remove_folders($result)
	{
		$controller = $this->_params->controller;
		$this->_params->controller = 'subnets';
		$return = parent::remove_folders($result);
		$this->_params->controller = $controller;
		return $return;
	}

	protected function remove_subnets($result)
	{
		$controller = $this->_params->controller;
		$this->_params->controller = 'subnets';
		$return = parent::remove_subnets($result);
		$this->_params->controller = $controller;
		return $return;
	}
}