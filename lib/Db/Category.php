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

	public function __construct() {
		$this->addType('name', 'string');
		$this->addType('order', 'int');
		$this->addType('list', 'int');
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'order' => $this->getOrder(),
			'name' => $this->getName(),
			'grocery_list' => $this->getList(),
		];
	}
}
