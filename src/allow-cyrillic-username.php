<?php
/**
 * @param string $username
 * @param string $raw_username
 * @param bool $strict
 * @return string
 */
function acu_sanitize_user( $username, $raw_username, $strict ) {
	$username = wp_strip_all_tags( $raw_username );
	$username = remove_accents( $username );
	// Kill octets
	$username = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '', $username );
	// Kill entities.
	$username = preg_replace( '/&.+?;/', '', $username ); // Kill entities

	// If strict, reduce to ASCII and Cyrillic characters for max portability.
	if ( $strict ) {
		$username = preg_replace( '|[^a-zа-яё0-9 _.\-@]|iu', '', $username );
	}

	$username = trim( $username );
	// Consolidate contiguous whitespace
	$username = preg_replace( '|\s+|', ' ', $username );

	return $username;
}

add_filter( 'sanitize_user', 'acu_sanitize_user', 10, 3 );
