<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Nav;

use Pop\Dom\Child;

/**
 * Nav builder class
 *
 * @category   Pop
 * @package    Pop\Nav
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.2.0
 */
class NavBuilder
{

    /**
     * Build the navigation from the nav object
     *
     * @param  Nav    $navObject
     * @param  array  $tree
     * @param  int    $depth
     * @param  string $parentHref
     * @throws Exception
     * @return Child
     */
    public static function build(Nav $navObject, array $tree, $depth = 1, $parentHref = null)
    {
        $config        = $navObject->getConfig();
        [$nav, $child] = self::prepare($navObject, $config, $depth);

        $navObject->incrementParentLevel();
        $depth++;

        // Recursively loop through the nodes
        foreach ($tree as $node) {
            $allowed = true;
            if (isset($node['acl'])) {
                if (null === $navObject->getAcl()) {
                    throw new Exception('The access control object is not set.');
                }
                if (empty($navObject->getRoles())) {
                    $allowed = false;
                } else {
                    $resource   = (isset($node['acl']['resource'])) ? $node['acl']['resource'] : null;
                    $permission = (isset($node['acl']['permission'])) ? $node['acl']['permission'] : null;
                    $allowed    = ($navObject->isAclStrict()) ?
                        $navObject->getAcl()->isAllowedManyStrict($navObject->getRoles(), $resource, $permission) :
                        $navObject->getAcl()->isAllowedMany($navObject->getRoles(), $resource, $permission);
                }
            }
            if (($allowed) && isset($node['name']) && isset($node['href'])) {
                // Create child node and child link node
                $a = new Child('a', $node['name']);

                if ((substr($node['href'], 0, 1) == '#') || (substr($node['href'], -1) == '#') ||
                    (substr($node['href'], 0, 4) == 'http') || (substr($node['href'], 0, 7) == 'mailto:')) {
                    $href = $node['href'];
                } else if (substr($node['href'], 0, 1) == '/') {
                    $href = $navObject->getBaseUrl() . $node['href'];
                } else {
                    if (substr($parentHref, -1) == '/') {
                        $href = $parentHref . $node['href'];
                    } else {
                        $href = $parentHref . '/' . $node['href'];
                    }
                }

                $a->setAttribute('href', $href);

                if (($navObject->isReturnFalse()) && (($href == '#') || (substr($href, -1) == '#'))) {
                    $a->setAttribute('onclick', 'return false;');
                }
                $url = $_SERVER['REQUEST_URI'];
                if (strpos($url, '?') !== false) {
                    $url = substr($url, strpos($url, '?'));
                }

                $linkClass = null;
                if ($href == $url) {
                    if (isset($config['on'])) {
                        $linkClass = $config['on'];
                    }
                } else {
                    if (isset($config['off'])) {
                        $linkClass = $config['off'];
                    }
                }

                // If the node has any attributes
                if (isset($node['attributes'])) {
                    foreach ($node['attributes'] as $attrib => $value) {
                        $value = (($attrib == 'class') && (null !== $linkClass)) ? $value . ' ' . $linkClass : $value;
                        $a->setAttribute($attrib, $value);
                    }
                } else if (null !== $linkClass) {
                    $a->setAttribute('class', $linkClass);
                }

                if (null !== $child) {
                    $navChild = new Child($child);

                    // Set child attributes if they exist
                    if (isset($config['child']) && isset($config['child']['id'])) {
                        $navChild->setAttribute('id', $config['child']['id'] . '-' . $navObject->getChildLevel());
                    }
                    if (isset($config['child']) && isset($config['child']['class'])) {
                        $navChild->setAttribute('class', $config['child']['class'] . '-' . ($depth - 1));
                    }
                    if (isset($config['child']['attributes'])) {
                        foreach ($config['child']['attributes'] as $attrib => $value) {
                            $navChild->setAttribute($attrib, $value);
                        }
                    }

                    // Add link node
                    $navChild->addChild($a);
                    $navObject->incrementChildLevel();

                    // If there are children, loop through and add them
                    if (isset($node['children']) && is_array($node['children']) && (count($node['children']) > 0)) {
                        $childrenAllowed = true;
                        // Check if the children are allowed

                        $i = 0;
                        foreach ($node['children'] as $nodeChild) {
                            if (isset($nodeChild['acl'])) {
                                if (null === $navObject->getAcl()) {
                                    throw new Exception('The access control object is not set.');
                                }
                                if (empty($navObject->getRoles())) {
                                    $childrenAllowed = false;
                                } else {
                                    $resource   = (isset($nodeChild['acl']['resource'])) ? $nodeChild['acl']['resource'] : null;
                                    $permission = (isset($nodeChild['acl']['permission'])) ? $nodeChild['acl']['permission'] : null;
                                    $method     = ($navObject->isAclStrict()) ? 'isAllowedManyStrict' : 'isAllowedMany';
                                    if (!($navObject->getAcl()->{$method}($navObject->getRoles(), $resource, $permission))) {
                                        $i++;
                                    }
                                }
                            }
                        }
                        if ($i == count($node['children'])) {
                            $childrenAllowed = false;
                        }
                        if ($childrenAllowed) {
                            $nextChild = self::build($navObject, $node['children'], $depth, $href);
                            if (($nextChild->hasChildren()) || (null !== $nextChild->getNodeValue())) {
                                $navChild->addChild($nextChild);
                            }
                        }
                    }
                    // Add child node
                    $nav->addChild($navChild);
                } else {
                    $nav->addChild($a);
                }
            }
        }

        return $nav;
    }

