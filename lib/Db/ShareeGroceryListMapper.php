<?php

/**
 * SPDX-FileCopyrightText: 2020 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroceryList\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Sharee>
 */
class ShareeGroceryListMapper extends QBMapper {
	private $userId;

	public function __construct(IDBConnection $db, $userId) {
		parent::__construct($db, 'grocerylist_sharee', Sharee::class);

		$this->userId = $userId;
	}

	/**
	 * @throws DoesNotExistException if the list is not shared with the current user
	 */
	public function find(int $id) : Sharee {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('list', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)));

		return $this->findEntity($qb);
	}

	/**
	 * @return Sharee[]
	 * @throws Exception
	 */
	public function findSharees(int $id) : array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('list', $qb->createNamedParameter($id)));

		return $this->findEntities($qb);
	}

	/**
	 * @return Sharee[]
	 * @throws Exception
	 */
	public function findAll(): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)));

		return $this->findEntities($qb);
	}

	public function isSharedWith(int $list, string $userId): bool {
		$qb = $this->db->getQueryBuilder();

		$qb->select('user_id')
			->from($this->getTableName())
			->where($qb->expr()->eq('list', $qb->createNamedParameter($list, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->setMaxResults(1);

		$result = $qb->executeQuery();
		$shared = $result->fetch() !== false;
		$result->closeCursor();

		return $shared;
	}

	/**
	 * The sharee table has no id column, so insert directly instead of via QBMapper::insert()
	 *
	 * @throws Exception
	 */
	public function share(int $list, string $userId): void {
		$qb = $this->db->getQueryBuilder();

		$qb->insert($this->getTableName())
			->values([
				'list' => $qb->createNamedParameter($list, IQueryBuilder::PARAM_INT),
				'user_id' => $qb->createNamedParameter($userId),
			])
			->executeStatement();
	}

	/**
	 * @throws Exception
	 */
	public function unshare(int $list, string $userId): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('list', $qb->createNamedParameter($list, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->executeStatement();
	}

	/**
	 * @throws Exception
	 */
	public function unshareAll(int $list): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('list', $qb->createNamedParameter($list, IQueryBuilder::PARAM_INT)))
			->executeStatement();
	}
}
