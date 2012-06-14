<?php
/**
 * Vovici API
 *
 * a PHP class to interact with the Vovici Web Services API
 *
 * @author        David Briggs
 * @copyright Copyright (c) 2011 Infosurv, Inc.
 * @link        http://www.infosurv.com
 */
 
/**
 * voviciAPI
 *
 * Create our new class called "voviciAPI"
 */
class voviciAPI {
  private $api_url = '';
  private $username = '';
  private $password = '';
  

  /**
   * __construct()
   * 
   * @access   public
   * @param    string
   * @param    string
   * @param    string
   * @return   voviciAPI instance
  */
  public function __construct($user,$pass,$url) {
    $this->set_username($user);
    $this->set_password($pass);
    $this->set_api_url($url);
  }
  /**
   * get_api_url()
   *
   * Get the API url
   *
   * @access    public
   * @return    string
   */
  public function get_api_url() {
    return $this->api_url;
  }
  /**
   * set_api_url()
   *
   * Set the API url
   *
   * @access    public
   * @param   string
   * @return    void
   */
  public function set_api_url($url) {
    $this->api_url = $url;
  }
  /**
   * set_username()
   *
   * Set the Username
   *
   * @access    public
   * @param   string
   * @return    void
   */
  public function set_username($username) {
    $this->username = $username;
  }
  /**
   * set_password()
   *
   * Set the Password
   *
   * @access    public
   * @param   string
   * @return    void
   */
  public function set_password($password) {
    $this->password = $password;
  }
  /**
   * request()
   *
   * Request data from the API
   *
   * @access    public
   * @param   string, array
   * @return    xml
   */
  public function request($func, $options) {
    ini_set('max_execution_time', 0);
    try {
      $soap = new SoapClient($this->api_url, array('trace'=>1,'cache_wsdl'=>WSDL_CACHE_NONE ));
      $params = array('userName' => $this->username, 'password' => $this->password );
      $soap->Login($params);      
      $response = $soap->$func($options);
    } catch (Exception $e) {
      echo 'Caught: ' . $e->getMessage();
      die;      
    }
    
    $r = $func . 'Result';
    
    
    if (!$response) {
      return false;
    } else {
      return $response->$r;//->any;
    }
  }
  
  /**
   * XMLToArray()
   *
   * Returns an array of the XML
   *
   * @access    private
   * @param   SimpleXMLElement
   * @return    array
   */
  private function XMLToArray($xml){
      if ($xml instanceof SimpleXMLElement){
        $children = $xml->children();
      $return = null;
    }
      foreach ($children as $element => $value){ 
        if ($value instanceof SimpleXMLElement){
            $values = (array)$value->children();
      
            if (count($values) > 0){
              $return[$element] = $this->XMLToArray($value); 
            } else { 
              if (!isset($return[$element])) { 
                  $return[$element] = (string)$value; 
              } else { 
                  if (!is_array($return[$element])) { 
                    $return[$element] = array($return[$element], (string)$value); 
                  } else { 
                    $return[$element][] = (string)$value; 
                  } 
              } 
            } 
        } 
      }
      if (is_array($return)) { 
        return $return; 
      } else { 
        return $false; 
      } 
  }
  
