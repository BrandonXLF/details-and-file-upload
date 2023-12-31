<?php
/**
 * Tests for the Tracked_Files class.
 *
 * @package Details and File Upload
 */

namespace DetailsAndFileUploadPlugin;

/**
 * Tests for the Tracked_Files class.
 */
class Tracked_Files_Tests extends \WP_UnitTestCase {
	public function set_up(): void {
		parent::set_up();

		Tracked_Files::setup();
	}

	public function test_table_name() {
		global $wpdb;

		$this->assertEquals( $wpdb->prefix . 'dfu_tracked_file_uploads', Tracked_Files::table_name() );
	}

	public function test_track_file() {
		Tracked_Files::track_file( WC()->session->get_customer_id(), '/foo/bar' );

		global $wpdb;

		$table = Tracked_Files::table_name();

		$count = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT count(*) FROM $table WHERE session_id = %d  AND file_path = %s",
				WC()->session->get_customer_id(),
				'/foo/bar'
			)
		)[0]->{'count(*)'};

		$this->assertEquals( 1, $count );
	}

	public function test_delete_file() {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image.tmp.png'
			)
		);

		Tracked_Files::track_file( WC()->session->get_customer_id(), $tmp_dir . '/example-image.tmp.png' );

		global $wpdb;
		$table = Tracked_Files::table_name();

		$this->assertFileExists( $tmp_dir . '/example-image.tmp.png' );

		$count = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT count(*) FROM $table WHERE session_id = %d  AND file_path = %s",
				WC()->session->get_customer_id(),
				$tmp_dir . '/example-image.tmp.png'
			)
		)[0]->{'count(*)'};

		$this->assertEquals( 1, $count );

		Tracked_Files::delete_file( WC()->session->get_customer_id(), $tmp_dir . '/example-image.tmp.png' );

		$this->assertFileDoesNotExist( $tmp_dir . '/example-image.tmp.png' );

		$count = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT count(*) FROM $table WHERE session_id = %d  AND file_path = %s",
				WC()->session->get_customer_id(),
				$tmp_dir . '/example-image.tmp.png'
			)
		)[0]->{'count(*)'};

		$this->assertEquals( 0, $count );
	}

	public function test_untrack_session() {
		Tracked_Files::track_file( WC()->session->get_customer_id(), '/foo/bar' );
		Tracked_Files::track_file( WC()->session->get_customer_id(), '/bar/baz' );

		Tracked_Files::untrack_session( WC()->session->get_customer_id() );

		global $wpdb;

		$table = Tracked_Files::table_name();

		$count = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT count(*) FROM $table WHERE session_id = %d",
				WC()->session->get_customer_id()
			)
		)[0]->{'count(*)'};

		$this->assertEquals( 0, $count );
	}

	public function test_cleanup() {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image.tmp1.png'
			)
		);

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image.tmp2.png'
			)
		);

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image.tmp3.png'
			)
		);

		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}woocommerce_sessions (`session_key`, `session_value`, `session_expiry`)
				VALUES (%s, '', %d)",
				WC()->session->get_customer_id(),
				time() + 30
			)
		);

		Tracked_Files::track_file( WC()->session->get_customer_id(), $tmp_dir . '/example-image.tmp1.png' );
		Tracked_Files::track_file( 'FAKE_KEY1', $tmp_dir . '/example-image.tmp2.png' );
		Tracked_Files::track_file( 'FAKE_KEY2', $tmp_dir . '/example-image.tmp3.png' );

		Tracked_Files::cleanup();

		$this->assertTrue( file_exists( $tmp_dir . '/example-image.tmp1.png' ) );
		$this->assertFalse( file_exists( $tmp_dir . '/example-image.tmp2.png' ) );
		$this->assertFalse( file_exists( $tmp_dir . '/example-image.tmp3.png' ) );

		global $wpdb;

		$table = Tracked_Files::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_results( "SELECT count(*) FROM $table" )[0]->{'count(*)'};

		$this->assertEquals( 1, $count );
	}

	public function test_uninstall() {
		$tmp_dir = ini_get( 'upload_tmp_dir' ) ?: sys_get_temp_dir();

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image.tmp1.png'
			)
		);

		$this->assertTrue(
			copy(
				__DIR__ . '/example-image.png',
				$tmp_dir . '/example-image.tmp2.png'
			)
		);

		Tracked_Files::track_file( WC()->session->get_customer_id(), $tmp_dir . '/example-image.tmp1.png' );
		Tracked_Files::track_file( 'FAKE_KEY1', $tmp_dir . '/example-image.tmp2.png' );

		Tracked_Files::uninstall();

		$this->assertFalse( file_exists( $tmp_dir . '/example-image.tmp1.png' ) );
		$this->assertFalse( file_exists( $tmp_dir . '/example-image.tmp2.png' ) );

		global $wpdb;

		$this->assertEmpty(
			$wpdb->get_var(
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( Tracked_Files::table_name() ) )
			)
		);
	}
}
