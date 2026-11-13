<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroceryList\Tests\Unit\Db;

use OCA\GroceryList\Db\Category;
use OCA\GroceryList\Db\GroceryList;
use OCA\GroceryList\Db\Item;
use OCA\GroceryList\Db\Sharee;
use Test\TestCase;

class EntityTest extends TestCase {

	public function testItemCheckedIsStoredAsInteger(): void {
		$item = new Item();

		$item->setChecked(true);
		$this->assertTrue($item->isChecked());
		$this->assertArrayHasKey('checked', $item->getUpdatedFields());

		$item->setChecked(false);
		$this->assertFalse($item->isChecked());
	}

	public function testItemJsonSerialize(): void {
		$item = new Item();
		$item->setId(3);
		$item->setName('Milk');
		$item->setQuantity('2 l');
		$item->setCategory(5);
		$item->setList(7);
		$item->setChecked(true);
		$item->setHidden(0);

		$this->assertSame([
			'id' => 3,
			'name' => 'Milk',
			'quantity' => '2 l',
			'category' => 5,
			'listId' => 7,
			'checked' => true,
			'hidden' => 0,
		], $item->jsonSerialize());
	}

	public function testGroceryListShowOnlyUncheckedMapsToBool(): void {
		$list = new GroceryList();

		$list->setShowOnlyUnchecked(true);
		$this->assertTrue($list->getShowOnlyUnchecked());

		$list->setShowOnlyUnchecked(false);
		$this->assertFalse($list->getShowOnlyUnchecked());
	}

	public function testGroceryListJsonSerialize(): void {
		$list = new GroceryList();
		$list->setId(1);
		$list->setTitle('Weekly');
		$list->setUserId('alice');
		$list->setShowOnlyUnchecked(true);
		$list->setUncheckedCount(4);

		$this->assertSame([
			'id' => 1,
			'title' => 'Weekly',
			'userId' => 'alice',
			'showOnlyUnchecked' => true,
			'uncheckedCount' => 4,
		], $list->jsonSerialize());
	}

	public function testCategoryItemCountDefaultsToZero(): void {
		$category = new Category();
		$category->setId(2);
		$category->setName('Dairy');
		$category->setOrder(1);
		$category->setList(7);

		$this->assertSame(0, $category->jsonSerialize()['itemCount']);

		$category->setItemCount(3);
		$this->assertSame(3, $category->jsonSerialize()['itemCount']);
	}

	public function testCategoryJsonSerialize(): void {
		$category = new Category();
		$category->setId(2);
		$category->setName('Dairy');
		$category->setOrder(1);
		$category->setList(7);
		$category->setColor('#ff0000');

		$this->assertSame([
			'id' => 2,
			'order' => 1,
			'name' => 'Dairy',
			'color' => '#ff0000',
			'grocery_list' => 7,
			'itemCount' => 0,
		], $category->jsonSerialize());
	}

	public function testShareeJsonSerialize(): void {
		$sharee = new Sharee();
		$sharee->setListId(7);
		$sharee->setUserId('bob');

		$this->assertSame([
			'id' => null,
			'listId' => 7,
			'userId' => 'bob',
		], $sharee->jsonSerialize());
	}
}
