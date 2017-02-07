<?php

namespace Pop\Nav\Test;

use Pop\Acl\Acl;
use Pop\Acl\AclRole;
use Pop\Acl\AclResource;
use Pop\Nav\Nav;

class NavTest extends \PHPUnit_Framework_TestCase
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

        $this->assertContains('/users/add', (string)$nav);
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
        $nav->build();
        $nav->rebuild();
        $this->assertContains('/pages/remove', (string)$nav);

        $nav = new Nav($tree);
        $this->assertInstanceOf('Pop\Dom\Child', $nav->nav());
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

        ob_start();
        $nav->render(false);
        $result = ob_get_clean();

        $menu = (string)$nav;
        $this->assertInstanceOf('Pop\Acl\Acl', $nav->getAcl());
        $this->assertInstanceOf('Pop\Acl\AclRole', $nav->getRole());
        $this->assertEquals('    ', $nav->getConfig()['indent']);
        $this->assertEquals('Pages', $nav->getTree()[0]['name']);
        $this->assertContains('/users/add', $menu);
        $this->assertContains('/users/edit', $result);
    }

    public function testAclNotSetException()
    {
        $this->expectException('Pop\Nav\Exception');
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
        $menu = $nav->render(true);
    }

}
