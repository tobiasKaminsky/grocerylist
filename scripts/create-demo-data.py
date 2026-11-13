#!/usr/bin/env python3
# SPDX-FileCopyrightText: 2026 Tobias Kaminsky <tobias@kaminsky.me>
# SPDX-License-Identifier: AGPL-3.0-or-later
"""
Create demo grocery lists for a Nextcloud user via the app's REST API.

The data resembles the weekly shopping of an average family of four, with
categories in the order you walk through a typical store. Lists of the user
that have the same title as a demo list are deleted and created again, so the
script can be run repeatedly. Other lists are left untouched.

Usage:
    scripts/create-demo-data.py [--url http://localhost/nc] [--user grocery] [--password grocery]
"""

import argparse
import base64
import json
import sys
import urllib.error
import urllib.request

# (category, color, [(item, quantity, checked)]), in store walking order
LISTS = {
	'Supermarket': [
		('Fruit & Vegetables', '#2e7d32', [
			('Bananas', '1 bunch', False),
			('Apples', '1 kg', False),
			('Carrots', '1 kg', False),
			('Tomatoes', '500 g', False),
			('Cucumber', '', False),
			('Bell peppers', '3', False),
			('Potatoes', '2.5 kg', False),
			('Onions', '1 kg', True),
			('Lettuce', '', False),
			('Lemons', '2', True),
		]),
		('Bakery', '#a1662f', [
			('Whole grain bread', '1 loaf', False),
			('Bread rolls', '6', False),
			('Sandwich bread', '', True),
		]),
		('Dairy & Eggs', '#1e88e5', [
			('Milk', '3 l', False),
			('Eggs', '10', False),
			('Butter', '250 g', False),
			('Natural yogurt', '1 kg', False),
			('Sliced cheese', '200 g', False),
			('Cream cheese', '', True),
		]),
		('Meat & Fish', '#c62828', [
			('Chicken breast', '600 g', False),
			('Ground beef', '500 g', False),
			('Salmon fillets', '2', False),
			('Sliced ham', '150 g', False),
		]),
		('Pasta, Rice & Baking', '#f9a825', [
			('Spaghetti', '2 × 500 g', False),
			('Rice', '1 kg', False),
			('Canned tomatoes', '2', False),
			('Flour', '1 kg', True),
			('Olive oil', '', False),
		]),
		('Breakfast & Spreads', '#ef6c00', [
			('Oatmeal', '500 g', False),
			('Muesli', '', False),
			('Strawberry jam', '', False),
			('Honey', '', True),
			('Peanut butter', '', False),
		]),
		('Frozen', '#00acc1', [
			('Frozen peas', '', False),
			('Fish sticks', '', False),
			('Pizza', '2', False),
			('Vanilla ice cream', '', False),
		]),
		('Drinks', '#3949ab', [
			('Sparkling water', '6 × 1.5 l', False),
			('Apple juice', '2 l', False),
			('Coffee', '500 g', False),
			('Black tea', '', True),
		]),
		('Snacks & Sweets', '#8e24aa', [
			('Crackers', '', False),
			('Dark chocolate', '2', False),
			('Pretzels', '', False),
		]),
		('Household', '#546e7a', [
			('Dish soap', '', False),
			('Paper towels', '', False),
			('Trash bags', '', True),
		]),
	],
	'Discount Store': [
		('Canned & Dry Goods', '#f9a825', [
			('Chickpeas', '3 cans', False),
			('Kidney beans', '2 cans', False),
			('Pasta sauce', '2', False),
			('Penne', '3 × 500 g', False),
			('Sugar', '1 kg', True),
		]),
		('Dairy', '#1e88e5', [
			('Long-life milk', '6 l', False),
			('Grated cheese', '2 × 200 g', False),
		]),
		('Drinks', '#3949ab', [
			('Still water', '6 × 1.5 l', False),
			('Orange juice', '2 l', False),
		]),
		('Household', '#546e7a', [
			('Toilet paper', '1 pack', False),
			('Laundry detergent', '', False),
			('Dishwasher tablets', '', True),
			('Aluminum foil', '', False),
		]),
	],
	'Drugstore': [
		('Oral Care', '#00897b', [
			('Toothpaste', '2', False),
			('Kids toothpaste', '', False),
			('Dental floss', '', True),
		]),
		('Hair & Body', '#d81b60', [
			('Shampoo', '', False),
			('Shower gel', '2', False),
			('Deodorant', '', False),
			('Sunscreen', '', False),
		]),
		('Baby & Kids', '#fb8c00', [
			('Diapers, size 5', '1 pack', False),
			('Wet wipes', '2', False),
		]),
		('First Aid', '#e53935', [
			('Band-aids', '', False),
			('Tissues', '1 pack', True),
		]),
		('Cleaning', '#546e7a', [
			('All-purpose cleaner', '', False),
			('Sponges', '1 pack', False),
		]),
	],
	"Farmers' Market": [
		('Vegetables', '#2e7d32', [
			('Zucchini', '2', False),
			('Leeks', '2', False),
			('Fresh herbs', '1 bunch', False),
			('Mushrooms', '250 g', True),
		]),
		('Fruit', '#43a047', [
			('Strawberries', '500 g', False),
			('Pears', '1 kg', False),
		]),
		('Eggs & Cheese', '#1e88e5', [
			('Free-range eggs', '10', False),
			('Mountain cheese', '300 g', False),
		]),
		('Bread', '#a1662f', [
			('Sourdough bread', '1 loaf', False),
		]),
		('Flowers', '#ec407a', [
			('Bouquet', '', False),
		]),
	],
}


