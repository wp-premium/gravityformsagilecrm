<?php
	
GFForms::include_feed_addon_framework();

class GFAgileCRM extends GFFeedAddOn {
	
	protected $_version = GF_AGILECRM_VERSION;
	protected $_min_gravityforms_version = '1.9.12';
	protected $_slug = 'gravityformsagilecrm';
	protected $_path = 'gravityformsagilecrm/agilecrm.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Gravity Forms Agile CRM Add-On';
	protected $_short_title = 'Agile CRM';
	protected $_enable_rg_autoupgrade = true;
	protected $api = null;
	private static $_instance = null;

	/* Permissions */
	protected $_capabilities_settings_page = 'gravityforms_agilecrm';
	protected $_capabilities_form_settings = 'gravityforms_agilecrm';
	protected $_capabilities_uninstall = 'gravityforms_agilecrm_uninstall';
	
	/* Members plugin integration */
	protected $_capabilities = array( 'gravityforms_agilecrm', 'gravityforms_agilecrm_uninstall' );

	/**
	 * @var string $custom_field_key The custom field key (label/name); used by get_full_address().
	 */
	protected $custom_field_key = '';
	
	/**
	 * Get instance of this class.
	 * 
	 * @access public
	 * @static
	 * @return $_instance
	 */
	public static function get_instance() {
		
		if ( self::$_instance == null ) {
			self::$_instance = new self;
		}

		return self::$_instance;
		
	}
	
