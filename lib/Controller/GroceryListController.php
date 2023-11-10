<?php

namespace OCA\GroceryList\Controller;

use OCA\GroceryList\Db\Category;
use OCA\GroceryList\Db\CategoryMapper;
use OCA\GroceryList\Db\GroceryList;
use OCA\GroceryList\Db\GroceryListMapper;
use OCA\GroceryList\Db\Item;
use OCA\GroceryList\Db\ItemMapper;
use OCA\GroceryList\Db\ShareeGroceryListMapper;
use OCP\ILogger;
use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;

class GroceryListController extends Controller
{

	private GroceryListMapper $groceryListMapper;
	private ItemMapper $itemMapper;
	private CategoryMapper $categoryMapper;
	private ShareeGroceryListMapper $shareeMapper;
	private $userId;
	private ILogger $logger;

	public function __construct($AppName,
								IRequest $request,
								GroceryListMapper $groceryListMapper,
								ItemMapper $itemMapper,
								CategoryMapper $categoryMapper,
								ShareeGroceryListMapper $shareeMapper,
								ILogger $logger,
		$UserId)
	{
		parent::__construct($AppName, $request);
		$this->groceryListMapper = $groceryListMapper;
		$this->itemMapper = $itemMapper;
		$this->categoryMapper = $categoryMapper;
		$this->shareeMapper = $shareeMapper;
		$this->logger = $logger;
		$this->userId = $UserId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index()
	{
		// Register all scripts and styles we use on the frontend
		\OCP\Util::addScript($this->appName, $this->appName . '-main'); // adding `js/grocerylist-main.mjs` generated from JS source
		\OCP\Util::addStyle($this->appName, $this->appName . '-main'); // adding `css/grocerylist-style.css` generated from JS source
		// return the template we use (see `templates/` folder)
		return new TemplateResponse('grocerylist', 'main');
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function showGroceryList($id)
	{
		return $this->index();
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function showGroceryListSettings($id)
	{
		return $this->index();
	}

	/**
	 * @NoAdminRequired
	 */
	public function lists()
	{
		return new DataResponse($this->groceryListMapper->findAll());
	}

	/**
	 * @NoAdminRequired
	 * @param string $title
	 * @return DataResponse
	 */
	public function saveList(string $title)
	{
		$groceryList = new GroceryList();
		$groceryList->setTitle($title);
		$groceryList->setUserId($this->userId);

		return new DataResponse($this->groceryListMapper->insert($groceryList));
	}

	/**
	 * @NoAdminRequired
	 * @param int $id
	 * @return \OCP\AppFramework\Db\Entity
	 */
	public function deleteList(int $id)
	{
		$groceryList = $this->groceryListMapper->find($id);
		$this->groceryListMapper->delete($groceryList);

		return $groceryList;
	}

	/**
	 * @NoAdminRequired
	 * @param int $id
	 * @param string $title
	 * @param string $showOnlyUnchecked
	 */
	public function renameList(int $id, string $title)
	{
		$list = $this->groceryListMapper->find($id);
		$list->setTitle($title);

		$this->groceryListMapper->update($list);
	}


	/**
	 * @NoAdminRequired
	 * @param int $id
	 * @param string $title
	 * @param string $showOnlyUnchecked
	 */
	public function updateList(int $id, string $title, int $showOnlyUnchecked = 0)
	{
		$list = $this->groceryListMapper->find($id);
		$list->setShowOnlyUnchecked($showOnlyUnchecked);
		$list->setTitle($title);

		$this->groceryListMapper->update($list);
	}

	/**
	 * @NoAdminRequired
	 * @param int $id
	 * @param string $title
	 * @param string $showOnlyUnchecked
	 */
	public function updateListVisibility(int $id, int $showOnlyUnchecked = 0)
	{
		$list = $this->groceryListMapper->find($id);
		$list->setShowOnlyUnchecked($showOnlyUnchecked);

		$this->groceryListMapper->update($list);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return DataResponse
	 */
	public function showList(int $id)
	{
		return new DataResponse($this->groceryListMapper->find($id));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param int $id
	 */
	public function listItems(int $id)
	{
		return new DataResponse($this->itemMapper->findAll($id));
	}

	/**
	 * @NoAdminRequired
	 * @param int $id
	 */
	public function deleteItem(int $id) {
		$item = $this->itemMapper->find($id);
		$this->itemMapper->delete($item);
	}

	/**
	 * @NoAdminRequired
	 * @param int $id
	 */
	public function listCategories(int $id)
	{
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
	 * @NoAdminRequired
	 * @param int $id
	 */
	public function listAllCategories(int $id)
	{
		return new DataResponse($this->categoryMapper->findAll($id));
	}

	/**
	 * @NoAdminRequired
	 * @param string $name
	 * @param string $quantity
	 * @param int $category
	 * @param int $list
	 * @return DataResponse
	 */
	public function addItem(string $name, string $quantity, int $category, int $list)
	{
		$item = new Item();
		$item->setName($name);
		$item->setQuantity($quantity);
		$item->setCategory($category);
		$item->setList($list);

		return new DataResponse($this->itemMapper->insert($item));
	}

	/**
	 * @NoAdminRequired
	 * @param int $id
	 * @param string $name
	 * @param string $quantity
	 * @param int $category
	 * @return DataResponse
	 */
	public function updateItem(int $id, string $name, string $quantity, int $category)
	{
		$item = $this->itemMapper->find($id);
		$item->setName($name);
		$item->setQuantity($quantity);
		$item->setCategory($category);

		return new DataResponse($this->itemMapper->update($item));
	}

	/**
	 * @NoAdminRequired
	 */
	public function hideItem(int $id)
	{
		$item = $this->itemMapper->find($id);
		$item->setHidden(time());

		return new DataResponse($this->itemMapper->update($item));
	}

	/**
	 * @NoAdminRequired
	 * @param string $id
	 * @param int $checked
	 * @return DataResponse
	 */
	public function checkItem(int $id, int $checked)
	{
		$item = $this->itemMapper->find($id);
		$item->setChecked($checked);

		return new DataResponse($this->itemMapper->update($item));
	}

	/**
	 * @NoAdminRequired
	 * @param int $id
	 * @param string $name
	 * @return DataResponse
	 */
	public function addCategory(int $id, string $name)
	{
		$category = new Category();
		$category->setList($id);
		$category->setName($name);
		$category->setOrder(0);

		$this->categoryMapper->insert($category);

		return new DataResponse($this->categoryMapper->findAll($id));
	}

	/**
	 * @NoAdminRequired
	 * @param int $id
	 * @param string $newName
	 * @return DataResponse
	 */
	public function updateCategory(int $id, string $newName)
	{
		$category = $this->categoryMapper->find($id);
		$category->setName($newName);

		$this->categoryMapper->update($category);

		return new DataResponse($this->categoryMapper->findAll($category->getList()));
	}

	/**
	 * @NoAdminRequired
	 * @param int $id
	 */
	public function sharees(int $id): DataResponse
	{
		return new DataResponse($this->shareeMapper->find($id));
	}
}
