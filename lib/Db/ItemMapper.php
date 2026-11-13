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
 * @template-extends QBMapper<Item>
 */
class ItemMapper extends QBMapper {

	public const OFFSET = 15 * 60;

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'grocerylist_items', Item::class);
	}

	public function find(int $id): Item {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id))
			);

		return $this->findEntity($qb);
	}

	/**
	 * @return Item[]
	 * @throws Exception
	 */
	public function findAll(int $id): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('list', $qb->createNamedParameter($id)));

		return $this->findEntities($qb);
	}

	/**
	 * Count the unchecked items of a list, without the currently snoozed ones.
	 *
	 * @throws Exception
	 */
	public function findAllUnchecked(int $id, ?int $now = null): int {
		$qb = $this->db->getQueryBuilder();

		$qb->select($qb->func()->count('*', 'count'))
			->from($this->getTableName())
			->where($qb->expr()->eq('list', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('checked', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->lte('hidden', $qb->createNamedParameter(($now ?? time()) - self::OFFSET, IQueryBuilder::PARAM_INT)));

		$result = $qb->executeQuery();
		$count = (int)$result->fetchOne();
		$result->closeCursor();

		return $count;
	}

	/**
	 * Unix timestamp at which the next currently snoozed, unchecked item of a
	 * list is no longer snoozed, or null if none is snoozed.
	 *
	 * @throws Exception
	 */
	public function findNextSnoozeEnd(int $id, ?int $now = null): ?int {
		$qb = $this->db->getQueryBuilder();

		$qb->select($qb->func()->min('hidden'))
			->from($this->getTableName())
			->where($qb->expr()->eq('list', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('checked', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->gt('hidden', $qb->createNamedParameter(($now ?? time()) - self::OFFSET, IQueryBuilder::PARAM_INT)));

		$result = $qb->executeQuery();
		$hidden = $result->fetchOne();
		$result->closeCursor();

		return $hidden === null || $hidden === false ? null : (int)$hidden + self::OFFSET;
	}

	public function categoryUsed(int $id): bool {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('category', $qb->createNamedParameter($id)));

		return sizeof($this->findEntities($qb)) > 0;
	}

	/**
	 * Return every item that belongs to the given category. Unlike
	 * {@see self::categoryUsed()} this includes snoozed/hidden items since
	 * the caller typically wants to act on (e.g. delete) every row that
	 * references the category.
	 *
	 * @return Item[]
	 */
	public function findByCategory(int $categoryId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('category', $qb->createNamedParameter($categoryId)));

		return $this->findEntities($qb);
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
