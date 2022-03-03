<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\GroceryList\Controller\GroceryListController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
	'routes' => [
		['name' => 'GroceryList#index', 'url' => '/', 'verb' => 'GET'],

		['name' => 'GroceryList#lists', 'url' => '/lists', 'verb' => 'GET'],
		['name' => 'GroceryList#saveList', 'url' => '/lists', 'verb' => 'POST'],
		['name' => 'GroceryList#deleteList', 'url' => '/lists/{id}', 'verb' => 'DELETE'],
		['name' => 'GroceryList#updateList', 'url' => '/lists/{id}', 'verb' => 'POST'],
		['name' => 'GroceryList#showList', 'url' => '/list/{id}', 'verb' => 'GET'],

		['name' => 'GroceryList#listItems', 'url' => '/items/{id}', 'verb' => 'GET'],
		['name' => 'GroceryList#addItem', 'url' => '/item/add', 'verb' => 'POST'],
		['name' => 'GroceryList#checkItem', 'url' => '/item/check', 'verb' => 'POST'],
		['name' => 'GroceryList#updateItem', 'url' => '/item/update', 'verb' => 'POST'],

		['name' => 'GroceryList#listCategories', 'url' => '/categories/{id}', 'verb' => 'GET'],
		['name' => 'GroceryList#listAllCategories', 'url' => '/all_categories/{id}', 'verb' => 'GET'],
		['name' => 'GroceryList#addCategory', 'url' => '/category/{id}/add', 'verb' => 'POST'],
		['name' => 'GroceryList#updateCategory', 'url' => '/category/update', 'verb' => 'POST'],

		['name' => 'GroceryList#sharees', 'url' => '/sharees/{id}', 'verb' => 'GET'],
	]
];