  /**
   * getCompleteArray()
   *
   * Performs the "GetSurveyDataPaged" request and returns the completed data in a nicely formatted array
   *
   * @access    public
   * @param   string
   * @param   string
   * @return    array
   */
  public function getCompleteArray($pid, $criteria = null, $datamap = null, $fields = null) {
    $responseCount = $this->request('GetResponseCount', array('projectId' => $pid, 'completedOnly' => true));
    
    $last_iteration = $responseCount % 1000;
    if ($last_iteration == 0) {
      $last_iteration = 1000;
    }
    $num_of_iterations = ceil($responseCount / 1000);
    
    $prevRecordId = 0;
    $result = array();
    
    //test condition
    //$num_of_iterations = 1;
    
    while ($num_of_iterations > 0) {
      $recordCount = 1000;
      if ($num_of_iterations == 1) {
        $recordCount = $last_iteration;
      }
      $o = array(
        'projectId'   => $pid,
        'completedOnly' => true,
        'recordCount'   => $recordCount,
        'prevRecordId'  => $prevRecordId);
      if ($criteria) {
        $o['filterXml'] = $criteria;
      }
      if ($datamap) {
        $o['dataMapXml'] = $datamap;
      }
      $data = $this->request('GetSurveyDataPaged', $o);
      
      $xml = new SimpleXMLElement($data->any);
      unset($data); // for quicker garbage collection
      $xmlAsArray = $xml->NewDataSet;
      unset($xml);
      foreach ($xmlAsArray->Table1 as $record) {
        $test = $this->XMLToArray($record);
        // if $fields is an array, then we want to return each thing in the array
        if (is_array($fields)) {
          $tempArray = array();
          foreach ($fields as $field) {
            $tempArray[$field] = $test[$field];
          }
          $result[] = $tempArray;
        } elseif ($fields) {
          // if $fields is a single entry, then only return that
          $result[] = $test[$fields];
        } else {
          // if $fields is not set, then return the entire record
          $result[] = $test;
        }
        // set the last record
        $prevRecordId = $test['recordid'];
      }
      unset($xmlAsArray); // for quicker garbage collection
      
      // reduce the number of iterations
      $num_of_iterations--;
    }
    
    return $result;
  }
  
  /**
   * getCompleteCSV()
   *
   * Performs the "GetSurveyDataEx" request and returns the completed data as a comma separated list
   *
   * @access    public
   * @param   string
   * @param   string
   * @return    array
   */
  public function getCompleteCSV($pid, $criteria = null) {
    $data = $this->getCompleteArray($pid, $criteria);
    
    if (!$data){
      return false;
    }
    
    $keys = array_keys($data[0]);
    array_unshift($data, $keys);
    
    $columns = $this->getColumnList($pid);
    
    
    
    $fp = fopen('completes.csv', 'w');
    foreach ($data as $record) {
      fputcsv($fp, $record);
    }
    fclose($fp);
  }
  
  public function getColumnList($pid) {
    $data = $this->request('GetColumnList', array('projectId'=>$pid));
    
    if (!$data){
      return false;
    }
    // parse the XML returned by request()
    $xml = new SimpleXMLElement($data->any);
    foreach ($xml as $field) {
      $result[] = (string) $field['id'];
    }
    return $result;
  }
  
  /**
   * getPreloadData()
   *
   * Performs the "GetSurveyDataPaged" request and returns the completed data in a nicely formatted array
   *
   * @access    public
   * @param   string
   * @param   string
   * @return    array
   */
  public function getPreloadData($pid, $field = null) {
    $participantCount = $this->request('GetAuthorizedParticipantCount', array('projectId' => $pid));
    
    $last_iteration = $participantCount % 1000;
    if ($last_iteration == 0) {
      $last_iteration = 1000;
    }
    $num_of_iterations = ceil($participantCount / 1000);
    
    $startRecordId = 0;
    $result = array();
    
    //test condition
    $num_of_iterations = 1;
    
    $records = array();
    while ($num_of_iterations > 0) {
      $recordCount = 1000;
      if ($num_of_iterations == 1) {
        $recordCount = $last_iteration;
      }
      $o = array(
        'projectId'   => $pid,
        'recordCount'   => $recordCount,
        'surveyStatus'  => 'Any',
        'startRecordId' => $startRecordId);
      $data = $this->request('GetParticipantDataPaged', $o);
      
      $xml = new SimpleXMLElement($data->any);
      unset($data); // for quicker garbage collection
      

      foreach ($xml->children() as $record) {
        $test = $record->attributes();

        $record = (string) $test['recordid'];
        
        $data = $this->request('GetPreloadData', array('projectId' => $pid, 'recordId' => $record));
        $xml = new SimpleXMLElement($data->any);
        $office = (string) $xml->Field[$field];
        if (!isset($result[$office])) {
          $result[$office] = 0;
        }
        $result[$office]++;
        
        $startRecordId = $record;
      }
      unset($xml); // for quicker garbage collection
      
      $num_of_iterations--;
    }
    
    return $result;
  }
}
?>