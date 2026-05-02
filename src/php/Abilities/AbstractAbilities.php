<?php
/**
 * Abstract base for all Abilities classes.
 *
 * @package VMFAAiAbility\Abilities
 */

declare(strict_types=1);

namespace VMFAAiAbility\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Shared helpers for Ability registration and REST execution.
 */
abstract class AbstractAbilities {

	/**
	 * Execute a WP REST API request internally and return the response data.
	 *
	 * For GET requests, $params are sent as query params.
	 * For all other methods, $params are sent as body params.
	 *
	 * @param string               $method HTTP method (GET, POST, PUT, DELETE, …).
	 * @param string               $route  REST route path, e.g. '/vmfa-rules/v1/rules'.
	 * @param array<string, mixed> $params Request parameters.
	 * @return array<string, mixed>|\WP_Error
	 */
	protected static function rest_request(
		string $method,
		string $route,
		array $params = []
	): array|\WP_Error {
		$request = new \WP_REST_Request( $method, $route );

		if ( 'GET' === $method ) {
			$request->set_query_params( $params );
		} elseif ( ! empty( $params ) ) {
			$request->set_body_params( $params );
		}

		$response = rest_do_request( $request );
		$status   = $response->get_status();

		if ( $status >= 400 ) {
			$data    = $response->get_data();
			$code    = is_array( $data ) ? ( $data['code'] ?? 'rest_error' ) : 'rest_error';
			$message = is_array( $data ) ? ( $data['message'] ?? __( 'REST request failed.', 'vmfa-ai-ability' ) ) : __( 'REST request failed.', 'vmfa-ai-ability' );
			return new \WP_Error( $code, $message, [ 'status' => $status ] );
		}

		$data = $response->get_data();
		return is_array( $data ) ? $data : [];
	}

	/**
	 * Build standard MCP meta array for an ability.
	 *
	 * @param bool $readonly    True if the ability is read-only.
	 * @param bool $idempotent  True if the ability is idempotent.
	 * @param bool $destructive True if the ability is destructive.
	 * @return array<string, mixed>
	 */
	protected static function mcp_meta(
		bool $readonly,
		bool $idempotent,
		bool $destructive = false
	): array {
		return [
			'show_in_rest' => true,
			'mcp'          => [
				'public' => true,
				'type'   => 'tool',
			],
			'annotations'  => [
				'readonly'    => $readonly,
				'destructive' => $destructive,
				'idempotent'  => $idempotent,
			],
		];
	}

	/**
	 * Permission: requires upload_files capability.
	 *
	 * @param array<string, mixed>|null $input Ability input (unused).
	 * @return bool|\WP_Error
	 */
	public static function require_upload( ?array $input = null ): bool|\WP_Error {
		unset( $input );
		if ( current_user_can( 'upload_files' ) ) {
			return true;
		}
		return new \WP_Error(
			'rest_forbidden',
			__( 'You do not have permission to perform this action.', 'vmfa-ai-ability' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

	/**
	 * Permission: requires manage_categories capability.
	 *
	 * @param array<string, mixed>|null $input Ability input (unused).
	 * @return bool|\WP_Error
	 */
	public static function require_manage_categories( ?array $input = null ): bool|\WP_Error {
		unset( $input );
		if ( current_user_can( 'manage_categories' ) ) {
			return true;
		}
		return new \WP_Error(
			'rest_forbidden',
			__( 'You do not have permission to manage folders.', 'vmfa-ai-ability' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

	/**
	 * Permission: requires manage_options capability.
	 *
	 * @param array<string, mixed>|null $input Ability input (unused).
	 * @return bool|\WP_Error
	 */
	public static function require_manage_options( ?array $input = null ): bool|\WP_Error {
		unset( $input );
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		return new \WP_Error(
			'rest_forbidden',
			__( 'You do not have permission to perform this action.', 'vmfa-ai-ability' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}
}
