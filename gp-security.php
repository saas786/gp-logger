<?php

class GP_Security extends GP_Plugin {

	var $meta_key = 'security_warning';
	var $id = 'security';

	static $log_entry;

	public function __construct() {
		parent::__construct();
		$this->db_setup();
		GP::$router->add( '/security', array( 'GP_Route_Security', 'security' ) );
		add_action( 'warning_discarded', array( $this, 'warning_discarded' ), 10, 5 );
	}


	function db_setup() {
		global $gpdb;
		global $gp_table_prefix;

		//TODO: only setup table with "secret" query string
		if ( $gpdb->query( "SHOW TABLES LIKE 'gp_security_log'" ) ) {
			$gpdb->set_prefix( $gp_table_prefix , array('security_log'));
			return;
		}

		require_once( BACKPRESS_PATH . 'class.bp-sql-schema-parser.php' );
		$sql = "CREATE TABLE IF NOT EXISTS `gp_security_log` (
	        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	        `translation_set_id` int(10) unsigned NOT NULL,
	        `translation_id` int(10) NOT NULL,
	        `event` varchar(30) DEFAULT NULL,
	        `date_added` datetime NOT NULL,
			`date_modified` datetime NOT NULL,
	        `user_id` int(10) NOT NULL,
	        `status` varchar(20) NOT NULL DEFAULT 'waiting',
	        PRIMARY KEY (`id`),
	        KEY `translation_set_id_status` (`translation_set_id`,`status`),
	        KEY `event` (`event`),
	        KEY `user_id` (`user_id`),
	        KEY `date_modified` (`date_modified`),
	        KEY `date_added` (`date_added`)
		);";

		$alterations = BP_SQL_Schema_Parser::delta( $gpdb, $sql );

		$errors = $alterations['errors'];
		if ( $errors )  {
			return $errors;
		} else {
			$gpdb->set_prefix( $gp_table_prefix , array('security_log') );
		}
	}

	function warning_discarded( $project_id, $translation_set_id, $translation_id, $warning, $user_id ){
		global $gp_security_log_entry;
		$meta_data = compact( 'translation', 'tag', 'user' );

		$meta_data['time'] = time();

		switch ( $warning ) {
			case 'urls': //TODO: implement in core GP, see https://glotpress.trac.wordpress.org/ticket/307
			case 'tags':
				$log_item = new GP_Security_Log_Entry( );
				$log_item->translation_set_id = $translation_set_id;
				$log_item->translation_id = $translation_id;
				$log_item->event = 'warning_discarded_' . $warning;
				$log_item->user_id = $user_id;
				$log_item->status = 'waiting';

				if( $log_item->validate() ) {
					$gp_security_log_entry->create( $log_item );
				}
		}

	}

	function get_set_security_warning( $set_id ) {
		//TODO: implement
	}

}

GP::$plugins->security = new GP_Security;


class GP_Route_Security extends GP_Route_Main {

	function __construct() {
		$this->template_path = dirname( __FILE__ ) . '/templates/';
	}

	 function security() {
		global $gp_security_log_entry;
		$warnings = $gp_security_log_entry->all();

		$this->tmpl('security', get_defined_vars() );
	}
}


class GP_Security_Log_Entry extends GP_Thing {

	var $table_basename = 'security_log';
	var $field_names = array( 'id', 'translation_set_id', 'translation_id', 'event', 'date_added', 'date_modified', 'user_id', 'status' );
	var $non_updatable_attributes = array( 'id' );

}

$gp_security_log_entry = new GP_Security_Log_Entry();