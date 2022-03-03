<?php

namespace OCA\GroceryList\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version000000Date20200604211400 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('grocerylist')) {
			$table = $schema->createTable('grocerylist');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('title', 'string', [
				'notnull' => true,
				'length' => 200
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 200
			]);
			$table->addColumn('show_only_unchecked', 'integer', [
				'notnull' => true,
				'default' => 0,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('grocerylist_categories')) {
			$table = $schema->createTable('grocerylist_categories');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('name', 'string', [
				'notnull' => true,
				'length' => 200
			]);
			$table->addColumn('order', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('list', 'integer', [
				'notnull' => true,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('grocerylist_items')) {
			$table = $schema->createTable('grocerylist_items');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('name', 'string', [
				'notnull' => true,
				'length' => 200
			]);
			$table->addColumn('quantity', 'string', [
				'notnull' => true,
				'length' => 200
			]);
			$table->addColumn('category', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('list', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('checked', 'integer', [
				'notnull' => true,
				'default' => 0,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('grocerylist_sharee')) {
			$table = $schema->createTable('grocerylist_sharee');
			$table->addColumn('list', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 200
			]);
		}

		return $schema;
	}
}