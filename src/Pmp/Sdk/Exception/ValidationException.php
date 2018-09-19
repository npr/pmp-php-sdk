<?php

namespace Pmp\Sdk\Exception;

/**
 * Remote exception specifically for bad request (400 status) responses that includes JSON validation errors
 */
class ValidationException extends RemoteException
{
    /**
     * Determine if a response looks like a validation error
     *
     * @param RemoteException $prev the original exception
     * @return bool whether or not it looks like a validation error
     */
    public static function looksValidationy(RemoteException $prev)
    {
        $json = $prev->getJsonResponse();
        if ($prev->httpStatus == 400 && $json) {
            if (empty($json->version) || empty($json->errors) || empty($json->errors->message)) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Get the validation error message
     *
     * @return string the validation error message
     */
    public function getValidationMessage()
    {
        $json = $this->getJsonResponse();
        if ($json && $json->errors && $json->errors->message) {
            return $json->errors->message;
        } else {
            return 'Unknown validation error';
        }
    }
}
