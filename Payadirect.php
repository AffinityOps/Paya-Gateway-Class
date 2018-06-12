<?php

/**
 * Paya Gateway
 *
 * @link https://developer.sagepayments.com/
 */

class payadirect {

	/**
	* setup Hash
	**/
	public function getHmac($toBeHashed, $privateKey) {
		$hmac = hash_hmac(
			'sha512', // use the SHA-512 algorithm...
			$toBeHashed, // ... to hash the combined string ...
			$privateKey, // .. using private dev key to sign it.
			true // (php returns hexits by default; override this)
		);

		// convert to base-64 for transport
		$hmac_b64 = base64_encode($hmac);
		return $hmac_b64;
	}

	/**
	* set login credentials
	**/
	public function setLogin($m_id, $m_key) {
		$this->login['merchantId']		= $m_id;
		$this->login['merchantKey']		= $m_key;
	}

	/**
	* set client credentials
	**/
	public function setClient($c_id, $c_key) {
		$this->client['clientId']		= $c_id;
		$this->client['clientKey']		= $c_key;
	}


	/**
	* Set API URL
	**/
	public function setApi($url) {
		$this->api['url'] = $url;
	}

	/**
	* Set Billing Information
	**/
	public function setBilling(
		$name,
		$address,
		$city,
		$state,
		$postal_code,
		$country
			){
		$this->billing['name']						= $name;
	    $this->billing['address']					= $address;
	    $this->billing['city']						= $city;
	    $this->billing['state']						= $state;
	    $this->billing['postalCode']       			= $postal_code;
	    $this->billing['country']   				= $country;
	}

	/**
	* Set Customer Information
	**/
	public function setCustomer(
		$email,
		$phone,
		$fax
			){
		$this->customer['email']					= $email;
	    $this->customer['telephone']				= $phone;
	    $this->customer['fax']						= $fax;
	}                     

	/**
	* Set level 2
	**/
	public function setLevel2($invoiceNumber, $customerNumber, $tax)
	{
		$this->level2['orderNumber'] 	= $invoiceNumber;
		$this->level2['customerNumber'] = $customerNumber;
		$this->level2['tax'] 			= $tax;
	}

	/**
	* Process Sale https://developer.sagepayments.com/bankcard-level-iii/apis/post/charges
	**/
	public function sale($account_number, $expiration, $amount, $cvv2='')
	{
		// Build arrayData
		$arrayData = array(
			"ECommerce" => [
				"amounts" => [ 
					"total" => $amount
				],
				"orderNumber" => $this->level2['orderNumber'],
				"cardData" => [ 
					"number"		=> $account_number,
					"expiration"	=> $expiration
				],
				"isRecurring"	=> false,
			]
		);

		$arrayData['ECommerce']['amounts']['tax']	= (!empty($this->level2['tax'])) ? $this->level2['tax'] : '';


		if(!empty($cvv2)) { $arrayData['ECommerce']['cardData']['cvv']	= $cvv2; }

		$arrayData['ECommerce']['customer']['email']		= (!empty($this->customer['email'])) ? $this->customer['email'] : '';
		$arrayData['ECommerce']['customer']['telephone']	= (!empty($this->customer['telephone'])) ? $this->customer['telephone'] : '';
		$arrayData['ECommerce']['billing']['name']			= (!empty($this->billing['name'])) ? $this->billing['name'] : '';
		$arrayData['ECommerce']['billing']['address']		= (!empty($this->billing['address'])) ? $this->billing['address'] : '';
		$arrayData['ECommerce']['billing']['city']			= (!empty($this->billing['city'])) ? $this->billing['city'] : '';
		$arrayData['ECommerce']['billing']['state']			= (!empty($this->billing['state'])) ? $this->billing['state'] : '';
		$arrayData['ECommerce']['billing']['postalCode']	= (!empty($this->billing['postalCode'])) ? $this->billing['postalCode'] : '';
		$arrayData['ECommerce']['billing']['country']		= (!empty($this->billing['country'])) ? $this->billing['country'] : '';
		
		
		$arrayData['ECommerce']['level2']['customerNumber']		= (!empty($this->level2['customerNumber'])) ? $this->level2['customerNumber'] : substr($account_number,-4,4);

		$payload = json_encode($arrayData);
    	
    	return $this->_Post($payload);
	}

