<?php

/**
 * SPDX-FileCopyrightText: 2021 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroceryList\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

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
	public $list;

	/**
	 * @var string
	 */
	protected $userId;

	public function __construct() {
		$this->addType('list', Types::INTEGER);
		$this->addType('userId', Types::STRING);
	}

	#[\Override]
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'list' => $this->getList(),
			'userId' => $this->getUserId(),
		];
	}
}
