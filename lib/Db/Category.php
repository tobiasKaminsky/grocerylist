<?php

namespace OCA\GroceryList\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getName()
 * @method void setName(string $name)
 * @method int getOrder()
 * @method void setOrder(int $order)
 * @method int getList()
 * @method void setList(int $list)
 */
class Category extends Entity implements JsonSerializable {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var int
	 */
	protected $order;

	/**
	 * @var int
	 */
	protected $list;

	/**
	 * Virtual field populated by the controller – not stored in the DB.
	 * @var int
	 */
	protected $itemCount;

	public function __construct() {
		$this->addType('name', 'string');
		$this->addType('order', 'int');
		$this->addType('list', 'int');
		$this->addType('itemCount', 'int');
	}

	public function setItemCount(int $count): void {
		$this->setter('itemCount', [$count]);
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'order' => $this->getOrder(),
			'name' => $this->getName(),
			'grocery_list' => $this->getList(),
			'itemCount' => $this->itemCount ?? 0,
		];
	}
}
