<?php

/**
 * Customer API
 *
 * @package	customerapi
 * @author	TC
 * @since	v1.0
 */

namespace App\Model;

class customerapi
{
	// Validation Error Constants
	const NotNumeric = 'customerapi_1502';
	const Required = 'customerapi_1503';
	
	protected $data;
	protected $isSingleRecord = true;
	
	protected $formFields = array(
		"CMCUST" => array("file" => "MU_CUSTF", "type" => "N", "length" => 7, "text" => "Customer Number", "keyField" => true, "required" => true, "value" => ""),
		"CMNAME" => array("file" => "MU_CUSTF", "type" => "A", "length" => 50, "text" => "Customer Name", "keyField" => false, "required" => true, "value" => ""),
		"CMADR1" => array("file" => "MU_CUSTF", "type" => "A", "length" => 50, "text" => "Address 1", "keyField" => false, "required" => true, "value" => ""),
		"CMADR2" => array("file" => "MU_CUSTF", "type" => "A", "length" => 50, "text" => "Address 2", "keyField" => false, "required" => true, "value" => ""),
		"CMCITY" => array("file" => "MU_CUSTF", "type" => "A", "length" => 20, "text" => "City", "keyField" => false, "required" => true, "value" => ""),
		"CMSTATE" => array("file" => "MU_CUSTF", "type" => "A", "length" => 2, "text" => "State/Prov", "keyField" => false, "required" => true, "value" => ""),
		"CMCOUNT" => array("file" => "MU_CUSTF", "type" => "A", "length" => 2, "text" => "Country", "keyField" => false, "required" => true, "value" => ""));
	// fields selected for the list display
	protected $listFields = array(
		"CMCUST" => array("file" => "MU_CUSTF", "type" => "N", "length" => 7, "text" => "Customer Number", "keyField" => true, "value" => ""),
		"CMNAME" => array("file" => "MU_CUSTF", "type" => "A", "length" => 50, "text" => "Customer Name", "keyField" => false, "value" => ""),
		"CMADR1" => array("file" => "MU_CUSTF", "type" => "A", "length" => 50, "text" => "Address 1", "keyField" => false, "value" => ""),
		"CMADR2" => array("file" => "MU_CUSTF", "type" => "A", "length" => 50, "text" => "Address 2", "keyField" => false, "value" => ""),
		"CMCITY" => array("file" => "MU_CUSTF", "type" => "A", "length" => 20, "text" => "City", "keyField" => false, "value" => ""),
		"CMSTATE" => array("file" => "MU_CUSTF", "type" => "A", "length" => 2, "text" => "State/Prov", "keyField" => false, "value" => ""),
		"CMCOUNT" => array("file" => "MU_CUSTF", "type" => "A", "length" => 2, "text" => "Country", "keyField" => false, "value" => ""));
	
	protected $primaryFile = 'MU_CUSTF';
	protected $joinedFiles = [];
	
	private $_cols = array();
	
	/**
	 * Class constructor
	 *
	 * @param array $data data to initiate object with
	 */
	public function __construct($data = array(), $isSingle = true)
	{
		$this->isSingleRecord = $isSingle;
		if (count($data) > 0)
		{
			$this->data = $data;
			$this->exchangeArray($data);
		}
	}
	
	/**
	 * Set the flag whether this object is for a single record or a list object
	 * @param boolean $isSingle
	 */
	public function SetIsSingle($isSingle)
	{
		$this->isSingleRecord = $isSingle;
	}
	
	/**
	 * Get the fields that are part of a given joined file from the list of fields
	 *
	 * @param string $file The name of the joined file
	 * @return array $fields An array of field names for the joined file
	 */
	public function GetJoinFields($file)
	{
		$fields = array();
		
		$listToCheck = $this->isSingleRecord ? $this->formFields : $this->listFields;
		
		foreach ($listToCheck as $field => $option)
		{
			if ($option['file'] === $file)
			{
				$fields[] = $field;
			}
		}
		
		return $fields;
	}
	
	/**
	 * Get the fields for the list that we want to return. It will include only fields
	 * from the primary file plus any calculated field.
	 *
	 * @return array $fields An array of field names for the list
	 */
	public function GetListColumns()
	{
		$fields = array();
		foreach ($this->listFields as $field => $option)
		{
			if ($option['file'] === $this->primaryFile || $option['file'] === '*CALCFLDS')
			{
				$fields[$field] = $this->listFields[$field];
			}
		}
		
		return $fields;
	}
	
