<?php
namespace Intersob\Models;
use Nette;

abstract class BaseModel extends Nette\Object {
	/** @var string */
	protected $name;
	
	/** @var Nette\Database\Connection */
	protected $connection;

	public function __construct(Nette\Database\Connection $connection) {
		$this->connection = $connection;
	}
	
	/**
	 * Returns next row of result.
	 * @return Nette\Database\Table\ActiveRow or FALSE if there is no row
	 */
	public function find($key) {
		if(empty($key)) {
			throw new InvalidArgumentException('Empty key');
		}
		return $this->getTableSelection()->find($key)->fetch();
	}
	
	/**
	 * Returns complete table
	 * @return Nette\Database\Table\Selection
	 */
	public function findAll() {
		return $this->getTableSelection();
	}
	
	/**
	 * Inserts row in a table and call 
	 * @param  mixed array($column => $value)|Traversable for single row insert or Selection|string for INSERT ... SELECT
	 * @param callback to be done in transaction
	 * @return ActiveRow or FALSE in case of an error or number of affected rows for INSERT ... SELECT
	 */
	public function insert($data, \Closure $postClosure = NULL) {
		$this->connection->beginTransaction();
		$table = $this->getTableSelection();
		try {
			$insertedRow = $table->insert($data);
			if($postClosure !== NULL && is_callable($postClosure)) {
				$postClosure($this, $insertedRow);
			}
			$this->connection->commit();
			return $insertedRow;
		} catch(\PDOException $ex) {
			$this->connection->rollBack();
			throw $ex;
		}
		
	}
	
	/**
	 * Updates all rows in result set.
	 * @param int primary key of data
	 * @param  array|\Traversable ($column => $value)
	 * @param callback to be done in after update (in transaction)
	 * @param callback to be done in before update (in transaction)
	 * @return int number of affected rows or FALSE in case of an error
	 */
	public function update($key,$data, \Closure $postClosure = NULL, \Closure $preClosure = NULL) {
		if(empty($key)) {
			throw new \InvalidArgumentException('Empty key');
		}
		$this->connection->beginTransaction();
		$table = $this->getTableSelection();
		try {
			$oldRow = $table->find($key)->fetch();
			if($preClosure !== NULL && is_callable($preClosure)) {
				$preClosure($this, $oldRow);
			}
			$oldRow->update($data);
			if($postClosure !== NULL && is_callable($postClosure)) {
				$postClosure($this, $oldRow);
			}
			$this->connection->commit();
			return $oldRow;
		} catch(\PDOException $ex) {
			$this->connection->rollBack();
			throw $ex;
		}
		
	}
	
	/**
	 * Delete row
	 * @param int primary key of data
	 * @param callback to be done in before delete (in transaction)
	 * @param callback to be done in after delete (in transaction)
	 * @return int number of affected rows or FALSE in case of an error
	 */
	public function delete($key, \Closure $preClosure = NULL, \Closure $postClosure = NULL) {
		if(empty($key)) {
			throw new InvalidArgumentException('Empty key');
		}
		$this->connection->beginTransaction();
		$table = $this->getTableSelection();
		try {
			$oldRow = $table->find($key)->fetch();
			if($preClosure !== NULL && is_callable($preClosure)) {
				$preClosure($this, $oldRow);
			}
			$return = $oldRow->delete();
			if($postClosure !== NULL && is_callable($postClosure)) {
				$postClosure($this, $oldRow);
			}
			$this->connection->commit();
			return $return;
		} catch(\PDOException $ex) {
			$this->connection->rollBack();
			throw $ex;
		}
	}
	
	/**
	 * Return table name in underscore notation. Name is given from class name or name attribute
	 * @return string
	 */
	public function getTableName() {
		if(isSet($this->name)) {
			return $this->name;
		}
		$class = new \ReflectionClass($this);
		$ns = $class->getNamespaceName();
		$name = $class->getName();
		$name = substr($name, strlen($ns)+1, strlen($name));
		$this->name = $this->fromCamelCase($name);
		return $this->name;
	}
	
	private function fromCamelCase($str) {
		$str[0] = strtolower($str[0]);
		$func = create_function('$c', 'return "_" . Nette\Utils\Strings::lower($c[1]);');
		return preg_replace_callback('/([A-Z])/', $func, $str);
	}

	
	/**
	 * Return table selection for this table
	 * @return \Nette\Database\Table\Selection
	 */
	public function getTableSelection() {
		return $this->connection->table($this->getTableName());
	}
	
	/**
	 * Fallback method for obtainting connection
	 * @return \Nette\Database\Connection
	 */
	public function getConnection() {
		return $this->connection;
	}

}