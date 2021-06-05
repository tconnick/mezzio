<?php

/**
 * Customer API
 *
 * @package	customerapi
 * @author	TC
 * @since	v1.0
 */

namespace App\Handler;

use App\Model\customerapiModel;
use App\Model\customerapi;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;

class customerapiHandler implements RequestHandlerInterface
{
	private $customerapiModel;
	
	const Messages = array(
		'customerapi_1001' => 'Failed to retrieve the list of records.',
		'customerapi_1701' => 'Records retreived successfully.',
		
		'customerapi_1002' => 'Failed to retrieve the requested record.',
		'customerapi_1003' => 'The record was not found.',
		'customerapi_1702' => 'Record retreived successfully',
		
		'customerapi_1004' => 'Failed to add a new record.',
		'customerapi_1703' => 'Record added successfully.',
		
		'customerapi_1005' => 'Failed to update the record.',
		'customerapi_1006' => 'The record you requested to be updated was not found.',
		'customerapi_1704' => 'Record updated successfully.',
		
		'customerapi_1007' => 'The record could not be deleted.',
		'customerapi_1705' => 'Record deleted successfully.',
		
		'customerapi_1501' => 'Invalid Data.',
		'customerapi_1502' => 'Not a numeric value.',
		'customerapi_1503' => 'This is a required field.',
		'customerapi_1999' => 'Failed to process your request',
	);
	
	public function __construct(customerapiModel $customerapiModel)
	{
		$this->customerapiModel = $customerapiModel;
	}
	
	/**
	 * Returns Error String for a given code.
	 *
	 * @param string $messageCode The message code
	 * @return string $message The message for the code
	 */
	private function getMessage($messageCode)
	{
		if (array_key_exists($messageCode, self::Messages))
		{
			return  self::Messages[$messageCode];
		}
		else
		{
			return self::Messages['customerapi_1999'];
		}
	}
	
	/**
	 * Function is called by the request to the endpoint and we'll check
	 * the request method to call the specific handler.
	 *
	 * @param ServerRequestInterface $request
	 * @return JsonResponse
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		switch ($request->getMethod())
		{
			case 'GET':
			return $this->handleGetRequest($request);
			case 'POST':
			return $this->handlePostRequest($request);
			case 'PUT':
			return $this->handlePutRequest($request);
			case 'DELETE':
			return $this->handleDeleteRequest($request);
		}
	}
	
	/**
	 * Handles Get Request
	 * If the query params contain a field that is only found in the uniqueFields array
	 * we'll check for a specific record. In all other cases a list of records will be returned.
	 *
	 * @param ServerRequestInterface
	 * @return JsonResponse
	 */
	private function handleGetRequest(ServerRequestInterface $request)
	{
		$params = $request->getQueryParams();
		
		$uniqueFields = $this->customerapiModel->GetUniqueFields();
		$filterFields = $this->customerapiModel->GetFilterFields();
		$getSingleRecord = false;
		$orderBy = '';
		
		// loop will build the orderBy string and check whether or not we want a specific record
		foreach ($params as $name => $value)
		{
			if ($name === 'orderby')
			{
				$orderBy = $value;
				if (isset($params['orderdir']))
				{
					$orderBy .= ' ' . $params['orderdir'];
				}
			}
			if (!array_key_exists($name, $filterFields) && in_array($name, $uniqueFields))
			{
				$getSingleRecord = true;
			}
		}
		
		if ($getSingleRecord)
		{
			$customerapi = new customerapi($params);
			$result = $this->customerapiModel->getSingleRecord($customerapi->getDisplayData());
		}
		else
		{
			$this->customerapiModel->SetFilterValues($params);
			$result = $this->customerapiModel->getAllRecords($orderBy);
		}
		
		$result['message'] = $this->getMessage($result['messageCode']);
		
		$jsonReturn = $this->getJsonResponse($result);
		
		return new JsonResponse($jsonReturn);
	}
	
	/**
	 * Handles POST Request
	 * A new record will be added if the validation passes.
	 *
	 * @param ServerRequestInterface
	 * @return JsonResponse
	 */
	private function handlePostRequest(ServerRequestInterface $request)
	{
		$param = $request->getParsedBody();
		$customerapi = new customerapi($param);
		$result = $this->customerapiModel->addRecord($customerapi);
		
		$result['message'] = $this->getMessage($result['messageCode']);
		
		if (isset($result['invalidFields']))
		{
			foreach($result['invalidFields'] as &$field)
			{
				$field['message'] = $this->getMessage($field['messageCode']);
			}
		}
		
		$jsonReturn = $this->getJsonResponse($result);
		
		return new JsonResponse($jsonReturn);
	}
	
	/**
	 * Handles Put Request
	 * Will update a record and return the new record details.
	 *
	 * @param ServerRequestInterface
	 * @return JsonResponse
	 */
	private function handlePutRequest(ServerRequestInterface $request)
	{
		$param = $request->getParsedBody();
		$customerapi = new customerapi($param);
		$result = $this->customerapiModel->updateRecord($customerapi);
		
		$result['message'] = $this->getMessage($result['messageCode']);
		if (isset($result['invalidFields']))
		{
			foreach($result['invalidFields'] as &$field)
			{
				$field['message'] = $this->getMessage( $field['messageCode'] );
			}
		}
		
		$jsonReturn = $this->getJsonResponse($result);
		
		return new JsonResponse($jsonReturn);
	}
	
	/**
	 * Handles Delete Request
	 * Removes a specified record
	 *
	 * @param ServerRequestInterface
	 * @return JsonResponse
	 */
	private function handleDeleteRequest(ServerRequestInterface $request)
	{
		$param = $request->getQueryParams();
		$customerapi = new customerapi($param);
		
		$result = $this->customerapiModel->deleteRecord($customerapi->getDisplayData());
		
		$result['message'] = $this->getMessage( $result['messageCode'] );
		
		$jsonReturn = $this->getJsonResponse($result);
		
		return new JsonResponse($jsonReturn);
	}
	
	/**
	 * Helper function to get the data for the records from the object into an associative array
	 *
	 * @param array $result Array of customerapi object(s)
	 * @param array $jsonReturn Associative array of field=>value for the endpoint return
	 */
	private function getJsonResponse($result)
	{
		if (!isset($result['records']) )
		{
			return $result;
		}
		
		$jsonReturn = $result;
		$jsonReturn['records'] = array();
		foreach ($result['records'] as $customerapi)
		{
			array_push($jsonReturn['records'], $customerapi->getDisplayData());
		}
		
		return $jsonReturn;
	}
}
