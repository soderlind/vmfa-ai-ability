<?php
/**
 * WordPress Abilities API integration — orchestrator.
 *
 * Delegates category and ability registration to per-plugin Abilities classes.
 * Add-on classes are only loaded when their plugin is active (detected via
 * the constant each add-on defines during bootstrap).
 *
 * @package VMFAAiAbility
 */

declare(strict_types=1);

namespace VMFAAiAbility;

defined( 'ABSPATH' ) || exit;

use VMFAAiAbility\Abilities\AiOrganizerAbilities;
use VMFAAiAbility\Abilities\BaseFolderAbilities;
use VMFAAiAbility\Abilities\FolderExporterAbilities;
use VMFAAiAbility\Abilities\MediaCleanupAbilities;
use VMFAAiAbility\Abilities\RulesEngineAbilities;

/**
 * Orchestrates Abilities API registration for all active VMFA plugins.
 *
 * Category and ability registration is delegated to per-plugin Abilities classes
 * under the VMFAAiAbility\Abilities namespace. Add-on classes are only invoked
 * when the corresponding plugin constant is defined (set during plugin bootstrap).
 */
final class AbilitiesIntegration {

	/**
	 * Initialize abilities hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wp_abilities_api_categories_init', [ self::class, 'register_categories' ] );
		add_action( 'wp_abilities_api_init', [ self::class, 'register_abilities' ] );
	}

	/**
	 * Register ability categories for all active plugins.
	 *
	 * @return void
	 */
	public static function register_categories(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		// Base plugin (always active — it is a hard dependency of this plugin).
		BaseFolderAbilities::register_category();

		// Add-ons: only register their category when the add-on is active.
		if ( defined( 'VMFA_RULES_ENGINE_VERSION' ) ) {
			RulesEngineAbilities::register_category();
		}

		if ( defined( 'VMFA_MEDIA_CLEANUP_VERSION' ) ) {
			MediaCleanupAbilities::register_category();
		}

		if ( defined( 'VMFA_FOLDER_EXPORTER_VERSION' ) ) {
			FolderExporterAbilities::register_category();
		}

		if ( defined( 'VMFA_AI_ORGANIZER_VERSION' ) ) {
			AiOrganizerAbilities::register_category();
		}
	}

	/**
	 * Register abilities for all active plugins.
	 *
	 * @return void
	 */
	public static function register_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		// Base plugin (always active).
		BaseFolderAbilities::register();

		// Add-ons: only register when the add-on is active.
		if ( defined( 'VMFA_RULES_ENGINE_VERSION' ) ) {
			RulesEngineAbilities::register();
		}

		if ( defined( 'VMFA_MEDIA_CLEANUP_VERSION' ) ) {
			MediaCleanupAbilities::register();
		}

		if ( defined( 'VMFA_FOLDER_EXPORTER_VERSION' ) ) {
			FolderExporterAbilities::register();
		}

		if ( defined( 'VMFA_AI_ORGANIZER_VERSION' ) ) {
			AiOrganizerAbilities::register();
		}
	}
}

