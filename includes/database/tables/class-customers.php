<?php
/**
 * Customers Table.
 *
 * @package     EDD
 * @subpackage  Database\Tables
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */
namespace EDD\Database\Tables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use EDD\Database\Table;

/**
 * Setup the global "edd_customers" database table
 *
 * @since 3.0
 */
final class Customers extends Table {

	/**
	 * Table name
	 *
	 * @access protected
	 * @since 3.0
	 * @var string
	 */
	protected $name = 'edd_customers';

	/**
	 * Database version
	 *
	 * @access protected
	 * @since 3.0
	 * @var int
	 */
	protected $version = 201807270003;

	/**
	 * Array of upgrade versions and methods
	 *
	 * @since 3.0
	 *
	 * @var array
	 */
	protected $upgrades = array(
		'201807110001' => 201807110001,
		'201807130001' => 201807130001,
		'201807130002' => 201807130002,
		'201807270003' => 201807270003,
	);

	/**
	 * Setup the database schema
	 *
	 * @access protected
	 * @since 3.0
	 * @return void
	 */
	protected function set_schema() {
		$this->schema = "id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL default '0',
			email varchar(100) NOT NULL default '',
			name mediumtext NOT NULL,
			status varchar(20) NOT NULL default '',
			purchase_value decimal(18,9) NOT NULL default '0',
			purchase_count bigint(20) unsigned NOT NULL default '0',
			date_created datetime NOT NULL default '0000-00-00 00:00:00',
			date_modified datetime NOT NULL default '0000-00-00 00:00:00',
			uuid varchar(100) NOT NULL default '',
			PRIMARY KEY (id),
			UNIQUE KEY email (email),
			KEY user (user_id),
			KEY status (status(20)),
			KEY date_created (date_created)";
	}

	/**
	 * Override the Base class `maybe_upgrade()` routine to do a very unique and
	 * special check against the old option.
	 *
	 * Maybe upgrades the database table from 2.x to 3.x standards. This method
	 * should be kept up-to-date with schema changes in `set_schema()` above.
	 *
	 * - Hooked to the "admin_init" action.
	 * - Calls the parent class `maybe_upgrade()` method
	 *
	 * @since 3.0
	 */
	public function maybe_upgrade() {
		if ( false !== get_option( $this->prefix . 'edd_customers_db_version', false ) ) {
			delete_option( $this->prefix . 'edd_customers_db_version' );

			$this->get_db()->query( "ALTER TABLE {$this->table_name} MODIFY `email` varchar(100) NOT NULL default ''" );
			$this->get_db()->query( "ALTER TABLE {$this->table_name} MODIFY `user_id` bigint(20) unsigned NOT NULL default '0'" );
			$this->get_db()->query( "ALTER TABLE {$this->table_name} MODIFY `purchase_value` decimal(18,9) NOT NULL default '0'" );
			$this->get_db()->query( "ALTER TABLE {$this->table_name} MODIFY `purchase_count` bigint(20) unsigned NOT NULL default '0'" );
			$this->get_db()->query( "ALTER TABLE {$this->table_name} ALTER COLUMN `date_created` SET DEFAULT '0000-00-00 00:00:00'" );

			if ( ! $this->column_exists( 'status' ) ) {
				$this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN `status` varchar(20) NOT NULL default 'active' AFTER `name`;" );
				$this->get_db()->query( "ALTER TABLE {$this->table_name} ADD INDEX status (status(20))" );
			}

			if ( ! $this->column_exists( 'date_modified' ) ) {
				$this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN `date_modified` datetime DEFAULT '0000-00-00 00:00:00' AFTER `date_created`" );
				$this->get_db()->query( "UPDATE {$this->table_name} SET `date_modified` = `date_created`" );
				$this->get_db()->query( "ALTER TABLE {$this->table_name} ADD INDEX date_created (date_created)" );
			}
		}

		parent::maybe_upgrade();
	}

	/**
	 * Upgrade to version 201806070001
	 * - Change `purchase_value` from mediumtext to decimal(18,9).
	 * - Add the `status` column.
	 *
	 * @since 3.0
	 *
	 * @return bool
	 */
	protected function __201807110001() {

		// Alter the database
		$this->get_db()->query( "ALTER TABLE {$this->table_name} MODIFY `purchase_value` decimal(18,9) NOT NULL default '0'" );

		if ( ! $this->column_exists( 'status' ) ) {
			$this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN `status` varchar(20) NOT NULL default 'active' AFTER `name`" );
			$this->get_db()->query( "ALTER TABLE {$this->table_name} ADD INDEX status (status(20))" );
		}

		// Return success/fail
		return $this->is_success( true );
	}

	/**
	 * Upgrade to version 201807130001
	 * - Add `date_modified` column.
	 *
	 * @since 3.0
	 *
	 * @return bool
	 */
	protected function __201807130001() {

		if ( ! $this->column_exists( 'date_modified' ) ) {
			$this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN date_modified datetime NOT NULL default '0000-00-00 00:00:00' AFTER `date_created`" );
		}

		// Return success/fail
		return $this->is_success( true );
	}

	/**
	 * Upgrade to version 201807130002
	 * - Set values of `date_modified` to `date_created` (no empties)
	 *
	 * @since 3.0
	 *
	 * @return bool
	 */
	protected function __201807130002() {

		// Update modified row values
		$this->get_db()->query( "UPDATE {$this->table_name} SET `date_modified` = `date_created`" );

		// Return success/fail
		return $this->is_success( true );
	}

	/**
	 * Upgrade to version 201807270003
	 * - Add the `uuid` varchar column
	 *
	 * @since 3.0
	 *
	 * @return boolean
	 */
	protected function __201807270003() {

		// Look for column
		$result = $this->column_exists( 'uuid' );

		// Maybe add column
		if ( false === $result ) {
			$result = $this->get_db()->query( "
				ALTER TABLE {$this->table_name} ADD COLUMN `uuid` varchar(100) default '' AFTER `date_modified`;
			" );
		}

		// Return success/fail
		return $this->is_success( $result );
	}
}