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
 * @method string|null getColor()
 * @method void setColor(?string $color)
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
	 * @var string|null
	 */
	protected $color;

	public function __construct() {
		$this->addType('name', 'string');
		$this->addType('order', 'int');
		$this->addType('list', 'int');
		$this->addType('color', 'string');
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'order' => $this->getOrder(),
			'name' => $this->getName(),
			'color' => $this->getColor(),
			'grocery_list' => $this->getList(),
		];
	}
}
