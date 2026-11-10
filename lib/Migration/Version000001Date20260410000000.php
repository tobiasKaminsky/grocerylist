<?php

namespace OCA\GroceryList\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version000001Date20260410000000 extends SimpleMigrationStep {

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('grocerylist_categories');
		if (!$table->hasColumn('color')) {
			$table->addColumn('color', 'string', [
				'notnull' => false,
				'length' => 7,
				'default' => null,
			]);
		}

		return $schema;
	}
}
