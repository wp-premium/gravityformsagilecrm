<?php

class AgileCRM_API {
	
	protected $curl;
	
	function __construct( $account_url, $email_address, $api_key, $verify_ssl = true ) {
		
		$this->account_url   = $account_url;
		$this->email_address = $email_address;
		$this->api_key       = $api_key;
		$this->verify_ssl    = $verify_ssl;
		
	}

	/**
	 * Make API request.
	 * 
	 * @access public
	 * @param string $action
	 * @param array $options (default: array())
	 * @param string $method (default: 'GET')
	 * @param int $expected_code (default: 200)
	 * @return array or int
	 */
	function make_request( $action, $options = array(), $method = 'GET', $expected_code = 200 ) {
					
		$request_options = ( $method == 'GET' ) ? '?' . http_build_query( $options ) : null;
		
		/* Build request URL. */
		$request_url = 'https://' . $this->account_url . '.agilecrm.com/dev/api/' . $action . $request_options;
		
		/* Setup request arguments. */
		$args = array(
			'headers'   => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( $this->email_address . ':' . $this->api_key ),
				'Content-Type'  => 'application/json'
			),
			'method'    => $method,
			'sslverify' => $this->verify_ssl	
		);

		/* Add request options to body of POST and PUT requests. */
		if ( $method == 'POST' || $method == 'PUT' ) {
			$args['body'] = $options;
		}

		/* Execute request. */
		$result = wp_remote_request( $request_url, $args );

		/* If WP_Error, throw exception */
		if ( is_wp_error( $result ) ) {
			throw new Exception( 'Request failed. '. $result->get_error_message() );
		}
		
		/* If response code does not match expected code, throw exception. */
		if ( $result['response']['code'] !== $expected_code ) {
			
			if ( $result['response']['code'] == 400 ) {
				throw new Exception( 'Input is in the wrong format.' );
			} elseif ( $result['response']['code'] == 401 ) {
				throw new Exception( 'API credentials invalid.' );			
			} else {
				throw new Exception( sprintf( '%s: %s', $result['response']['code'], $result['response']['message'] ) );
			}
			
		}
		
		return json_decode( $result['body'], true );
		
	}
	
	/**
	 * Create a contact.
	 * 
	 * @access public
	 * @param array $contact
	 * @return array $contact
	 */
	function create_contact( $contact ) {
		
		return $this->make_request( 'contacts', json_encode( $contact ), 'POST' );
		
	}

	/**
	 * Create a note.
	 * 
	 * @access public
	 * @param array $note
	 * @return array $note
	 */
	function create_note( $note ) {
		
		return $this->make_request( 'notes', json_encode( $note ), 'POST' );
		
	}
	
	/**
	 * Get all contacts.
	 * 
	 * @access public
	 * @return void
	 */
	function get_contacts() {
		
		return $this->make_request( 'contacts' );
		
	}

	/**
	 * Create a task.
	 * 
	 * @access public
	 * @param array $task
	 * @return array $task
	 */
	function create_task( $task ) {
		
		return $this->make_request( 'tasks', json_encode( $task ), 'POST' );
		
	}
	
	/**
	 * Search contacts.
	 * 
	 * @access public
	 * @param string $query
	 * @return array $contacts
	 */
	function search_contacts( $query ) {
		
		return $this->make_request( 'search', array( 'q' => $query, 'type' => 'PERSON', 'page_size' => 999 ) );
		
	}
	
	/**
	 * Update a contact.
	 * 
	 * @access public
	 * @param array $contact
	 * @return array $contact
	 */
	function update_contact( $contact ) {
		
		return $this->make_request( 'contacts', json_encode( $contact ), 'PUT' );
		
	}

}
