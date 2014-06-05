<?php
class GP_Logger extends GP_Plugin {

	var $id = 'logger';
	static $log_entry;

	public function __construct() {
		parent::__construct();
		$this->db_setup();
		GP::$router->add( '/log', array( 'GP_Route_Logger', 'log' ) );
		GP::$router->add( '/log/-set-status', array( 'GP_Route_Logger', 'set_status' ) );
		add_action( 'warning_discarded', array( $this, 'warning_discarded' ), 10, 5 );
	}


	function db_setup() {
		global $gpdb;
		global $gp_table_prefix;

		//TODO: only setup table with "secret" query string

		if ( gp_get_option('gp_log_table_created') ) {
			$gpdb->set_prefix( $gp_table_prefix , array('log'));
			return;
		}

		require_once( BACKPRESS_PATH . 'class.bp-sql-schema-parser.php' );
		$sql = "CREATE TABLE IF NOT EXISTS `gp_log` (
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
			$gpdb->set_prefix( $gp_table_prefix , array('log') );
		}

		gp_update_option( 'gp_log_table_created', true);
	}

	function warning_discarded( $project_id, $translation_set_id, $translation_id, $warning, $user_id ){
		global $gp_log_entry;
		$meta_data = compact( 'translation', 'tag', 'user' );

		$meta_data['time'] = time();

		switch ( $warning ) {
			case 'urls': //TODO: implement in core GP, see https://glotpress.trac.wordpress.org/ticket/307
			case 'length':
			case 'placeholders':
			case 'tags':
				$log_item = new GP_Log_Entry( );
				$log_item->translation_set_id = $translation_set_id;
				$log_item->translation_id = $translation_id;
				$log_item->event = 'warning_discarded_' . $warning;
				$log_item->user_id = $user_id;
				$log_item->status = 'waiting';

				if( $log_item->validate() ) {
					$gp_log_entry->create( $log_item );
				}
		}

	}

	function get_set_warnings( $set_id ) {
		global $gp_log_entry;
		return $gp_log_entry->by_translation_set_id( $set_id );
	}

}

GP::$plugins->log = new GP_Logger;


class GP_Route_Logger extends GP_Route_Main {

	function __construct() {
		$this->template_path = dirname( __FILE__ ) . '/templates/';
	}

	 function log() {
		 global $gp_log_entry;

		 //TODO: permissions
		$warnings = $gp_log_entry->all();
		$this->tmpl( 'log', get_defined_vars() );
	}


	function set_status(){
		global $gp_log_entry;

		$log_id = absint( gp_post( 'log_id' ) );

		//TODO: whitelist status
		$status = gp_post( 'post' );

		$entry = $gp_log_entry->get( $log_id );

		//TODO: permissions
		$entry->set_status( $status );
	}
}


class GP_Log_Entry extends GP_Thing {

	var $table_basename = 'log';
	var $field_names = array( 'id', 'translation_set_id', 'translation_id', 'event', 'date_added', 'date_modified', 'user_id', 'status' );
	var $non_updatable_attributes = array( 'id' );

	function restrict_fields( $gp_log_entry ) {
		$gp_log_entry->translation_set_id_should_be( 'positive_int' );
		$gp_log_entry->translation_id_should_be( 'positive_int' );
		$gp_log_entry->event_should_not_be( 'empty' );
		$gp_log_entry->user_id_should_be( 'positive_int' );
		$gp_log_entry->status_should_not_be( 'empty' );
	}

	function by_translation_set_id( $translation_set_id ) {
		return $this->many( "SELECT * FROM $this->table WHERE transaltion_set_id= %d", $translation_set_id );
	}

	function set_status( $status ) {
		return $this->update( array( 'status' => $status ) );
	}

}

$gp_log_entry = new GP_Log_Entry();
