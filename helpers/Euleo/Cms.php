<?php
/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/**
 * Euleo-CMS SOAP-Client
 * @author Euleo GmbH
 */
class Euleo_Cms
{
	protected $client;
	protected $usercode;
	protected $handle;
	protected $clientVersion = 2.0;
	protected $cms = 'contao';

	public function __construct($customer, $usercode, $cms) {
		$this->customer = $customer;
		$this->usercode = $usercode;
			
		$this->cms = $cms;
	
		if ($_SERVER['SERVER_ADDR'] == '192.168.1.10') {
			$this->host = 'http://euleo/';
		} else {
			$this->host = 'https://www.euleo.com/';
		}
			
		$this->client = new SoapClient($this->host . '/soap/index?wsdl=1');
		
		if (!empty($customer) && !empty($usercode)) {
			$request = array();
			$request['customer'] = $this->customer;
			$request['usercode'] = $this->usercode;
			$request['clientVersion'] = $this->clientVersion;
			
			$requestXml = $this->_createRequest($request, 'connect');
			
			$responseXml = $this->client->__soapCall('connect', array('xml' => $requestXml));
			
			$response = $this->_parseXml($responseXml);
	
			if (!empty($response['handle'])) {
				$this->handle = $response['handle'];
			} else {
				$this->handle = false;
				
				throw new Exception($response['errors']);
			}
		}
	}
	
	public function getRegisterToken($cmsroot, $returnUrl)
	{
		$request = array();
		$request['clientVersion'] = $this->clientVersion;
		$request['cms'] = $this->cms;
		$request['cmsroot'] = $cmsroot;
		$request['returnUrl'] = $returnUrl;
		
		$requestXml = $this->_createRequest($request, 'getRegisterToken');
		
		$responseXml = $this->client->__soapCall('getRegisterToken', array('xml' => $requestXml));
			
		$response = $this->_parseXml($responseXml);

		return $response['token'];
		
		if (empty($response['token'])) {
			throw new Exception($response['errors']);
		}
	}
	
	public function install($token)
	{
		$request = array();
		$request['token'] = $token;
		
		$requestXml = $this->_createRequest($request, 'getTokenInfo');
		
		$responseXml = $this->client->__soapCall('getTokenInfo', array('xml' => $requestXml));
			
		$response = $this->_parseXml($responseXml);

		if (empty($response['data'])) {
			throw new Exception($response['errors']);
		}
		
		return $response['data'];
	}
	
	public function getCustomerData()
	{
		$request = array();
		$request['handle'] = $this->handle;
		$requestXml = $this->_createRequest($request, 'getCustomerData');
		
		$responseXml = $this->client->__soapCall('getCustomerData', array('xml' => $requestXml));
			
		$response = $this->_parseXml($responseXml);

		if (empty($response['data'])) {
			throw new Exception($response['errors']);
		}
		
		return $response['data'];
	}
	
	public function connected()
	{
		return $this->handle != false;
	}
	
	public function setLanguageList($languageList)
	{
		$request = array();
		$request['handle'] = $this->handle;
		$request['languageList'] = $languageList;
		
		$requestXml = $this->_createRequest($request, 'setLanguageList');
		$responseXml = $this->client->__soapCall('setLanguageList', array('xml' => $requestXml));
		$response = $this->_parseXml($responseXml);

		return $response;
	}


	public function startEuleoWebsite() {
		return $this->host . "/business/auth/index/handle/" . $this->handle;
	}

	public function getRows($translationIdList = array()) {
	    $request = array();
	    $request['handle'] = $this->handle;

	    $request['translationIdList'] = $translationIdList;
	    
	    $requestXml = $this->_createRequest($request, 'getRows');
	    
		$responseXml = $this->client->__soapCall('getRows', array('xml' => $requestXml));

		$response = $this->_parseXml($responseXml);

		if (!$response['rows']){			
			echo 'No translations available at this time.';
		}

		return $response['rows'];
	}