	/**
	* Process Void https://developer.sagepayments.com/bankcard-ecommerce-moto/apis/delete/charges/%7Breference%7D
	**/
	public function void()
	{
		return $this->_Delete();
	}

	/**
	* Process Refund https://developer.sagepayments.com/bankcard-ecommerce-moto/apis/post/credits/%7Breference%7D
	**/
	public function refund($transaction_id, $amount)
	{
		// Build arrayData
		$arrayData = array(
			"transactionId"	=> $transaction_id,
		    "amount"		=> $amount
		);

		$payload = json_encode($arrayData);

		return $this->_Post($payload);
	}

	/**
	* Create Token
	**/
	public function addToken($account_number, $expiration) {
	
		// Build arrayData
		$arrayData = array(
			"cardData" => [ 
				"number"		=> $account_number,
				"expiration"	=> $expiration
			]
		);

		$payload = json_encode($arrayData);
    	
    	return $this->_Post($payload);
	}

	/**
	* Delete Token
	**/
	public function deleteToken() {
    	return $this->_Delete();
	}

	/**
	* Update Token
	**/
	public function updateToken($expiration) {
	
		// Build arrayData
		$arrayData = array(
			"cardData" => [
				"expiration"	=> $expiration
			]
		);

		$payload = json_encode($arrayData);
    	
    	return $this->_Put($payload);
	}

	/**
	* Get Transaction Details
	**/
	public function getTransactionInfo() {
		return $this->_Get();
	}

	/**
	* Sale With Token
	**/
	public function saleToken($token, $amount) {
		// Use payment method id 3813
		// Build arrayData
		$arrayData = array(
			"ECommerce" => [
				"amounts" => [ 
					"total" => $amount
				],
				"orderNumber" => $this->level2['orderNumber'],
				"isRecurring"	=> false,
			],
			"vault"	=>	[
				"token" =>	$token,
				"operation"	=>	"Read"
			]
		);

		$arrayData['ECommerce']['amounts']['tax']	= (!empty($this->level2['tax'])) ? $this->level2['tax'] : '';


		if(!empty($cvv2)) { $arrayData['ECommerce']['cardData']['cvv']	= $cvv2; }

		$arrayData['ECommerce']['customer']['email']		= (!empty($this->customer['email'])) ? $this->customer['email'] : '';
		$arrayData['ECommerce']['customer']['telephone']	= (!empty($this->customer['telephone'])) ? $this->customer['telephone'] : '';
		$arrayData['ECommerce']['billing']['name']			= (!empty($this->billing['name'])) ? $this->billing['name'] : '';
		$arrayData['ECommerce']['billing']['address']		= (!empty($this->billing['address'])) ? $this->billing['address'] : '';
		$arrayData['ECommerce']['billing']['city']			= (!empty($this->billing['city'])) ? $this->billing['city'] : '';
		$arrayData['ECommerce']['billing']['state']			= (!empty($this->billing['state'])) ? $this->billing['state'] : '';
		$arrayData['ECommerce']['billing']['postalCode']	= (!empty($this->billing['postalCode'])) ? $this->billing['postalCode'] : '';
		$arrayData['ECommerce']['billing']['country']		= (!empty($this->billing['country'])) ? $this->billing['country'] : '';

		$payload = json_encode($arrayData);
    	
    	return $this->_Post($payload);
	}

	public function _Post($postData)
	{
	    $verb = "POST";

	    $url = $this->api['url'];

	    $nonce = uniqid();
	    
	    $timestamp = (string)time();

		$toBeHashed = $verb . $url . $postData . $this->login['merchantId'] . $nonce . $timestamp;
		$hmac = $this->getHmac($toBeHashed, $this->client['clientKey']);

		$curl = curl_init();

		curl_setopt_array($curl, array(
		    CURLOPT_URL => $url,
		    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_MAXREDIRS => 10,
		    CURLOPT_TIMEOUT => 30,
		    CURLOPT_SSLVERSION => 6,
		    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		    CURLOPT_POSTFIELDS => $postData,
		    CURLOPT_HTTPHEADER => array(
		    	'clientId: '	. $this->client['clientId'],
				'merchantId: '	. $this->login['merchantId'],
				'merchantKey: ' . $this->login['merchantKey'],
				'nonce: ' . $nonce,
				'timestamp: ' . $timestamp,
				'authorization: ' . $hmac,
				'content-type: application/json',
		    )
		));

		$response = curl_exec($curl);
		$this->response = json_decode($response, true);

		$this->err = curl_error($curl);
		curl_close($curl);

		if ($this->err) {
		    return $this->err;
		} else {
		    return $this->response;
		}

	    return $response;
	}

