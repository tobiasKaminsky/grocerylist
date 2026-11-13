<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroceryList\Tests\Integration\Service;

use OCA\GroceryList\Db\Category;
use OCA\GroceryList\Db\CategoryMapper;
use OCA\GroceryList\Db\GroceryList;
use OCA\GroceryList\Db\GroceryListMapper;
use OCA\GroceryList\Db\Item;
use OCA\GroceryList\Db\ItemMapper;
use OCA\GroceryList\Db\ShareeGroceryListMapper;
use OCA\GroceryList\Service\ListService;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group('DB')]
class ListServiceTest extends TestCase {
	private IDBConnection $db;
	private ShareeGroceryListMapper $shareeMapper;
	private GroceryListMapper $listMapper;
	private ItemMapper $itemMapper;
	private CategoryMapper $categoryMapper;
	private ListService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->db = Server::get(IDBConnection::class);
		$this->shareeMapper = new ShareeGroceryListMapper($this->db, 'admin');
		$this->listMapper = new GroceryListMapper($this->db, $this->shareeMapper, 'admin');
		$this->itemMapper = new ItemMapper($this->db);
		$this->categoryMapper = new CategoryMapper($this->db);
		$this->service = new ListService($this->db, $this->listMapper, $this->itemMapper, $this->categoryMapper, $this->shareeMapper);
		$this->cleanUp();
	}

	protected function tearDown(): void {
		$this->cleanUp();
		parent::tearDown();
	}

	private function cleanUp(): void {
		foreach (['grocerylist', 'grocerylist_items', 'grocerylist_categories', 'grocerylist_shares'] as $table) {
			$qb = $this->db->getQueryBuilder();
			$qb->delete($table)->executeStatement();
		}
	}

	private function createListWithContent(string $userId, string $title): int {
		$list = new GroceryList();
		$list->setTitle($title);
		$list->setUserId($userId);
		$list->setShowOnlyUnchecked(false);
		$listId = $this->listMapper->insert($list)->getId();

		$category = new Category();
		$category->setName('Fruit');
		$category->setList($listId);
		$category->setOrder(0);
		$categoryId = $this->categoryMapper->insert($category)->getId();

		$item = new Item();
		$item->setName('Apple');
		$item->setQuantity('');
		$item->setList($listId);
		$item->setCategory($categoryId);
		$item->setChecked(false);
		$item->setHidden(0);
		$this->itemMapper->insert($item);

		return $listId;
	}

	public function testDeleteUserDataRemovesOwnListsAndShares(): void {
		$own = $this->createListWithContent('alice', 'Alice');
		$foreign = $this->createListWithContent('bob', 'Bob');
		$this->shareeMapper->share($own, 'bob');
		$this->shareeMapper->share($foreign, 'alice');
		$this->shareeMapper->share($foreign, 'carol');

		$this->service->deleteUserData('alice');

		$this->assertSame([], $this->listMapper->findOwnedBy('alice'));
		$this->assertSame([], $this->itemMapper->findAll($own));
		$this->assertSame([], $this->categoryMapper->findAll($own));
		$this->assertSame([], $this->shareeMapper->findSharees($own));

		$this->assertCount(1, $this->listMapper->findOwnedBy('bob'));
		$this->assertCount(1, $this->itemMapper->findAll($foreign));
		$this->assertCount(1, $this->categoryMapper->findAll($foreign));
		$this->assertFalse($this->shareeMapper->isSharedWith($foreign, 'alice'));
		$this->assertTrue($this->shareeMapper->isSharedWith($foreign, 'carol'));
	}
}
