<?php
/**
 * Main plugin class.
 *
 * @package VMFAAiAbility
 */

declare(strict_types=1);

namespace VMFAAiAbility;

defined( 'ABSPATH' ) || exit;

use VirtualMediaFolders\Addon\AbstractPlugin;

/**
 * Plugin bootstrap class.
 */
final class Plugin extends AbstractPlugin {

	/** @inheritDoc */
	protected function get_text_domain(): string {
		return 'vmfa-ai-ability';
	}

	/** @inheritDoc */
	protected function get_plugin_file(): string {
		return VMFA_AI_ABILITY_FILE;
	}

	/** @inheritDoc */
	protected function init_services(): void {
		// No services needed — this add-on registers abilities only.
	}

	/** @inheritDoc */
	protected function init_hooks(): void {
		AbilitiesIntegration::init();
	}
}
