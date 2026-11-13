<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroceryList\Tests\Unit\Listener;

use OCA\GroceryList\Db\ShareeGroceryListMapper;
use OCA\GroceryList\Listener\UserDeletedListener;
use OCP\EventDispatcher\Event;
use OCP\IUser;
use OCP\User\Events\UserDeletedEvent;
use Test\TestCase;

class UserDeletedListenerTest extends TestCase {
	public function testRemovesSharesOfDeletedUser(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$mapper = $this->createMock(ShareeGroceryListMapper::class);
		$mapper->expects($this->once())->method('unshareAllForUser')->with('alice');

		(new UserDeletedListener($mapper))->handle(new UserDeletedEvent($user));
	}

	public function testIgnoresOtherEvents(): void {
		$mapper = $this->createMock(ShareeGroceryListMapper::class);
		$mapper->expects($this->never())->method('unshareAllForUser');

		(new UserDeletedListener($mapper))->handle(new Event());
	}
}
