<?php

namespace OCA\GroceryList\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getName()
 * @method void setName(string $name)
 * @method string getQuantity()
 * @method void setQuantity(string $quantity)
 * @method int getCategory()
 * @method void setCategory(int $category)
 * @method int getList()
 * @method void setList(int $list)
 */
class Item extends Entity implements JsonSerializable {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $quantity;

	/**
	 * @var int
	 */
	protected $category;

	/**
	 * @var int
	 */
	protected $list;

	/**
	 * @var int
	 */
	protected $checked;

	/**
	 * @var int
	 */
	protected $hidden;

	public function __construct() {
		$this->addType('name', 'string');
		$this->addType('quantity', 'string');
		$this->addType('category', 'int');
		$this->addType('list', 'int');
		$this->addType('checked', 'int');
		$this->addType('hidden', 'int');
	}

	public function isChecked(): bool {
		return $this->checked !== 0;
	}

	public function setChecked(bool $checked): void {
		$this->setter('checked', [$checked ? 1 : 0]);
	}

	public function isHidden(): bool {
		return $this->hidden !== 0;
	}

	public function setHidden(bool $hidden): void {
		$this->setter('hidden', [$hidden ? 1 : 0]);
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'quantity' => $this->getQuantity(),
			'category' => $this->getCategory(),
			'list' => $this->getList(),
			'checked' => $this->isChecked(),
			'hidden' => $this->isHidden(),
		];
	}
}
