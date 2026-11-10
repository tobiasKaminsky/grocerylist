<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroceryList\Tests\Integration\Db;

use OCA\GroceryList\Db\Category;
use OCA\GroceryList\Db\CategoryMapper;
use OCA\GroceryList\Db\GroceryList;
use OCA\GroceryList\Db\GroceryListMapper;
use OCA\GroceryList\Db\Item;
use OCA\GroceryList\Db\ItemMapper;
use OCA\GroceryList\Db\Sharee;
use OCA\GroceryList\Db\ShareeGroceryListMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group('DB')]
class MapperTest extends TestCase {
	private IDBConnection $db;
	private ItemMapper $itemMapper;
	private CategoryMapper $categoryMapper;

	protected function setUp(): void {
		parent::setUp();

		$this->db = Server::get(IDBConnection::class);
		$this->itemMapper = new ItemMapper($this->db);
		$this->categoryMapper = new CategoryMapper($this->db);
		$this->cleanUp();
	}

	protected function tearDown(): void {
		$this->cleanUp();
		parent::tearDown();
	}

	private function cleanUp(): void {
		foreach (['grocerylist', 'grocerylist_items', 'grocerylist_categories', 'grocerylist_sharee'] as $table) {
			$qb = $this->db->getQueryBuilder();
			$qb->delete($table)->executeStatement();
		}
	}

	private function listMapper(string $userId): GroceryListMapper {
		return new GroceryListMapper($this->db, $this->shareeMapper($userId), $userId);
	}

	private function shareeMapper(string $userId): ShareeGroceryListMapper {
		return new ShareeGroceryListMapper($this->db, $userId);
	}

	private function createList(string $userId, string $title): GroceryList {
		$list = new GroceryList();
		$list->setTitle($title);
		$list->setUserId($userId);
		$list->setShowOnlyUnchecked(false);
		return $this->listMapper($userId)->insert($list);
	}

	private function createItem(int $listId, string $name, int $category = 0, bool $checked = false): Item {
		$item = new Item();
		$item->setName($name);
		$item->setQuantity('');
		$item->setList($listId);
		$item->setCategory($category);
		$item->setChecked($checked);
		$item->setHidden(0);
		return $this->itemMapper->insert($item);
	}

	private function createCategory(int $listId, string $name, int $order): Category {
		$category = new Category();
		$category->setName($name);
		$category->setList($listId);
		$category->setOrder($order);
		return $this->categoryMapper->insert($category);
	}

	/**
	 * The sharee table has no id column, so insert directly instead of via QBMapper::insert()
	 */
	private function share(int $listId, string $userId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->insert('grocerylist_sharee')
			->values([
				'list' => $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT),
				'user_id' => $qb->createNamedParameter($userId),
			])
			->executeStatement();
	}

	public function testFindAllReturnsOwnAndSharedLists(): void {
		$own = $this->createList('alice', 'Alice');
		$shared = $this->createList('bob', 'Bob shared');
		$this->createList('bob', 'Bob private');
		$this->share($shared->getId(), 'alice');

		$titles = array_map(fn (GroceryList $list) => $list->getTitle(), $this->listMapper('alice')->findAll());
		sort($titles);

		$this->assertSame(['Alice', 'Bob shared'], $titles);
		$this->assertSame($own->getId(), $this->listMapper('alice')->find($own->getId())->getId());
		$this->assertSame($shared->getId(), $this->listMapper('alice')->find($shared->getId())->getId());
	}

	public function testFindRoundTripsShowOnlyUnchecked(): void {
		$list = $this->createList('alice', 'Alice');
		$list->setShowOnlyUnchecked(true);
		$this->listMapper('alice')->update($list);

		$this->assertTrue($this->listMapper('alice')->find($list->getId())->getShowOnlyUnchecked());
	}

	public function testItemQueries(): void {
		$list = $this->createList('alice', 'Alice')->getId();
		$other = $this->createList('alice', 'Other')->getId();
		$milk = $this->createItem($list, 'Milk', 1);
		$this->createItem($list, 'Bread', 0, true);
		$this->createItem($list, 'Cheese', 1);
		$this->createItem($other, 'Apples', 2);

		$this->assertCount(3, $this->itemMapper->findAll($list));
		$this->assertSame(2, $this->itemMapper->findAllUnchecked($list));
		$this->assertTrue($this->itemMapper->categoryUsed(1));
		$this->assertFalse($this->itemMapper->categoryUsed(99));
		$this->assertCount(2, $this->itemMapper->findByCategory(1));

		$found = $this->itemMapper->find($milk->getId());
		$this->assertSame('Milk', $found->getName());
		$this->assertFalse($found->isChecked());
	}

	public function testDeletedItemIsGone(): void {
		$list = $this->createList('alice', 'Alice')->getId();
		$item = $this->createItem($list, 'Milk');

		$this->itemMapper->delete($item);

		$this->expectException(DoesNotExistException::class);
		$this->itemMapper->find($item->getId());
	}

	public function testCategoriesAreOrdered(): void {
		$list = $this->createList('alice', 'Alice')->getId();
		$this->createCategory($list, 'Second', 1);
		$this->createCategory($list, 'First', 0);

		$names = array_map(fn (Category $category) => $category->getName(), $this->categoryMapper->findAll($list));

		$this->assertSame(['First', 'Second'], $names);
	}

	public function testReorderOnlyTouchesCategoriesOfTheGivenList(): void {
		$list = $this->createList('alice', 'Alice')->getId();
		$other = $this->createList('bob', 'Bob')->getId();
		$a = $this->createCategory($list, 'A', 0);
		$b = $this->createCategory($list, 'B', 1);
		$c = $this->createCategory($list, 'C', 2);
		$foreign = $this->createCategory($other, 'Foreign', 5);

		$this->categoryMapper->reorder($list, [$c->getId(), $foreign->getId(), $a->getId(), $b->getId()]);

		$names = array_map(fn (Category $category) => $category->getName(), $this->categoryMapper->findAll($list));
		$this->assertSame(['C', 'A', 'B'], $names);
		$this->assertSame(5, $this->categoryMapper->find($foreign->getId())->getOrder());
	}

	public function testCategoryColorIsPersisted(): void {
		$list = $this->createList('alice', 'Alice')->getId();
		$category = new Category();
		$category->setName('Dairy');
		$category->setList($list);
		$category->setOrder(0);
		$category->setColor('#00ff00');
		$category = $this->categoryMapper->insert($category);

		$this->assertSame('#00ff00', $this->categoryMapper->find($category->getId())->getColor());
	}

	public function testFindSharees(): void {
		$list = $this->createList('bob', 'Bob')->getId();
		$this->share($list, 'alice');
		$this->share($list, 'carol');

		$users = array_map(fn (Sharee $sharee) => $sharee->getUserId(), $this->shareeMapper('bob')->findSharees($list));
		sort($users);

		$this->assertSame(['alice', 'carol'], $users);
	}
}
