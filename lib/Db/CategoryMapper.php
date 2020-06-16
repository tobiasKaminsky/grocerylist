<?php

namespace OCA\GroceryList\Db;

use OCP\AppFramework\Db\Entity;
use OCP\IDbConnection;
use OCP\AppFramework\Db\QBMapper;

class CategoryMapper extends QBMapper {

	public function __construct(IDbConnection $db) {
		parent::__construct($db, 'grocerylist_categories', Category::class);
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
}