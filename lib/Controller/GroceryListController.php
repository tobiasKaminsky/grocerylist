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
use OCA\GroceryList\Service\ListService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;

class GroceryListController extends Controller {

	/** Maximum length of titles, names and quantities, as defined by the database columns */
	private const MAX_LENGTH = 200;

	private GroceryListMapper $groceryListMapper;
	private ItemMapper $itemMapper;
	private CategoryMapper $categoryMapper;
	private ShareeGroceryListMapper $shareeMapper;
	private IUserManager $userManager;
	private ISearch $collaboratorSearch;
	private ListService $listService;
	private IShareManager $shareManager;
	private IGroupManager $groupManager;
	private $userId;

	public function __construct($AppName,
		IRequest $request,
		GroceryListMapper $groceryListMapper,
		ItemMapper $itemMapper,
		CategoryMapper $categoryMapper,
		ShareeGroceryListMapper $shareeMapper,
		IUserManager $userManager,
		ISearch $collaboratorSearch,
		ListService $listService,
		IShareManager $shareManager,
		IGroupManager $groupManager,
		$UserId) {
		parent::__construct($AppName, $request);
		$this->groceryListMapper = $groceryListMapper;
		$this->itemMapper = $itemMapper;
		$this->categoryMapper = $categoryMapper;
		$this->shareeMapper = $shareeMapper;
		$this->userManager = $userManager;
		$this->collaboratorSearch = $collaboratorSearch;
		$this->listService = $listService;
		$this->shareManager = $shareManager;
		$this->groupManager = $groupManager;
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

		$now = time();
		foreach ($lists as $list) {
			$list->setUncheckedCount($this->itemMapper->findAllUnchecked($list->getId(), $now));
			$list->setSnoozedUntil($this->itemMapper->findNextSnoozeEnd($list->getId(), $now));
		}

		return new DataResponse($lists);
	}

	/**
	 * @param string $title
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function saveList(string $title) {
		$title = trim($title);
		if (!$this->isValidText($title)) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

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
		$groceryList = $this->findAccessibleList($id);
		if ($groceryList === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		if ($groceryList->getUserId() !== $this->userId) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		$this->listService->delete($groceryList);

		return new DataResponse($groceryList);
	}

	/**
	 * @param int $id
	 * @param string $title
	 */
	#[NoAdminRequired]
	public function renameList(int $id, string $title) {
		return $this->updateList($id, $title, null);
	}