	public function sendRows($rows) {
	    $request = array();
	    $request['handle'] = $this->handle;
	    $request['cms'] = $this->cms;
	    $request['rows'] = $rows;

	    $requestXml = $this->_createRequest($request, 'sendRows');
	    
	    $responseXml = $this->client->__soapCall('sendRows', array('xml' => $requestXml));
	    
	    $response = $this->_parseXml($responseXml);
	    
	   	return $response;
	}

	public function confirmDelivery(array $translationids) {
		$request['handle'] = $this->handle;
		$request['translationIdList'] = implode(',', $translationids);
		$requestXml = $this->_createRequest($request, 'confirmDelivery');
		
		$responseXml = $this->client->__soapCall('confirmDelivery', array('xml' => $requestXml));
		
		$response = $this->_parseXml($responseXml);
		
		return $response;
	}

	
	public function getCart()
	{
		$request['handle'] = $this->handle;
		$requestXml = $this->_createRequest($request, 'getCart');
		
		$responseXml = $this->client->__soapCall('getCart', array('xml' => $requestXml));
		$response = $this->_parseXml($responseXml);
		
		return $response;
	}
	
	public function clearCart()
	{
		$request['handle'] = $this->handle;
		$requestXml = $this->_createRequest($request, 'clearCart');
		
		$responseXml = $this->client->__soapCall('clearCart', array('xml' => $requestXml));
		
		$response = $this->_parseXml($responseXml);
		return $response;
	}
	
	public function addLanguage($code, $language)
	{
		$request = array();
		$request['handle'] = $this->handle;
		$request['code'] = $code;
		$request['language'] = $language;
		
		$requestXml = $this->_createRequest($request, 'addLanguage');
		
		$responseXml = $this->client->__soapCall('addLanguage', array('xml' => $requestXml));
		
		$response = $this->_parseXml($responseXml);
		return $response;
	}
	
	public function removeLanguage($code, $language)
	{
		$request = array();
		$request['handle'] = $this->handle;
		$request['code'] = $code;
		$request['language'] = $language;
		
		$requestXml = $this->_createRequest($request, 'removeLanguage');
		
		$responseXml = $this->client->__soapCall('removeLanguage', array('xml' => $requestXml));
		
		$response = $this->_parseXml($responseXml);
		
		return $response;
	}
	
	public function identifyLanguages($texts)
    {
    	$request = array();
		$request['handle'] = $this->handle;
		$request['texts'] = $texts;
		
		$requestXml = $this->_createRequest($request, 'getLanguages');
		
		$responseXml = $this->client->__soapCall('identifyLanguages', array('xml' => $requestXml));
		
		$response = $this->_parseXml($responseXml);
		
		return $response;
    }
    
    public function setCallbackUrl($url)
    {
    	$request = array();
		$request['handle'] = $this->handle;
		$request['callbackurl'] = $url;
		
		$requestXml = $this->_createRequest($request, 'setCallbackUrl');
		
		$responseXml = $this->client->__soapCall('setCallbackUrl', array('xml' => $requestXml));
		
		$response = $this->_parseXml($responseXml);
		
		return $response;
    }
	
	protected function _createRequest($data, $action)
    {
        if (!is_array($data)) {
            return false;
        }
        
        $xml = array();
        $xml[] = '<?xml version="1.0" encoding="utf-8" ?>';
        $xml[] = '<request action="' . $action . '">';
        
        $xml[] = self::_createRequest_sub($data);
        
        $xml[] = '</request>';
        
        
        $xmlstr = implode("\n", $xml);
        
        return $xmlstr;
    }
    
