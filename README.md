pop-nav
=======

[![Build Status](https://github.com/popphp/pop-nav/workflows/phpunit/badge.svg)](https://github.com/popphp/pop-nav/actions)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-nav)](http://cc.popphp.org/pop-nav/)

[![Join the chat at https://discord.gg/TZjgT74U7E](https://media.popphp.org/img/discord.svg)](https://discord.gg/TZjgT74U7E)

* [Overview](#overview)
* [Install](#install)
* [Quickstart](#quickstart)
* [Config](#config)
* [Using ACL](#using-acl)

Overview
--------
`pop-nav` is a component for managing and rendering an HTML navigation tree. It includes support for
injecting ACL functionality to display only the certain branches of the navigation tree that the
current user role is allowed to access. For that, the `pop-acl` component is used.

`pop-nav` is a component of the [Pop PHP Framework](https://www.popphp.org/).

[Top](#pop-nav)

Install
-------

Install `pop-nav` using Composer.

    composer require popphp/pop-nav

Or, require it in your composer.json file

    "require": {
        "popphp/pop-nav" : "^4.1.3"
    }

[Top](#pop-nav)

Quickstart
----------

First, you can define the navigation tree:

```php
$tree = [
    [
        'name'     => 'Users',
        'href'     => '/users',
        'children' => [
            [
                'name' => 'Roles',
                'href' => 'roles'
            ],
            [
                'name' => 'Config',
                'href' => 'config'
            ]
        ]
    ],
    [
        'name' => 'Orders',
        'href' => '/orders'
    ]
];
```

Then you can pass that to the nav object and render the nav:

```php
$nav = new Nav($tree);
echo $nav;
```

```html
<nav>
    <nav>
        <a href="/users">Users</a>
        <nav>
            <nav>
                <a href="/users/roles">Roles</a>
            </nav>
            <nav>
                <a href="/users/config">Config</a>
            </nav>
        </nav>
    </nav>
    <nav>
        <a href="/orders">Orders</a>
    </nav>
</nav>

```

[Top](#pop-nav)

Config
------

You have a significant amount of control over the branch nodes and attributes
via a configuration array:

```php
$config = [
    'top' => [
        'node'  => 'nav',
        'id'    => 'main-nav'
    ],
    'parent' => [
        'node'  => 'nav',
        'id'    => 'nav',
        'class' => 'level'
    ],
    'child' => [
        'node'  => 'nav',
        'id'    => 'menu',
        'class' => 'item'
    ],
    'on'  => 'link-on',
    'off' => 'link-off',
    'indent' => '    '
];
```

Using the same navigation tree from above, you can then create and render your nav object
with the config:

```php
use Pop\Nav\Nav;

$nav = new Nav($tree, $config);
echo $nav;
```

```html
    <nav id="main-nav">
    <nav id="menu-1" class="item-1">
        <a href="/users" class="link-off">Users</a>
        <nav id="nav-2" class="level-2">
            <nav id="menu-2" class="item-2">
                <a href="/users/roles" class="link-off">Roles</a>
            </nav>
            <nav id="menu-3" class="item-2">
                <a href="/users/config" class="link-off">Config</a>
            </nav>
        </nav>
    </nav>
    <nav id="menu-4" class="item-1">
        <a href="/orders" class="link-off">Orders</a>
    </nav>
</nav>
```

[Top](#pop-nav)

Using ACL
---------

First, let's set up the ACL object with some roles and resources:

```php
use Pop\Acl\Acl;
use Pop\Acl\AclRole as Role;
use Pop\Acl\AclResource as Resource;

$acl = new Acl();

$admin  = new Role('admin');
$editor = new Role('editor');

$acl->addRoles([$admin, $editor]);

$acl->addResource(new Resource('config'));
$acl->allow('admin');
$acl->deny('editor', 'config');
```

And then we add the ACL rules to the navigation tree:

```php
$tree = [
    [
        'name'     => 'Home',
        'href'     => '/home',
        'children' => [
            [
                'name' => 'Users',
                'href' => 'users'
            ],
            [
                'name' => 'Config',
                'href' => 'config',
                'acl'  => [
                    'resource' => 'config'
                ]
            ]
        ]
    ],
    [
        'name' => 'Orders',
        'href' => '/orders'
    ]
];
```

We then inject the ACL object into the navigation object, set the current role and render the navigation:

```php
$nav = new Nav($tree);
$nav->setAcl($acl);
$nav->setRole($editor);
echo $nav;
```

```html
<nav>
    <nav>
        <a href="/home">Home</a>
        <nav>
            <nav>
                <a href="/home/users">Users</a>
            </nav>
        </nav>
    </nav>
    <nav>
        <a href="/orders">Orders</a>
    </nav>
</nav>
```

Because the 'editor' role is denied access to the `config` page, that nav branch is not rendered. However,
if the role is set to `$admin`, the `config` branch renders:

```php
$nav = new Nav($tree);
$nav->setAcl($acl);
$nav->setRole($admin);
echo $nav;
```

```html
<nav>
    <nav>
        <a href="/home">Home</a>
        <nav>
            <nav>
                <a href="/home/users">Users</a>
            </nav>
            <nav>
                <a href="/home/config">Config</a>
            </nav>
        </nav>
    </nav>
    <nav>
        <a href="/orders">Orders</a>
    </nav>
</nav>
```

[Top](#pop-nav)
