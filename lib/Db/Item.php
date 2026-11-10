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
 * @method string getQuantity()
 * @method void setQuantity(string $quantity)
 * @method int getCategory()
 * @method void setCategory(int $category)
 * @method int getList()
 * @method void setList(int $list)
 * @method int getHidden()
 * @method void setHidden(int $hidden)
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
		$this->addType('name', Types::STRING);
		$this->addType('quantity', Types::STRING);
		$this->addType('category', Types::INTEGER);
		$this->addType('list', Types::INTEGER);
		$this->addType('checked', Types::INTEGER);
		$this->addType('hidden', Types::INTEGER);
	}

	public function isChecked(): bool {
		return $this->checked !== 0;
	}

	public function setChecked(bool $checked): void {
		$this->setter('checked', [$checked ? 1 : 0]);
	}

	#[\Override]
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'quantity' => $this->getQuantity(),
			'category' => $this->getCategory(),
			'list' => $this->getList(),
			'checked' => $this->isChecked(),
			'hidden' => $this->getHidden(),
		];
	}
}
