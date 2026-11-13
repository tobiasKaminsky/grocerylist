<?php

/**
 * SPDX-FileCopyrightText: 2020 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroceryList\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Category>
 */
class CategoryMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'grocerylist_categories', Category::class);
	}

	public function find(int $id): Category {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));

		return $this->findEntity($qb);
	}

	/**
	 * @return Category[]
	 */
	public function findAll(int $id): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('list', $qb->createNamedParameter($id))
			)
			->orderBy('order')
		;

		return $this->findEntities($qb);
	}

	/**
	 * Persist a new order for the given list's categories.
	 *
	 * Categories are updated in the order in which their ids are provided;
	 * the first id receives order 0, the second order 1, and so on. Ids that
	 * do not belong to the given list are ignored so a stale or malicious
	 * payload can not touch categories of other lists.
	 *
	 * @param int $listId The list whose categories are being reordered.
	 * @param int[] $orderedCategoryIds Category ids in the desired order.
	 */
	public function reorder(int $listId, array $orderedCategoryIds): void {
		$this->db->beginTransaction();
		try {
			$order = 0;
			foreach ($orderedCategoryIds as $categoryId) {
				$qb = $this->db->getQueryBuilder();
				$qb->update($this->getTableName())
					->set('order', $qb->createNamedParameter($order, \PDO::PARAM_INT))
					->where($qb->expr()->eq('id', $qb->createNamedParameter((int)$categoryId, \PDO::PARAM_INT)))
					->andWhere($qb->expr()->eq('list', $qb->createNamedParameter($listId, \PDO::PARAM_INT)))
					->executeStatement();
				$order++;
			}
			$this->db->commit();
		} catch (\Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/**
	 * @throws Exception
	 */
	public function deleteByList(int $listId): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('list', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)))
			->executeStatement();
	}
}
