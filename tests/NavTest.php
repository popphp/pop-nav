<?php

namespace Pop\Nav\Test;

use Pop\Acl\Acl;
use Pop\Acl\AclRole;
use Pop\Acl\AclResource;
use Pop\Nav\Nav;
use PHPUnit\Framework\TestCase;

class NavTest extends TestCase
{

    public function testConstructor()
    {
        $nav = new Nav();
        $this->assertInstanceOf('Pop\Nav\Nav', $nav);
    }

    public function testReturnFalse()
    {
        $nav = new Nav();
        $nav->returnFalse(true);
        $this->assertTrue($nav->isReturnFalse());
    }

    public function testSetIndent()
    {
        $nav = new Nav();
        $nav->setIndent('    ');
        $this->assertEquals('    ', $nav->getIndent());
    }

    public function testSetBaseUrl()
    {
        $nav = new Nav();
        $nav->setBaseUrl('/home');
        $this->assertEquals('/home', $nav->getBaseUrl());
    }

    public function testSetCurrentUrl()
    {
        $nav = new Nav();
        $nav->setCurrentUrl('/pages/edit');
        $this->assertEquals('/pages/edit', $nav->getCurrentUrl());
    }

    public function testConfigCurrentUrl()
    {
        $nav = new Nav(null, ['top' => ['node' => 'nav'], 'currentUrl' => '/pages/edit']);
        $this->assertEquals('/pages/edit', $nav->getCurrentUrl());
    }

    public function testParentLevel()
    {
        $nav = new Nav();
        $nav->setParentLevel(2);
        $this->assertEquals(2, $nav->getParentLevel());
        $nav->incrementParentLevel();
        $this->assertEquals(3, $nav->getParentLevel());
        $nav->decrementParentLevel();
        $this->assertEquals(2, $nav->getParentLevel());
    }

    public function testChildLevel()
    {
        $nav = new Nav();
        $nav->setChildLevel(2);
        $this->assertEquals(2, $nav->getChildLevel());
        $nav->incrementChildLevel();
        $this->assertEquals(3, $nav->getChildLevel());
        $nav->decrementChildLevel();
        $this->assertEquals(2, $nav->getChildLevel());
    }

    public function testAddBranch()
    {
        $_SERVER['REQUEST_URI'] = '/home';
        $tree = [
            [
                'name'     => 'Pages',
                'href'     => '/pages',
                'children' => [
                    [
                        'name' => 'Add Page',
                        'href' => 'add'
                    ],
                    [
                        'name' => 'Edit Page',
                        'href' => 'edit'
                    ]
                ]
            ]
        ];

        $nav = new Nav($tree);
        $nav->addBranch([
            'name'     => 'Users',
            'href'     => '/users',
            'children' => [
                [
                    'name' => 'Add User',
                    'href' => 'add'
                ],
                [
                    'name' => 'Edit User',
                    'href' => 'edit'
                ]
            ]
        ]);

        $this->assertStringContainsString('/users/add', (string)$nav);
    }

    public function testAddLeaf()
    {
        $_SERVER['REQUEST_URI'] = '/home';
        $tree = [
            [
                'name'     => 'Pages',
                'href'     => '/pages',
                'children' => [
                    [
                        'name' => 'Add Page',
                        'href' => 'add'
                    ],
                    [
                        'name' => 'Edit Page',
                        'href' => 'edit'
                    ]
                ]
            ]
        ];

        $nav = new Nav($tree);
        $nav->addLeaf('Pages', [
            'name' => 'Remove Page',
            'href' => 'remove'
        ]);
        $this->assertStringContainsString('/pages/remove', (string)$nav);
    }

    public function testAddLeafIntoNodeWithoutExistingChildren()
    {
        $_SERVER['REQUEST_URI'] = '/home';
        $tree = [
            ['name' => 'Pages', 'href' => '/pages']
        ];

        $nav = new Nav($tree);
        $nav->addLeaf('Pages', ['name' => 'Add Page', 'href' => 'add']);

        $this->assertStringContainsString('/pages/add', (string)$nav);
    }

