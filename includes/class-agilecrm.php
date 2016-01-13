<?php
	
	class AgileCRM {
		
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
			
			/* Initialize cURL session. */
			$this->curl = curl_init();
			
			/* Setup cURL options. */
			curl_setopt( $this->curl, CURLOPT_URL, $request_url );
			curl_setopt( $this->curl, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $this->curl, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl );
			curl_setopt( $this->curl, CURLOPT_USERPWD, $this->email_address . ':' . $this->api_key );
			curl_setopt( $this->curl, CURLOPT_HTTPHEADER, array( 'Accept: application/json' , 'Content-Type: application/json' ) );
			curl_setopt( $this->curl, CURLOPT_HEADER, true );

			/* If this is a POST request, pass the request options via cURL option. */
			if ( $method == 'POST' ) {
				
				curl_setopt( $this->curl, CURLOPT_POST, true );
				curl_setopt( $this->curl, CURLOPT_POSTFIELDS, $options );
				
			}

			/* If this is a PUT request, pass the request options via cURL option. */
			if ( $method == 'PUT' ) {
				
				curl_setopt( $this->curl, CURLOPT_CUSTOMREQUEST, 'PUT' );
				curl_setopt( $this->curl, CURLOPT_POSTFIELDS, $options );
				
			}
			
			/* Execute request. */
			$response = curl_exec( $this->curl );
			
			/* If cURL error, die. */
			if ( $response === false )
				throw new Exception( 'Request failed. ' . curl_error( $this->curl ) );

			/* Decode response. */
			list( $headers, $body ) = explode( "\r\n\r\n", $response, 2 );
			$response_code = curl_getinfo( $this->curl, CURLINFO_HTTP_CODE );
			
			if ( $response_code !== $expected_code ) {
				
				if ( $response_code == 400 )
					throw new Exception( 'Input is in the wrong format.' );

				if ( $response_code == 401 )
					throw new Exception( 'API credentials invalid.' );
				
			}
			
			return json_decode( $body, true );
			
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
