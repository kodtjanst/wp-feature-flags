<?php
/**
 * Class to handle registration, activation, and checking of feature flags.
 *
 * @package WP Feature Flags.
 */

namespace WP_Feature_Flags;

class FeatureFlags {

	/**
	 * instance
	 * Hold our class instance for single instantiation.
	 *
	 * @var PetitionCount
	 * @access private
	 */
	private static $instance = null;

	public $flags_option = 'feature_flags';

	public $flags = [];

	/**
	 * Set and get an instance of this class.
	 *
	 * @return FeatureFlags
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function register_flag( $flag ) {
		// if we have nothing to parse, bounce.
		if ( empty( $flag ) ) {
			return false;
		}

		// If default enabled is turned on, and the flag has not been enabled yet, enable it.
		if ( $flag->auto_enabled ) {
			$this->enable_flag( $flag->id );
		}

		// Add this flag to our filter of all flags.
		$this->flags[] = $flag;

		return true;
	}

	public function enable_flag( $flag ) {
		$this->update_flag( $flag, 'enabled' );
		do_action( "enable_{$flag}_flag" );
	}

	public function disable_flag( $flag ) {
		$this->update_flag( $flag, 'disabled' );
		do_action( "disable_{$flag}_flag" );
	}

	private function update_flag( $flag, $status ) {
		$all_flags = $this->get_flag_statuses();

		// If we don't have a value or the value is not the status - update it.
		if ( ! isset( $all_flags[ $flag ] ) || $status !== $all_flags[ $flag ] ) {
			$all_flags[ $flag ] = $status;
			update_option( $this->flags_option, json_encode( $all_flags ), true );
		}
	}

	public function flag_enabled( $flag ) {
		$flag_statuses = $this->get_flag_statuses();
		$all_flags     = apply_filters( 'available_feature_flags', [] );

		// Does this flag exist?
		if ( ! isset( $all_flags[ $flag ] ) ) {
			// TODO: not sure what to return here, can't do false becuase we might have expired a flag
			//return false;
		}

		// Is flag auto-enabled?
		if ( isset( $all_flags[ $flag ] ) && $all_flags[ $flag ]->auto_enabled ) {
			return true;
		}

		// Is flag network-enabled?
		if ( is_multisite() && $this->flag_network_enabled( $flag ) ) {
			return true;
		}

		// Have we turned this feature on?
		if ( $flag_statuses[ $flag ] === 'enabled' ) {
			return true;
		}

		return false;
	}

	public function flag_network_enabled( $flag ) {
		if ( ! is_multisite() ) {
			return false;
		}

		return false;
	}

	public function network_enable_flag() {
		if ( ! is_multisite() ) {
			return false;
		}
	}

	public function spoof_flag_enabled( $flag ) {

	}

	public function get_flag_statuses() {
		return json_decode( get_option( $this->flags_option, true ) );
	}

	public function get_flags() {
		return $this->flags;
	}

}