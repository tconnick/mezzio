<?php

/**
 * Customer API
 *
 * This Model handles the requests to the database for reading, writing, changing and deleting of records.
 * Each method will return if it was successful and an object of the record(s).
 *
 * @package	customerapi
 * @author	TC
 * @since	v1.0
 */

namespace App\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;

use App\Model\customerapi;
use App\Model\DB;

class customerapiModel
{
	// ERROR Constants
	const GetAllStatementFailed = 'customerapi_1001';
	const GetAllStatementSuccess = 'customerapi_1701';
	
	const GetSingleStatementFailed = 'customerapi_1002';
	const GetSingleNotFound = 'customerapi_1003';
	const GetSingleStatementSuccess = 'customerapi_1702';
	
	const CreateStatementFailed = 'customerapi_1004';
	const CreateStatementSuccess = 'customerapi_1703';
	
	const UpdateStatementFailed = 'customerapi_1005';
	const UpdateNotFound = 'customerapi_1006';
	const UpdateStatementSuccess = 'customerapi_1704';
	
	const DeleteStatementFailed = 'customerapi_1007';
	const DeleteStatementSuccess = 'customerapi_1705';
	
	const InvalidData = 'customerapi_1501';
	// END ERROR Constants
	
	protected $keyFields = array('CMCUST');
	protected $uniqueFields = array('CMCUST');
	
	protected $filterFields = array('CMNAME' => '');
	
	protected $_adapter;
	protected $sql;
	protected $records = array(); // Stores a list of records
	
	public function __construct(DB $db)
	{
		$this->_adapter = $db->getAdapter();
		$this->sql = new Sql($this->_adapter);
	}
	
	/**
	 * Return the array of fields that identify a record in the primary file uniquely.
	 *
	 * @return array $uniqueFields Unique Field Array
	 */
	public function GetUniqueFields()
	{
		return $this->uniqueFields;
	}
	
	/**
	 * Return the array of filter fields with the current value.
	 *
	 * @return array $filterFields Associative filter field array
	 */
	public function GetFilterFields()
	{
		return $this->filterFields;
	}
	
	/**
	 * Set the value in the filters array.
	 *
	 * @param array $filters An associative array of filter name => value
	 */
	public function SetFilterValues($filters = array())
	{
		foreach($filters as $filter => $value)
		{
			if (isset($this->filterFields[$filter]))
			{
				$this->filterFields[$filter] = $value;
			}
		}
	}
	
	/**
	 * Returns a list of all records
	 *
	 * @param string $orderBy The string to be used to order the results
	 * @return array $result
	 */
	public function getAllRecords($orderBy = '')
	{
		$select = $this->sql->select();
		
		$customerapi = new customerapi();
		$customerapi->SetIsSingle(false);
		
		// this will add all columns for the list from the primary file plus any calculated field
		$select->columns($this->getColumnsFromArray($customerapi->GetListColumns()));
		
		$select->from('MU_CUSTF');
		
		
		// orderBy is expected to be a string including the sort order, e.g. CUSTNO DESC
		if ($orderBy !== '')
		{
			$select->order($orderBy);
		}
		
		
		$where = new Where();
		
		// Filter by CMNAME
		if ($this->filterFields['CMNAME'] != '')
		{
			$where->like('CMNAME', '%' . $this->filterFields['CMNAME'] . '%');
		}
		
		$select->where($where);
		
		
		$statement = $this->sql->prepareStatementForSqlObject($select);
		
		// execute the statement, if it fails catch the exception and return an error
		try {
			$records = $statement->execute();
		}
		catch (\Exception $exception)
		{
			$result['success'] = false;
			$result['messageCode'] = self::GetAllStatementFailed;
			return $result;
		}
		
		// put the returned records in an array we can use for the return
		$this->setRecordsArray($records, false);
		
		$result['success'] = true;
		$result['records'] = $this->records;
		$result['messageCode'] = self::GetAllStatementSuccess;
		
		return $result;
	}
	
	/**
	 * Returns a single record
	 *
	 * @param array $keyFieldValues Associative array of the keyfields and their value
	 * @return array $result
	 */
	public function getSingleRecord($keyFieldValues = array())
	{
		$errorMsg = $this->getRecord($keyFieldValues);
		
		// if there was an error retrieving the record, return a message
		if ($errorMsg !== "")
		{
			$result['success'] = false;
			$result['messageCode'] = self::GetSingleStatementFailed;
			$result['dberror'] = $errorMsg;
			return $result;
		}
		
		// If the record is not found
		if (count($this->records) == 0)
		{
			$result['success'] = false;
			$result['messageCode'] = self::GetSingleNotFound;
			return $result;
		}
		
		// return the record
		$result['success'] = true;
		$result['records'] = $this->records;
		$result['messageCode'] = self::GetSingleStatementSuccess;
		
		return $result;
	}
	
	/**
	 * Internal function to retrieve a specific record using the keyFieldValues
	 *
	 * @param array $keyFieldValues Associative array of the keyfields and their value
	 * @return string An error thrown by the database or an empty string when it was successfull
	 */
	private function getRecord($keyFieldValues = array())
	{
		$select = $this->sql->select();
		
		$customerapi = new customerapi();
		
		// this will add all columns for the list from the primary file plus any calculated field
		$select->columns($this->getColumnsFromArray($customerapi->GetRecordColumns()));
		
		$select->from('MU_CUSTF');
		
		
		// using the keyFieldValues to identify the record
		$where = $this->buildRecordWhere($keyFieldValues);
		$select->where($where);
		
		$statement = $this->sql->prepareStatementForSqlObject($select);
		
		try {
			$records = $statement->execute();
		}
		catch (\Exception $exception)
		{
			$this->records = array();
			return $exception->__toString();
		}
		
		$this->setRecordsArray($records);
		
		// No error, return an empty string.
		return "";
	}
	