class Client:
	def __init__(self, url: str, user: str, password: str):
		self.base = url.rstrip('/') + '/index.php/apps/grocerylist/api'
		token = base64.b64encode(f'{user}:{password}'.encode()).decode()
		self.headers = {
			'Authorization': f'Basic {token}',
			# Lets the API accept basic auth without a CSRF token
			'OCS-APIRequest': 'true',
			'Accept': 'application/json',
			'Content-Type': 'application/json',
		}

	def request(self, method: str, path: str, data=None):
		body = json.dumps(data).encode() if data is not None else None
		request = urllib.request.Request(self.base + path, data=body, headers=self.headers, method=method)
		with urllib.request.urlopen(request) as response:
			content = response.read()
			return json.loads(content) if content else None


def create_list(client: Client, title: str, categories) -> None:
	grocery_list = client.request('POST', '/lists', {'title': title})
	list_id = grocery_list['id']

	ids = {}
	for name, color, _ in categories:
		created = client.request('POST', f'/category/{list_id}/add', {'name': name, 'color': color})
		ids.update({category['name']: category['id'] for category in created})
	client.request('POST', f'/categories/{list_id}/reorder', {
		'orderedCategoryIds': [ids[name] for name, _, _ in categories],
	})

	count = 0
	for name, _, items in categories:
		for item_name, quantity, checked in items:
			item = client.request('POST', '/item/add', {
				'name': item_name,
				'quantity': quantity,
				'list': list_id,
				'category': ids[name],
			})
			if checked:
				client.request('POST', '/item/check', {'id': item['id'], 'checked': True})
			count += 1

	print(f'Created "{title}" with {len(categories)} categories and {count} items')


def main() -> int:
	parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
	parser.add_argument('--url', default='http://localhost/nc', help='Nextcloud base URL')
	parser.add_argument('--user', default='grocery')
	parser.add_argument('--password', default='grocery')
	args = parser.parse_args()

	client = Client(args.url, args.user, args.password)
	try:
		for existing in client.request('GET', '/lists'):
			if existing['title'] in LISTS and existing['userId'] == args.user:
				client.request('DELETE', f'/lists/{existing["id"]}')
				print(f'Deleted existing "{existing["title"]}"')

		for title, categories in LISTS.items():
			create_list(client, title, categories)
	except urllib.error.HTTPError as e:
		print(f'{e.code} {e.reason} for {e.url}', file=sys.stderr)
		return 1

	return 0


if __name__ == '__main__':
	sys.exit(main())
