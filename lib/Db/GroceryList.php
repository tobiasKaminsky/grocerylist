<?php

namespace OCA\GroceryList\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class GroceryList extends Entity implements JsonSerializable {

	protected $title;
	protected $userId;

	public function __construct() {
		$this->addType('title', 'string');
	}

	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'title' => $this->title,
			'userId' => $this->userId,
			];
	}
}