<?php

namespace OCA\GroceryList\Db;

use OCP\AppFramework\Db\Entity;
use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class GroceryListMapper extends QBMapper {
	private $userId;
	private $shareeMapper;

	public function __construct(IDBConnection $db, ShareeGroceryListMapper $shareeMapper, $userId) {
		parent::__construct($db, 'grocerylist', GroceryList::class);

		$this->shareeMapper = $shareeMapper;
		$this->userId = $userId;
	}

	public function find(int $id) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)));

		$result = $this->findEntity($qb);

		if ($result !== null) {
			return $result;
		} else {
			return $this->shareeMapper->find($id);
		}
	}

	private function findById(int $id) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));

		return $this->findEntity($qb);
	}

	public function findAll() {
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