<?php

/**
 * SPDX-FileCopyrightText: 2020 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroceryList\Controller;

use OCA\GroceryList\Db\Category;
use OCA\GroceryList\Db\CategoryMapper;
use OCA\GroceryList\Db\GroceryList;
use OCA\GroceryList\Db\GroceryListMapper;
use OCA\GroceryList\Db\Item;
use OCA\GroceryList\Db\ItemMapper;
use OCA\GroceryList\Db\ShareeGroceryListMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Share\IShare;

class GroceryListController extends Controller {

	private GroceryListMapper $groceryListMapper;
	private ItemMapper $itemMapper;
	private CategoryMapper $categoryMapper;
	private ShareeGroceryListMapper $shareeMapper;
	private IUserManager $userManager;
	private ISearch $collaboratorSearch;
	private $userId;

	public function __construct($AppName,
		IRequest $request,
		GroceryListMapper $groceryListMapper,
		ItemMapper $itemMapper,
		CategoryMapper $categoryMapper,
		ShareeGroceryListMapper $shareeMapper,
		IUserManager $userManager,
		ISearch $collaboratorSearch,
		$UserId) {
		parent::__construct($AppName, $request);
		$this->groceryListMapper = $groceryListMapper;
		$this->itemMapper = $itemMapper;
		$this->categoryMapper = $categoryMapper;
		$this->shareeMapper = $shareeMapper;
		$this->userManager = $userManager;
		$this->collaboratorSearch = $collaboratorSearch;
		$this->userId = $UserId;
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function index() {
		// Register all scripts and styles we use on the frontend
		\OCP\Util::addScript($this->appName, $this->appName . '-main'); // adding `js/grocerylist-main.mjs` generated from JS source
		\OCP\Util::addStyle($this->appName, $this->appName . '-main'); // adding `css/grocerylist-style.css` generated from JS source
		// return the template we use (see `templates/` folder)
		return new TemplateResponse('grocerylist', 'main');
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function showGroceryList($id) {
		return $this->index();
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function showGroceryListSettings($id) {
		return $this->index();
	}

	#[NoAdminRequired]
	public function lists() {
		$lists = $this->groceryListMapper->findAll();

		foreach ($lists as $list) {
			$list->setUncheckedCount($this->itemMapper->findAllUnchecked($list->getId()));
		}

		return new DataResponse($lists);
	}

	/**
	 * @param string $title
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function saveList(string $title) {
		$groceryList = new GroceryList();
		$groceryList->setTitle($title);
		$groceryList->setUserId($this->userId);

		return new DataResponse($this->groceryListMapper->insert($groceryList));
	}

	/**
	 * Delete a list. Only the owner may delete it, sharees can leave it via unshareList().
	 *
	 * @param int $id
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function deleteList(int $id) {
		$groceryList = $this->groceryListMapper->find($id);
		if ($groceryList->getUserId() !== $this->userId) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->shareeMapper->unshareAll($id);
		$this->groceryListMapper->delete($groceryList);

		return new DataResponse($groceryList);
	}

	/**
	 * @param int $id
	 * @param string $title
	 * @param int $showOnlyUnchecked
	 */
	#[NoAdminRequired]
	public function renameList(int $id, string $title) {
		$list = $this->groceryListMapper->find($id);
		$list->setTitle($title);

		$this->groceryListMapper->update($list);
	}

	/**
	 * @param int $id
	 * @param string $title
	 * @param int $showOnlyUnchecked
	 */
	#[NoAdminRequired]
	public function updateList(int $id, string $title, int $showOnlyUnchecked = 0) {
		$list = $this->groceryListMapper->find($id);
		$list->setShowOnlyUnchecked($showOnlyUnchecked !== 0);
		$list->setTitle($title);

		$this->groceryListMapper->update($list);
	}

	/**
	 * @param int $id
	 * @param string $title
	 * @param int $showOnlyUnchecked
	 */
	#[NoAdminRequired]
	public function updateListVisibility(int $id, int $showOnlyUnchecked = 0) {
		$list = $this->groceryListMapper->find($id);
		$list->setShowOnlyUnchecked($showOnlyUnchecked !== 0);

		$this->groceryListMapper->update($list);
	}

	/**
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function showList(int $id) {
		return new DataResponse($this->groceryListMapper->find($id));
	}

	/**
	 * @param int $id
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function listItems(int $id) {
		return new DataResponse($this->itemMapper->findAll($id));
	}

	/**
	 * @param int $id
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function countUnckedItems(int $id) {
		return new DataResponse($this->itemMapper->findAllUnchecked($id));
	}

	/**
	 * @param int $id
	 */
	#[NoAdminRequired]
	public function deleteItem(int $id) {
		$item = $this->itemMapper->find($id);
		$this->itemMapper->delete($item);
	}

	/**
	 * @param int $id
	 */
	#[NoAdminRequired]
	public function listCategories(int $id) {
		$returnList = [];
		$categories = $this->categoryMapper->findAll($id);

		foreach ($categories as $category) {
			if ($this->itemMapper->categoryUsed($category->id)) {
				$returnList[] = $category;
			}
		}

		return new DataResponse($returnList);
	}

	/**
	 * @param int $id
	 */
	#[NoAdminRequired]
	public function listAllCategories(int $id) {
		return new DataResponse($this->enrichWithItemCount($this->categoryMapper->findAll($id)));
	}

	/**
	 * Populate the virtual itemCount property on each category so the
	 * frontend can decide whether to ask for confirmation before deleting.
	 *
	 * @param Category[] $categories
	 * @return Category[]
	 */
	private function enrichWithItemCount(array $categories): array {
		foreach ($categories as $category) {
			$category->setItemCount(count($this->itemMapper->findByCategory($category->getId())));
		}

		return $categories;
	}

	/**
	 * @param string $name
	 * @param string $quantity
	 * @param int $category
	 * @param int $list
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function addItem(string $name, string $quantity, int $list, int $category = 0) {
		$item = new Item();
		$item->setName($name);
		$item->setQuantity($quantity);
		$item->setCategory($category);
		$item->setList($list);

		return new DataResponse($this->itemMapper->insert($item));
	}

	/**
	 * @param int $id
	 * @param string $name
	 * @param string $quantity
	 * @param int $category
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function updateItem(int $id, string $name, string $quantity, int $category) {
		$item = $this->itemMapper->find($id);
		$item->setName(trim($name));
		$item->setQuantity(trim($quantity));
		$item->setCategory($category);

		return new DataResponse($this->itemMapper->update($item));
	}

	#[NoAdminRequired]
	public function hideItem(int $id) {
		$item = $this->itemMapper->find($id);
		$item->setHidden(time());

		return new DataResponse($this->itemMapper->update($item));
	}

	/**
	 * @param int $id
	 * @param bool $checked
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function checkItem(int $id, bool $checked) {
		$item = $this->itemMapper->find($id);
		$item->setChecked($checked);

		return new DataResponse($this->itemMapper->update($item));
	}

	/**
	 * @param int $id
	 * @param string $name
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function addCategory(int $id, string $name, ?string $color = null) {
		$category = new Category();
		$category->setList($id);
		$category->setName($name);
		$category->setOrder(0);
		$category->setColor($color);

		$this->categoryMapper->insert($category);

		return new DataResponse($this->enrichWithItemCount($this->categoryMapper->findAll($id)));
	}

	/**
	 * @param int $id
	 * @param string $newName
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function updateCategory(int $id, string $newName, ?string $color = null) {
		$category = $this->categoryMapper->find($id);
		$category->setName($newName);
		if ($color !== null) {
			$category->setColor($color);
		}

		$this->categoryMapper->update($category);

		return new DataResponse($this->enrichWithItemCount($this->categoryMapper->findAll($category->getList())));
	}

	/**
	 * @param int $id
	 * @param string $color
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function updateCategoryColor(int $id, string $color) {
		$category = $this->categoryMapper->find($id);
		$category->setColor($color);

		$this->categoryMapper->update($category);

		return new DataResponse($this->enrichWithItemCount($this->categoryMapper->findAll($category->getList())));
	}

	/**
	 * Delete a category and – if any exist – also delete every item that
	 * references it. Caller is expected to have asked the user for
	 * confirmation when items are present.
	 *
	 * @param int $id The category id.
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function deleteCategory(int $id) {
		$category = $this->categoryMapper->find($id);
		$listId = $category->getList();

		foreach ($this->itemMapper->findByCategory($id) as $item) {
			$this->itemMapper->delete($item);
		}

		$this->categoryMapper->delete($category);

		return new DataResponse($this->enrichWithItemCount($this->categoryMapper->findAll($listId)));
	}

	/**
	 * @param int $id The list id.
	 * @param int[] $orderedCategoryIds Category ids in the new order.
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function reorderCategories(int $id, array $orderedCategoryIds) {
		$this->categoryMapper->reorder($id, $orderedCategoryIds);

		return new DataResponse($this->enrichWithItemCount($this->categoryMapper->findAll($id)));
	}

	/**
	 * @param int $id
	 */
	#[NoAdminRequired]
	public function sharees(int $id): DataResponse {
		$list = $this->findAccessibleList($id);
		if ($list === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		return new DataResponse($this->formatSharees($list));
	}

	/**
	 * Share a list with another user. Only the owner of a list may share it.
	 */
	#[NoAdminRequired]
	public function shareList(int $id, string $userId): DataResponse {
		$list = $this->findAccessibleList($id);
		if ($list === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		if ($list->getUserId() !== $this->userId) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}
		if ($userId === $this->userId || !$this->userManager->userExists($userId)) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		if (!$this->shareeMapper->isSharedWith($id, $userId)) {
			$this->shareeMapper->share($id, $userId);
		}

		return new DataResponse($this->formatSharees($list));
	}

	/**
	 * Remove a sharee from a list. The owner may remove anyone, a sharee may
	 * only remove themselves (leave the list).
	 */
	#[NoAdminRequired]
	public function unshareList(int $id, string $userId): DataResponse {
		$list = $this->findAccessibleList($id);
		if ($list === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		if ($list->getUserId() !== $this->userId && $userId !== $this->userId) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->shareeMapper->unshare($id, $userId);

		return new DataResponse($this->formatSharees($list));
	}

	/**
	 * Search for users the list can be shared with, honoring the instance's
	 * sharing settings (e.g. restricting sharing to group members).
	 */
	#[NoAdminRequired]
	public function searchSharees(int $id, string $search = ''): DataResponse {
		$list = $this->findAccessibleList($id);
		if ($list === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		if ($list->getUserId() !== $this->userId) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		[$result] = $this->collaboratorSearch->search($search, [IShare::TYPE_USER], false, 25, 0);

		$excluded = [$list->getUserId() => true];
		foreach ($this->shareeMapper->findSharees($id) as $sharee) {
			$excluded[$sharee->getUserId()] = true;
		}

		$users = [];
		foreach (array_merge($result['exact']['users'] ?? [], $result['users'] ?? []) as $match) {
			$userId = (string)($match['value']['shareWith'] ?? '');
			if ($userId === '' || isset($excluded[$userId])) {
				continue;
			}
			$excluded[$userId] = true;
			$users[] = [
				'userId' => $userId,
				'displayName' => (string)($match['label'] ?? $userId),
				'subname' => (string)($match['shareWithDisplayNameUnique'] ?? ''),
			];
		}

		return new DataResponse($users);
	}

	private function findAccessibleList(int $id): ?GroceryList {
		try {
			return $this->groceryListMapper->find($id);
		} catch (DoesNotExistException) {
			return null;
		}
	}

	private function formatSharees(GroceryList $list): array {
		$sharees = array_map(
			fn ($sharee) => $this->formatUser($sharee->getUserId()),
			$this->shareeMapper->findSharees($list->getId()),
		);

		return [
			'owner' => $this->formatUser($list->getUserId()),
			'sharees' => $sharees,
		];
	}

	private function formatUser(string $userId): array {
		return [
			'userId' => $userId,
			'displayName' => $this->userManager->getDisplayName($userId) ?? $userId,
		];
	}
}
