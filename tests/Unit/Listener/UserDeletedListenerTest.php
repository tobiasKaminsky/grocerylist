<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroceryList\Tests\Unit\Listener;

use OCA\GroceryList\Listener\UserDeletedListener;
use OCA\GroceryList\Service\ListService;
use OCP\EventDispatcher\Event;
use OCP\IUser;
use OCP\User\Events\UserDeletedEvent;
use Test\TestCase;

class UserDeletedListenerTest extends TestCase {
	public function testRemovesDataOfDeletedUser(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$service = $this->createMock(ListService::class);
		$service->expects($this->once())->method('deleteUserData')->with('alice');

		(new UserDeletedListener($service))->handle(new UserDeletedEvent($user));
	}

	public function testIgnoresOtherEvents(): void {
		$service = $this->createMock(ListService::class);
		$service->expects($this->never())->method('deleteUserData');

		(new UserDeletedListener($service))->handle(new Event());
	}
}
