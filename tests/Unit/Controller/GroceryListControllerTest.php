<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroceryList\Tests\Unit\Controller;

use OCA\GroceryList\Controller\GroceryListController;
use OCA\GroceryList\Db\Category;
use OCA\GroceryList\Db\CategoryMapper;
use OCA\GroceryList\Db\GroceryList;
use OCA\GroceryList\Db\GroceryListMapper;
use OCA\GroceryList\Db\Item;
use OCA\GroceryList\Db\ItemMapper;
use OCA\GroceryList\Db\ShareeGroceryListMapper;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class GroceryListControllerTest extends TestCase {
	private GroceryListMapper&MockObject $groceryListMapper;
	private ItemMapper&MockObject $itemMapper;
	private CategoryMapper&MockObject $categoryMapper;
	private ShareeGroceryListMapper&MockObject $shareeMapper;
	private GroceryListController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->groceryListMapper = $this->createMock(GroceryListMapper::class);
		$this->itemMapper = $this->createMock(ItemMapper::class);
		$this->categoryMapper = $this->createMock(CategoryMapper::class);
		$this->shareeMapper = $this->createMock(ShareeGroceryListMapper::class);

		$this->controller = new GroceryListController(
			'grocerylist',
			$this->createMock(IRequest::class),
			$this->groceryListMapper,
			$this->itemMapper,
			$this->categoryMapper,
			$this->shareeMapper,
			'alice',
		);
	}

	private function category(int $id, int $list): Category {
		$category = new Category();
		$category->setId($id);
		$category->setName('Category ' . $id);
		$category->setOrder(0);
		$category->setList($list);
		return $category;
	}

	public function testSaveListSetsOwner(): void {
		$this->groceryListMapper->expects($this->once())
			->method('insert')
			->with($this->callback(fn (GroceryList $list) => $list->getTitle() === 'Weekly' && $list->getUserId() === 'alice'))
			->willReturnArgument(0);

		$response = $this->controller->saveList('Weekly');

		$this->assertInstanceOf(DataResponse::class, $response);
		$this->assertSame('alice', $response->getData()->getUserId());
	}

	public function testListsAddsUncheckedCount(): void {
		$list = new GroceryList();
		$list->setId(7);
		$this->groceryListMapper->method('findAll')->willReturn([$list]);
		$this->itemMapper->method('findAllUnchecked')->with(7)->willReturn(3);

		$data = $this->controller->lists()->getData();

		$this->assertSame(3, $data[0]->jsonSerialize()['uncheckedCount']);
	}

	public static function dataShowOnlyUnchecked(): array {
		return [
			[0, false],
			[1, true],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataShowOnlyUnchecked')]
	public function testUpdateListStoresVisibilityAsBool(int $input, bool $expected): void {
		$list = new GroceryList();
		$list->setId(7);
		$this->groceryListMapper->method('find')->with(7)->willReturn($list);
		$this->groceryListMapper->expects($this->once())->method('update')->with($list);

		$this->controller->updateList(7, 'Renamed', $input);

		$this->assertSame('Renamed', $list->getTitle());
		$this->assertSame($expected, $list->getShowOnlyUnchecked());
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataShowOnlyUnchecked')]
	public function testUpdateListVisibilityStoresBool(int $input, bool $expected): void {
		$list = new GroceryList();
		$list->setId(7);
		$this->groceryListMapper->method('find')->with(7)->willReturn($list);
		$this->groceryListMapper->expects($this->once())->method('update')->with($list);

		$this->controller->updateListVisibility(7, $input);

		$this->assertSame($expected, $list->getShowOnlyUnchecked());
	}

	public function testListCategoriesOnlyReturnsUsedCategories(): void {
		$used = $this->category(1, 7);
		$unused = $this->category(2, 7);
		$this->categoryMapper->method('findAll')->with(7)->willReturn([$used, $unused]);
		$this->itemMapper->method('categoryUsed')->willReturnMap([
			[1, true],
			[2, false],
		]);

		$this->assertSame([$used], $this->controller->listCategories(7)->getData());
	}

	public function testListAllCategoriesAddsItemCount(): void {
		$category = $this->category(1, 7);
		$this->categoryMapper->method('findAll')->with(7)->willReturn([$category]);
		$this->itemMapper->method('findByCategory')->with(1)->willReturn([new Item(), new Item()]);

		$data = $this->controller->listAllCategories(7)->getData();

		$this->assertSame(2, $data[0]->jsonSerialize()['itemCount']);
	}

	public function testAddItemDefaultsToNoCategory(): void {
		$this->itemMapper->expects($this->once())
			->method('insert')
			->willReturnArgument(0);

		$item = $this->controller->addItem('Milk', '2 l', 7)->getData();

		$this->assertSame('Milk', $item->getName());
		$this->assertSame(0, $item->getCategory());
		$this->assertSame(7, $item->getList());
	}

	public function testUpdateItemTrimsInput(): void {
		$item = new Item();
		$item->setId(3);
		$this->itemMapper->method('find')->with(3)->willReturn($item);
		$this->itemMapper->method('update')->willReturnArgument(0);

		$this->controller->updateItem(3, '  Milk ', ' 2 l ', 5);

		$this->assertSame('Milk', $item->getName());
		$this->assertSame('2 l', $item->getQuantity());
		$this->assertSame(5, $item->getCategory());
	}

	public function testCheckItem(): void {
		$item = new Item();
		$item->setId(3);
		$this->itemMapper->method('find')->with(3)->willReturn($item);
		$this->itemMapper->expects($this->once())->method('update')->with($item)->willReturnArgument(0);

		$this->controller->checkItem(3, true);

		$this->assertTrue($item->isChecked());
	}

	public function testDeleteCategoryAlsoDeletesItsItems(): void {
		$category = $this->category(1, 7);
		$itemA = new Item();
		$itemB = new Item();
		$this->categoryMapper->method('find')->with(1)->willReturn($category);
		$this->itemMapper->method('findByCategory')->willReturnMap([
			[1, [$itemA, $itemB]],
		]);

		$deleted = [];
		$this->itemMapper->expects($this->exactly(2))
			->method('delete')
			->willReturnCallback(function (Item $item) use (&$deleted) {
				$deleted[] = $item;
				return $item;
			});
		$this->categoryMapper->expects($this->once())->method('delete')->with($category);
		$this->categoryMapper->method('findAll')->with(7)->willReturn([]);

		$this->assertSame([], $this->controller->deleteCategory(1)->getData());
		$this->assertSame([$itemA, $itemB], $deleted);
	}

	public function testReorderCategoriesDelegatesToMapper(): void {
		$this->categoryMapper->expects($this->once())->method('reorder')->with(7, [3, 1, 2]);
		$this->categoryMapper->method('findAll')->with(7)->willReturn([]);

		$this->controller->reorderCategories(7, [3, 1, 2]);
	}
}
