<?php

namespace ultimo\net\http\php\sapi;

class UploadedFile {
  
  /**
   * The name of the file.
   * @var string
   */
  public $name;
  
  /**
   * The type of the file.
   * @var string
   */
  public $type;
  
  /**
   * The temporary file path to the file.
   * @var string
   */
  public $tmp_name;
  
  /**
   * The upload error of the file, or false if there is no error.
   * @var string|boolean
   */
  public $error;
  
  /**
   * The size of the file in bytes.
   * @var integer
   */
  public $size;
  
  const UPLOAD_ERR_OK = UPLOAD_ERR_OK;
  const UPLOAD_ERR_INI_SIZE = UPLOAD_ERR_INI_SIZE;
  const UPLOAD_ERR_FORM_SIZE = UPLOAD_ERR_FORM_SIZE;
  const UPLOAD_ERR_PARTIAL = UPLOAD_ERR_PARTIAL;
  const UPLOAD_ERR_NO_FILE = UPLOAD_ERR_NO_FILE;
  const UPLOAD_ERR_NO_TMP_DIR = UPLOAD_ERR_NO_TMP_DIR;
  const UPLOAD_ERR_CANT_WRITE = UPLOAD_ERR_CANT_WRITE;
  const UPLOAD_ERR_EXTENSION = UPLOAD_ERR_EXTENSION;
  
  /**
   * Constructor.
   * @param string $name The name of the file.
   * @param string $type The size of the file.
   * @param string $tmp_name The temporary filepath of the file.
   * @param string $error The upload error of the file.
   * @param integer $size The size of the file in bytes.
   */
  public function __construct($name, $type, $tmp_name, $error, $size) {
    $this->name = $name;
    $this->type = $type;
    $this->tmp_name = $tmp_name;
    $this->error = $error;
    $this->size = $size;
  }
  
  /**
   * Moves the file to another location.
   * @param string $destination The filepath to move the file to.
   * @return boolean Whether the move was succesfull.
   */
  public function move($destination) {
    return move_uploaded_file($this->tmp_name, $destination);
  }
  
  /**
   * Returns whether this file is an uploaded file. Used for security.
   * @return boolean Whether the file is an uploaded file.
   */
  public function isUploadedFile() {
    return is_uploaded_file($this->tmp_name);
  }
  
  /**
   * Returns an array of uploaded files based on an array of file data.
   * @param array $data The data to parse.
   * @return array The uploaded files.
   */
  static public function fromPostData($data) {
    if (isset($data['name']) && !is_array($data['name'])) {
      if ($data['error'] == 4) {
        return null;
      }
      return new UploadedFile($data['name'], $data['type'], $data['tmp_name'], $data['error'], $data['size']);
    } else {
      $result = array();
      foreach ($data['name'] as $key => $value) {
        $recData = array();
        $recData['name'] = $data['name'][$key];
        $recData['type'] = $data['type'][$key];
        $recData['tmp_name'] = $data['tmp_name'][$key];
        $recData['error'] = $data['error'][$key];
        $recData['size'] = $data['size'][$key];
        $result[$key] = static::fromPostData($recData);
      }
      return $result;
    }
  }
  
  /**
   * Returns the posted uploaded files from the $_FILES global array.
   * @return array The posted uploaded file from the $_FILES global array.
   */
  static public function getPostedFiles() {
    $result = array();
    foreach ($_FILES as $name => $value) {
      $result[$name] = static::fromPostData($value);
    }
    return $result;
  }
  
}