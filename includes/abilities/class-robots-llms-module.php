<?php
/**
 * robots.txt / llms.txt / llms-full.txt read and update abilities.
 *
 * @package RootsAndFruitAbilities
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RF_Robots_Llms_Module implements RF_Ability_Module {

	public function category_slug(): string {
		return 'rootsandfruit-discovery';
	}

	public function category_label(): string {
		return __( 'Roots & Fruit — Agent discovery', 'rootsandfruit-abilities' );
	}

	public function category_description(): string {
		return __( 'Read and update robots.txt, llms.txt, and llms-full.txt at the document root.', 'rootsandfruit-abilities' );
	}

	public function definitions(): array {
		return array(
			RF_Ability_Definition::make( 'rootsandfruit/get-robots-llms-txt' )
				->label( __( 'Get robots / llms text file', 'rootsandfruit-abilities' ) )
				->description(
					__(
						'Returns content and sha256 for robots.txt, llms.txt, or llms-full.txt. File must already exist.',
						'rootsandfruit-abilities'
					)
				)
				->category( $this->category_slug() )
				->input( RF_Schemas::robots_llms_file_input() )
				->output( RF_Schemas::robots_llms_get_output() )
				->execute( array( RF_Robots_Llms_Files::class, 'get' ) )
				->permission( array( RF_Permissions::class, 'can_read' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::read_only() )
				->build(),
			RF_Ability_Definition::make( 'rootsandfruit/update-robots-llms-txt' )
				->label( __( 'Update robots / llms text file', 'rootsandfruit-abilities' ) )
				->description(
					__(
						'Updates an existing robots.txt, llms.txt, or llms-full.txt. Requires expected_sha256, creates .bak backup, verifies after write. No create or delete.',
						'rootsandfruit-abilities'
					)
				)
				->category( $this->category_slug() )
				->input( RF_Schemas::robots_llms_update_input() )
				->output( RF_Schemas::robots_llms_update_output() )
				->execute( array( RF_Robots_Llms_Files::class, 'update' ) )
				->permission( array( RF_Permissions::class, 'can_update_robots_llms_txt' ) )
				->mcp_public( true )
				->annotations( RF_Annotations::write_safe() )
				->build(),
		);
	}
}
