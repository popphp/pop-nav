<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Nav;

use Pop\Acl\Acl;
use Pop\Acl\AclRole;
use Pop\Dom\Child;

/**
 * Nav class
 *
 * @category   Pop
 * @package    Pop\Nav
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.3.0
 */
class Nav
{

    /**
     * Nav tree
     * @var array
     */
    protected array $tree = [];

    /**
     * Nav config
     * @var array
     */
    protected array $config = [];

    /**
     * Acl object
     * @var ?Acl
     */
    protected ?Acl $acl = null;

    /**
     * AclRole role objects
     * @var array
     */
    protected array $roles = [];

    /**
     * Acl strict flag
     * @var bool
     */
    protected bool $aclStrict = false;

    /**
     * Indentation
     * @var ?string
     */
    protected ?string $indent = null;

    /**
     * Base URL
     * @var ?string
     */
    protected ?string $baseUrl = null;

    /**
     * Nav parent level
     * @var int
     */
    protected int $parentLevel = 1;

    /**
     * Nav child level
     * @var int
     */
    protected int $childLevel = 1;

    /**
     * Return false flag
     * @var bool
     */
    protected bool $returnFalse = false;

    /**
     * Parent nav element
     * @var ?Child
     */
    protected ?Child $nav = null;

    /**
     * Constructor
     *
     * Instantiate the nav object
     *
     * @param  ?array $tree
     * @param  ?array $config
     */
    public function __construct(?array $tree = null, ?array $config = null)
    {
        $this->setTree($tree);
        $this->setConfig($config);
    }

    /**
     * Set the return false flag
     *
     * @param  bool $return
     * @return Nav
     */
    public function returnFalse(bool $return): Nav
    {
        $this->returnFalse = $return;
        return $this;
    }

    /**
     * Set the nav tree
     *
     * @param  ?array $tree
     * @return Nav
     */
    public function setTree(?array $tree = null): Nav
    {
        $this->tree = ($tree !== null) ? $tree : [];
        return $this;
    }

    /**
     * Add to a nav tree branch
     *
     * @param  array   $branch
     * @param  bool $prepend
     * @return Nav
     */
    public function addBranch(array $branch, bool $prepend = false): Nav
    {
        if (isset($branch['name'])) {
            $branch = [$branch];
        }
        $this->tree = ($prepend) ? array_merge($branch, $this->tree) : array_merge($this->tree, $branch);
        return $this;
    }

    /**
     * Add to a leaf to nav tree branch
     *
     * @param  string $branch
     * @param  array  $leaf
     * @param  ?int   $pos
     * @param  bool   $prepend
     * @return Nav
     */
    public function addLeaf(string $branch, array $leaf, ?int $pos = null, bool $prepend = false): Nav
    {
        $this->tree        = $this->traverseTree($this->tree, $branch, $leaf, $pos, $prepend);
        $this->parentLevel = 1;
        $this->childLevel  = 1;
        return $this;
    }

    /**
     * Set the nav tree
     *
     * @param  ?array $config
     * @return Nav
     */
    public function setConfig(?array $config = null): Nav
    {
        if ($config === null) {
            $this->config = [
                'top'    => [
                    'node'  => 'nav'
                ],
                'parent' => [
                    'node'  => 'nav'
                ],
                'child' => [
                    'node'  => 'nav'
                ]
            ];
        } else {
            $this->config = $config;
        }

        if (isset($config['indent'])) {
            $this->setIndent($config['indent']);
        }

        if (isset($config['baseUrl'])) {
            $this->setBaseUrl($config['baseUrl']);
        }

        return $this;
    }

    /**
     * Set the Acl object
     *
     * @param  ?Acl $acl
     * @return Nav
     */
    public function setAcl(?Acl $acl = null): Nav
    {
        $this->acl = $acl;
        return $this;
    }

    /**
     * Set a AclRole object (alias method)
     *
     * @param  ?AclRole $role
     * @return Nav
     */
    public function setRole(?AclRole $role = null): Nav
    {
        $this->roles[$role->getName()] = $role;
        return $this;
    }

    /**
     * Add a AclRole object
     *
     * @param  ?AclRole $role
     * @return Nav
     */
    public function addRole(?AclRole $role = null): Nav
    {
        return $this->setRole($role);
    }

    /**
     * Add AclRole objects
     *
     * @param  array $roles
     * @return Nav
     */
    public function addRoles(array $roles): Nav
    {
        foreach ($roles as $role) {
            $this->setRole($role);
        }

        return $this;
    }

    /**
     * Set the Acl object as strict evaluation
     *
     * @param  bool $strict
     * @return Nav
     */
    public function setAclStrict(bool $strict): Nav
    {
        $this->aclStrict = $strict;
        return $this;
    }

    /**
     * Set the indent
     *
     * @param  string $indent
     * @return Nav
     */
    public function setIndent(string $indent): Nav
    {
        $this->indent = $indent;
        return $this;
    }

