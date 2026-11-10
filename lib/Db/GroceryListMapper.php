<?php

/**
 * SPDX-FileCopyrightText: 2020 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroceryList\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<GroceryList>
 */
class GroceryListMapper extends QBMapper {
	private $userId;
	private $shareeMapper;

	public function __construct(IDBConnection $db, ShareeGroceryListMapper $shareeMapper, $userId) {
		parent::__construct($db, 'grocerylist', GroceryList::class);

		$this->shareeMapper = $shareeMapper;
		$this->userId = $userId;
	}

	public function find(int $id) : GroceryList {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)));

		$result = $this->findEntities($qb);

		if (sizeof($result) > 0) {
			return $result[0];
		} else {
			$sharedList = $this->shareeMapper->find($id);
			return $this->findById($sharedList->list);
		}
	}

	private function findById(int $id): GroceryList {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));

		return $this->findEntity($qb);
	}

	/**
	 * @return GroceryList[]
	 */
	public function findAll() : array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)));

		$returnList = $this->findEntities($qb);

		$shareeList = $this->shareeMapper->findAll();

		foreach ($shareeList as $sharee) {
			$returnList[] = $this->findById($sharee->list);
		}

		return $returnList;
	}
}
