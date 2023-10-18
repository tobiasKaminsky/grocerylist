<?php
return [
	'routes' => [
		['name' => 'GroceryList#showGroceryList', 'url' => '/list/{id}', 'verb' => 'GET'],

		['name' => 'GroceryList#index', 'url' => '/', 'verb' => 'GET'],

		['name' => 'GroceryList#lists', 'url' => '/api/lists', 'verb' => 'GET'],
		['name' => 'GroceryList#saveList', 'url' => '/api/lists', 'verb' => 'POST'],
		['name' => 'GroceryList#deleteList', 'url' => '/api/lists/{id}', 'verb' => 'DELETE'],
		['name' => 'GroceryList#updateList', 'url' => '/api/lists/{id}', 'verb' => 'POST'],
		['name' => 'GroceryList#updateListVisibility', 'url' => '/api/lists/{id}/visibility', 'verb' => 'POST'],
		['name' => 'GroceryList#renameList', 'url' => '/api/list/{id}', 'verb' => 'POST'],
		['name' => 'GroceryList#showList', 'url' => '/api/list/{id}', 'verb' => 'GET'],

		['name' => 'GroceryList#listItems', 'url' => '/api/items/{id}', 'verb' => 'GET'],
		['name' => 'GroceryList#addItem', 'url' => '/api/item/add', 'verb' => 'POST'],
		['name' => 'GroceryList#checkItem', 'url' => '/api/item/check', 'verb' => 'POST'],
		['name' => 'GroceryList#updateItem', 'url' => '/api/item/update', 'verb' => 'POST'],
		['name' => 'GroceryList#hideItem', 'url' => '/api/item/hide', 'verb' => 'POST'],
		['name' => 'GroceryList#deleteItem', 'url' => '/item/{id}', 'verb' => 'DELETE'],

		['name' => 'GroceryList#listCategories', 'url' => '/api/categories/{id}', 'verb' => 'GET'],
		['name' => 'GroceryList#listAllCategories', 'url' => '/api/all_categories/{id}', 'verb' => 'GET'],
		['name' => 'GroceryList#addCategory', 'url' => '/api/category/{id}/add', 'verb' => 'POST'],
		['name' => 'GroceryList#updateCategory', 'url' => '/api/category/update', 'verb' => 'POST'],

		['name' => 'GroceryList#sharees', 'url' => '/api/sharees/{id}', 'verb' => 'GET'],
	]
];
