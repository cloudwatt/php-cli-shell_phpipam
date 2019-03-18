<?php

require_once(__DIR__ . '/../Sections.php');

class Cw_sections_controller extends Sections_controller {

	public function GET()
	{
		if(isset($this->_params->id) && $this->_params->id === 'search')
		{
			$where = array();
			$searchOptions = array(
				'name' => 'name',
				'description' => 'description',
				'masterSection' => 'masterSection',
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
				$query = "SELECT * FROM sections WHERE ".implode(' AND ', $where)." ORDER BY `name`, `order`";
				$results = $this->Database->getObjectsQuery($query, $values);
			}
			else {
				$results = false;
			}
			
			if($results === false) {
				$this->Response->throw_exception(200, 'Sections not found');
			}
			else {
				return array("code" => 200, "data" => $this->prepare_result($results, 'sections', true, true));
			}
		}
		else {
			return parent::GET();
		}
	}
}