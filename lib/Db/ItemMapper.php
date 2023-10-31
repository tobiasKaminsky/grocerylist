<?php

namespace OCA\GroceryList\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Exception;
use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

/**
 * @template-implements QBMapper<Item>
 */
class ItemMapper extends QBMapper {

	const OFFSET = 1 * 60;

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
	 * @throws Exception
	 */
	public function findAll(int $id): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('list', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->lt('hidden', $qb->createNamedParameter(time() - $this::OFFSET)));

		return $this->findEntities($qb);
	}

	public function categoryUsed(int $id): bool {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('category', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->lt('hidden', $qb->createNamedParameter(time() - $this::OFFSET)));

		return sizeof($this->findEntities($qb)) > 0;
	}
}