    public function testBuildRebuildAndNavAlias()
    {
        $_SERVER['REQUEST_URI'] = '/home';
        $tree = [['name' => 'Pages', 'href' => '/pages']];

        // build() on a fresh (unbuilt) Nav exercises its own build-and-cache branch.
        $nav = new Nav($tree);
        $nav->build();
        $this->assertStringContainsString('/pages', $nav->render());

        // nav()/getNav() on a separate, fresh (unbuilt) Nav exercises their own
        // build-and-cache branch, rather than reusing $nav's already-cached build.
        $nav2 = new Nav($tree);
        $this->assertInstanceOf('Pop\Dom\Child', $nav2->nav());

        $nav2->rebuild();
        $this->assertStringContainsString('/pages', $nav2->render());
    }

    public function testNameIsEscaped()
    {
        $_SERVER['REQUEST_URI'] = '/home';
        $tree = [
            [
                'name' => '<script>alert(1)</script>',
                'href' => '/xss'
            ]
        ];

        $nav = new Nav($tree);
        $menu = (string)$nav;

        $this->assertStringNotContainsString('<script>alert(1)</script>', $menu);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $menu);
    }

    public function testRenderWithoutRequestUri()
    {
        unset($_SERVER['REQUEST_URI']);

        $tree = [
            ['name' => 'Home', 'href' => '/home']
        ];
        $nav = new Nav($tree);

        set_error_handler(function ($errno, $errstr) {
            throw new \ErrorException($errstr, 0, $errno);
        }, E_DEPRECATED);

        try {
            $menu = (string)$nav;
        } finally {
            restore_error_handler();
        }

        $this->assertStringContainsString('/home', $menu);
    }

    public function testAddBranchAfterRenderInvalidatesCache()
    {
        $_SERVER['REQUEST_URI'] = '/home';
        $tree = [
            ['name' => 'Pages', 'href' => '/pages']
        ];

        $nav = new Nav($tree);
        // Force the nav to build and cache before the tree is mutated.
        $nav->render();

        $nav->addBranch(['name' => 'Orders', 'href' => '/orders']);

        $this->assertStringContainsString('/orders', $nav->render());
    }

    public function testAddLeafAfterRenderInvalidatesCache()
    {
        $_SERVER['REQUEST_URI'] = '/home';
        $tree = [
            [
                'name'     => 'Pages',
                'href'     => '/pages',
                'children' => [
                    ['name' => 'Add Page', 'href' => 'add']
                ]
            ]
        ];

        $nav = new Nav($tree);
        // Force the nav to build and cache before the tree is mutated.
        $nav->render();

        $nav->addLeaf('Pages', ['name' => 'Remove Page', 'href' => 'remove']);

        $this->assertStringContainsString('/pages/remove', $nav->render());
    }

    public function testCurrentUrlDrivesOnOffClassWithoutRequestUri()
    {
        unset($_SERVER['REQUEST_URI']);

        $tree = [
            ['name' => 'Pages', 'href' => '/pages'],
            ['name' => 'Orders', 'href' => '/orders']
        ];
        $config = [
            'top'        => ['node' => 'nav'],
            'on'         => 'link-on',
            'off'        => 'link-off',
            'currentUrl' => '/pages'
        ];

        $nav = new Nav($tree, $config);
        $menu = (string)$nav;

        $this->assertStringContainsString('href="/pages" class="link-on"', $menu);
        $this->assertStringContainsString('href="/orders" class="link-off"', $menu);
    }

    public function testOnClassMatchesWhenCurrentUrlHasQueryString()
    {
        unset($_SERVER['REQUEST_URI']);

        $tree   = [['name' => 'Pages', 'href' => '/pages']];
        $config = ['on' => 'link-on', 'off' => 'link-off', 'currentUrl' => '/pages?tab=2'];

        $nav  = new Nav($tree, $config);
        $menu = (string)$nav;

        $this->assertStringContainsString('href="/pages" class="link-on"', $menu);
    }

    public function testAbsoluteAndMailtoHrefsAreUsedVerbatim()
    {
        $_SERVER['REQUEST_URI'] = '/home';
        $tree = [
            ['name' => 'External', 'href' => 'https://example.com'],
            ['name' => 'Email',    'href' => 'mailto:test@example.com']
        ];
        // baseUrl must NOT be prefixed onto either of these.
        $config = ['baseUrl' => '/app'];

        $nav = new Nav($tree, $config);
        $menu = (string)$nav;

        $this->assertStringContainsString('href="https://example.com"', $menu);
        $this->assertStringContainsString('href="mailto:test@example.com"', $menu);
    }

    public function testReturnFalseAddsOnclickAttribute()
    {
        $_SERVER['REQUEST_URI'] = '/home';
        $tree = [['name' => 'Logout', 'href' => '#']];

        $nav = new Nav($tree);
        $nav->returnFalse(true);

        $this->assertStringContainsString('onclick="return false;"', (string)$nav);
    }

    public function testRelativeHrefJoinsWithoutDoubleSlashWhenParentEndsInSlash()
    {
        $_SERVER['REQUEST_URI'] = '/home';
        $tree = [
            [
                'name'     => 'Pages',
                'href'     => '/pages/',
                'children' => [
                    ['name' => 'Add Page', 'href' => 'add']
                ]
            ]
        ];

        $nav = new Nav($tree);
        $menu = (string)$nav;

        $this->assertStringContainsString('href="/pages/add"', $menu);
        $this->assertStringNotContainsString('/pages//add', $menu);
    }

    public function testNodeAttributesMergeWithOnOffClass()
    {
        $_SERVER['REQUEST_URI'] = '/home';
        $tree = [
            [
                'name'       => 'Pages',
                'href'       => '/pages',
                'attributes' => ['class' => 'icon-pages', 'data-test' => 'pages-link']
            ]
        ];
        $config = ['on' => 'link-on', 'off' => 'link-off'];

        $nav = new Nav($tree, $config);
        $menu = (string)$nav;

        $this->assertStringContainsString('class="icon-pages link-off"', $menu);
        $this->assertStringContainsString('data-test="pages-link"', $menu);
    }

    public function testNoChildWrapperWhenConfigOmitsChildNode()
    {
        $_SERVER['REQUEST_URI'] = '/home';
        $tree   = [['name' => 'Pages', 'href' => '/pages']];
        $config = ['parent' => ['node' => 'ul']];

        $nav  = new Nav($tree, $config);
        $menu = (string)$nav;

        $this->assertStringContainsString('<ul>', $menu);
        $this->assertStringNotContainsString('<nav>', $menu);
        $this->assertStringContainsString('<a href="/pages">Pages</a>', $menu);
    }

    public function testAcl()
    {
        $_SERVER['REQUEST_URI'] = '/home';

        $reader = new AclRole('reader');
        $editor = new AclRole('editor');
        $page   = new AclResource('page');
        $user   = new AclResource('user');

        $acl = new Acl();
        $acl->addRoles([$reader, $editor]);
        $acl->addResources([$page, $user]);

        $acl->allow('reader', 'page', 'read')
            ->allow('editor', 'page')
            ->allow('editor', 'user');

        $tree = [
            [
                'name'     => 'Pages',
                'href'     => '/pages',
                'children' => [
                    [
                        'name' => 'Add Page',
                        'href' => 'add',
                        'acl'  => [
                            'resource'   => 'page',
                            'permission' => 'add'
                        ]
                    ],
                    [
                        'name' => 'Edit Page',
                        'href' => 'edit',
                        'acl'  => [
                            'resource'   => 'page',
                            'permission' => 'edit'
                        ]
                    ]
                ]
            ],
            [
                'name'     => 'Users',
                'href'     => '/users',
                'acl'  => [
                    'resource'   => 'user'
                ],
                'children' => [
                    [
                        'name' => 'Add User',
                        'href' => 'add',
                        'acl'  => [
                            'resource'   => 'user',
                            'permission' => 'add'
                        ]
                    ],
                    [
                        'name' => 'Edit User',
                        'href' => 'edit',
                        'acl'  => [
                            'resource'   => 'user',
                            'permission' => 'edit'
                        ]
                    ]
                ]
            ]
        ];

        $config = [
            'baseUrl' => '/home',
            'on'  => 'link-on',
            'off' => 'link-off',
            'top' => [
                'id'    => 'main-nav',
                'class' => 'main-nav',
                'attributes' => [
                    'style' => 'display: block;'
                ]
            ],
            'parent' => [
                'id'    => 'top',
                'class' => 'top',
                'attributes' => [
                    'style' => 'display: block;'
                ]
            ],
            'child' => [
                'id'    => 'top',
                'class' => 'top',
                'attributes' => [
                    'style' => 'display: block;'
                ]
            ],
            'indent' => '    '
        ];

        $nav = new Nav($tree, $config);
        $nav->setAcl($acl)
            ->setRole($editor);

        $menu = (string)$nav;
        $this->assertTrue($nav->hasAcl());
        $this->assertInstanceOf('Pop\Acl\Acl', $nav->getAcl());
        $this->assertInstanceOf('Pop\Acl\AclRole', $nav->getRole('editor'));
        $this->assertEquals('    ', $nav->getConfig()['indent']);
        $this->assertEquals('Pages', $nav->getTree()[0]['name']);
        $this->assertStringContainsString('/users/add', $menu);
        $this->assertStringContainsString('/users/edit', $menu);
    }

    /**
     * @return array{0: Acl, 1: AclRole} an Acl with a 'reader' role that has no
     *         explicit 'add' permission on 'page', so under strict evaluation
     *         isAllowedMultiStrict() alone would deny it.
     */
    private function createAclDenyingAddPermission(): array
    {
        $reader = new AclRole('reader');
        $page   = new AclResource('page');

        $acl = new Acl();
        $acl->addRoles([$reader]);
        $acl->addResources([$page]);

        return [$acl, $reader];
    }

    private function newPolicyRole(): AclRole
    {
        return new class('policy-role') extends AclRole {
            use \Pop\Acl\Policy\PolicyTrait;
            public function add($role, $resource = null): bool
            {
                return true;
            }
        };
    }

    /**
     * @param mixed $policy the value to place at 'acl.policy' on 'Add Page'
     */
    private function policyOverrideTree(mixed $policy): array
    {
        return [
            [
                'name'     => 'Pages',
                'href'     => '/pages',
                'children' => [
                    [
                        'name' => 'Add Page',
                        'href' => 'add',
                        'acl'  => [
                            'resource'   => 'page',
                            'permission' => 'add',
                            'policy'     => $policy,
                        ]
                    ],
                    // An unconditioned sibling so the pre-check that decides whether
                    // to descend into 'children' at all doesn't drop the whole subtree
                    // (that pre-check is policy-blind - see NavBuilder::build()).
                    [
                        'name' => 'Edit Page',
                        'href' => 'edit'
                    ]
                ]
            ]
        ];
    }

    public function testAclPolicyOverridesDeniedPermission()
    {
        $_SERVER['REQUEST_URI'] = '/home';
        [$acl, $reader] = $this->createAclDenyingAddPermission();
        $policyRole = $this->newPolicyRole();

        $nav = new Nav($this->policyOverrideTree(fn() => $policyRole));
        $nav->setAcl($acl)
            ->setRole($reader)
            ->setAclStrict(true);

        $this->assertStringContainsString('/pages/add', (string)$nav);
    }

    public function testAclPolicyAcceptsCallableObject()
    {
        $_SERVER['REQUEST_URI'] = '/home';
        [$acl, $reader] = $this->createAclDenyingAddPermission();
        $policyRole = $this->newPolicyRole();
        $policy = new \Pop\Utils\CallableObject(fn() => $policyRole);

        $nav = new Nav($this->policyOverrideTree($policy));
        $nav->setAcl($acl)
            ->setRole($reader)
            ->setAclStrict(true);

        $this->assertStringContainsString('/pages/add', (string)$nav);
    }

    public function testAclPolicyAcceptsArrayCallableForm()
    {
        $_SERVER['REQUEST_URI'] = '/home';
        [$acl, $reader] = $this->createAclDenyingAddPermission();
        $policyRole = $this->newPolicyRole();
        $identity = fn($role) => $role;

        // [callable, ...args] - array_values() of everything after index 0
        // is call_user_func_array()'d against the callable at index 0.
        $nav = new Nav($this->policyOverrideTree([$identity, $policyRole]));
        $nav->setAcl($acl)
            ->setRole($reader)
            ->setAclStrict(true);

        $this->assertStringContainsString('/pages/add', (string)$nav);
    }

    public function testAclPolicyAcceptsRegisteredRoleNameDirectly()
    {
        $_SERVER['REQUEST_URI'] = '/home';

        $policyRole = new class('policy-reader') extends AclRole {
            use \Pop\Acl\Policy\PolicyTrait;
            public function add($role, $resource = null): bool
            {
                return true;
            }
        };
        $page = new AclResource('page');

        $acl = new Acl();
        $acl->addRoles([$policyRole]);
        $acl->addResources([$page]);
        // No explicit 'add' permission granted, so the plain ACL check alone denies this node.

        // A non-callable string is passed straight through to Acl::evaluatePolicy(),
        // which resolves it against the role already registered under that name.
        $nav = new Nav($this->policyOverrideTree('policy-reader'));
        $nav->setAcl($acl)
            ->setRole($policyRole)
            ->setAclStrict(true);

        $this->assertStringContainsString('/pages/add', (string)$nav);
    }

    public function testAclStrict()
    {
        $nav = new Nav();
        $nav->setAclStrict(true);
        $this->assertTrue($nav->isAclStrict());
    }

    public function testAddRoles()
    {
        $reader = new AclRole('reader');
        $editor = new AclRole('editor');

        $nav = new Nav();
        $nav->addRole($reader);
        $nav->addRoles([$editor]);
        $this->assertEquals(2, count($nav->getRoles()));
        $this->assertTrue($nav->hasRoles());
        $this->assertTrue($nav->hasRole('editor'));
        $this->assertTrue($nav->hasRole('reader'));
    }

    public function testAclNotSetExceptionOnNode()
    {
        $this->expectException('Pop\Nav\Exception');

        $tree = [
            [
                'name' => 'Config',
                'href' => '/config',
                'acl'  => ['resource' => 'config']
            ]
        ];

        (new Nav($tree))->render();
    }

    public function testAclNotSetExceptionOnChildPreCheck()
    {
        $this->expectException('Pop\Nav\Exception');

        $tree = [
            [
                'name'     => 'Pages',
                'href'     => '/pages',
                'children' => [
                    [
                        'name' => 'Add Page',
                        'href' => 'add',
                        'acl'  => ['resource' => 'page', 'permission' => 'add']
                    ]
                ]
            ]
        ];

        (new Nav($tree))->render();
    }

    public function testAclNoRolesSetSkipsGatedNodes()
    {
        $_SERVER['REQUEST_URI'] = '/home';

        $page = new AclResource('page');
        $acl  = new Acl();
        $acl->addResources([$page]);

        $tree = [
            [
                'name' => 'Config',
                'href' => '/config',
                'acl'  => ['resource' => 'page']
            ],
            [
                'name'     => 'Pages',
                'href'     => '/pages',
                'children' => [
                    [
                        'name' => 'Add Page',
                        'href' => 'add',
                        'acl'  => ['resource' => 'page', 'permission' => 'add']
                    ]
                ]
            ]
        ];

        $nav = new Nav($tree);
        $nav->setAcl($acl); // No roles set.

        $menu = (string)$nav;

        $this->assertStringNotContainsString('/config', $menu);
        $this->assertStringContainsString('href="/pages"', $menu);
        $this->assertStringNotContainsString('/pages/add', $menu);
    }

    public function testAclAllChildrenDeniedDropsSubtree()
    {
        $_SERVER['REQUEST_URI'] = '/home';

        $reader = new AclRole('reader');
        $page   = new AclResource('page');

        $acl = new Acl();
        $acl->addRoles([$reader]);
        $acl->addResources([$page]);
        $acl->deny('reader', 'page', 'add');

        $tree = [
            [
                'name'     => 'Pages',
                'href'     => '/pages',
                'children' => [
                    [
                        'name' => 'Add Page',
                        'href' => 'add',
                        'acl'  => ['resource' => 'page', 'permission' => 'add']
                    ]
                ]
            ]
        ];

        $nav = new Nav($tree);
        $nav->setAcl($acl)->setRole($reader);

        $menu = (string)$nav;

        $this->assertStringContainsString('href="/pages"', $menu);
        $this->assertStringNotContainsString('/pages/add', $menu);
    }

}
