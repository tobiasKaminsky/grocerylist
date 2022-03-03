<?php

namespace OCA\GroceryList\Db;

use OCP\AppFramework\Db\Entity;
use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class ItemMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'grocerylist_items', Item::class);
	}

	public function find(int $id) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id))
			);

		return $this->findEntity($qb);
	}

	public function findAll(int $id) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('list', $qb->createNamedParameter($id))
			);

		return $this->findEntities($qb);
	}

	public function categoryUsed(int $id): bool {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('category', $qb->createNamedParameter($id))
			);

		return sizeof($this->findEntities($qb)) > 0;
	}
}