	/**
	 * Get the fields for the record that we want to return. It will include only fields
	 * from the primary file plus any calculated field.
	 *
	 * @return array $fields An array of field names for the record
	 */
	public function GetRecordColumns()
	{
		$fields = array();
		foreach ($this->formFields as $field => $option)
		{
			if ($option['file'] === $this->primaryFile || $option['file'] === '*CALCFLDS')
			{
				$fields[$field] = $this->formFields[$field];
			}
		}
		
		return $fields;
	}
	
	/**
	 * Get the data array, which is an associative array of field=>value.
	 *
	 * @return array $data An array of fields and their value
	 */
	public function getDataArray()
	{
		$data = array();
		
		foreach ($this->formFields as $field => $options)
		{
			if (isset($options['value']))
			{
				$data[$field] = $options['value'];
			}
		}
		
		return $data;
	}
	
	/**
	 * Get the data array, which is an associative array of field=>value.
	 * However this version only includes fields from the primary file.
	 *
	 * @return array $data An array of fields and their value
	 */
	public function getPrimaryFileDataArray()
	{
		$data = array();
		
		foreach ($this->formFields as $field => $options)
		{
			if ($options['file'] === $this->primaryFile && isset($options['value']))
			{
				$data[$field] = $options['value'];
			}
		}
		
		return $data;
	}
	
	/**
	 * A generic validate function to ensure field types N are numeric and required fields have a value.
	 *
	 * @return array $invalidFields Array of all fields that are invalid, will contain the messageCode on the field
	 */
	public function validate()
	{
		$invalidFields = array();
		foreach($this->formFields as $field => &$fieldSettings)
		{
			if ($fieldSettings['file'] === $this->primaryFile)
			{
				if ($fieldSettings['type'] === 'N')
				{
					if (isset($fieldSettings['value']) && !is_numeric($fieldSettings['value']))
					{
						$invalidFields[$field] = array('messageCode' => self::NotNumeric);
					}
				}
				else if ($fieldSettings['required'])
				{
					if (!isset($fieldSettings['value']) || $fieldSettings['value'] === '')
					{
						$invalidFields[$field] = array('messageCode' => self::Required);
					}
				}
			}
		}
		
		return $invalidFields;
	}
	
	/**
	 * Return the internal data array
	 *
	 * @return array $data row data
	 */
	public function getData()
	{
		return $this->data;
	}
	
	/**
	 * Get the value of a requested field name.
	 *
	 * @param string $name The name of the field
	 * @param mixed The value of the field
	 */
	public function getField($name)
	{
		if ($this->isSingleRecord)
		{
			return $this->formFields[$name]['value'];
		}
		else
		{
			return $this->listFields[$name]['value'];
		}
	}
	
	/**
	 * Set the value of a field
	 *
	 * @param string $name The name of the field
	 * @param mixed $value The value of the field
	 */
	public function setField($name, $value)
	{
		if ($this->isSingleRecord)
		{
			$fieldType = $this->formFields[$name]['type'];
			$this->formFields[$name]['value'] = ($fieldType === 'N') ? (float) $value: $value;
		}
		else
		{
			$fieldType = $this->listFields[$name]['type'];
			$this->listFields[$name]['value'] = ($fieldType === 'N') ? (float) $value: $value;
		}
	}
	
	/**
	 * Move data from an array into the class variables
	 *
	 * @param array $data data to initiate object with
	 */
	protected function exchangeArray($data)
	{
		if ($this->isSingleRecord)
		{
			foreach ($this->formFields as $field => &$options)
			{
				if (isset($data[$field]))
				{
					$this->formFields[$field]['value'] = trim($data[$field]);
				}
				else if (isset($data['fld' . $field]))
				{
					$this->formFields[$field]['value'] = trim($data['fld' . $field]);
				}
			}
		}
		else
		{
			foreach ($this->listFields as $field => &$options)
			{
				if (isset($data[$field]))
				{
					$this->listFields[$field]['value'] = trim($data[$field]);
				}
				else if (isset($data['fld' . $field]))
				{
					$this->listFields[$field]['value'] = trim($data['fld' . $field]);
				}
			}
		}
	}
	
	/**
	 * Get the data of the record. An associative array of $field=>value will be returned.
	 *
	 * @return array $data Array of fields and their value
	 */
	public function getDisplayData()
	{
		$data = array();
		if ($this->isSingleRecord)
		{
			foreach ($this->formFields as $key => $options)
			{
				$data[$key] = $options['value'];
			}
		}
		else
		{
			foreach ($this->listFields as $key => $options)
			{
				$data[$key] = $options['value'];
			}
		}
		
		return $data;
	}
}