    protected function _createRequest_sub($data, $parentKey = '')
    {
        $xml = array();
        
        foreach ($data as $key => $value) {
            if (!is_numeric($key)) {
	            $xml[] = '<' . $key . '>';
	            if (is_array($value)) {
	                $xml[] = self::_createRequest_sub($value, $key);
	            } else {
	                $xml[] = '<![CDATA[' . trim($value) . ']]>';
	            }
	            $xml[] = '</' . $key . '>';
            } else {
                $xml[] = self::_rowToXml_sub($value);
            }
        }
        
        $xmlstr = implode("\n", $xml);
        return $xmlstr;
    }
    
    protected function _rowToXml_sub($row)
    {
        $lines=array();
                
        $lines[] = '<row id="' . htmlspecialchars($row['code'], ENT_COMPAT, 'UTF-8') . 
                   '" label="' . htmlspecialchars($row['label'], ENT_COMPAT, 'UTF-8') . 
                   '" title="' . htmlspecialchars($row['title'], ENT_COMPAT, 'UTF-8') . 
                   ($row['url'] ? '" url="' . htmlspecialchars($row['url'], ENT_COMPAT, 'UTF-8') : '') .
        		   '">';
        
        foreach ($row as $key => $value) {
            if ($key != 'fields' && $key != 'rows') {
                $lines[] = '<' . $key . '><![CDATA[' . $value . ']]></' .$key .'>';
            }
        }
        
        if ($row['fields']) {
            $lines[] = '<fields>';
    
            foreach ($row['fields'] as $fieldname => $field) {
    
                $label = $field['label'];
    
                if ($label == '') {
                    $label = ucfirst($fieldname);
                }
                $lines[] = '<field name="' . $fieldname . '" label="' . $label . '" type="' . 
                           $field['type'] . '">';
    
                $lines[] = '<![CDATA[';
                $lines[] = $field['value'];
                $lines[] = ']]>';
    
                $lines[]='</field>';
    
            }
            $lines[]='</fields>';
        }
    
        if (isset($row['rows'])) {
            $lines[] = '<rows>';
            foreach ($row['rows'] as $childrow) {
                $lines[] = self::_rowToXml_sub($childrow);
            }
            $lines[] = '</rows>';
        }
    
        $lines[] = '</row>';

        $xmlstr = implode("\n", $lines);

        return $xmlstr;
    }
    
	/**
     * converts xml to arrays
     * 
     * @param string $xml
     * 
     * @return array
     */
    protected function _parseXml ($xml)
    {
        if (!$xml) {
            throw new SoapException('error in your XML markup');
        }
        try {
            $return = array();
            
            $node = new SimpleXMLElement($xml);
            
            if (! $node) {
                throw new SoapException('error in your XML markup');
            }
            
            $return = self::_parseXml_sub($node);

            return $return;
        } catch (Exception $e) {
            throw new SoapException($e->getMessage());
        }
    }
    
	/**
     * recursive sub-function on _parseXml
     * 
     * @param object $rownode
     * 
     * @return array
     */
    protected static function _parseXml_sub($rownode)
    {
        foreach ($rownode->attributes() as $key => $value) {
            $row[$key] = trim((string) $value);
        }
        
        foreach ($rownode->children() as $name => $child) {
            if ($name == 'rows') {
                foreach ($child->row as $childRow) {
                    $row[$name][] = self::_parseXml_sub($childRow);
                }
            } else if ($name == 'fields') {
                foreach ($child->field as $childField) {
	                $field = array();
		            foreach ($childField->attributes() as $key => $value) {
		                $field[$key] = trim((string) $value);
		                if ($key == 'name'){
		                    $fieldname = trim((string) $value);
		                }
		            }
		            $field['value'] = trim((string) $childField);
		            $row['fields'][$fieldname] = $field;
                }
            } else if($name == 'requests') {
                foreach ($child->request as $childRequest) {
                    $row[$name][] = self::_parseXml_sub($childRequest);
                }
            } else {
                if ($child->children()) {
                    $row[$name] = self::_parseXml_sub($child);
                } else {
                    $row[$name] = trim((string)$child);
                }
            }
        }

        return $row;
    }
}