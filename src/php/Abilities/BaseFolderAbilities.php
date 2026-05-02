<?php
/**
 * Base folder ability registrations.
 *
 * Covers Virtual Media Folders (base plugin) operations.
 *
 * @package VMFAAiAbility\Abilities
 */

declare(strict_types=1);

namespace VMFAAiAbility\Abilities;

defined( 'ABSPATH' ) || exit;

use VirtualMediaFolders\RestApi;
use VirtualMediaFolders\Taxonomy;
use WP_Error;

/**
 * Registers MCP abilities for the Virtual Media Folders base plugin.
 *
 * The first three abilities (list, create, add-to-folder) keep their original
 * direct-PHP implementation. The four new abilities (update, delete,
 * remove-from-folder, get-suggestions) delegate to the existing REST API via
 * rest_do_request() to avoid compile-time coupling.
 */
final class BaseFolderAbilities extends AbstractAbilities {

	private const CATEGORY_SLUG = 'vmfo-folder-management';

	/**
	 * Register the ability category.
	 */
	public static function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY_SLUG,
			[
				'label'       => __( 'Folder Management', 'vmfa-ai-ability' ),
				'description' => __( 'Abilities for assigning and organizing media in folders.', 'vmfa-ai-ability' ),
			]
		);
	}

	/**
	 * Register all base folder abilities.
	 */
	public static function register(): void {
		self::register_list_folders();
		self::register_create_folder();
		self::register_add_to_folder();
		self::register_update_folder();
		self::register_delete_folder();
		self::register_remove_from_folder();
		self::register_get_suggestions();
	}

	// -----------------------------------------------------------------------
	// Ability registrations
	// -----------------------------------------------------------------------

	private static function register_list_folders(): void {
		wp_register_ability(
			'vmfo/list-folders',
			[
				'label'               => __( 'List Folders', 'vmfa-ai-ability' ),
				'description'         => __( 'Lists folders with IDs, names, and paths for name-to-ID resolution.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'search'     => [
							'type'        => 'string',
							'description' => __( 'Optional search term for folder names.', 'vmfa-ai-ability' ),
						],
						'parent_id'  => [
							'type'        => 'integer',
							'minimum'     => 0,
							'description' => __( 'Optional parent folder ID to scope results.', 'vmfa-ai-ability' ),
						],
						'hide_empty' => [
							'type'        => 'boolean',
							'description' => __( 'Whether to exclude empty folders.', 'vmfa-ai-ability' ),
							'default'     => false,
						],
					],
					'additionalProperties' => false,
				],
				'output_schema'       => [
					'type'                 => 'object',
					'properties'           => [
						'folders' => [
							'type'  => 'array',
							'items' => [
								'type'                 => 'object',
								'properties'           => [
									'id'        => [ 'type' => 'integer' ],
									'name'      => [ 'type' => 'string' ],
									'parent_id' => [ 'type' => 'integer' ],
									'path'      => [ 'type' => 'string' ],
									'count'     => [ 'type' => 'integer' ],
								],
								'required'             => [ 'id', 'name', 'parent_id', 'path', 'count' ],
								'additionalProperties' => false,
							],
						],
						'total'   => [ 'type' => 'integer' ],
					],
					'required'             => [ 'folders', 'total' ],
					'additionalProperties' => false,
				],
				'execute_callback'    => [ self::class, 'execute_list_folders' ],
				'permission_callback' => [ self::class, 'require_upload' ],
				'meta'                => self::mcp_meta( readonly: true, idempotent: true ),
			]
		);
	}

	private static function register_create_folder(): void {
		wp_register_ability(
			'vmfo/create-folder',
			[
				'label'               => __( 'Create Folder', 'vmfa-ai-ability' ),
				'description'         => __( 'Creates a Virtual Media Folders folder with an optional parent.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'name'      => [
							'type'        => 'string',
							'minLength'   => 1,
							'description' => __( 'Folder name to create.', 'vmfa-ai-ability' ),
						],
						'parent_id' => [
							'type'        => 'integer',
							'minimum'     => 0,
							'default'     => 0,
							'description' => __( 'Optional parent folder ID (0 for top-level).', 'vmfa-ai-ability' ),
						],
					],
					'required'             => [ 'name' ],
					'additionalProperties' => false,
				],
				'output_schema'       => [
					'type'                 => 'object',
					'properties'           => [
						'id'        => [ 'type' => 'integer' ],
						'name'      => [ 'type' => 'string' ],
						'parent_id' => [ 'type' => 'integer' ],
						'path'      => [ 'type' => 'string' ],
						'count'     => [ 'type' => 'integer' ],
					],
					'required'             => [ 'id', 'name', 'parent_id', 'path', 'count' ],
					'additionalProperties' => false,
				],
				'execute_callback'    => [ self::class, 'execute_create_folder' ],
				'permission_callback' => [ self::class, 'require_manage_categories' ],
				'meta'                => self::mcp_meta( readonly: false, idempotent: false ),
			]
		);
	}

	private static function register_add_to_folder(): void {
		wp_register_ability(
			'vmfo/add-to-folder',
			[
				'label'               => __( 'Add Media To Folder', 'vmfa-ai-ability' ),
				'description'         => __( 'Adds one or more media items to a Virtual Media Folders folder.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'folder_id'      => [
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'The target folder term ID.', 'vmfa-ai-ability' ),
						],
						'attachment_ids' => [
							'type'        => 'array',
							'minItems'    => 1,
							'uniqueItems' => true,
							'items'       => [
								'type'    => 'integer',
								'minimum' => 1,
							],
							'description' => __( 'Attachment IDs to assign to the folder.', 'vmfa-ai-ability' ),
						],
					],
					'required'             => [ 'folder_id', 'attachment_ids' ],
					'additionalProperties' => false,
				],
				'output_schema'       => [
					'type'                 => 'object',
					'properties'           => [
						'success'         => [
							'type'        => 'boolean',
							'description' => __( 'Whether the folder assignment completed successfully.', 'vmfa-ai-ability' ),
						],
						'folder_id'       => [
							'type'        => 'integer',
							'description' => __( 'The folder that received the media items.', 'vmfa-ai-ability' ),
						],
						'attachment_ids'  => [
							'type'        => 'array',
							'items'       => [ 'type' => 'integer' ],
							'description' => __( 'The media IDs processed by the ability.', 'vmfa-ai-ability' ),
						],
						'processed_count' => [
							'type'        => 'integer',
							'description' => __( 'The number of media items processed.', 'vmfa-ai-ability' ),
						],
						'message'         => [
							'type'        => 'string',
							'description' => __( 'Summary of the completed folder assignment.', 'vmfa-ai-ability' ),
						],
						'results'         => [
							'type'        => 'array',
							'items'       => [
								'type'                 => 'object',
								'properties'           => [
									'success'   => [ 'type' => 'boolean' ],
									'media_id'  => [ 'type' => 'integer' ],
									'folder_id' => [ 'type' => 'integer' ],
									'message'   => [ 'type' => 'string' ],
								],
								'required'             => [ 'success', 'media_id', 'folder_id', 'message' ],
								'additionalProperties' => false,
							],
							'description' => __( 'Per-item results from the folder assignment operations.', 'vmfa-ai-ability' ),
						],
					],
					'required'             => [ 'success', 'folder_id', 'attachment_ids', 'processed_count', 'message', 'results' ],
					'additionalProperties' => false,
				],
				'execute_callback'    => [ self::class, 'execute_add_to_folder' ],
				'permission_callback' => [ self::class, 'require_upload' ],
				'meta'                => self::mcp_meta( readonly: false, idempotent: true ),
			]
		);
	}

	private static function register_update_folder(): void {
		wp_register_ability(
			'vmfo/update-folder',
			[
				'label'               => __( 'Update Folder', 'vmfa-ai-ability' ),
				'description'         => __( 'Renames or moves a Virtual Media Folders folder to a new parent.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'folder_id' => [
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'The folder term ID to update.', 'vmfa-ai-ability' ),
						],
						'name'      => [
							'type'        => 'string',
							'minLength'   => 1,
							'description' => __( 'New name for the folder.', 'vmfa-ai-ability' ),
						],
						'parent_id' => [
							'type'        => 'integer',
							'minimum'     => 0,
							'description' => __( 'New parent folder ID (0 for top-level).', 'vmfa-ai-ability' ),
						],
					],
					'required'             => [ 'folder_id' ],
					'additionalProperties' => false,
				],
				'output_schema'       => [
					'type'                 => 'object',
					'properties'           => [
						'id'        => [ 'type' => 'integer' ],
						'name'      => [ 'type' => 'string' ],
						'parent_id' => [ 'type' => 'integer' ],
						'path'      => [ 'type' => 'string' ],
						'count'     => [ 'type' => 'integer' ],
					],
					'required'             => [ 'id', 'name', 'parent_id', 'path', 'count' ],
					'additionalProperties' => false,
				],
				'execute_callback'    => [ self::class, 'execute_update_folder' ],
				'permission_callback' => [ self::class, 'require_manage_categories' ],
				'meta'                => self::mcp_meta( readonly: false, idempotent: true ),
			]
		);
	}

	private static function register_delete_folder(): void {
		wp_register_ability(
			'vmfo/delete-folder',
			[
				'label'               => __( 'Delete Folder', 'vmfa-ai-ability' ),
				'description'         => __( 'Deletes a Virtual Media Folders folder. Media items in the folder are unassigned, not deleted.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'folder_id' => [
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'The folder term ID to delete.', 'vmfa-ai-ability' ),
						],
						'force'     => [
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Force deletion even if the folder has sub-folders.', 'vmfa-ai-ability' ),
						],
					],
					'required'             => [ 'folder_id' ],
					'additionalProperties' => false,
				],
				'output_schema'       => [
					'type'       => 'object',
					'properties' => [
						'deleted'  => [ 'type' => 'boolean' ],
						'previous' => [ 'type' => 'object' ],
					],
				],
				'execute_callback'    => [ self::class, 'execute_delete_folder' ],
				'permission_callback' => [ self::class, 'require_manage_categories' ],
				'meta'                => self::mcp_meta( readonly: false, idempotent: false, destructive: true ),
			]
		);
	}

	private static function register_remove_from_folder(): void {
		wp_register_ability(
			'vmfo/remove-from-folder',
			[
				'label'               => __( 'Remove Media From Folder', 'vmfa-ai-ability' ),
				'description'         => __( 'Removes one or more media items from a Virtual Media Folders folder.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'folder_id'      => [
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'The folder term ID to remove media from.', 'vmfa-ai-ability' ),
						],
						'attachment_ids' => [
							'type'        => 'array',
							'minItems'    => 1,
							'uniqueItems' => true,
							'items'       => [
								'type'    => 'integer',
								'minimum' => 1,
							],
							'description' => __( 'Attachment IDs to remove from the folder.', 'vmfa-ai-ability' ),
						],
					],
					'required'             => [ 'folder_id', 'attachment_ids' ],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_remove_from_folder' ],
				'permission_callback' => [ self::class, 'require_upload' ],
				'meta'                => self::mcp_meta( readonly: false, idempotent: true ),
			]
		);
	}

	private static function register_get_suggestions(): void {
		wp_register_ability(
			'vmfo/get-suggestions',
			[
				'label'               => __( 'Get Folder Suggestions', 'vmfa-ai-ability' ),
				'description'         => __( 'Returns heuristic folder placement suggestions for a media item.', 'vmfa-ai-ability' ),
				'category'            => self::CATEGORY_SLUG,
				'input_schema'        => [
					'type'                 => 'object',
					'properties'           => [
						'media_id' => [
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'The attachment ID to get folder suggestions for.', 'vmfa-ai-ability' ),
						],
					],
					'required'             => [ 'media_id' ],
					'additionalProperties' => false,
				],
				'output_schema'       => [ 'type' => 'object' ],
				'execute_callback'    => [ self::class, 'execute_get_suggestions' ],
				'permission_callback' => [ self::class, 'require_upload' ],
				'meta'                => self::mcp_meta( readonly: true, idempotent: true ),
			]
		);
	}

	// -----------------------------------------------------------------------
	// Execute callbacks
	// -----------------------------------------------------------------------

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function execute_list_folders( array $input ): array|WP_Error {
		$search     = isset( $input['search'] ) ? trim( (string) $input['search'] ) : '';
		$hide_empty = isset( $input['hide_empty'] ) ? (bool) $input['hide_empty'] : false;

		$args = [
			'taxonomy'     => Taxonomy::TAXONOMY,
			'hide_empty'   => $hide_empty,
			'hierarchical' => true,
			'number'       => 0,
		];

		if ( isset( $input['parent_id'] ) && $input['parent_id'] !== null && $input['parent_id'] !== '' ) {
			$args['parent'] = absint( $input['parent_id'] );
		}

		if ( $search !== '' ) {
			$args['search'] = $search;
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) ) {
			return $terms;
		}

		if ( ! is_array( $terms ) ) {
			return [ 'folders' => [], 'total' => 0 ];
		}

		$term_cache = [];
		foreach ( $terms as $term ) {
			if ( isset( $term->term_id ) ) {
				$term_cache[ (int) $term->term_id ] = $term;
			}
		}

		$folders = [];
		foreach ( $terms as $term ) {
			$folders[] = [
				'id'        => (int) $term->term_id,
				'name'      => (string) $term->name,
				'parent_id' => (int) $term->parent,
				'path'      => self::build_folder_path( $term, $term_cache ),
				'count'     => (int) $term->count,
			];
		}

		return [
			'folders' => $folders,
			'total'   => count( $folders ),
		];
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function execute_create_folder( array $input ): array|WP_Error {
		$name      = isset( $input['name'] ) ? trim( (string) $input['name'] ) : '';
		$parent_id = absint( $input['parent_id'] ?? 0 );

		if ( '' === $name ) {
			return new WP_Error(
				'ability_invalid_input',
				__( 'A non-empty folder name is required.', 'vmfa-ai-ability' )
			);
		}

		if ( $parent_id > 0 ) {
			$parent_validation = self::validate_folder( $parent_id );
			if ( is_wp_error( $parent_validation ) ) {
				return new WP_Error(
					'parent_not_exists',
					__( 'Parent folder does not exist.', 'vmfa-ai-ability' ),
					[ 'status' => 400 ]
				);
			}
		}

		$result = wp_insert_term( $name, Taxonomy::TAXONOMY, [ 'parent' => $parent_id ] );

		if ( is_wp_error( $result ) ) {
			$error_code = $result->get_error_code();

			$error_messages = [
				'term_exists'       => __( 'A folder with this name already exists.', 'vmfa-ai-ability' ),
				'empty_term_name'   => __( 'Folder name cannot be empty.', 'vmfa-ai-ability' ),
				'invalid_term'      => __( 'Invalid folder.', 'vmfa-ai-ability' ),
				'invalid_taxonomy'  => __( 'Invalid folder taxonomy.', 'vmfa-ai-ability' ),
				'parent_not_exists' => __( 'Parent folder does not exist.', 'vmfa-ai-ability' ),
			];

			return new WP_Error(
				$error_code,
				$error_messages[ $error_code ] ?? $result->get_error_message(),
				[ 'status' => 400 ]
			);
		}

		$folder = get_term( $result['term_id'], Taxonomy::TAXONOMY );
		if ( is_wp_error( $folder ) || ! $folder ) {
			return new WP_Error( 'rest_folder_not_found', __( 'Folder not found.', 'vmfa-ai-ability' ), [ 'status' => 404 ] );
		}

		$term_cache = [ (int) $folder->term_id => $folder ];

		return [
			'id'        => (int) $folder->term_id,
			'name'      => (string) $folder->name,
			'parent_id' => (int) $folder->parent,
			'path'      => self::build_folder_path( $folder, $term_cache ),
			'count'     => (int) $folder->count,
		];
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function execute_add_to_folder( array $input ): array|WP_Error {
		$folder_id      = absint( $input['folder_id'] ?? 0 );
		$attachment_ids = self::normalize_attachment_ids( $input['attachment_ids'] ?? [] );

		if ( $folder_id < 1 || [] === $attachment_ids ) {
			return new WP_Error(
				'ability_invalid_input',
				__( 'A valid folder_id and at least one attachment_id are required.', 'vmfa-ai-ability' )
			);
		}

		$folder_validation = self::validate_folder( $folder_id );
		if ( is_wp_error( $folder_validation ) ) {
			return $folder_validation;
		}

		$attachment_validation = self::validate_attachments( $attachment_ids );
		if ( is_wp_error( $attachment_validation ) ) {
			return $attachment_validation;
		}

		$rest_api = new RestApi();
		$results  = [];

		foreach ( $attachment_ids as $attachment_id ) {
			$result = $rest_api->assign_media_to_folder( $attachment_id, $folder_id );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$results[] = [
				'success'   => (bool) ( $result['success'] ?? false ),
				'media_id'  => absint( $result['media_id'] ?? 0 ),
				'folder_id' => absint( $result['folder_id'] ?? 0 ),
				'message'   => (string) ( $result['message'] ?? '' ),
			];
		}

		return [
			'success'         => true,
			'folder_id'       => $folder_id,
			'attachment_ids'  => $attachment_ids,
			'processed_count' => count( $results ),
			'message'         => sprintf(
				/* translators: %d: number of media items processed. */
				__( 'Processed %d media items.', 'vmfa-ai-ability' ),
				count( $results )
			),
			'results'         => $results,
		];
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function execute_update_folder( array $input ): array|WP_Error {
		$folder_id = absint( $input['folder_id'] ?? 0 );

		if ( $folder_id < 1 ) {
			return new WP_Error( 'ability_invalid_input', __( 'A valid folder_id is required.', 'vmfa-ai-ability' ) );
		}

		$body = [];

		if ( isset( $input['name'] ) ) {
			$name = trim( (string) $input['name'] );
			if ( $name !== '' ) {
				$body['name'] = $name;
			}
		}

		if ( array_key_exists( 'parent_id', $input ) ) {
			$body['parent'] = absint( $input['parent_id'] ); // REST API param is 'parent'
		}

		if ( empty( $body ) ) {
			return new WP_Error(
				'ability_invalid_input',
				__( 'At least one of name or parent_id must be provided.', 'vmfa-ai-ability' )
			);
		}

		$rest_result = self::rest_request( 'PUT', '/vmfo/v1/folders/' . $folder_id, $body );
		if ( is_wp_error( $rest_result ) ) {
			return $rest_result;
		}

		// Fetch updated term to return consistent shape with other folder abilities.
		$folder = get_term( $folder_id, Taxonomy::TAXONOMY );
		if ( is_wp_error( $folder ) || ! $folder ) {
			return new WP_Error( 'rest_folder_not_found', __( 'Folder not found after update.', 'vmfa-ai-ability' ), [ 'status' => 404 ] );
		}

		$term_cache = [ (int) $folder->term_id => $folder ];

		return [
			'id'        => (int) $folder->term_id,
			'name'      => (string) $folder->name,
			'parent_id' => (int) $folder->parent,
			'path'      => self::build_folder_path( $folder, $term_cache ),
			'count'     => (int) $folder->count,
		];
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function execute_delete_folder( array $input ): array|WP_Error {
		$folder_id = absint( $input['folder_id'] ?? 0 );

		if ( $folder_id < 1 ) {
			return new WP_Error( 'ability_invalid_input', __( 'A valid folder_id is required.', 'vmfa-ai-ability' ) );
		}

		$params = [ 'force' => (bool) ( $input['force'] ?? false ) ];

		return self::rest_request( 'DELETE', '/vmfo/v1/folders/' . $folder_id, $params );
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function execute_remove_from_folder( array $input ): array|WP_Error {
		$folder_id      = absint( $input['folder_id'] ?? 0 );
		$attachment_ids = self::normalize_attachment_ids( $input['attachment_ids'] ?? [] );

		if ( $folder_id < 1 || [] === $attachment_ids ) {
			return new WP_Error(
				'ability_invalid_input',
				__( 'A valid folder_id and at least one attachment_id are required.', 'vmfa-ai-ability' )
			);
		}

		return self::rest_request(
			'DELETE',
			'/vmfo/v1/folders/' . $folder_id . '/media',
			[ 'attachment_ids' => $attachment_ids ]
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|WP_Error
	 */
	public static function execute_get_suggestions( array $input ): array|WP_Error {
		$media_id = absint( $input['media_id'] ?? 0 );

		if ( $media_id < 1 ) {
			return new WP_Error( 'ability_invalid_input', __( 'A valid media_id is required.', 'vmfa-ai-ability' ) );
		}

		return self::rest_request( 'GET', '/vmfo/v1/suggestions/' . $media_id );
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	/**
	 * Normalize attachment IDs from ability input.
	 *
	 * @param mixed $attachment_ids Raw attachment IDs.
	 * @return array<int, int>
	 */
	private static function normalize_attachment_ids( mixed $attachment_ids ): array {
		if ( ! is_array( $attachment_ids ) ) {
			return [];
		}

		$attachment_ids = array_map( 'absint', $attachment_ids );
		$attachment_ids = array_filter( $attachment_ids );

		return array_values( array_unique( $attachment_ids ) );
	}

	/**
	 * Validate that a folder term exists.
	 *
	 * @param int $folder_id Folder term ID.
	 * @return bool|WP_Error
	 */
	private static function validate_folder( int $folder_id ): bool|WP_Error {
		$folder = get_term( $folder_id, Taxonomy::TAXONOMY );

		if ( is_wp_error( $folder ) ) {
			return $folder;
		}

		if ( ! $folder ) {
			return new WP_Error( 'rest_folder_not_found', __( 'Folder not found.', 'vmfa-ai-ability' ), [ 'status' => 404 ] );
		}

		return true;
	}

	/**
	 * Validate that all IDs correspond to real attachment posts.
	 *
	 * @param array<int, int> $attachment_ids Attachment IDs.
	 * @return bool|WP_Error
	 */
	private static function validate_attachments( array $attachment_ids ): bool|WP_Error {
		foreach ( $attachment_ids as $attachment_id ) {
			$attachment = get_post( $attachment_id );

			if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
				return new WP_Error( 'rest_media_not_found', __( 'Media not found.', 'vmfa-ai-ability' ), [ 'status' => 404 ] );
			}
		}

		return true;
	}

	/**
	 * Build a human-readable hierarchical folder path.
	 *
	 * @param object               $term       Folder term.
	 * @param array<int, object> &$term_cache  Cached terms keyed by term_id.
	 * @return string
	 */
	private static function build_folder_path( object $term, array &$term_cache ): string {
		$segments = [ (string) ( $term->name ?? '' ) ];
		$visited  = [];
		$parent   = (int) ( $term->parent ?? 0 );

		while ( $parent > 0 && ! isset( $visited[ $parent ] ) ) {
			$visited[ $parent ] = true;

			if ( isset( $term_cache[ $parent ] ) ) {
				$parent_term = $term_cache[ $parent ];
			} else {
				$parent_term = get_term( $parent, Taxonomy::TAXONOMY );
				if ( is_wp_error( $parent_term ) || ! $parent_term ) {
					break;
				}
				$term_cache[ $parent ] = $parent_term;
			}

			array_unshift( $segments, (string) ( $parent_term->name ?? '' ) );
			$parent = (int) ( $parent_term->parent ?? 0 );
		}

		return implode( ' / ', array_filter( $segments, static fn( string $s ): bool => $s !== '' ) );
	}
}
