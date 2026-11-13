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
use OCA\GroceryList\Db\Sharee;
use OCA\GroceryList\Db\ShareeGroceryListMapper;
use OCA\GroceryList\Service\ListService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Share\IManager as IShareManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class GroceryListControllerTest extends TestCase {
	private GroceryListMapper&MockObject $groceryListMapper;
	private ItemMapper&MockObject $itemMapper;
	private CategoryMapper&MockObject $categoryMapper;
	private ShareeGroceryListMapper&MockObject $shareeMapper;
	private IUserManager&MockObject $userManager;
	private ISearch&MockObject $collaboratorSearch;
	private ListService&MockObject $listService;
	private IShareManager&MockObject $shareManager;
	private IGroupManager&MockObject $groupManager;
	private GroceryListController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->groceryListMapper = $this->createMock(GroceryListMapper::class);
		$this->itemMapper = $this->createMock(ItemMapper::class);
		$this->categoryMapper = $this->createMock(CategoryMapper::class);
		$this->shareeMapper = $this->createMock(ShareeGroceryListMapper::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userManager->method('getDisplayName')->willReturnCallback(fn (string $uid) => ucfirst($uid));
		$this->collaboratorSearch = $this->createMock(ISearch::class);
		$this->listService = $this->createMock(ListService::class);
		$this->shareManager = $this->createMock(IShareManager::class);
		$this->shareManager->method('shareApiEnabled')->willReturn(true);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager->method('get')->willReturnCallback(function (string $uid) {
			if ($uid === 'nobody') {
				return null;
			}
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn($uid);
			return $user;
		});

		$this->controller = new GroceryListController(
			'grocerylist',
			$this->createMock(IRequest::class),
			$this->groceryListMapper,
			$this->itemMapper,
			$this->categoryMapper,
			$this->shareeMapper,
			$this->userManager,
			$this->collaboratorSearch,
			$this->listService,
			$this->shareManager,
			$this->groupManager,
			'alice',
		);
	}

	/**
	 * Let the current user access the given lists, all other lists are not found.
	 */
	private function accessibleLists(int ...$ids): void {
		$this->groceryListMapper->method('find')->willReturnCallback(function (int $id) use ($ids) {
			if (!in_array($id, $ids, true)) {
				throw new DoesNotExistException('');
			}
			return $this->list($id, 'alice');
		});
	}

	private function item(int $id, int $list): Item {
		$item = new Item();
		$item->setId($id);
		$item->setList($list);
		return $item;
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
		$this->accessibleLists(7);
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
		$this->accessibleLists(7);
		$category = $this->category(1, 7);
		$this->categoryMapper->method('findAll')->with(7)->willReturn([$category]);
		$this->itemMapper->method('findByCategory')->with(1)->willReturn([new Item(), new Item()]);

		$data = $this->controller->listAllCategories(7)->getData();

		$this->assertSame(2, $data[0]->jsonSerialize()['itemCount']);
	}

	public function testAddItemDefaultsToNoCategory(): void {
		$this->accessibleLists(7);
		$this->itemMapper->expects($this->once())
			->method('insert')
			->willReturnArgument(0);

		$item = $this->controller->addItem('Milk', '2 l', 7)->getData();

		$this->assertSame('Milk', $item->getName());
		$this->assertSame(0, $item->getCategory());
		$this->assertSame(7, $item->getList());
	}

	public function testUpdateItemTrimsInput(): void {
		$this->accessibleLists(7);
		$item = $this->item(3, 7);
		$this->itemMapper->method('find')->with(3)->willReturn($item);
		$this->categoryMapper->method('find')->with(5)->willReturn($this->category(5, 7));
		$this->itemMapper->method('update')->willReturnArgument(0);

		$this->controller->updateItem(3, '  Milk ', ' 2 l ', 5);

		$this->assertSame('Milk', $item->getName());
		$this->assertSame('2 l', $item->getQuantity());
		$this->assertSame(5, $item->getCategory());
	}

	public function testCheckItem(): void {
		$this->accessibleLists(7);
		$item = $this->item(3, 7);
		$this->itemMapper->method('find')->with(3)->willReturn($item);
		$this->itemMapper->expects($this->once())->method('update')->with($item)->willReturnArgument(0);

		$this->controller->checkItem(3, true);

		$this->assertTrue($item->isChecked());
	}

	public function testUnhideItemEndsSnooze(): void {
		$this->accessibleLists(7);
		$item = $this->item(3, 7);
		$item->setHidden(1700000000);
		$this->itemMapper->method('find')->with(3)->willReturn($item);
		$this->itemMapper->expects($this->once())->method('update')->with($item)->willReturnArgument(0);

		$this->controller->unhideItem(3);

		$this->assertSame(0, $item->getHidden());
	}

	public function testDeleteCategoryAlsoDeletesItsItems(): void {
		$this->accessibleLists(7);
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

	public function testAddCategoryAppendsAtEnd(): void {
		$this->accessibleLists(7);
		$existing = $this->category(1, 7);
		$existing->setOrder(4);
		$this->categoryMapper->method('findAll')->with(7)->willReturn([$existing]);
		$this->categoryMapper->expects($this->once())
			->method('insert')
			->with($this->callback(fn (Category $category) => $category->getName() === 'Bakery' && $category->getOrder() === 5))
			->willReturnArgument(0);

		$this->controller->addCategory(7, '  Bakery ');
	}

	public function testAddCategoryReusesSameName(): void {
		$this->accessibleLists(7);
		$this->categoryMapper->method('findAll')->with(7)->willReturn([$this->category(1, 7)]);
		$this->categoryMapper->expects($this->never())->method('insert');

		$this->assertSame(Http::STATUS_OK, $this->controller->addCategory(7, 'category 1')->getStatus());
	}

	public function testAddCategoryRejectsEmptyName(): void {
		$this->categoryMapper->expects($this->never())->method('insert');

		$this->assertSame(Http::STATUS_BAD_REQUEST, $this->controller->addCategory(7, '  ')->getStatus());
	}

	public function testUpdateCategoryRejectsDuplicateName(): void {
		$this->accessibleLists(7);
		$category = $this->category(1, 7);
		$this->categoryMapper->method('find')->with(1)->willReturn($category);
		$this->categoryMapper->method('findAll')->with(7)->willReturn([$category, $this->category(2, 7)]);
		$this->categoryMapper->expects($this->never())->method('update');

		$this->assertSame(Http::STATUS_CONFLICT, $this->controller->updateCategory(1, 'Category 2')->getStatus());
		$this->assertSame(Http::STATUS_BAD_REQUEST, $this->controller->updateCategory(1, '')->getStatus());
	}

	public function testReorderCategoriesDelegatesToMapper(): void {
		$this->accessibleLists(7);
		$this->categoryMapper->expects($this->once())->method('reorder')->with(7, [3, 1, 2]);
		$this->categoryMapper->method('findAll')->with(7)->willReturn([]);

		$this->controller->reorderCategories(7, [3, 1, 2]);
	}

	private function list(int $id, string $owner): GroceryList {
		$list = new GroceryList();
		$list->setId($id);
		$list->setUserId($owner);
		return $list;
	}

	private function sharee(int $list, string $userId): Sharee {
		$sharee = new Sharee();
		$sharee->setListId($list);
		$sharee->setUserId($userId);
		return $sharee;
	}

	public function testShareesReturnsOwnerAndSharees(): void {
		$this->groceryListMapper->method('find')->with(7)->willReturn($this->list(7, 'bob'));
		$this->shareeMapper->method('findSharees')->with(7)->willReturn([$this->sharee(7, 'alice')]);

		$data = $this->controller->sharees(7)->getData();

		$this->assertSame(['userId' => 'bob', 'displayName' => 'Bob'], $data['owner']);
		$this->assertSame([['userId' => 'alice', 'displayName' => 'Alice']], $data['sharees']);
	}

	public function testShareesOfInaccessibleListIsNotFound(): void {
		$this->groceryListMapper->method('find')->willThrowException(new DoesNotExistException(''));

		$this->assertSame(Http::STATUS_NOT_FOUND, $this->controller->sharees(7)->getStatus());
	}

	public function testOwnerCanShareList(): void {
		$this->groceryListMapper->method('find')->willReturn($this->list(7, 'alice'));
		$this->shareeMapper->method('isSharedWith')->willReturn(false);
		$this->shareeMapper->expects($this->once())->method('share')->with(7, 'bob');

		$this->assertSame(Http::STATUS_OK, $this->controller->shareList(7, 'bob')->getStatus());
	}

	public function testShareIsNotDuplicated(): void {
		$this->groceryListMapper->method('find')->willReturn($this->list(7, 'alice'));
		$this->shareeMapper->method('isSharedWith')->willReturn(true);
		$this->shareeMapper->expects($this->never())->method('share');

		$this->controller->shareList(7, 'bob');
	}

	public function testShareeCannotShareList(): void {
		$this->groceryListMapper->method('find')->willReturn($this->list(7, 'bob'));
		$this->shareeMapper->expects($this->never())->method('share');

		$this->assertSame(Http::STATUS_FORBIDDEN, $this->controller->shareList(7, 'carol')->getStatus());
	}

	public function testCannotShareWithSelfOrUnknownUser(): void {
		$this->groceryListMapper->method('find')->willReturn($this->list(7, 'alice'));
		$this->shareeMapper->expects($this->never())->method('share');

		$this->assertSame(Http::STATUS_BAD_REQUEST, $this->controller->shareList(7, 'alice')->getStatus());
		$this->assertSame(Http::STATUS_BAD_REQUEST, $this->controller->shareList(7, 'nobody')->getStatus());
	}

	public function testShareeCanLeaveButNotRemoveOthers(): void {
		$this->groceryListMapper->method('find')->willReturn($this->list(7, 'bob'));
		$this->shareeMapper->expects($this->once())->method('unshare')->with(7, 'alice');

		$this->assertSame(Http::STATUS_FORBIDDEN, $this->controller->unshareList(7, 'carol')->getStatus());
		$this->assertSame(Http::STATUS_OK, $this->controller->unshareList(7, 'alice')->getStatus());
	}

	public function testOnlyOwnerCanDeleteList(): void {
		$this->groceryListMapper->method('find')->willReturn($this->list(7, 'bob'));
		$this->listService->expects($this->never())->method('delete');

		$response = $this->controller->deleteList(7);

		$this->assertInstanceOf(DataResponse::class, $response);
		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
	}

	public function testOwnerCanDeleteList(): void {
		$list = $this->list(7, 'alice');
		$this->groceryListMapper->method('find')->willReturn($list);
		$this->listService->expects($this->once())->method('delete')->with($list);

		$this->controller->deleteList(7);
	}

	public static function dataInaccessible(): array {
		return [
			'list items' => [fn (GroceryListController $c) => $c->listItems(9)],
			'used categories' => [fn (GroceryListController $c) => $c->listCategories(9)],
			'all categories' => [fn (GroceryListController $c) => $c->listAllCategories(9)],
			'show list' => [fn (GroceryListController $c) => $c->showList(9)],
			'rename list' => [fn (GroceryListController $c) => $c->renameList(9, 'x')],
			'update list' => [fn (GroceryListController $c) => $c->updateList(9, 'x', 1)],
			'list visibility' => [fn (GroceryListController $c) => $c->updateListVisibility(9, 1)],
			'delete list' => [fn (GroceryListController $c) => $c->deleteList(9)],
			'add item' => [fn (GroceryListController $c) => $c->addItem('x', '', 9)],
			'update item' => [fn (GroceryListController $c) => $c->updateItem(3, 'x', '', 0)],
			'check item' => [fn (GroceryListController $c) => $c->checkItem(3, true)],
			'snooze item' => [fn (GroceryListController $c) => $c->hideItem(3)],
			'unsnooze item' => [fn (GroceryListController $c) => $c->unhideItem(3)],
			'delete item' => [fn (GroceryListController $c) => $c->deleteItem(3)],
			'add category' => [fn (GroceryListController $c) => $c->addCategory(9, 'x')],
			'update category' => [fn (GroceryListController $c) => $c->updateCategory(4, 'x')],
			'category color' => [fn (GroceryListController $c) => $c->updateCategoryColor(4, '#ffffff')],
			'delete category' => [fn (GroceryListController $c) => $c->deleteCategory(4)],
			'reorder categories' => [fn (GroceryListController $c) => $c->reorderCategories(9, [4])],
		];
	}

	/**
	 * Lists, items and categories of lists the user neither owns nor has been
	 * shared are not found, and nothing is changed.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataInaccessible')]
	public function testInaccessibleListIsNotFound(\Closure $call): void {
		$this->accessibleLists(7);
		$this->itemMapper->method('find')->with(3)->willReturn($this->item(3, 9));
		$this->categoryMapper->method('find')->with(4)->willReturn($this->category(4, 9));
		foreach (['insert', 'update', 'delete'] as $method) {
			$this->itemMapper->expects($this->never())->method($method);
			$this->categoryMapper->expects($this->never())->method($method);
			$this->groceryListMapper->expects($this->never())->method($method);
		}
		$this->categoryMapper->expects($this->never())->method('reorder');
		$this->listService->expects($this->never())->method('delete');

		$this->assertSame(Http::STATUS_NOT_FOUND, $call($this->controller)->getStatus());
	}

	public function testItemCanNotUseCategoryOfOtherList(): void {
		$this->accessibleLists(7, 8);
		$this->itemMapper->method('find')->with(3)->willReturn($this->item(3, 7));
		$this->categoryMapper->method('find')->with(4)->willReturn($this->category(4, 8));
		$this->itemMapper->expects($this->never())->method('insert');
		$this->itemMapper->expects($this->never())->method('update');

		$this->assertSame(Http::STATUS_BAD_REQUEST, $this->controller->addItem('x', '', 7, 4)->getStatus());
		$this->assertSame(Http::STATUS_BAD_REQUEST, $this->controller->updateItem(3, 'x', '', 4)->getStatus());
	}

	public static function dataInvalidInput(): array {
		$long = str_repeat('a', 201);
		return [
			'empty list title' => [fn (GroceryListController $c) => $c->saveList(' ')],
			'long list title' => [fn (GroceryListController $c) => $c->saveList($long)],
			'long rename' => [fn (GroceryListController $c) => $c->renameList(7, $long)],
			'empty item name' => [fn (GroceryListController $c) => $c->addItem('', '1', 7)],
			'long item name' => [fn (GroceryListController $c) => $c->addItem($long, '1', 7)],
			'long quantity' => [fn (GroceryListController $c) => $c->addItem('x', $long, 7)],
			'long item update' => [fn (GroceryListController $c) => $c->updateItem(3, $long, '', 0)],
			'long category name' => [fn (GroceryListController $c) => $c->addCategory(7, $long)],
			'invalid color on add' => [fn (GroceryListController $c) => $c->addCategory(7, 'x', 'red;x')],
			'invalid color' => [fn (GroceryListController $c) => $c->updateCategoryColor(4, 'url(x)')],
			'short hex color' => [fn (GroceryListController $c) => $c->updateCategoryColor(4, '#fff')],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataInvalidInput')]
	public function testInvalidInputIsRejected(\Closure $call): void {
		$this->accessibleLists(7);
		$this->itemMapper->method('find')->willReturn($this->item(3, 7));
		$this->categoryMapper->method('find')->willReturn($this->category(4, 7));
		$this->itemMapper->expects($this->never())->method('insert');
		$this->itemMapper->expects($this->never())->method('update');
		$this->categoryMapper->expects($this->never())->method('insert');
		$this->categoryMapper->expects($this->never())->method('update');
		$this->groceryListMapper->expects($this->never())->method('insert');
		$this->groceryListMapper->expects($this->never())->method('update');

		$this->assertSame(Http::STATUS_BAD_REQUEST, $call($this->controller)->getStatus());
	}

	public function testCategoryColorAcceptsHex(): void {
		$this->accessibleLists(7);
		$category = $this->category(4, 7);
		$this->categoryMapper->method('find')->willReturn($category);
		$this->categoryMapper->method('findAll')->willReturn([$category]);

		$this->assertSame(Http::STATUS_OK, $this->controller->updateCategoryColor(4, '#A5b872')->getStatus());
		$this->assertSame('#A5b872', $category->getColor());
	}

	public function testCannotShareWhenSharingIsDisabledForUser(): void {
		$this->groceryListMapper->method('find')->willReturn($this->list(7, 'alice'));
		$this->shareManager->method('sharingDisabledForUser')->with('alice')->willReturn(true);
		$this->shareeMapper->expects($this->never())->method('share');
		$this->collaboratorSearch->expects($this->never())->method('search');

		$this->assertSame(Http::STATUS_FORBIDDEN, $this->controller->shareList(7, 'bob')->getStatus());
		$this->assertSame(Http::STATUS_FORBIDDEN, $this->controller->searchSharees(7, 'b')->getStatus());
	}

	public function testShareWithGroupMembersOnly(): void {
		$this->groceryListMapper->method('find')->willReturn($this->list(7, 'alice'));
		$this->shareManager->method('shareWithGroupMembersOnly')->willReturn(true);
		$this->shareManager->method('shareWithGroupMembersOnlyExcludeGroupsList')->willReturn(['everyone']);
		$this->groupManager->method('getUserGroupIds')->willReturnCallback(fn (IUser $user) => match ($user->getUID()) {
			'alice' => ['family', 'everyone'],
			'bob' => ['family'],
			default => ['everyone'],
		});
		$this->shareeMapper->method('isSharedWith')->willReturn(false);
		$this->shareeMapper->expects($this->once())->method('share')->with(7, 'bob');

		$this->assertSame(Http::STATUS_OK, $this->controller->shareList(7, 'bob')->getStatus());
		// only an excluded group in common
		$this->assertSame(Http::STATUS_BAD_REQUEST, $this->controller->shareList(7, 'mallory')->getStatus());
	}

	public function testSearchShareesSkipsOwnerAndExistingSharees(): void {
		$this->groceryListMapper->method('find')->willReturn($this->list(7, 'alice'));
		$this->shareeMapper->method('findSharees')->willReturn([$this->sharee(7, 'bob')]);
		$this->collaboratorSearch->method('search')->willReturn([[
			'exact' => ['users' => [['label' => 'Carol', 'value' => ['shareWith' => 'carol']]]],
			'users' => [
				['label' => 'Alice', 'value' => ['shareWith' => 'alice']],
				['label' => 'Bob', 'value' => ['shareWith' => 'bob']],
				['label' => 'Carol', 'value' => ['shareWith' => 'carol']],
				['label' => 'Dave', 'shareWithDisplayNameUnique' => 'dave@example.com', 'value' => ['shareWith' => 'dave']],
			],
		], false]);

		$data = $this->controller->searchSharees(7, 'a')->getData();

		$this->assertSame([
			['userId' => 'carol', 'displayName' => 'Carol', 'subname' => ''],
			['userId' => 'dave', 'displayName' => 'Dave', 'subname' => 'dave@example.com'],
		], $data);
	}
}
