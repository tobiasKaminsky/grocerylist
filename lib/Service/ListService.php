<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroceryList\Service;

use OCA\GroceryList\Db\CategoryMapper;
use OCA\GroceryList\Db\GroceryList;
use OCA\GroceryList\Db\GroceryListMapper;
use OCA\GroceryList\Db\ItemMapper;
use OCA\GroceryList\Db\ShareeGroceryListMapper;
use OCP\IDBConnection;

class ListService {

	public function __construct(
		private IDBConnection $db,
		private GroceryListMapper $groceryListMapper,
		private ItemMapper $itemMapper,
		private CategoryMapper $categoryMapper,
		private ShareeGroceryListMapper $shareeMapper,
	) {
	}

	/**
	 * Delete a list together with its items, categories and shares.
	 */
	public function delete(GroceryList $list): void {
		$this->db->beginTransaction();
		try {
			$this->shareeMapper->unshareAll($list->getId());
			$this->itemMapper->deleteByList($list->getId());
			$this->categoryMapper->deleteByList($list->getId());
			$this->groceryListMapper->delete($list);
			$this->db->commit();
		} catch (\Throwable $e) {
			$this->db->rollBack();
			throw $e;
		}
	}

	/**
	 * Remove everything belonging to a user: the lists they own (with all
	 * their content) and their access to lists shared with them.
	 */
	public function deleteUserData(string $userId): void {
		foreach ($this->groceryListMapper->findOwnedBy($userId) as $list) {
			$this->delete($list);
		}

		$this->shareeMapper->unshareAllForUser($userId);
	}
}
