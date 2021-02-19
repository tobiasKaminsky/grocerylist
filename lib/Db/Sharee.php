<?php

namespace OCA\GroceryList\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class Sharee extends Entity implements JsonSerializable {

	public $list;
	protected $userId;

	public function __construct() {
		$this->addType('list', 'int');
		$this->addType('userId', 'string');
	}

	public function jsonSerialize(): array {
		return [
			'list' => $this->list,
			'userId' => $this->userId,
			];
	}
}