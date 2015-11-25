<?php
/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 11/14/15
 * Time: 11:46 AM.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace NilPortugues\Laravel5\JsonApiJsonApiSerializer;

use NilPortugues\Api\JsonApi\Http\Error;
use NilPortugues\Api\JsonApi\Http\Factory\RequestFactory;
use NilPortugues\Api\JsonApi\Http\Message\Request;
use NilPortugues\Laravel5\JsonApi\JsonApiSerializer;

/**
 * Class RequestTrait.
 */
trait RequestTrait
{
    /**
     * @var Error[]
     */
    private $queryParamErrorBag = [];

    /**
     * @return Error[]
     */
    protected function getQueryParamsErrors()
    {
        return $this->queryParamErrorBag;
    }

    /**
     * @param JsonApiSerializer $serializer
     *
     * @return bool
     */
    protected function hasValidQueryParams(JsonApiSerializer $serializer)
    {
        $apiRequest = $this->apiRequest();
        $this->validateQueryParamsTypes($serializer, $apiRequest->getFields(), 'Fields');
        $this->validateQueryParamsTypes($serializer, $apiRequest->getIncludedRelationships(), 'Include');

        return empty($this->queryParamErrorBag);
    }

    /**
     * @return Request
     */
    protected function apiRequest()
    {
        return RequestFactory::create();
    }

    /**
     * @param JsonApiSerializer $serializer
     * @param array             $fields
     * @param                   $paramName
     */
    private function validateQueryParamsTypes(JsonApiSerializer $serializer, array $fields, $paramName)
    {
        if (!empty($fields)) {
            $transformer = $serializer->getTransformer();
            $validateFields = array_keys($fields);

            foreach ($validateFields as $key => $field) {
                $mapping = $transformer->getMappingByAlias($field);

                if (null !== $mapping) {
                    $invalidProperties = array_diff($fields[$field], $mapping->getProperties());
                    foreach ($invalidProperties as $extraField) {
                        //@todo add attribute error to Error.
                        $error = new Error(
                            sprintf('Invalid %s Attribute', $paramName),
                            sprintf("Attribute '%s' for resource '%s' does not exist.", $extraField, $field)
                        );

                        $this->queryParamErrorBag[] = $error;
                    }

                    unset($validateFields[$key]);
                }
            }

            if (false === empty($validateFields)) {
                foreach ($validateFields as $field) {
                    $this->queryParamErrorBag[] = new Error(
                        sprintf('Invalid %s Parameter', $paramName),
                        sprintf("The resource type '%s' does not exist.", $field)
                    );
                }
            }
        }
    }
}