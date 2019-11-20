<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
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
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.2.1
 */
class Nav
{

    /**
     * Nav tree
     * @var array
     */
    protected $tree = [];

    /**
     * Nav config
     * @var array
     */
    protected $config = [];

    /**
     * Acl object
     * @var Acl
     */
    protected $acl = null;

    /**
     * AclRole role objects
     * @var array
     */
    protected $roles = [];

    /**
     * Acl strict flag
     * @var boolean
     */
    protected $aclStrict = false;

    /**
     * Indentation
     * @var string
     */
    protected $indent = null;

    /**
     * Base URL
     * @var string
     */
    protected $baseUrl = null;

    /**
     * Nav parent level
     * @var int
     */
    protected $parentLevel = 1;

    /**
     * Nav child level
     * @var int
     */
    protected $childLevel = 1;

    /**
     * Return false flag
     * @var boolean
     */
    protected $returnFalse = false;

    /**
     * Parent nav element
     * @var Child
     */
    protected $nav = null;

    /**
     * Constructor
     *
     * Instantiate the nav object
     *
     * @param  array $tree
     * @param  array $config
     */
    public function __construct(array $tree = null, array $config = null)
    {
        $this->setTree($tree);
        $this->setConfig($config);
    }

    /**
     * Set the return false flag
     *
     * @param  boolean $return
     * @return Nav
     */
    public function returnFalse($return)
    {
        $this->returnFalse = (bool)$return;
        return $this;
    }

    /**
     * Set the nav tree
     *
     * @param  array $tree
     * @return Nav
     */
    public function setTree(array $tree = null)
    {
        $this->tree = (null !== $tree) ? $tree : [];
        return $this;
    }

    /**
     * Add to a nav tree branch
     *
     * @param  array   $branch
     * @param  boolean $prepend
     * @return Nav
     */
    public function addBranch(array $branch, $prepend = false)
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
     * @param  string  $branch
     * @param  array   $leaf
     * @param  int     $pos
     * @param  boolean $prepend
     * @return Nav
     */
    public function addLeaf($branch, array $leaf, $pos = null, $prepend = false)
    {
        $this->tree        = $this->traverseTree($this->tree, $branch, $leaf, $pos, $prepend);
        $this->parentLevel = 1;
        $this->childLevel  = 1;
        return $this;
    }

    /**
     * Set the nav tree
     *
     * @param  array $config
     * @return Nav
     */
    public function setConfig(array $config = null)
    {
        if (null === $config) {
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
     * @param  Acl $acl
     * @return Nav
     */
    public function setAcl(Acl $acl = null)
    {
        $this->acl = $acl;
        return $this;
    }

    /**
     * Set a AclRole object (alias method)
     *
     * @param  AclRole $role
     * @return Nav
     */
    public function setRole(AclRole $role = null)
    {
        $this->roles[$role->getName()] = $role;
        return $this;
    }

    /**
     * Add a AclRole object
     *
     * @param  AclRole $role
     * @return Nav
     */
    public function addRole(AclRole $role = null)
    {
        return $this->setRole($role);
    }

    /**
     * Add AclRole objects
     *
     * @param  array $roles
     * @return Nav
     */
    public function addRoles(array $roles)
    {
        foreach ($roles as $role) {
            $this->setRole($role);
        }

        return $this;
    }

    /**
     * Set the Acl object as strict evaluation
     *
     * @param  boolean $strict
     * @return Nav
     */
    public function setAclStrict($strict)
    {
        $this->aclStrict = (bool)$strict;
        return $this;
    }

    /**
     * Set the indent
     *
     * @param  string $indent
     * @return Nav
     */
    public function setIndent($indent)
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
    public function setBaseUrl($baseUrl)
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
    public function setParentLevel($level)
    {
        $this->parentLevel = (int)$level;
        return $this;
    }

    /**
     * Increment parent level
     *
     * @return Nav
     */
    public function incrementParentLevel()
    {
        $this->parentLevel++;
        return $this;
    }

    /**
     * Decrement parent level
     *
     * @return Nav
     */
    public function decrementParentLevel()
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
    public function setChildLevel($level)
    {
        $this->childLevel = (int)$level;
        return $this;
    }

    /**
     * Increment child level
     *
     * @return Nav
     */
    public function incrementChildLevel()
    {
        $this->childLevel++;
        return $this;
    }

    /**
     * Decrement child level
     *
     * @return Nav
     */
    public function decrementChildLevel()
    {
        $this->childLevel--;
        return $this;
    }

    /**
     * Set the return false flag
     *
     * @return boolean
     */
    public function isReturnFalse()
    {
        return $this->returnFalse;
    }

    /**
     * Determine if the Acl object is set as strict evaluation
     *
     * @return boolean
     */
    public function isAclStrict()
    {
        return $this->aclStrict;
    }

    /**
     * Get the nav tree
     *
     * @return array
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * Get the config
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get the Acl object
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->acl;
    }

    /**
     * Determine if there are roles
     *
     * @return boolean
     */
    public function hasRoles()
    {
        return (count($this->roles) > 0);
    }

    /**
     * Determine if there is a certain role
     *
     * @param  string $name
     * @return boolean
     */
    public function hasRole($name)
    {
        return (isset($this->roles[$name]));
    }

    /**
     * Get the AclRole objects
     *
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Get a AclRole object
     *
     * @param  string $name
     * @return AclRole
     */
    public function getRole($name)
    {
        return (isset($this->roles[$name])) ? $this->roles[$name] : null;
    }

    /**
     * Get the indent
     *
     * @return string
     */
    public function getIndent()
    {
        return $this->indent;
    }

    /**
     * Get the base URL
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Get parent level
     *
     * @return int
     */
    public function getParentLevel()
    {
        return $this->parentLevel;
    }

    /**
     * Get child level
     *
     * @return int
     */
    public function getChildLevel()
    {
        return $this->childLevel;
    }

    /**
     * Get the nav object
     *
     * @return Child
     */
    public function getNav()
    {
        if (null === $this->nav) {
            $this->nav = NavBuilder::build($this, $this->tree);
        }
        return $this->nav;
    }

    /**
     * Get the nav object (alias)
     *
     * @return Child
     */
    public function nav()
    {
        return $this->getNav();
    }

    /**
     * Build the nav object
     *
     * @return Nav
     */
    public function build()
    {
        if (null === $this->nav) {
            $this->nav = NavBuilder::build($this, $this->tree);
        }
        return $this;
    }

    /**
     * Re-build the nav object
     *
     * @return Nav
     */
    public function rebuild()
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
    public function render()
    {
        if (null === $this->nav) {
            $this->nav = NavBuilder::build($this, $this->tree);
        }

        $result = ($this->nav->hasChildren()) ? $this->nav->render() : '';
        return $result;
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
     * @param  array   $tree
     * @param  string  $branch
     * @param  array   $newLeaf
     * @param  int     $pos
     * @param  boolean $prepend
     * @param  int     $depth
     * @return array
     */
    protected function traverseTree($tree, $branch, $newLeaf, $pos = null, $prepend = false, $depth = 0)
    {
        $t = [];
        foreach ($tree as $leaf) {
            if (((null === $pos) || ($pos == $depth)) && ($leaf['name'] == $branch)) {
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