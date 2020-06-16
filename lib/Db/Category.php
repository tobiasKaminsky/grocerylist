<?php

namespace OCA\GroceryList\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class Category extends Entity implements JsonSerializable {

	protected $name;
	protected $order;
	protected $list;

	public function __construct() {
		$this->addType('name', 'string');
		$this->addType('order', 'int');
		$this->addType('list', 'int');
	}

	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'order' => $this->order,
			'name' => $this->name,
			'grocery_list' => $this->list,
			];
	}
}