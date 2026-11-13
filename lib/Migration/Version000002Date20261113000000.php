<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroceryList\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Replace the legacy `grocerylist_sharee` table (no primary key, no indexes)
 * with `grocerylist_shares` and copy over all shares of lists that still exist.
 * The legacy table is dropped in the next migration step.
 */
class Version000002Date20261113000000 extends SimpleMigrationStep {

	public function __construct(
		private IDBConnection $db,
	) {
	}

	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('grocerylist_shares')) {
			return null;
		}

		$table = $schema->createTable('grocerylist_shares');
		$table->addColumn('id', Types::BIGINT, [
			'autoincrement' => true,
			'notnull' => true,
			'unsigned' => true,
		]);
		$table->addColumn('list_id', Types::INTEGER, [
			'notnull' => true,
		]);
		$table->addColumn('user_id', Types::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->setPrimaryKey(['id']);
		$table->addUniqueIndex(['list_id', 'user_id'], 'grocerylist_shares_list_user');
		$table->addIndex(['user_id'], 'grocerylist_shares_user');

		return $schema;
	}

	#[\Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if (!$schema->hasTable('grocerylist_sharee')) {
			return;
		}

		$select = $this->db->getQueryBuilder();
		$select->selectDistinct(['s.list', 's.user_id'])
			->from('grocerylist_sharee', 's')
			->innerJoin('s', 'grocerylist', 'g', $select->expr()->eq('g.id', 's.list'));

		$insert = $this->db->getQueryBuilder();
		$insert->insert('grocerylist_shares')
			->values([
				'list_id' => $insert->createParameter('list_id'),
				'user_id' => $insert->createParameter('user_id'),
			]);

		$result = $select->executeQuery();
		while ($row = $result->fetch()) {
			$insert->setParameter('list_id', (int)$row['list'], IQueryBuilder::PARAM_INT)
				->setParameter('user_id', (string)$row['user_id'])
				->executeStatement();
		}
		$result->closeCursor();
	}
}
