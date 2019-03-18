<?php

require_once(__DIR__ . '/../Vlans.php');

class Cw_vlans_controller extends Vlans_controller {

	public function GET ()
	{
		if(isset($this->_params->id) && $this->_params->id === 'search' && !isset($this->_params->id2))
		{
			$where = array();
			$searchOptions = array(
				'name' => 'name',
				'number' => 'number',
				'description' => 'description',
				'domainId' => 'domainId',
			);

			foreach($searchOptions as $fieldName => $searchOption)
			{
				if(isset($this->_params->{$searchOption}))
				{
					$param = $this->_params->{$searchOption};
					$param = preg_replace('/^(##not##)/i', '', $param, 1, $not);

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
				$query = "SELECT * FROM vlans WHERE ".implode(' AND ', $where)." ORDER BY `number`, `name`";
				$results = $this->Database->getObjectsQuery($query, $values);
			}
			else {
				$results = false;
			}

			if($results === false) {
				$this->Response->throw_exception(200, 'No vlan found');
			}
			else {
				return array("code" => 200, "data" => $this->prepare_result($results, 'vlans', true, true));
			}
		}
		else {
			return parent::GET();
		}
	}
}