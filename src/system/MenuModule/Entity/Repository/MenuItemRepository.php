<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\MenuModule\Entity\Repository;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Zikula\MenuModule\Entity\RepositoryInterface\MenuItemRepositoryInterface;

class MenuItemRepository extends NestedTreeRepository implements MenuItemRepositoryInterface
{
}
