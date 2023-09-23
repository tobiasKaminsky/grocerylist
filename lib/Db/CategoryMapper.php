<?php

namespace OCA\GroceryList\Db;

use OCP\AppFramework\Db\Entity;
use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class CategoryMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'grocerylist_categories', Category::class);
	}

	public function find(int $id) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)));

		return $this->findEntity($qb);
	}

	public function findAll(int $id) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('list', $qb->createNamedParameter($id))
			)
			->orderBy("order")
		;

		return $this->findEntities($qb);
	}
}
