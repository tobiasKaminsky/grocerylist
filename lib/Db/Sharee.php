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
 * @method int getListId()
 * @method void setListId(int $listId)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 */
class Sharee extends Entity implements JsonSerializable {

	/**
	 * @var int
	 */
	protected $listId;

	/**
	 * @var string
	 */
	protected $userId;

	public function __construct() {
		$this->addType('listId', Types::INTEGER);
		$this->addType('userId', Types::STRING);
	}

	#[\Override]
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'listId' => $this->getListId(),
			'userId' => $this->getUserId(),
		];
	}
}