	/**
	 * Register needed plugin hooks and PayPal delayed payment support.
	 * 
	 * @access public
	 * @return void
	 */
	public function init() {
		
		parent::init();
		
		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Create Agile CRM object only when payment is received.', 'gravityformsagilecrm' )
			)
		);
		
	}
	
	/**
	 * Add hook for Javascript analytics tracking.
	 * 
	 * @access public
	 * @return void
	 */
	public function init_frontend() {
		
		parent::init_frontend();
		
		if ( $this->get_plugin_setting( 'enableAnalyticsTracking' ) == '1' ) {
			add_action( 'wp_footer', array( $this, 'add_analytics_tracking_to_footer' ) );
		}
		
	}

	/**
	 * Register needed styles.
	 * 
	 * @access public
	 * @return array $styles
	 */
	public function styles() {
		
		$styles = array(
			array(
				'handle'  => 'gform_agilecrm_form_settings_css',
				'src'     => $this->get_base_url() . '/css/form_settings.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( 'admin_page' => array( 'form_settings' ) ),
				)
			)
		);
		
		return array_merge( parent::styles(), $styles );
		
	}

	/**
	 * Setup plugin settings fields.
	 * 
	 * @access public
	 * @return array
	 */
	public function plugin_settings_fields() {
						
		return array(
			array(
				'title'       => '',
				'description' => $this->plugin_settings_description(),
				'fields'      => array(
					array(
						'name'              => 'accountURL',
						'label'             => __( 'Account URL', 'gravityformsagilecrm' ),
						'type'              => 'text',
						'class'             => 'small',
						'after_input'       => '.agilecrm.com',
						'feedback_callback' => array( $this, 'initialize_api' )
					),
					array(
						'name'              => 'emailAddress',
						'label'             => __( 'Email Address', 'gravityformsagilecrm' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_api' )
					),
					array(
						'name'              => 'apiKey',
						'label'             => __( 'API Key', 'gravityformsagilecrm' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_api' )
					),
				),
			),
			array(
				'title'       => __( 'Analytics Tracking', 'gravityformsagilecrm' ),
				'description' => '<p>' . __( 'Agile CRM Javascript analytics tracking allows you to track page views on your website. To use Agile CRM analytics, enable the analytics tracking below and provide your Javascript API key.', 'gravityformsagilecrm' ) . '</p>',
				'fields'      => array(
					array(
						'name'              => 'enableAnalyticsTracking',
						'label'             => __( 'Enable Analytics Tracking', 'gravityformsagilecrm' ),
						'type'              => 'checkbox',
						'onclick'           => "jQuery(this).parents('form').submit();",
						'choices'           => array(
							array(
								'name'          => 'enableAnalyticsTracking',
								'label'         => __( 'Enable Javascript Analytics Tracking', 'gravityformsagilecrm' ),
							),
						)
					),
					array(
						'name'              => 'javascriptAPIKey',
						'label'             => __( 'Javascript API Key', 'gravityformsagilecrm' ),
						'type'              => 'text',
						'class'             => 'medium',
						'dependency'        => array( 'field' => 'enableAnalyticsTracking', 'values' => array( '1' ) )
					),
					array(
						'type'              => 'save',
						'messages'          => array(
							'success' => __( 'Agile CRM settings have been updated.', 'gravityformsagilecrm' )
						),
					),
				),
			),
		);
		
	}

	/**
	 * Prepare plugin settings description.
	 * 
	 * @access public
	 * @return string $description
	 */
	public function plugin_settings_description() {
		
		$description  = '<p>';
		$description .= sprintf(
			__( 'Agile CRM is a contact management tool makes it easy to track cases, contacts and deals. Use Gravity Forms to collect customer information and automatically add them to your Agile CRM account. If you don\'t have a Agile CRM account, you can %1$s sign up for one here.%2$s', 'gravityformsagilecrm' ),
			'<a href="http://www.agilecrm.com/" target="_blank">', '</a>'
		);
		$description .= '</p>';
		
		if ( ! $this->initialize_api() ) {
			
			$description .= '<p>';
			$description .= __( 'Gravity Forms Agile CRM Add-On requires your account URL, account email address and API key, which can be found on the API & Analytics page in the Admin Settings section.', 'gravityformsagilecrm' );
			$description .= '</p>';
			
		}
				
		return $description;
		
	}

	/**
	 * Setup fields for feed settings.
	 * 
	 * @access public
	 * @return array
	 */
	public function feed_settings_fields() {
		
		/* Build base fields array. */
		$base_fields = array(
			'title'  => '',
			'fields' => array(
				array(
					'name'           => 'feedName',
					'label'          => __( 'Feed Name', 'gravityformsagilecrm' ),
					'type'           => 'text',
					'required'       => true,
					'default_value'  => $this->get_default_feed_name(),
					'tooltip'        => '<h6>'. __( 'Name', 'gravityformsagilecrm' ) .'</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'gravityformsagilecrm' )
				),
				array(
					'name'           => 'action',
					'label'          => __( 'Action', 'gravityformsagilecrm' ),
					'type'           => 'checkbox',
					'required'       => true,
					'onclick'        => "jQuery(this).parents('form').submit();",
					'choices'        => array(
						array(
							'name'  => 'createContact',
							'label' => __( 'Create Contact', 'gravityformsagilecrm' ),
							'icon'  => 'fa-user',
						),
						array(
							'name'  => 'createTask',
							'label' => __( 'Create Task', 'gravityformsagilecrm' ),
							'icon'  => 'fa-tasks'
						),
					)
				)
			)
		);
		
		/* Build contact fields array. */
		$contact_fields = array(
			'title'  => __( 'Contact Details', 'gravityformsagilecrm' ),
			'dependency' => array( 'field' => 'createContact', 'values' => ( '1' ) ),
			'fields' => array(
				array(
					'name'           => 'contactStandardFields',
					'label'          => __( 'Map Fields', 'gravityformsagilecrm' ),
					'type'           => 'field_map',
					'field_map'      => $this->standard_fields_for_feed_mapping(),
					'tooltip'        => '<h6>'. __( 'Map Fields', 'gravityformsagilecrm' ) .'</h6>' . __( 'Select which Gravity Form fields pair with their respective Agile CRM fields.', 'gravityformsagilecrm' )
				),
				array(
					'name'           => 'contactCustomFields',
					'label'          => '',
					'type'           => 'dynamic_field_map',
					'field_map'      => $this->custom_fields_for_feed_mapping(),
				),
				array(
					'name'           => 'contactTags',
					'label'          => __( 'Tags', 'gravityformsagilecrm' ),
					'type'           => 'text',
					'class'          => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
				),		
				array(
					'name'           => 'updateContact',
					'label'          => __( 'Update Contact', 'gravityformsagilecrm' ),
					'type'           => 'checkbox_and_select',
					'checkbox'       => array(
						'name'          => 'updateContactEnable',
						'label'         => __( 'Update Contact if already exists', 'gravityformsagilecrm' ),
					),
					'select'         => array(
						'name'          => 'updateContactAction',
						'choices'       => array(
							array(
								'label'         => __( 'and replace existing data', 'gravityformsagilecrm' ),
								'value'         => 'replace'
							),
							array(
								'label'         => __( 'and append new data', 'gravityformsagilecrm' ),
								'value'         => 'append'
							)
						)	
					),
				),
			)
		);

		/* Build task fields array. */
		$task_fields = array(
			'title'      => __( 'Task Details', 'gravityformsagilecrm' ),
			'dependency' => array( 'field' => 'createTask', 'values' => ( '1' ) ),
			'fields'     => array(
				array(
					'name'                => 'taskSubject',
					'type'                => 'text',
					'required'            => true,
					'class'               => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					'label'               => __( 'Subject', 'gravityformsagilecrm' ),
				),
				array(
					'name'                => 'taskDaysUntilDue',
					'type'                => 'text',
					'required'            => true,
					'class'               => 'small',
					'label'               => __( 'Days Until Due', 'gravityformsagilecrm' ),
					'validation_callback' => array( $this, 'validate_task_days_until_due' )
				),
				array(
					'name'                => 'taskPriority',
					'type'                => 'select',
					'label'               => __( 'Priority', 'gravityformsagilecrm' ),
					'default_value'       => 'NORMAL',
					'choices'             => array(
						array(
							'label'          => __( 'High', 'gravityformsagilecrm' ),
							'value'          => 'HIGH'
						),
						array(
							'label'          => __( 'Normal', 'gravityformsagilecrm' ),
							'value'          => 'NORMAL'
						),
						array(
							'label'          => __( 'Low', 'gravityformsagilecrm' ),
							'value'          => 'LOW'
						)
					)
				),
				array(
					'name'                => 'taskStatus',
					'type'                => 'select',
					'label'               => __( 'Status', 'gravityformsagilecrm' ),
					'choices'             => array(
						array(
							'label'          => __( 'Yet To Start', 'gravityformsagilecrm' ),
							'value'          => 'YET_TO_START'
						),
						array(
							'label'          => __( 'In Progress', 'gravityformsagilecrm' ),
							'value'          => 'IN_PROGRESS'
						),
						array(
							'label'          => __( 'Completed', 'gravityformsagilecrm' ),
							'value'          => 'COMPLETED'
						)
					)
				),
				array(
					'name'                => 'taskType',
					'type'                => 'select',
					'label'               => __( 'Type', 'gravityformsagilecrm' ),
					'choices'             => array(
						array(
							'label'          => __( 'Call', 'gravityformsagilecrm' ),
							'value'          => 'CALL'
						),
						array(
							'label'          => __( 'Email', 'gravityformsagilecrm' ),
							'value'          => 'EMAIL'
						),
						array(
							'label'          => __( 'Follow Up', 'gravityformsagilecrm' ),
							'value'          => 'FOLLOW_UP'
						),
						array(
							'label'          => __( 'Meeting', 'gravityformsagilecrm' ),
							'value'          => 'MEETING'
						),
						array(
							'label'          => __( 'Milestone', 'gravityformsagilecrm' ),
							'value'          => 'MILESTONE'
						),
						array(
							'label'          => __( 'Send', 'gravityformsagilecrm' ),
							'value'          => 'SEND'
						),
						array(
							'label'          => __( 'Tweet', 'gravityformsagilecrm' ),
							'value'          => 'TWEET'
						),
						array(
							'label'          => __( 'Other', 'gravityformsagilecrm' ),
							'value'          => 'OTHER'
						),
					)
				),
				array(
					'name'                => 'taskNote',
					'label'               => __( 'Create Note', 'gravityformsagilecrm' ),
					'type'                => 'checkbox',
					'onclick'             => "jQuery(this).parents('form').submit();",
					'choices'             => array(
						array(
							'name'          => 'taskCreateNote',
							'label'         => __( 'Create Note for Task', 'gravityformsagilecrm' ),
						),
					)
				),
				array(
					'name'                => 'taskNoteSubject',
					'label'               => __( 'Note Subject', 'gravityformsagilecrm' ),
					'type'                => 'text',
					'required'            => true,
					'class'               => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					'dependency'          => array( 'field' => 'taskCreateNote', 'values' => ( '1' ) ),
				),		
				array(
					'name'                => 'taskNoteDescription',
					'label'               => __( 'Note Description', 'gravityformsagilecrm' ),
					'type'                => 'textarea',
					'required'            => true,
					'class'               => 'medium merge-tag-support mt-position-right mt-hide_all_fields',
					'dependency'          => array( 'field' => 'taskCreateNote', 'values' => ( '1' ) ),
				),		
				array(
					'name'                => 'taskAssign',
					'label'               => __( 'Assign To', 'gravityformsagilecrm' ),
					'type'                => 'checkbox',
					'onclick'             => "jQuery(this).parents('form').submit();",
					'choices'             => array(
						array(
							'name'          => 'taskAssignToContact',
							'label'         => __( 'Assign Task to Created Contact', 'gravityformsagilecrm' ),
						),
					)
				),
			)
		);

		/* Build conditional logic fields array. */
		$conditional_fields = array(
			'title'      => __( 'Feed Conditional Logic', 'gravityformsagilecrm' ),
			'dependency' => array( $this, 'show_conditional_logic_field' ),
			'fields'     => array(
				array(
					'name'           => 'feedCondition',
					'type'           => 'feed_condition',
					'label'          => __( 'Conditional Logic', 'gravityformsagilecrm' ),
					'checkbox_label' => __( 'Enable', 'gravityformsagilecrm' ),
					'instructions'   => __( 'Export to Agile CRM if', 'gravityformsagilecrm' ),
					'tooltip'        => '<h6>' . __( 'Conditional Logic', 'gravityformsagilecrm' ) . '</h6>' . __( 'When conditional logic is enabled, form submissions will only be exported to Agile CRM when the condition is met. When disabled, all form submissions will be posted.', 'gravityformsagilecrm' )
				),
				
			)
		);
		
		return array( $base_fields, $contact_fields, $task_fields, $conditional_fields );
		
	}
	
	/**
	 * Set custom dependency for conditional logic.
	 * 
	 * @access public
	 * @return bool
	 */
	public function show_conditional_logic_field() {
		
		/* Get current feed. */
		$feed = $this->get_current_feed();
		
		/* Get posted settings. */
		$posted_settings = $this->get_posted_settings();
		
		/* Show if an action is chosen */
		if ( rgar( $posted_settings, 'createContact' ) == '1' || rgars( $feed, 'meta/createContact' ) == '1' || rgar( $posted_settings, 'createTask' ) == '1' || rgars( $feed, 'meta/createTask' ) == '1' ) {
			
			return true;
						
		}
		
		return false;
		
	}

	/**
	 * Validate Task Days Until Due feed settings field.
	 * 
	 * @access public
	 * @param array $field
	 * @param string $field_setting
	 * @return void
	 */
	public function validate_task_days_until_due( $field, $field_setting ) {
		
		if ( ! is_numeric( $field_setting ) ) {
			$this->set_field_error( $field, esc_html__( 'This field must be numeric.', 'gravityforms' ) );
		}
		
	}

	/**
	 * Prepare standard fields for feed field mapping.
	 * 
	 * @access public
	 * @return array
	 */
	public function standard_fields_for_feed_mapping() {
		
		return array(
			array(	
				'name'          => 'first_name',
				'label'         => __( 'First Name', 'gravityformsagilecrm' ),
				'required'      => true,
				'field_type'    => array( 'name', 'text', 'hidden' ),
				'default_value' => $this->get_first_field_by_type( 'name', 3 ),
			),
			array(	
				'name'          => 'last_name',
				'label'         => __( 'Last Name', 'gravityformsagilecrm' ),
				'required'      => true,
				'field_type'    => array( 'name', 'text', 'hidden' ),
				'default_value' => $this->get_first_field_by_type( 'name', 6 ),
			),
			array(	
				'name'          => 'email_address',
				'label'         => __( 'Email Address', 'gravityformsagilecrm' ),
				'required'      => true,
				'field_type'    => array( 'email', 'hidden' ),
				'default_value' => $this->get_first_field_by_type( 'email' ),
			),
		);
		
	}

	/**
	 * Prepare contact and custom fields for feed field mapping.
	 * 
	 * @access public
	 * @return array
	 */
	public function custom_fields_for_feed_mapping() {
		
		return array(
			array(
				'label'   => __( 'Choose a Field', 'gravityformsagilecrm' ),	
			),
			array(	
				'value'    => 'title',
				'label'    => __( 'Job Title', 'gravityformsagilecrm' ),
			),
			array(	
				'value'    => 'company',
				'label'    => __( 'Company', 'gravityformsagilecrm' ),
			),
			array(	
				'label'   => __( 'Email Address', 'gravityformsagilecrm' ),
				'choices' => array(
					array(
						'label' => __( 'Work', 'gravityformsagilecrm' ),
						'value' => 'email_work'	
					),
					array(
						'label' => __( 'Personal', 'gravityformsagilecrm' ),
						'value' => 'email_personal'	
					),
				)
			),
			array(	
				'label'   => __( 'Phone Number', 'gravityformsagilecrm' ),
				'choices' => array(
					array(
						'label' => __( 'Work', 'gravityformsagilecrm' ),
						'value' => 'phone_work'	
					),
					array(
						'label' => __( 'Home', 'gravityformsagilecrm' ),
						'value' => 'phone_home'	
					),
					array(
						'label' => __( 'Mobile', 'gravityformsagilecrm' ),
						'value' => 'phone_mobile'	
					),
					array(
						'label' => __( 'Home Fax', 'gravityformsagilecrm' ),
						'value' => 'phone_home_fax'	
					),
					array(
						'label' => __( 'Work Fax', 'gravityformsagilecrm' ),
						'value' => 'phone_work_fax'	
					),
					array(
						'label' => __( 'Other', 'gravityformsagilecrm' ),
						'value' => 'phone_other'	
					),
				)
			),
			array(	
				'label'   => __( 'Address', 'gravityformsagilecrm' ),
				'choices' => array(
					array(
						'label' => __( 'Home', 'gravityformsagilecrm' ),
						'value' => 'address_home'	
					),
					array(
						'label' => __( 'Postal', 'gravityformsagilecrm' ),
						'value' => 'address_postal'	
					),
					array(
						'label' => __( 'Office', 'gravityformsagilecrm' ),
						'value' => 'address_office'	
					),
				)
			),
			array(	
				'label'   => __( 'Website', 'gravityformsagilecrm' ),
				'choices' => array(
					array(
						'label' => __( 'URL', 'gravityformsagilecrm' ),
						'value' => 'website_url'	
					),
					array(
						'label' => __( 'Skype', 'gravityformsagilecrm' ),
						'value' => 'website_skype'	
					),
					array(
						'label' => __( 'Twitter', 'gravityformsagilecrm' ),
						'value' => 'website_twitter'	
					),
					array(
						'label' => __( 'LinkedIn', 'gravityformsagilecrm' ),
						'value' => 'website_linkedin'	
					),
					array(
						'label' => __( 'Facebook', 'gravityformsagilecrm' ),
						'value' => 'website_facebook'	
					),
					array(
						'label' => __( 'Xing', 'gravityformsagilecrm' ),
						'value' => 'website_xing'	
					),
					array(
						'label' => __( 'Feed', 'gravityformsagilecrm' ),
						'value' => 'website_feed'	
					),
					array(
						'label' => __( 'Google Plus', 'gravityformsagilecrm' ),
						'value' => 'website_google_plus'	
					),
					array(
						'label' => __( 'Flickr', 'gravityformsagilecrm' ),
						'value' => 'website_flickr'	
					),
					array(
						'label' => __( 'GitHub', 'gravityformsagilecrm' ),
						'value' => 'website_github'	
					),
					array(
						'label' => __( 'YouTube', 'gravityformsagilecrm' ),
						'value' => 'website_youtube'	
					),
				)
			),
			array(	
				'value'    => 'gf_custom',
				'label'    => __( 'Add a Custom Field', 'gravityformsagilecrm' ),
			),
		);
		
	}

	/**
	 * Set feed creation control.
	 * 
	 * @access public
	 * @return bool
	 */
	public function can_create_feed() {
		
		return $this->initialize_api();
		
	}

	/**
	 * Setup columns for feed list table.
	 * 
	 * @access public
	 * @return array
	 */
	public function feed_list_columns() {
		
		return array(
			'feedName' => __( 'Name', 'gravityformsagilecrm' ),
			'action'   => __( 'Action', 'gravityformsagilecrm' ),
		);
		
	}
	
	/**
	 * Get value for action feed list column.
	 * 
	 * @access public
	 * @param array $feed
	 * @return string $action
	 */
	public function get_column_value_action( $feed ) {
		
		if ( rgars( $feed, 'meta/createContact' ) == '1' && rgars( $feed, 'meta/createTask' ) == '1' ) {
			return esc_html__( 'Create New Contact & New Task', 'gravityformsagilecrm' );
		} else if ( rgars( $feed, 'meta/createContact' ) == '1' ) {
			return esc_html__( 'Create New Contact', 'gravityformsagilecrm' );			
		} else if ( rgars( $feed, 'meta/createTask' ) == '1' ) {
			return esc_html__( 'Create New Task', 'gravityformsagilecrm' );			
		}
		
	}

	/**
	 * Process feed.
	 * 
	 * @access public
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return void
	 */
	public function process_feed( $feed, $entry, $form ) {
		
		$this->log_debug( __METHOD__ . '(): Processing feed.' );
		
		/* If API instance is not initialized, exit. */
		if ( ! $this->initialize_api() ) {
			
			$this->add_feed_error( esc_html__( 'Feed was not processed because API was not initialized.', 'gravityformsicontact' ), $feed, $entry, $form );
			return;
			
		}
		
		/* Create contact? */
		if ( rgars( $feed, 'meta/createContact') == 1 ) {
			
			$existing_contact = $this->api->search_contacts( $this->get_field_value( $form, $entry, $feed['meta']['contactStandardFields_email_address'] ) );
			
			if ( empty( $existing_contact ) ) {
				
				$contact = $this->create_contact( $feed, $entry, $form );
				
			} else {
				
				if ( rgars( $feed, 'meta/updateContactEnable' ) == 1 ) {
					
					$contact = $this->update_contact( $existing_contact[0], $feed, $entry, $form );
					
				}
				
			}
			
		}
		
		/* Create task? */
		if ( rgars( $feed, 'meta/createTask' ) == 1 ) {
			
			$task = $this->create_task( $feed, $entry, $form );
			
		}

	}
	
	/**
	 * Create contact.
	 * 
	 * @access public
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return array $contact
	 */
	public function create_contact( $feed, $entry, $form ) {
		
		$this->log_debug( __METHOD__ . '(): Creating contact.' );

		/* Setup mapped fields array. */
		$contact_standard_fields = $this->get_field_map_fields( $feed, 'contactStandardFields' );
		$contact_custom_fields   = $this->get_dynamic_field_map_fields( $feed, 'contactCustomFields' );
		
		/* Setup base fields. */
		$first_name    = $this->get_field_value( $form, $entry, $contact_standard_fields['first_name'] );
		$last_name     = $this->get_field_value( $form, $entry, $contact_standard_fields['last_name'] );
		$default_email = $this->get_field_value( $form, $entry, $contact_standard_fields['email_address'] );
		
		/* If the name is empty, exit. */
		if ( rgblank( $first_name ) || rgblank( $last_name ) ) {
			
			$this->add_feed_error( esc_html__( 'Contact could not be created as first and/or last name were not provided.', 'gravityformsagilecrm' ), $feed, $entry, $form );
			return null;
			
		}

		/* If the email address is empty, exit. */
		if ( GFCommon::is_invalid_or_empty_email( $default_email ) ) {
			
			$this->add_feed_error( esc_html__( 'Contact could not be created as email address was not provided.', 'gravityformsagilecrm' ), $feed, $entry, $form );
			return null;
			
		}
		
		/* Build base contact. */
		$contact = array(
			'type'       => 'PERSON',
			'tags'       => array(),
			'properties' => array(
				array(
					'type'  => 'SYSTEM',
					'name'  => 'first_name',
					'value' => $first_name
				),
				array(
					'type'  => 'SYSTEM',
					'name'  => 'last_name',
					'value' => $last_name
				),
				array(
					'type'  => 'SYSTEM',
					'name'  => 'email',
					'value' => $default_email
				),
			),
		);
		
		/* Add custom field data. */
		foreach ( $contact_custom_fields as $field_key => $field_id ) {
			
			/* Get the field value. */
			$this->custom_field_key = $field_key;
			$field_value = $this->get_field_value( $form, $entry, $field_id );
			
			/* If the field value is empty, skip this field. */
			if ( rgblank( $field_value ) ) {
				continue;
			}
			
			$contact = $this->add_contact_property( $contact, $field_key, $field_value );
			
		}
		
		/* Prepare tags. */
		if ( rgars( $feed, 'meta/contactTags' ) ) {
			
			$tags            = GFCommon::replace_variables( $feed['meta']['contactTags'], $form, $entry, false, false, false, 'text' );
			$tags            = array_map( 'trim', explode( ',', $tags ) );
			$contact['tags'] = gf_apply_filters( 'gform_agilecrm_tags', $form['id'], $tags, $feed, $entry, $form );
			
		}

		$this->log_debug( __METHOD__ . '(): Creating contact: ' . print_r( $contact, true ) );
		
		try {
			
			/* Create contact. */
			$contact = $this->api->create_contact( $contact );

			/* Save contact ID to entry. */
			gform_update_meta( $entry['id'], 'agilecrm_contact_id', $contact['id'] );

			/* Log that contact was created. */
			$this->log_debug( __METHOD__ . '(): Contact #' . $contact['id'] . ' created.' );
			
		} catch ( Exception $e ) {
			
			$this->add_feed_error( sprintf( esc_html__( 'Contact could not be created. %s', 'gravityformsagilecrm' ), $e->getMessage() ), $feed, $entry, $form );
			
			return null;
			
		}
		
		return $contact;
		
	}

	/**
	 * Create task.
	 * 
	 * @access public
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return array $task
	 */
	public function create_task( $feed, $entry, $form ) {
		
		$this->log_debug( __METHOD__ . '(): Creating task.' );

		/* Prepare task object */
		$task = array(
			'subject'       => GFCommon::replace_variables( $feed['meta']['taskSubject'], $form, $entry, false, false, false, 'text' ),
			'due'           => strtotime( '+' . $feed['meta']['taskDaysUntilDue'] . ' days' ),
			'type'          => $feed['meta']['taskType'],
			'priority_type' => $feed['meta']['taskPriority'],
			'status'        => $feed['meta']['taskStatus'],
		);
		
		/* If the subject is empty, exit. */
		if ( rgblank( $task['subject'] ) ) {
			
			$this->add_feed_error( esc_html__( 'Task could not be created as subject was not provided.', 'gravityformsagilecrm' ), $feed, $entry, $form );
			
			return array();
			
		}
		
		/* Create note if set. */
		if ( rgars( $feed, 'meta/taskCreateNote') == 1 ) {
			
			$this->log_debug( __METHOD__ . '(): Creating note for task.' );
			
			/* Create note object. */
			$note = array(
				'subject'     => GFCommon::replace_variables( $feed['meta']['taskNoteSubject'], $form, $entry, false, false, false, 'text' ),
				'description' => GFCommon::replace_variables( $feed['meta']['taskNoteDescription'], $form, $entry, false, false, false, 'text' )
			);
			
			/* If the subject or description is empty, skip creation. */
			if ( rgblank( $note['subject'] ) || rgblank( $note['description'] ) ) {
				
				$this->add_feed_error( esc_html__( 'Note could not be created for task as subject and/or description were not provided.', 'gravityformsagilecrm' ), $feed, $entry, $form );
				
				$note = array();
				
			} else {
				
				$this->log_debug( __METHOD__ . '(): Creating note: ' . print_r( $note, true ) );

				try {
					
					/* Create note. */
					$note = $this->api->create_note( $note );

					/* Log that note was created. */
					$this->log_debug( __METHOD__ . '(): Note #' . $note['id'] . ' created.' );
					
					/* Assign note to task. */
					$task['notes'] = array( $note['id'] );
					
				} catch ( Exception $e ) {
					
					$this->log_error( __METHOD__ . '(): Note was not created; ' . $e->getMessage() );
					
					$note = array();
					
				}
				
			}
			
		}

		/* Assign contact if needed. */
		$contact_id = gform_get_meta( $entry['id'], 'agilecrm_contact_id' );
		if ( rgars( $feed, 'meta/taskAssignToContact' ) == 1 && ! rgblank( $contact_id ) ) {
			$task['contacts'] = array( $contact_id );
		}
				
		$this->log_debug( __METHOD__ . '(): Creating task: ' . print_r( $task, true ) );
		try {
			
			/* Create task. */
			$task = $this->api->create_task( $task );

			/* Save task ID to entry. */
			gform_update_meta( $entry['id'], 'agilecrm_task_id', $task['id'] );

			/* Log that task was created. */
			$this->log_debug( __METHOD__ . '(): Task #' . $task['id'] . ' created.' );
			
		} catch ( Exception $e ) {
			
			$this->add_feed_error( sprintf( esc_html__( 'Task could not be created. %s', 'gravityformsagilecrm' ), $e->getMessage() ), $feed, $entry, $form );
			
			return null;
			
		}
		
		return $task;
		
	}

	/**
	 * Update contact.
	 * 
	 * @access public
	 * @param array $contact
	 * @param array $feed
	 * @param array $entry
	 * @param array $form
	 * @return array $contact
	 */
	public function update_contact( $contact, $feed, $entry, $form ) {
		
		$this->log_debug( __METHOD__ . '(): Updating existing contact.' );

		/* Setup mapped fields array. */
		$contact_standard_fields = $this->get_field_map_fields( $feed, 'contactStandardFields' );
		$contact_custom_fields   = $this->get_dynamic_field_map_fields( $feed, 'contactCustomFields' );
		
		/* Setup base fields. */
		$first_name    = $this->get_field_value( $form, $entry, $contact_standard_fields['first_name'] );
		$last_name     = $this->get_field_value( $form, $entry, $contact_standard_fields['last_name'] );
		$default_email = $this->get_field_value( $form, $entry, $contact_standard_fields['email_address'] );
		
		/* If the name is empty, exit. */
		if ( rgblank( $first_name ) || rgblank( $last_name ) ) {
			
			$this->add_feed_error( esc_html__( 'Contact could not be created as first and/or last name were not provided.', 'gravityformsagilecrm' ), $feed, $entry, $form );
			return null;
		
		}

		/* If the email address is empty, exit. */
		if ( GFCommon::is_invalid_or_empty_email( $default_email ) ) {
			
			$this->add_feed_error( esc_html__( 'Contact could not be created as email address was not provided.', 'gravityformsagilecrm' ), $feed, $entry, $form );
			return null;
			
		}
		
		/* Clear out unneeded data. */
		foreach ( $contact as $key => $value ) {
			
			if ( ! in_array( $key, array( 'tags', 'properties', 'id', 'type' ) ) ) {
				unset( $contact[ $key ] );
			}
			
		}
		
		/* If we're replacing all data, clear out the properties and add the base properties. */
		if ( rgars( $feed, 'meta/updateContactAction' ) == 'replace' ) {
						
			$contact['tags']       = array();
			$contact['properties'] = array(
				array(
					'type'  => 'SYSTEM',
					'name'  => 'first_name',
					'value' => $first_name
				),
				array(
					'type'  => 'SYSTEM',
					'name'  => 'last_name',
					'value' => $last_name
				),
				array(
					'type'  => 'SYSTEM',
					'name'  => 'email',
					'value' => $default_email
				),
			);
			
		}
				
		/* Add custom field data. */
		foreach ( $contact_custom_fields as $field_key => $field_id ) {
			
			/* Get the field value. */
			$this->custom_field_key = $field_key;
			$field_value = $this->get_field_value( $form, $entry, $field_id );
			
			/* If the field value is empty, skip this field. */
			if ( rgblank( $field_value ) ) {
				continue;
			}
			
			$contact = $this->add_contact_property( $contact, $field_key, $field_value, ( rgars( $feed, 'meta/updateContactAction' ) == 'replace' ) );
			
		}
		
		/* Prepare tags. */
		if ( rgars( $feed, 'meta/contactTags' ) ) {

			$tags            = GFCommon::replace_variables( $feed['meta']['contactTags'], $form, $entry, false, false, false, 'text' );
			$tags            = array_map( 'trim', explode( ',', $tags ) );
			$tags            = array_merge( $contact['tags'], $tags );
			$contact['tags'] = gf_apply_filters( 'gform_agilecrm_tags', $form['id'], $tags, $feed, $entry, $form );

		}

		$this->log_debug( __METHOD__ . '(): Updating contact: ' . print_r( $contact, true ) );
		
		try {
			
			/* Update contact. */
			$this->api->update_contact( $contact );

			/* Save contact ID to entry. */
			gform_update_meta( $entry['id'], 'agilecrm_contact_id', $contact['id'] );

			/* Log that contact was updated. */
			$this->log_debug( __METHOD__ . '(): Contact #' . $contact['id'] . ' updated.' );
			
		} catch ( Exception $e ) {
			
			$this->add_feed_error( sprintf( esc_html__( 'Contact could not be updated. %s', 'gravityformsagilecrm' ), $e->getMessage() ), $feed, $entry, $form );
			
			return null;
			
		}
		
		return $contact;
		
	}
	
	/**
	 * Add property to contact object.
	 * 
	 * @access public
	 * @param array $contact
	 * @param string $field_key
	 * @param string $field_value
	 * @param bool $replace (default: false)
	 * @return array $contact
	 */
	public function add_contact_property( $contact, $field_key, $field_value, $replace = false ) {
		
		/* Prepare property object. */
		$property = array(
			'type'  => 'SYSTEM',
			'name'  => $field_key,
			'value' => $field_value
		);
		
		if ( strpos( $field_key, 'email_' ) === 0 ) {
			
			$property['name']    = 'email';
			$property['subtype'] = str_replace( 'email_', '', $field_key );
			
		} else if ( strpos( $field_key, 'address_' ) === 0 ) {
			
			$property['name']    = 'address';
			$property['subtype'] = str_replace( 'address_', '', $field_key );

		} else if ( strpos( $field_key, 'phone_' ) === 0 ) {
			
			$property['name']    = 'phone';
			$property['subtype'] = str_replace( array( 'phone_', '_' ), array( '', ' ') , $field_key );

		} else if ( strpos( $field_key, 'website_' ) === 0 ) {
			
			$property['name']    = 'website';
			$property['subtype'] = strtoupper( str_replace( 'website_', '' , $field_key ) );

		} else {
			
			if ( ! in_array( $field_key, array( 'title', 'company' ) ) ) {
			
				$property['type'] = 'CUSTOM';
				$property['name'] = $field_key;
				
			}
			
		}
		
		/* Check for existing property before adding. */
		$add_property = true;
		
		foreach ( $contact['properties'] as &$_property ) {
			
			if ( $_property['name'] === $property['name'] && $_property['value'] === $property['value'] ) {
				$add_property = false;
			}
			
			if ( $_property['name'] === $property['name'] && $_property['value'] !== $property['value'] && ( $replace || ( ! $replace && in_array( $field_key, array( 'title', 'company' ) ) ) ) ) {
				$_property['value'] = $property['value'];
				$add_property = false;
			}			
			
		}
		
		/* Add property object to properties array. */
		if ( $add_property ) {
			$contact['properties'][] = $property;
		}
		
		return $contact;
		
	}

	/**
	 * Add Javascript analytics tracking to footer.
	 * 
	 * @access public
	 * @return void
	 */
	public function add_analytics_tracking_to_footer() {
		
		/* Get plugin settings. */
		$settings = $this->get_plugin_settings();
		
		/* If account URL or Javascript API key is empty, exit. */
		if ( rgblank( $settings['accountURL'] ) || rgblank( $settings['javascriptAPIKey'] ) ) {
			return;
		}
			
		/* Prepare HTML block. */
		$html  = '<script type="text/javascript" src="https://' . esc_html( $settings['accountURL'] ) . '.agilecrm.com/stats/min/agile-min.js"></script>';
		$html .= '<script type="text/javascript">';
		$html .= '_agile.set_account( "' . esc_html( $settings['javascriptAPIKey'] ) . '", "' . esc_html( $settings['accountURL'] ) . '"); ';
		$html .= '_agile.track_page_view();';
		$html .= '</script>';
		
		/* Echo HTML block. */
		echo $html;
		
	}

	/**
	 * Initializes Agile CRM API if credentials are valid.
	 * 
	 * @access public
	 * @return bool
	 */
	public function initialize_api() {

		if ( ! is_null( $this->api ) ) {
			return true;
		}
		
		/* Load the Agile CRM API library. */
		if ( ! class_exists( 'AgileCRM_API' ) ) {
			require_once 'includes/class-agilecrm-api.php';
		}

		/* Get the plugin settings */
		$settings = $this->get_plugin_settings();
		
		/* If any of the account information fields are empty, return null. */
		if ( rgblank( $settings['accountURL'] ) || rgblank( $settings['emailAddress'] ) || rgblank( $settings['apiKey'] ) ) {
			return null;
		}
			
		$this->log_debug( __METHOD__ . "(): Validating API info for {$settings['accountURL']} / {$settings['emailAddress']}." );
		
		$agile = new AgileCRM_API( $settings['accountURL'], $settings['emailAddress'], $settings['apiKey'] );
		
		try {
			
			/* Run API test. */
			$agile->get_contacts();
			
			/* Log that test passed. */
			$this->log_debug( __METHOD__ . '(): API credentials are valid.' );
			
			/* Assign Agile CRM object to the class. */
			$this->api = $agile;
			
			return true;
			
		} catch ( Exception $e ) {
			
			/* Log that test failed. */
			$this->log_error( __METHOD__ . '(): API credentials are invalid; '. $e->getMessage() );			

			return false;
			
		}
		
	}

	/**
	 * Returns the combined value of the specified Address field.
	 *
	 * @param array $entry
	 * @param string $field_id
	 *
	 * @return string
	 */
	public function get_full_address( $entry, $field_id ) {

		$street_value  = str_replace( '  ', ' ', trim( rgar( $entry, $field_id . '.1' ) ) );
		$street2_value = str_replace( '  ', ' ', trim( rgar( $entry, $field_id . '.2' ) ) );
		$city_value    = str_replace( '  ', ' ', trim( rgar( $entry, $field_id . '.3' ) ) );
		$state_value   = str_replace( '  ', ' ', trim( rgar( $entry, $field_id . '.4' ) ) );
		$zip_value     = trim( rgar( $entry, $field_id . '.5' ) );
		$country_value = trim( rgar( $entry, $field_id . '.6' ) );

		$address = $street_value;
		$address .= ! empty( $street_value ) && ! empty( $street2_value ) ? "  $street2_value" : $street2_value;

		if ( strpos( $this->custom_field_key, 'address_' ) === 0 ) {

			$address_array = array(
				'address' => $address,
				'city'    => $city_value,
				'state'   => $state_value,
				'zip'     => $zip_value,
				'country' => $country_value,
			);

			return json_encode( $address_array );
		} else {

			$address .= ! empty( $address ) && ( ! empty( $city_value ) || ! empty( $state_value ) ) ? ", $city_value," : $city_value;
			$address .= ! empty( $address ) && ! empty( $city_value ) && ! empty( $state_value ) ? "  $state_value" : $state_value;
			$address .= ! empty( $address ) && ! empty( $zip_value ) ? "  $zip_value," : $zip_value;
			$address .= ! empty( $address ) && ! empty( $country_value ) ? "  $country_value" : $country_value;

			return $address;
		}

	}

}