	public function _Put($postData)
	{
		$verb = "PUT";

	    $url = $this->api['url'];

	    $nonce = uniqid();
	    
	    $timestamp = (string)time();

		$toBeHashed = $verb . $url . $postData . $this->login['merchantId'] . $nonce . $timestamp;
		$hmac = $this->getHmac($toBeHashed, $this->client['clientKey']);

		$curl = curl_init();

		curl_setopt_array($curl, array(
		    CURLOPT_URL => $url,
		    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_MAXREDIRS => 10,
		    CURLOPT_TIMEOUT => 30,
		    CURLOPT_SSLVERSION => 6,
		    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		    CURLOPT_POSTFIELDS => $postData,
		    CURLOPT_VERBOSE	=> true,
		    CURLOPT_HTTPHEADER => array(
		    	'clientId: '	. $this->client['clientId'],
				'merchantId: '	. $this->login['merchantId'],
				'merchantKey: ' . $this->login['merchantKey'],
				'nonce: ' . $nonce,
				'timestamp: ' . $timestamp,
				'authorization: ' . $hmac,
				'content-type: application/json',
		    )
		));

		$response = curl_exec($curl);

		$this->response = json_decode($response, true);

		$this->err = curl_error($curl);
		curl_close($curl);

		if ($this->err) {
		    return $this->err;
		} else {
		    return $this->response;
		}

	    return $response;
	}

	public function _Get()
	{
		$verb = "GET";

	    $url = $this->api['url'];

	    $nonce = uniqid();
	    
	    $timestamp = (string)time();

		$toBeHashed = $verb . $url . $this->login['merchantId'] . $nonce . $timestamp;
		$hmac = $this->getHmac($toBeHashed, $this->client['clientKey']);

		$curl = curl_init();

		curl_setopt_array($curl, array(
		    CURLOPT_URL => $url,
		    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_MAXREDIRS => 10,
		    CURLOPT_TIMEOUT => 30,
		    CURLOPT_SSLVERSION => 6,
		    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		    CURLOPT_VERBOSE	=> true,
		    CURLOPT_HTTPHEADER => array(
		    	'clientId: '	. $this->client['clientId'],
				'merchantId: '	. $this->login['merchantId'],
				'merchantKey: ' . $this->login['merchantKey'],
				'nonce: ' . $nonce,
				'timestamp: ' . $timestamp,
				'authorization: ' . $hmac,
				'content-type: application/json',
		    )
		));

		$response = curl_exec($curl);

		$this->response = json_decode($response, true);

		$this->err = curl_error($curl);
		curl_close($curl);

		if ($this->err) {
		    return $this->err;
		} else {
		    return $this->response;
		}

	    return $response;
	}

	public function _Delete()
	{
		$verb = "DELETE";

	    $url = $this->api['url'];

	    $nonce = uniqid();
	    
	    $timestamp = (string)time();

		$toBeHashed = $verb . $url . $this->login['merchantId'] . $nonce . $timestamp;
		$hmac = $this->getHmac($toBeHashed, $this->client['clientKey']);

		$curl = curl_init();

		curl_setopt_array($curl, array(
		    CURLOPT_URL => $url,
		    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_MAXREDIRS => 10,
		    CURLOPT_TIMEOUT => 30,
		    CURLOPT_SSLVERSION => 6,
		    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		    CURLOPT_HTTPHEADER => array(
		    	'clientId: '	. $this->client['clientId'],
				'merchantId: '	. $this->login['merchantId'],
				'merchantKey: ' . $this->login['merchantKey'],
				'nonce: ' . $nonce,
				'timestamp: ' . $timestamp,
				'authorization: ' . $hmac,
				'content-type: application/json',
		    )
		));

		$response = curl_exec($curl);
		$this->response = json_decode($response, true);

		$this->err = curl_error($curl);
		curl_close($curl);

		if ($this->err) {
		    return $this->err;
		} else {
		    return $this->response;
		}

	    return $response;
	}
}
