pop-nav
=======

[![Build Status](https://github.com/popphp/pop-nav/workflows/phpunit/badge.svg)](https://github.com/popphp/pop-nav/actions)
[![Coverage Status](https://cc.popphp.org/coverage.php?comp=pop-nav)](https://cc.popphp.org/pop-nav/)

[![Join the chat at https://discord.gg/TZjgT74U7E](https://media.popphp.org/img/discord.svg)](https://discord.gg/TZjgT74U7E)

* [Overview](#overview)
* [Install](#install)
* [Quickstart](#quickstart)
* [Tree Node Options](#tree-node-options)
* [Modifying the Tree](#modifying-the-tree)
* [Config](#config)
* [Using ACL](#using-acl)
* [Using an ACL Policy](#using-an-acl-policy)

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
        "popphp/pop-nav" : "^5.0.0"
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

Tree Node Options
-----------------

Each node in the tree supports a few options beyond `name`, `href`, and `children`.

**Href resolution** — how `href` is resolved depends on its shape:

* `#`, anything starting with or ending in `#`, `http...`, or `mailto:...` is used exactly as given — external
  links, mailto links, and JS-hook anchors all bypass `baseUrl` entirely.
* Anything starting with `/` is prefixed with `baseUrl` (see [Config](#config) below).
* Anything else is treated as relative and joined onto its parent node's already-resolved href — this is how
  the nested `'href' => 'roles'` in the Quickstart example above becomes `/users/roles`. Top-level nodes have no
  parent href to join onto, so they should always use an absolute (`/...`) href.

```php
$tree = [
    ['name' => 'Docs',    'href' => 'https://docs.example.com'],
    ['name' => 'Support', 'href' => 'mailto:support@example.com'],
    ['name' => 'Top',     'href' => '#top']
];
```

**Per-node attributes** — a node can carry its own `attributes`, applied to its `<a>` tag. If `on`/`off` is also
configured (see [Config](#config)) and the node's `attributes` already has a `class`, the on/off class is
appended to it rather than replacing it:

```php
$tree = [
    [
        'name'       => 'Dashboard',
        'href'       => '/dashboard',
        'attributes' => ['class' => 'icon-dashboard', 'data-tooltip' => 'Go to dashboard']
    ]
];
```

**`returnFalse()`** — for `href="#"`-style links meant to trigger JS rather than navigate, calling
`$nav->returnFalse(true)` adds `onclick="return false;"` to any link whose resolved href is `#` or ends with `#`:

```php
$nav = new Nav($tree);
$nav->returnFalse(true);
```

[Top](#pop-nav)

Modifying the Tree
------------------

Beyond passing the whole tree to the constructor up front, branches and leaves can be added after the fact —
useful when nav items come from more than one source (installed modules, plugins, etc.):

```php
// Append a new top-level branch (pass true as the 2nd argument to prepend it instead)
$nav->addBranch([
    'name' => 'Orders',
    'href' => '/orders'
]);

// Insert a leaf into an existing branch, found by matching 'name'
$nav->addLeaf('Users', [
    'name' => 'Permissions',
    'href' => 'permissions'
]);
```

`addLeaf()` walks the whole tree looking for node(s) named `$branch` and appends `$leaf` to their `children`
(creating that key if it doesn't already exist). If more than one node in the tree shares that name, the leaf
is inserted into *all* of them — pass a depth as the third argument (the root level is depth `0`) to restrict
the match to one level, and/or `true` as the fourth argument to prepend instead of append:

```php
// Only match a 'Users' branch at the top level (depth 0)
$nav->addLeaf('Users', ['name' => 'Permissions', 'href' => 'permissions'], 0);
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
    'on'      => 'link-on',
    'off'     => 'link-off',
    'indent'  => '    ',
    'baseUrl' => '/app'
];
```

`baseUrl` is prefixed onto any node href that starts with `/` (see [Tree Node Options](#tree-node-options) above)
— leave it unset if your app is served from the domain root.

By default, the `on`/`off` link class is decided by comparing each link's `href` against `$_SERVER['REQUEST_URI']`.
To control that comparison explicitly — useful outside a normal HTTP request, or when you need it to differ from
the literal request URI — set `currentUrl` in the config (or call `$nav->setCurrentUrl('/pages')` directly), and
it takes precedence over `$_SERVER['REQUEST_URI']`:

```php
$config = [
    'on'         => 'link-on',
    'off'        => 'link-off',
    'currentUrl' => '/pages'
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

The `-1`, `-2`, `-3`... suffixes on the generated `id`/`class` values above come from counters that increment
once per node processed across the *entire* tree, not per-branch — so the exact numbers depend on tree order
and will shift if you add or reorder nodes. See **docs/POP-NAV.md** for the full mechanics if you need to target
specific levels with CSS.

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

> **Note:** any node with an `acl` key requires `setAcl()` to have been called on the `Nav` object first. If it
> hasn't, rendering throws a `Pop\Nav\Exception` — even for a role that would ultimately have been denied anyway.

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

> **Default ACL evaluation is permissive.** Unless you opt into strict mode, `pop-acl` allows anything that
> isn't *explicitly* denied. `config` is hidden from `editor` above only because it was explicitly denied — any
> *other* `acl`-gated resource that nobody ever mentioned to the `Acl` object at all is visible to every role by
> default. For example, add a resource nobody has an explicit rule for:

```php
$acl->addResource(new Resource('reports'));
// No allow() or deny() call for 'reports' at all.

$tree[0]['children'][] = [
    'name' => 'Reports',
    'href' => 'reports',
    'acl'  => ['resource' => 'reports']
];

$nav = new Nav($tree);
$nav->setAcl($acl);
$nav->setRole($editor);
echo $nav; // 'Reports' renders - nothing denied it, so it's allowed by default
```

> Call `$nav->setAclStrict(true)` to flip this to "deny unless explicitly allowed" instead — with strict mode on,
> that same render would *hide* `Reports`, because `editor` has no explicit `allow()` rule for it:

```php
$nav = new Nav($tree);
$nav->setAcl($acl);
$nav->setRole($editor);
$nav->setAclStrict(true);
echo $nav; // 'Reports' is now hidden - nothing explicitly allowed it
```

`setRole()`/`addRole()` accumulate rather than replace, so a `Nav` can carry more than one role at once via
`addRoles()` — but this is **not** the "grant access if any role qualifies" union that RBAC systems usually mean
by "multiple roles." What it actually does depends on strict mode:

* **Default (non-strict) mode:** a check fails the moment *any* currently-set role is explicitly denied — even
  if another role in the set would otherwise be allowed. Given the setup above, adding `$admin` alongside
  `$editor` does *not* restore access to `config`; `editor`'s explicit denial still wins:

  ```php
  $nav->setAcl($acl);
  $nav->addRoles([$editor, $admin]); // 'config' is still hidden - editor's deny wins
  echo $nav;
  ```

* **Strict mode:** a check only passes if *every* currently-set role has its own explicit allow — so adding more
  roles can only narrow what's visible, never widen it, since each additional role must independently qualify.

If you want traditional "grant access if the user has any qualifying role" behavior, resolve the user's single
most-applicable role yourself before calling `setRole()`, or use a [policy](#using-an-acl-policy) to express that
logic explicitly, rather than passing a user's whole role set to `addRoles()`.

[Top](#pop-nav)

Using an ACL Policy
--------------------

Beyond a plain resource/permission check, a node's ACL decision can be overridden by a policy — a callable that
resolves to the ACL role whose custom logic should decide access instead. Set it globally with `config['policy']`,
or per-node with `acl.policy` (which takes precedence over the global one for that node):

```php
use Pop\Acl\AclRole as Role;
use Pop\Acl\Policy\PolicyTrait;

class OwnerPolicy extends Role
{
    use PolicyTrait;

    // Method name must match the node's 'acl.permission'
    public function edit($role, $resource = null): bool
    {
        return $this->isOwnerOfCurrentPage(); // your own app-specific logic
    }
}
```

```php
$tree = [
    [
        'name' => 'Edit Page',
        'href' => 'edit',
        'acl'  => [
            'resource'   => 'page',
            'permission' => 'edit',
            'policy'     => fn() => new OwnerPolicy('owner'),
        ]
    ]
];
```

The policy callable's return value must be either the *name* of a role already registered on the `Acl` object, or
an object that uses `Pop\Acl\Policy\PolicyTrait` (as above) — `pop-nav` passes it straight to `Acl::evaluatePolicy()`,
which calls `$role->can($permission, $resource)`. If the policy returns a non-`null` result, it replaces the normal
`isAllowedMulti()`/`isAllowedMultiStrict()` decision for that node.

`Pop\Utils\CallableObject`, a plain callable, and the `[callable, ...args]` array form are all accepted, matching
the other callable-accepting spots in the Pop PHP Framework.

> **Note:** a node's ACL/policy is only evaluated once the render walks into it. If a node's `children` are *all*
> denied by their own plain resource/permission checks, the whole `children` branch is skipped before any of their
> individual `policy` overrides are evaluated — give at least one child an unconditional or already-allowed
> path if you need a policy-only child to be reachable. See **docs/POP-NAV.md** for the full mechanics.

[Top](#pop-nav)
