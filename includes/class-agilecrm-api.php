<?php

/**
 * Gravity Forms Agile CRM API Library.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2016, Rocketgenius
 */
class GF_AgileCRM_API {

	/**
	 * Initialize Agile CRM API library.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $account_url   Agile CRM account URL.
	 * @param string $email_address Agile CRM account email address.
	 * @param string $api_key       Agile CRM API key.
	 * @param bool   $verify_ssl    Verify SSL when processing request. Defaults to true.
	 */
	public function __construct( $account_url, $email_address, $api_key, $verify_ssl = true ) {
		
		$this->account_url   = $account_url;
		$this->email_address = $email_address;
		$this->api_key       = $api_key;
		$this->verify_ssl    = $verify_ssl;
		
	}
	
	/**
	 * Create a contact.
	 * 
	 * @since  1.0
	 * @access public
	 * 
	 * @param array $contact Contact details.
	 *
	 * @uses GF_AgileCRM_API::make_request()
	 * 
	 * @return array
	 */
	public function create_contact( $contact ) {
		
		return $this->make_request( 'contacts', json_encode( $contact ), 'POST' );
		
	}

	/**
	 * Create a note.
	 * 
	 * @since  1.0
	 * @access public
	 * 
	 * @param array $note Note details.
	 *
	 * @uses GF_AgileCRM_API::make_request()
	 * 
	 * @return array
	 */
	public function create_note( $note ) {
		
		return $this->make_request( 'notes', json_encode( $note ), 'POST' );
		
	}
	
	/**
	 * Get all contacts.
	 * 
	 * @since  1.0
	 * @access public
	 *
	 * @uses GF_AgileCRM_API::make_request()
	 * 
	 * @return array
	 */
	public function get_contacts() {
		
		return $this->make_request( 'contacts' );
		
	}

	/**
	 * Create a task.
	 * 
	 * @since  1.0
	 * @access public
	 * 
	 * @param array $task Task details.
	 *
	 * @uses GF_AgileCRM_API::make_request()
	 * 
	 * @return array
	 */
	public function create_task( $task ) {
		
		return $this->make_request( 'tasks', json_encode( $task ), 'POST' );
		
	}
	
	/**
	 * Search contacts.
	 * 
	 * @since  1.0
	 * @access public
	 * 
	 * @param string $query Search query.
	 *
	 * @uses GF_AgileCRM_API::make_request()
	 * 
	 * @return array
	 */
	public function search_contacts( $query ) {
		
		return $this->make_request( 'search', array( 'q' => $query, 'type' => 'PERSON', 'page_size' => 999 ) );
		
	}
	
	/**
	 * Update a contact.
	 * 
	 * @since  1.0
	 * @access public
	 * 
	 * @param array $contact Contact details.
	 *
	 * @uses GF_AgileCRM_API::make_request()
	 * 
	 * @return array
	 */
	public function update_contact( $contact ) {
		
		return $this->make_request( 'contacts', json_encode( $contact ), 'PUT' );
		
	}

	/**
	 * Make API request.
	 *
	 * @since  1.0 
	 * @access public
	 *
	 * @param string $action        Request action.
	 * @param array  $options       Request options.
	 * @param string $method        HTTP method. Defaults to GET.
	 * @param int    $response_code Expected HTTP response code. Defaults to 200.
	 *
	 * @return array or int
	 */
	private function make_request( $action, $options = array(), $method = 'GET', $expected_code = 200 ) {
					
		// Build request options string.
		$request_options = 'GET' === $method ? '?' . http_build_query( $options ) : null;
		
		// Build request URL.
		$request_url = 'https://' . $this->account_url . '.agilecrm.com/dev/api/' . $action . $request_options;
		
		// Build request arguments.
		$request_args = array(
			'headers'   => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( $this->email_address . ':' . $this->api_key ),
				'Content-Type'  => 'application/json',
			),
			'method'    => $method,
			'sslverify' => apply_filters( 'https_local_ssl_verify', $this->verify_ssl ),	
		);

		// Add body to non-GET requests.
		if ( 'GET' !== $method ) {
			$request_args['body'] = $options;
		}

		// Execute API request.
		$response = wp_remote_request( $request_url, $request_args );

		// If API request returns a WordPress error, throw an exception.
		if ( is_wp_error( $response ) ) {
			throw new Exception( 'Request failed. '. $response->get_error_message() );
		}
		
		// If response code does not match expected code, throw exception.
		if ( $response['response']['code'] !== $expected_code ) {
			
			if ( $response['response']['code'] == 400 ) {
				throw new Exception( 'Input is in the wrong format.' );
			} elseif ( $response['response']['code'] == 401 ) {
				throw new Exception( 'API credentials invalid.' );			
			} else {
				throw new Exception( sprintf( '%s: %s', $response['response']['code'], $response['response']['message'] ) );
			}
			
		}
		
		return json_decode( $response['body'], true );
		
	}

}
