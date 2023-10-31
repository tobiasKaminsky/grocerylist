<?php

namespace OCA\GroceryList\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method int getList()
 * @method void setList(int $list)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 */
class Sharee extends Entity implements JsonSerializable {

	/**
	 * @var int
	 */
	protected $list;

	/**
	 * @var string
	 */
	protected $userId;

	public function __construct() {
		$this->addType('list', 'int');
		$this->addType('userId', 'string');
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'list' => $this->getList(),
			'userId' => $this->getUserId(),
		];
	}
}