	/**
	 * @param int $id
	 * @param string $title
	 * @param int|null $showOnlyUnchecked
	 */
	#[NoAdminRequired]
	public function updateList(int $id, string $title, ?int $showOnlyUnchecked = 0) {
		$title = trim($title);
		if (!$this->isValidText($title)) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$list = $this->findAccessibleList($id);
		if ($list === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		if ($showOnlyUnchecked !== null) {
			$list->setShowOnlyUnchecked($showOnlyUnchecked !== 0);
		}
		$list->setTitle($title);

		return new DataResponse($this->groceryListMapper->update($list));
	}

	/**
	 * @param int $id
	 * @param int $showOnlyUnchecked
	 */
	#[NoAdminRequired]
	public function updateListVisibility(int $id, int $showOnlyUnchecked = 0) {
		$list = $this->findAccessibleList($id);
		if ($list === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		$list->setShowOnlyUnchecked($showOnlyUnchecked !== 0);

		return new DataResponse($this->groceryListMapper->update($list));
	}

	/**
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function showList(int $id) {
		$list = $this->findAccessibleList($id);
		if ($list === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		return new DataResponse($list);
	}

	/**
	 * @param int $id
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function listItems(int $id) {
		if ($this->findAccessibleList($id) === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		return new DataResponse($this->itemMapper->findAll($id));
	}

	/**
	 * @param int $id
	 */
	#[NoAdminRequired]
	public function deleteItem(int $id) {
		$item = $this->findAccessibleItem($id);
		if ($item === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$this->itemMapper->delete($item);

		return new DataResponse([]);
	}

	/**
	 * @param int $id
	 */
	#[NoAdminRequired]
	public function listCategories(int $id) {
		if ($this->findAccessibleList($id) === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

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
		if ($this->findAccessibleList($id) === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

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
		$name = trim($name);
		$quantity = trim($quantity);
		if (!$this->isValidText($name) || !$this->isValidText($quantity, true)) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		if ($this->findAccessibleList($list) === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		if (!$this->isCategoryOfList($category, $list)) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

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
		$name = trim($name);
		$quantity = trim($quantity);
		if (!$this->isValidText($name) || !$this->isValidText($quantity, true)) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$item = $this->findAccessibleItem($id);
		if ($item === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		if (!$this->isCategoryOfList($category, $item->getList())) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$item->setName($name);
		$item->setQuantity($quantity);
		$item->setCategory($category);

		return new DataResponse($this->itemMapper->update($item));
	}

	#[NoAdminRequired]
	public function hideItem(int $id) {
		$item = $this->findAccessibleItem($id);
		if ($item === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		$item->setHidden(time());

		return new DataResponse($this->itemMapper->update($item));
	}

	/**
	 * End the snooze of an item right away.
	 */
	#[NoAdminRequired]
	public function unhideItem(int $id) {
		$item = $this->findAccessibleItem($id);
		if ($item === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		$item->setHidden(0);

		return new DataResponse($this->itemMapper->update($item));
	}

	/**
	 * @param int $id
	 * @param bool $checked
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function checkItem(int $id, bool $checked) {
		$item = $this->findAccessibleItem($id);
		if ($item === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
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
		$name = trim($name);
		if (!$this->isValidText($name) || !$this->isValidColor($color)) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		if ($this->findAccessibleList($id) === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		$categories = $this->categoryMapper->findAll($id);

		// A category with the same name is reused instead of creating a duplicate
		if ($this->findCategoryByName($categories, $name) === null) {
			$order = 0;
			foreach ($categories as $existing) {
				$order = max($order, $existing->getOrder() + 1);
			}

			$category = new Category();
			$category->setList($id);
			$category->setName($name);
			$category->setOrder($order);
			$category->setColor($color === '' ? null : $color);

			$this->categoryMapper->insert($category);
			$categories = $this->categoryMapper->findAll($id);
		}

		return new DataResponse($this->enrichWithItemCount($categories));
	}

	/**
	 * @param Category[] $categories
	 */
	private function findCategoryByName(array $categories, string $name): ?Category {
		foreach ($categories as $category) {
			if (mb_strtolower($category->getName()) === mb_strtolower($name)) {
				return $category;
			}
		}
		return null;
	}

	/**
	 * @param int $id
	 * @param string $newName
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function updateCategory(int $id, string $newName, ?string $color = null) {
		$newName = trim($newName);
		if (!$this->isValidText($newName) || !$this->isValidColor($color)) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$category = $this->findAccessibleCategory($id);
		if ($category === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		$sameName = $this->findCategoryByName($this->categoryMapper->findAll($category->getList()), $newName);
		if ($sameName !== null && $sameName->getId() !== $category->getId()) {
			return new DataResponse([], Http::STATUS_CONFLICT);
		}

		$category->setName($newName);
		if ($color !== null) {
			$category->setColor($color === '' ? null : $color);
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
		if (!$this->isValidColor($color)) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		$category = $this->findAccessibleCategory($id);
		if ($category === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		$category->setColor($color === '' ? null : $color);

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
		$category = $this->findAccessibleCategory($id);
		if ($category === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
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
		if ($this->findAccessibleList($id) === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

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
	 * Share a list with another user. Only the owner of a list may share it,
	 * and only with users the instance's sharing settings allow.
	 */
	#[NoAdminRequired]
	public function shareList(int $id, string $userId): DataResponse {
		$list = $this->findAccessibleList($id);
		if ($list === null) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		if ($list->getUserId() !== $this->userId || !$this->canShare()) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}
		// Unknown users and users that may not be shared with get the same
		// answer, so the endpoint can not be used to find out which users exist
		if ($userId === $this->userId || !$this->canShareWith($userId)) {
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
		if ($list->getUserId() !== $this->userId || !$this->canShare()) {
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

	/**
	 * Whether the current user may share at all.
	 */
	private function canShare(): bool {
		return $this->shareManager->shareApiEnabled()
			&& !$this->shareManager->sharingDisabledForUser($this->userId);
	}

	/**
	 * Whether the current user may share with the given user. Follows the
	 * "restrict users to only share with users in their groups" setting.
	 */
	private function canShareWith(string $userId): bool {
		$sharee = $this->userManager->get($userId);
		if ($sharee === null) {
			return false;
		}
		if (!$this->shareManager->shareWithGroupMembersOnly()) {
			return true;
		}

		$owner = $this->userManager->get($this->userId);
		if ($owner === null) {
			return false;
		}
		$excludedGroups = $this->shareManager->shareWithGroupMembersOnlyExcludeGroupsList();
		$ownerGroups = array_diff($this->groupManager->getUserGroupIds($owner), $excludedGroups);
		$shareeGroups = $this->groupManager->getUserGroupIds($sharee);

		return array_intersect($ownerGroups, $shareeGroups) !== [];
	}

	/**
	 * The list if the current user owns it or it is shared with them.
	 */
	private function findAccessibleList(int $id): ?GroceryList {
		try {
			return $this->groceryListMapper->find($id);
		} catch (DoesNotExistException) {
			return null;
		}
	}

	/**
	 * The item if it belongs to a list the current user can access.
	 */
	private function findAccessibleItem(int $id): ?Item {
		try {
			$item = $this->itemMapper->find($id);
		} catch (DoesNotExistException) {
			return null;
		}

		return $this->findAccessibleList($item->getList()) === null ? null : $item;
	}

	/**
	 * The category if it belongs to a list the current user can access.
	 */
	private function findAccessibleCategory(int $id): ?Category {
		try {
			$category = $this->categoryMapper->find($id);
		} catch (DoesNotExistException) {
			return null;
		}

		return $this->findAccessibleList($category->getList()) === null ? null : $category;
	}

	/**
	 * Category 0 means "no category", any other category must belong to the list.
	 */
	private function isCategoryOfList(int $categoryId, int $listId): bool {
		if ($categoryId === 0) {
			return true;
		}

		try {
			return $this->categoryMapper->find($categoryId)->getList() === $listId;
		} catch (DoesNotExistException) {
			return false;
		}
	}

	private function isValidText(string $text, bool $allowEmpty = false): bool {
		return ($allowEmpty || $text !== '') && mb_strlen($text) <= self::MAX_LENGTH;
	}

	/**
	 * Colors are stored as "#rrggbb"; null or an empty string means no color.
	 */
	private function isValidColor(?string $color): bool {
		return $color === null || $color === '' || preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1;
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
