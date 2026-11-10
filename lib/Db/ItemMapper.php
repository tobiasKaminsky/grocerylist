<?php

/**
 * SPDX-FileCopyrightText: 2020 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroceryList\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
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
	 * @throws Exception
	 */
	public function findAllUnchecked(int $id): int {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('list', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('checked', $qb->createNamedParameter(0)));

		return sizeof($this->findEntities($qb));
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
}