    /**
     * Prepare nav node
     *
     * @param  Nav   $navObject
     * @param  array $config
     * @param  int   $depth
     * @return array
     */
    public static function prepare(Nav $navObject, $config, $depth = 1)
    {
        // Create overriding top level parent, if set
        if (($depth == 1) && isset($config['top'])) {
            $parent = (isset($config['top']) && isset($config['top']['node'])) ? $config['top']['node'] : 'nav';
            $child  = null;
            if (isset($config['child']) && isset($config['child']['node'])) {
                $child = $config['child']['node'];
            } else if ($parent == 'nav') {
                $child = 'nav';
            }

            // Create parent node
            $nav = new Child($parent);
            if (null !== $navObject->getIndent()) {
                $nav->setIndent(str_repeat($navObject->getIndent(), $depth));
            }

            // Set top attributes if they exist
            if (isset($config['top']) && isset($config['top']['id'])) {
                $nav->setAttribute('id', $config['top']['id']);
            }
            if (isset($config['top']) && isset($config['top']['class'])) {
                $nav->setAttribute('class', $config['top']['class']);
            }
            if (isset($config['top']['attributes'])) {
                foreach ($config['top']['attributes'] as $attrib => $value) {
                    $nav->setAttribute($attrib, $value);
                }
            }
        } else {
            // Set up parent/child node names
            $parent = (isset($config['parent']) && isset($config['parent']['node'])) ? $config['parent']['node'] : 'nav';
            $child  = null;
            if (isset($config['child']) && isset($config['child']['node'])) {
                $child = $config['child']['node'];
            } else if ($parent == 'nav') {
                $child = 'nav';
            }

            // Create parent node
            $nav = new Child($parent);
            if (null !== $navObject->getIndent()) {
                $nav->setIndent(str_repeat($navObject->getIndent(), $depth));
            }

            // Set parent attributes if they exist
            if (isset($config['parent']) && isset($config['parent']['id'])) {
                $nav->setAttribute('id', $config['parent']['id'] . '-' . $navObject->getParentLevel());
            }
            if (isset($config['parent']) && isset($config['parent']['class'])) {
                $nav->setAttribute('class', $config['parent']['class'] . '-' . $depth);
            }
            if (isset($config['parent']['attributes'])) {
                foreach ($config['parent']['attributes'] as $attrib => $value) {
                    $nav->setAttribute($attrib, $value);
                }
            }
        }

        return [$nav, $child];
    }

}