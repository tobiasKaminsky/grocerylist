<?php

namespace OCA\GroceryList\Db;

use OCP\AppFramework\Db\Entity;
use OCP\IDbConnection;
use OCP\AppFramework\Db\QBMapper;

class GroceryListMapper extends QBMapper {
	private $userId;

	public function __construct(IDbConnection $db, $UserId) {
		parent::__construct($db, 'grocerylist', GroceryList::class);

		$this->userId = $UserId;
	}

	public function find(int $id) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)));

		return $this->findEntity($qb);
	}

	public function findAll() {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)));

		return $this->findEntities($qb);
	}
}