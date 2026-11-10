<?php

/**
 * SPDX-FileCopyrightText: 2020 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroceryList\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method string getTitle()
 * @method void setTitle(string $title)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 */
class GroceryList extends Entity implements JsonSerializable {

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $userId;

	/**
	 * @var int
	 */
	protected $showOnlyUnchecked;

	/**
	 * @var int|null
	 */
	protected $uncheckedCount;

	public function __construct() {
		$this->addType('title', Types::STRING);
		$this->addType('userId', Types::STRING);
		$this->addType('showOnlyUnchecked', Types::INTEGER);
		$this->addType('uncheckedCount', Types::INTEGER);
	}

	public function getShowOnlyUnchecked(): bool {
		return $this->getter('showOnlyUnchecked') !== 0;
	}

	public function setShowOnlyUnchecked(bool $showOnlyUnchecked): void {
		$this->setter('showOnlyUnchecked', [$showOnlyUnchecked ? 1 : 0]);
	}

	public function setUncheckedCount(int $count): void {
		$this->setter('uncheckedCount', [$count]);
	}

	#[\Override]
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'title' => $this->getTitle(),
			'userId' => $this->getUserId(),
			'showOnlyUnchecked' => $this->getShowOnlyUnchecked(),
			'uncheckedCount' => $this->uncheckedCount,
		];
	}
}