    /**
     * Set the base URL
     *
     * @param  string $baseUrl
     * @return Nav
     */
    public function setBaseUrl(string $baseUrl): Nav
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * Set parent level
     *
     * @param  int $level
     * @return Nav
     */
    public function setParentLevel(int $level): Nav
    {
        $this->parentLevel = $level;
        return $this;
    }

    /**
     * Increment parent level
     *
     * @return Nav
     */
    public function incrementParentLevel(): Nav
    {
        $this->parentLevel++;
        return $this;
    }

    /**
     * Decrement parent level
     *
     * @return Nav
     */
    public function decrementParentLevel(): Nav
    {
        $this->parentLevel--;
        return $this;
    }

    /**
     * Set child level
     *
     * @param  int $level
     * @return Nav
     */
    public function setChildLevel(int $level): Nav
    {
        $this->childLevel = $level;
        return $this;
    }

    /**
     * Increment child level
     *
     * @return Nav
     */
    public function incrementChildLevel(): Nav
    {
        $this->childLevel++;
        return $this;
    }

    /**
     * Decrement child level
     *
     * @return Nav
     */
    public function decrementChildLevel(): Nav
    {
        $this->childLevel--;
        return $this;
    }

    /**
     * Set the return false flag
     *
     * @return bool
     */
    public function isReturnFalse(): bool
    {
        return $this->returnFalse;
    }

    /**
     * Determine if the Acl object is set as strict evaluation
     *
     * @return bool
     */
    public function isAclStrict(): bool
    {
        return $this->aclStrict;
    }

    /**
     * Get the nav tree
     *
     * @return array
     */
    public function getTree(): array
    {
        return $this->tree;
    }

    /**
     * Get the config
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get the Acl object
     *
     * @return Acl|null
     */
    public function getAcl(): Acl|null
    {
        return $this->acl;
    }

    /**
     * Determine if there are roles
     *
     * @return bool
     */
    public function hasRoles(): bool
    {
        return (count($this->roles) > 0);
    }

    /**
     * Determine if there is a certain role
     *
     * @param  string $name
     * @return bool
     */
    public function hasRole(string $name): bool
    {
        return (isset($this->roles[$name]));
    }

    /**
     * Get the AclRole objects
     *
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * Get a AclRole object
     *
     * @param  string $name
     * @return AclRole|null
     */
    public function getRole(string $name): AclRole|null
    {
        return $this->roles[$name] ?? null;
    }

    /**
     * Get the indent
     *
     * @return string|null
     */
    public function getIndent(): string|null
    {
        return $this->indent;
    }

    /**
     * Get the base URL
     *
     * @return string|null
     */
    public function getBaseUrl(): string|null
    {
        return $this->baseUrl;
    }

    /**
     * Get parent level
     *
     * @return int
     */
    public function getParentLevel(): int
    {
        return $this->parentLevel;
    }

    /**
     * Get child level
     *
     * @return int
     */
    public function getChildLevel(): int
    {
        return $this->childLevel;
    }

    /**
     * Get the nav object
     *
     * @return Child
     */
    public function getNav(): Child
    {
        if ($this->nav === null) {
            $this->nav = NavBuilder::build($this, $this->tree);
        }
        return $this->nav;
    }

    /**
     * Get the nav object (alias)
     *
     * @return Child
     */
    public function nav(): Child
    {
        return $this->getNav();
    }

    /**
     * Build the nav object
     *
     * @return Nav
     */
    public function build(): Nav
    {
        if ($this->nav === null) {
            $this->nav = NavBuilder::build($this, $this->tree);
        }
        return $this;
    }

    /**
     * Re-build the nav object
     *
     * @return Nav
     */
    public function rebuild(): Nav
    {
        $this->parentLevel = 1;
        $this->childLevel  = 1;
        $this->nav         = NavBuilder::build($this, $this->tree);
        return $this;
    }

    /**
     * Render the nav object
     *
     * @return string
     */
    public function render(): string
    {
        if ($this->nav === null) {
            $this->nav = NavBuilder::build($this, $this->tree);
        }

        return ($this->nav->hasChildren()) ? $this->nav->render() : '';
    }

    /**
     * Render Nav object to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Traverse tree to insert new leaf
     *
     * @param  array  $tree
     * @param  string $branch
     * @param  array  $newLeaf
     * @param  ?int   $pos
     * @param  bool   $prepend
     * @param  int    $depth
     * @return array
     */
    protected function traverseTree(
        array $tree, string $branch, array $newLeaf, ?int $pos = null, bool $prepend = false, int $depth = 0
    ): array
    {
        $t = [];
        foreach ($tree as $leaf) {
            if ((($pos === null) || ($pos == $depth)) && ($leaf['name'] == $branch)) {
                if (isset($leaf['children'])) {
                    $leaf['children'] = ($prepend) ?
                        array_merge([$newLeaf], $leaf['children']) : array_merge($leaf['children'], [$newLeaf]);
                } else {
                    $leaf['children'] = [$newLeaf];
                }
            }
            if (isset($leaf['children'])) {
                $leaf['children'] = $this->traverseTree($leaf['children'], $branch, $newLeaf, $pos, $prepend, ($depth + 1));
            }
            $t[] = $leaf;
        }

        return $t;
    }

}