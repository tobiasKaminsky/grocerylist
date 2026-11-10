<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

require_once __DIR__ . '/../../../tests/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

\OC_App::loadApp('grocerylist');
OC_Hook::clear();