	/**
	 * Using the keyFieldValues array, an object of type Where is build and returned
	 * to be used by the calling function. The comparison is an equalTo check.
	 *
	 * @param array $keyFieldValues Associative array of the keyfields and their value
	 * @return Where $where SQL Where object to identify a specific record
	 */
	private function buildRecordWhere($keyFieldValues)
	{
		$where = new Where();
		
		foreach($this->uniqueFields as $field)
		{
			if (array_key_exists($field, $keyFieldValues))
			{
				$where->equalTo($field, $keyFieldValues[$field]);
			}
		}
		
		return $where;
	}
	
	/**
	 * Add a new record
	 *
	 * @param object $customerapi Record details
	 * @return array $result
	 */
	public function addRecord($customerapi)
	{
		// validate the input, the function will return an array of invalid fields
		$invalidFields = $customerapi->validate();
		
		// if some field values were invalid return them in the result
		if (count($invalidFields) > 0)
		{
			$result['success'] = false;
			$result['messageCode'] = self::InvalidData;
			$result['invalidFields'] = $invalidFields;
			
			return $result;
		}
		
		$insert = $this->sql->insert('MU_CUSTF');
		
		// for the insert values we'll need to get the primary file fields only for the single record
		$insert->values($customerapi->getPrimaryFileDataArray());
		
		$statement = $this->sql->prepareStatementForSqlObject($insert);
		$result = array();
		
		try {
			$ret = $statement->execute();
		}
		catch (\Exception $exception)
		{
			$result['success'] = false;
			$result['messageCode'] = self::CreateStatementFailed;
			$result['debug'] = $exception->__toString();
			
			return $result;
		}
		
		// retrieve the record that was inserted so we can return it
		$errorMsg = $this->getRecord($customerapi->getDataArray());
		
		if ($errorMsg !== "")
		{
			$result['success'] = false;
			$result['messageCode'] = self::GetSingleStatementFailed;
			$result['debug'] = $errorMsg;
			return $result;
		}
		
		$result['success'] = true;
		$result['records'] = $this->records;
		$result['messageCode'] = self::CreateStatementSuccess;
		
		return $result;
	}
	
	/**
	 * Deletes a record from the table
	 *
	 * @param array $keyFieldValues Associative array of the keyfields and their value
	 * @return array $result
	 */
	public function deleteRecord($keyFieldValues)
	{
		$delete = $this->sql->delete('MU_CUSTF');
		
		// using the keyFieldValues to identify the record
		$where = $this->buildRecordWhere($keyFieldValues);
		$delete->where($where);
		
		$statement = $this->sql->prepareStatementForSqlObject($delete);
		
		try {
			$statement->execute();
		}
		catch (\Exception $exception)
		{
			$result['success'] = false;
			$result['messageCode'] = self::DeleteStatementFailed;
			$result['debug'] = $exception->__toString();
			return $result;
		}
		
		$result['success'] = true;
		$result['messageCode'] = self::DeleteStatementSuccess;
		return $result;
	}
	
	/**
	 * Updates a record
	 *
	 * @param object $customerapi Record details
	 * @return array $result
	 */
	public function updateRecord($customerapi)
	{
		// validate the input, the function will return an array of invalid fields
		$invalidFields = $customerapi->validate();
		
		// if some field values were invalid return them in the result
		if (count($invalidFields) > 0)
		{
			$result['success'] = false;
			$result['messageCode'] = self::InvalidData;
			$result['invalidFields'] = $invalidFields;
			
			return $result;
		}
		
		// retrieve the record first to make sure we have one to update
		$errorMsg = $this->getRecord($customerapi->getDataArray());
		
		// the records array is populated in the getRecord() method
		if (count($this->records) === 0)
		{
			$result['success'] = false;
			$result['messageCode'] = self::UpdateNotFound;
			
			return $result;
		}
		
		$update = $this->sql->update('MU_CUSTF');
		
		// for the update set we'll need to get the primary file fields only for the single record
		$update->set($customerapi->getPrimaryFileDataArray());
		
		$where = $this->buildRecordWhere($customerapi->getDisplayData());
		$update->where($where);
		
		$statement = $this->sql->prepareStatementForSqlObject($update);
		
		try {
			$statement->execute();
		}
		catch (\Exception $exception)
		{
			$result['success'] = false;
			$result['messageCode'] = self::UpdateStatementFailed;
			$result['debug'] = $exception->__toString();
			return $result;
		}
		
		// retrieve the record again to get the updated values and return it
		$errorMsg = $this->getRecord($customerapi->getDataArray());
		
		$result['success'] = true;
		$result['records'] = $this->records;
		$result['messageCode'] = self::UpdateStatementSuccess;
		
		return $result;
	}
	
	/**
	 * Helper method to get the column names for the select statement from a given array.
	 * This will return the fields of the primary file and any calculated field.
	 *
	 * @param array $fieldArray An array of fields to parse
	 * @return array $columns An array of column names
	 */
	private static function getColumnsFromArray($fieldArray)
	{
		$columns = array();
		foreach($fieldArray as $field => $options)
		{
			if (array_key_exists('calculatedField', $options))
			{
				$columns[$field] = new \Laminas\Db\Sql\Expression($options['calculatedField']);
			}
			else
			{
				$columns[$field] = $field;
			}
		}
		
		return $columns;
	}
	
	/**
	 * Sets an array of Records using the $results
	 *
	 * @param array $result The output of databases successfull execute statement
	 */
	private function setRecordsArray($result, $isSingle = true)
	{
		$this->records = array();
		
		foreach ($result as $row)
		{
			$record = new customerapi($row, $isSingle);
			array_push($this->records, $record);
		}
	}
	
}

