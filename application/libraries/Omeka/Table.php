<?php 
/**
* 
*/
class Omeka_Table
{
	//What kind of model should this table class retrieve from the DB
	protected $_target;
	
	public function __construct($targetModel)
	{
		$this->_target = $targetModel;
	}
	
	//Duplicated in the Omeka_Record class
	public function getConn()
	{
		return get_db();
	}
	
	public function hasColumn($field)
	{
		$cols = $this->getColumns();
		
		return in_array($field, $cols);
	}

	//This has to be here and not in the model itself because get_class_vars() returns private/protected
	//when called inside its own class	
	public function getColumns()
	{
		return array_keys(get_class_vars($this->_target));
	}
	
	public function getTableName()
	{
		$target = $this->_target;
		return $this->getConn()->$target;
	}
	
	//Find a single record given an ID
	public function find($id)
	{		
		//Cast to integer to prevent SQL injection
		$id = (int) $id;

		$table = $this->getTableName();

		$sql = "SELECT t.* FROM $table t WHERE t.id = $id LIMIT 1";
//var_dump( $sql );exit;
		$records = $this->fetchObjects($sql);

		if (count($records) === 0) {
		    return false;
		}

		return current($records);
	}
	
	public function findAll()
	{
		$table = $this->getTableName();
		
		$sql = "SELECT t.* FROM $table t";
		
		return $this->fetchObjects($sql);
	}
	
	public function findBySql($sql, array $params=null, $findOne=false)
	{
		$table = $this->getTableName();
		
		$sql = "SELECT t.* FROM $table t WHERE $sql";
		
		return $this->fetchObjects($sql, $params, $findOne);
	}
	
	public function count()
	{
		$table = $this->getTableName();
		
		$select = new Omeka_Select;
		$select->from("$table t ", "COUNT(DISTINCT(t.id))");
		
		return get_db()->fetchOne($select);
	}
	
	public function checkExists($id)
	{
		$table = $this->getTableName();
		
		$select = new Omeka_Select;
		$select->from("$table t", "COUNT(DISTINCT(t.id))")
				->where("t.id = ?", $id);
				
		$count = get_db()->fetchOne($select);
		
		return ($count == 1);
	}
	
	public function fetchObjects($sql, $params=array(), $onlyOne=false)
	{
		$db = $this->getConn();
		
		$res = $db->query((string) $sql, $params);
		
		$data = $res->fetchAll();
		
		if(!count($data) or !$data) {
			return !$onlyOne ? array() : null;
		}
					
		if($onlyOne) return $this->recordFromData(current($data));
		
		//Would use fetchAll() but it can be memory-intensive
		$objs = array();
		foreach ($data as $k => $row) {
			$objs[$k] = $this->recordFromData($row);
		}
		
		return $objs;
	}
	
	protected function recordFromData(array $data)
	{
		$class = $this->_target;
		$obj = new $class;
		foreach ($data as $key => $value) {
			$obj->$key = $value;
		}
		
		return $obj;
	}
}

?>
