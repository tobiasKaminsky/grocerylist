<?php

namespace OCA\GroceryList\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class Item extends Entity implements JsonSerializable {

	protected $name;
	protected $quantity;
	protected $category;
	protected $list;
	protected $checked;
	protected $hidden;

	public function __construct() {
		$this->addType('name', 'string');
		$this->addType('quantity', 'string');
		$this->addType('category', 'int');
		$this->addType('list', 'int');
		$this->addType('checked', 'boolean');
		$this->addType('hidden', 'int');
	}

	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'name' => $this->name,
			'quantity' => $this->quantity,
			'category' => $this->category,
			'list' => $this->list,
			'checked' => $this->checked,
			'hidden' => $this->hidden,
			];
	}
}