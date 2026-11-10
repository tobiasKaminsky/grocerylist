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
 * @method string getName()
 * @method void setName(string $name)
 * @method int getOrder()
 * @method void setOrder(int $order)
 * @method int getList()
 * @method void setList(int $list)
 * @method string|null getColor()
 * @method void setColor(?string $color)
 * @method int getItemCount()
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

	/**
	 * Virtual field populated by the controller – not stored in the DB.
	 * @var int|null
	 */
	protected $itemCount;

	public function __construct() {
		$this->addType('name', Types::STRING);
		$this->addType('order', Types::INTEGER);
		$this->addType('list', Types::INTEGER);
		$this->addType('color', Types::STRING);
		$this->addType('itemCount', Types::INTEGER);
	}

	public function setItemCount(int $count): void {
		$this->setter('itemCount', [$count]);
	}

	#[\Override]
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'order' => $this->getOrder(),
			'name' => $this->getName(),
			'color' => $this->getColor(),
			'grocery_list' => $this->getList(),
			'itemCount' => $this->itemCount ?? 0,
		];
	}
}
