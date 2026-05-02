<?php
/**
 * Rules Engine ability registrations.
 *
 * Active only when vmfa-rules-engine is loaded (VMFA_RULES_ENGINE_VERSION defined).
 *
 * @package VMFAAiAbility\Abilities
 */

declare(strict_types=1);

namespace VMFAAiAbility\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers MCP abilities for the Virtual Media Folders – Rules Engine add-on.
 *
 * REST namespace: vmfa-rules/v1
 */
final class RulesEngineAbilities extends AbstractAbilities {

	private const CATEGORY_SLUG = 'vmfo-rules-engine';

	/**
	 * Register the ability category.
	 */
	public static function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY_SLUG,
			[
				'label'       => __( 'Rules Engine', 'vmfa-ai-ability' ),
				'description' => __( 'Abilities for managing automatic folder-assignment rules.', 'vmfa-ai-ability' ),
			]
		);
	}

	/**
	 * Register all Rules Engine abilities.
	 */
	public static function register(): void {
		self::register_list_rules();
		self::register_create_rule();
		self::register_update_rule();
		self::register_delete_rule();
		self::register_preview();
		self::register_apply();
	}

	// -----------------------------------------------------------------------
	// Ability registrations
	// -----------------------------------------------------------------------

	private static function register_list_rules(): void {
		wp_register_ability(
			'vmfo-rules/list-rules',
			[
				'label'               => __( 'List Rules', 'vmfa-ai-ability' ),
				'description'         => __( 'Returns all folder-assignment rules ordered by priority.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_list_rules' ],
				'permission_callback' => [ self::class, 'require_upload' ],
				'meta'                => self::mcp_meta( readonly: true, idempotent: true ),
			]
		);
	}

	private static function register_create_rule(): void {
		wp_register_ability(
			'vmfo-rules/create-rule',
			[
				'label'               => __( 'Create Rule', 'vmfa-ai-ability' ),
				'description'         => __( 'Creates a new folder-assignment rule.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'name'        => [
							'type'        => 'string',
							'minLength'   => 1,
							'description' => __( 'Rule display name.', 'vmfa-ai-ability' ),
						],
						'folder_id'   => [
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'Target folder term ID.', 'vmfa-ai-ability' ),
						],
						'conditions'  => [
							'type'        => 'array',
							'description' => __( 'Array of condition objects (type, operator, value).', 'vmfa-ai-ability' ),
						],
						'enabled'     => [
							'type'        => 'boolean',
							'default'     => true,
							'description' => __( 'Whether the rule is active.', 'vmfa-ai-ability' ),
						],
						'priority'    => [
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'Evaluation priority (lower = evaluated first).', 'vmfa-ai-ability' ),
						],
						'description' => [
							'type'        => 'string',
							'description' => __( 'Optional rule description.', 'vmfa-ai-ability' ),
						],
					],
					'required'             => [ 'name', 'folder_id', 'conditions' ],
					'additionalProperties' => true,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_create_rule' ],
				'permission_callback' => [ self::class, 'require_manage_options' ],
				'meta'                => self::mcp_meta( readonly: false, idempotent: false ),
			]
		);
	}

	private static function register_update_rule(): void {
		wp_register_ability(
			'vmfo-rules/update-rule',
			[
				'label'               => __( 'Update Rule', 'vmfa-ai-ability' ),
				'description'         => __( 'Updates an existing folder-assignment rule.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'rule_id'     => [
							'type'        => 'string',
							'description' => __( 'The rule ID to update.', 'vmfa-ai-ability' ),
						],
						'name'        => [
							'type'        => 'string',
							'minLength'   => 1,
							'description' => __( 'New rule display name.', 'vmfa-ai-ability' ),
						],
						'folder_id'   => [
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'New target folder term ID.', 'vmfa-ai-ability' ),
						],
						'conditions'  => [
							'type'        => 'array',
							'description' => __( 'Updated condition objects.', 'vmfa-ai-ability' ),
						],
						'enabled'     => [
							'type'        => 'boolean',
							'description' => __( 'Whether the rule is active.', 'vmfa-ai-ability' ),
						],
						'priority'    => [
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'Evaluation priority.', 'vmfa-ai-ability' ),
						],
						'description' => [
							'type'        => 'string',
							'description' => __( 'Rule description.', 'vmfa-ai-ability' ),
						],
					],
					'required'             => [ 'rule_id' ],
					'additionalProperties' => true,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_update_rule' ],
				'permission_callback' => [ self::class, 'require_manage_options' ],
				'meta'                => self::mcp_meta( readonly: false, idempotent: true ),
			]
		);
	}

	private static function register_delete_rule(): void {
		wp_register_ability(
			'vmfo-rules/delete-rule',
			[
				'label'               => __( 'Delete Rule', 'vmfa-ai-ability' ),
				'description'         => __( 'Permanently deletes a folder-assignment rule.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'rule_id' => [
							'type'        => 'string',
							'description' => __( 'The rule ID to delete.', 'vmfa-ai-ability' ),
						],
					],
					'required'             => [ 'rule_id' ],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_delete_rule' ],
				'permission_callback' => [ self::class, 'require_manage_options' ],
				'meta'                => self::mcp_meta( readonly: false, idempotent: false, destructive: true ),
			]
		);
	}

	private static function register_preview(): void {
		wp_register_ability(
			'vmfo-rules/preview',
			[
				'label'               => __( 'Preview Rule Application', 'vmfa-ai-ability' ),
				'description'         => __( 'Dry-runs all enabled rules against media and returns what would be changed without applying.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'unassigned_only' => [
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Only preview unassigned media.', 'vmfa-ai-ability' ),
						],
						'mime_type'       => [
							'type'        => 'string',
							'description' => __( 'Limit preview to a specific MIME type (e.g. image/jpeg).', 'vmfa-ai-ability' ),
						],
					],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_preview' ],
				'permission_callback' => [ self::class, 'require_upload' ],
				'meta'                => self::mcp_meta( readonly: true, idempotent: true ),
			]
		);
	}

	private static function register_apply(): void {
		wp_register_ability(
			'vmfo-rules/apply',
			[
				'label'               => __( 'Apply Rules', 'vmfa-ai-ability' ),
				'description'         => __( 'Runs all enabled rules against media and assigns folders in batch.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'unassigned_only' => [
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Only process unassigned media.', 'vmfa-ai-ability' ),
						],
						'mime_type'       => [
							'type'        => 'string',
							'description' => __( 'Limit to a specific MIME type.', 'vmfa-ai-ability' ),
						],
					],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_apply' ],
				'permission_callback' => [ self::class, 'require_manage_options' ],
				'meta'                => self::mcp_meta( readonly: false, idempotent: false ),
			]
		);
	}

	// -----------------------------------------------------------------------
	// Execute callbacks
	// -----------------------------------------------------------------------

	/** @param array<string, mixed> $input */
	public static function execute_list_rules( array $input ): array|\WP_Error {
		return self::rest_request( 'GET', '/vmfa-rules/v1/rules' );
	}

	/** @param array<string, mixed> $input */
	public static function execute_create_rule( array $input ): array|\WP_Error {
		return self::rest_request( 'POST', '/vmfa-rules/v1/rules', $input );
	}

	/** @param array<string, mixed> $input */
	public static function execute_update_rule( array $input ): array|\WP_Error {
		$rule_id = (string) ( $input['rule_id'] ?? '' );

		if ( $rule_id === '' ) {
			return new \WP_Error( 'ability_invalid_input', __( 'A valid rule_id is required.', 'vmfa-ai-ability' ) );
		}

		$body = $input;
		unset( $body['rule_id'] );

		return self::rest_request( 'PUT', '/vmfa-rules/v1/rules/' . rawurlencode( $rule_id ), $body );
	}

	/** @param array<string, mixed> $input */
	public static function execute_delete_rule( array $input ): array|\WP_Error {
		$rule_id = (string) ( $input['rule_id'] ?? '' );

		if ( $rule_id === '' ) {
			return new \WP_Error( 'ability_invalid_input', __( 'A valid rule_id is required.', 'vmfa-ai-ability' ) );
		}

		return self::rest_request( 'DELETE', '/vmfa-rules/v1/rules/' . rawurlencode( $rule_id ) );
	}

	/** @param array<string, mixed> $input */
	public static function execute_preview( array $input ): array|\WP_Error {
		return self::rest_request( 'POST', '/vmfa-rules/v1/preview', $input );
	}

	/** @param array<string, mixed> $input */
	public static function execute_apply( array $input ): array|\WP_Error {
		return self::rest_request( 'POST', '/vmfa-rules/v1/apply', $input );
	}
}
