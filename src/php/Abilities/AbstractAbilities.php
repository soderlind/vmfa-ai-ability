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
			// Top-level public flag (WP 7.1): advertises the ability to REST/MCP/AI
			// discovery clients. Harmless on earlier versions (extra meta key).
			'public'       => true,
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

	/**
	 * Standard 403 authorization error.
	 *
	 * Both permission tiers return this shape so a denial can never be mistaken
	 * for an application-level failure payload.
	 *
	 * @param string $message Human-readable reason.
	 * @return \WP_Error
	 */
	protected static function forbidden( string $message = '' ): \WP_Error {
		if ( '' === $message ) {
			$message = __( 'You do not have permission to perform this action.', 'vmfa-ai-ability' );
		}
		// Uniform 403 for both permission tiers (rule 1): a denial must never be
		// ambiguous with the 401 that rest_authorization_required_code() returns
		// for anonymous callers.
		return new \WP_Error( 'rest_forbidden', $message, [ 'status' => 403 ] );
	}

	/**
	 * Tier-2 per-object authorization for a single attachment.
	 *
	 * The tier-1 permission_callback only proves a coarse capability
	 * (e.g. `upload_files`) before the object id is known. This re-checks the
	 * singular meta-capability against THIS attachment so ownership, locking,
	 * and post-type rules are honored — closing the IDOR gap where a caller
	 * could act on an attachment id they cannot actually edit.
	 *
	 * Also confirms an authenticated principal exists, because MCP/agent or
	 * background invocations may run with no current user.
	 *
	 * @param int    $attachment_id Attachment (post) ID.
	 * @param string $cap           Singular meta-capability, e.g. 'edit_post' or 'delete_post'.
	 * @return true|\WP_Error
	 */
	protected static function authorize_attachment( int $attachment_id, string $cap = 'edit_post' ): true|\WP_Error {
		if ( get_current_user_id() <= 0 ) {
			return self::forbidden( __( 'Authentication required.', 'vmfa-ai-ability' ) );
		}

		$attachment = $attachment_id > 0 ? get_post( $attachment_id ) : null;
		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return new \WP_Error( 'rest_media_not_found', __( 'Media not found.', 'vmfa-ai-ability' ), [ 'status' => 404 ] );
		}

		if ( ! current_user_can( $cap, $attachment_id ) ) {
			return self::forbidden();
		}

		return true;
	}

	/**
	 * Tier-2 per-object authorization for a set of attachments.
	 *
	 * Denies the whole batch on the first attachment the caller cannot act on,
	 * so a partially-authorized request never silently succeeds.
	 *
	 * @param array<int, int> $attachment_ids Attachment (post) IDs.
	 * @param string          $cap            Singular meta-capability.
	 * @return true|\WP_Error
	 */
	protected static function authorize_attachments( array $attachment_ids, string $cap = 'edit_post' ): true|\WP_Error {
		foreach ( $attachment_ids as $attachment_id ) {
			$authorized = self::authorize_attachment( (int) $attachment_id, $cap );
			if ( is_wp_error( $authorized ) ) {
				return $authorized;
			}
		}

		return true;
	}
}
