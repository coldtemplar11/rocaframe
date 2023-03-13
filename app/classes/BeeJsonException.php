<?php 

/**
 * Handler para excepciones de elementos Json
 * 
 * @since 1.1.4
 * 
 */
class BeeJsonException extends Exception {

  private $statusCode = null;
  private $errorCode  = null;

  public function __construct(String $message, Int $statusCode = 400, Mixed $errorCode = null) {
    
    $this->statusCode = (int) $statusCode;
    $this->errorCode  = $errorCode;

    parent::__construct($message);
  }

  public function getStatusCode(){
    return $this->statusCode;
  }

  public function getErrorCode()
  {
    return $this->errorCode;
  }

}