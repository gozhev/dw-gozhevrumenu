<?php

if (!defined('DOKU_INC')) {
    die();
}
if (!defined('DOKU_LF')) {
    define('DOKU_LF', "\n");
}
if (!defined('DOKU_TAB')) {
    define('DOKU_TAB', "\t");
}
if (!defined('DOKU_PLUGIN')) {
    define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
}

require_once DOKU_PLUGIN.'syntax.php';
require_once DOKU_INC.'inc/search.php';

class syntax_plugin_gozhevrumenu extends DokuWiki_Syntax_Plugin {
    function getType() {
        return 'substition';
    }

    function getPType() {
        return 'block';
    }

    function getSort() {
        return 155;
    }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{gozhevrumenu>[^}]*}}', $mode, 'plugin_gozhevrumenu');
    }

    function handle($match, $state, $pos, Doku_Handler $handler) {
        return array(cleanID(substr($match, strlen('{{gozhevrumenu>'), -strlen('}}'))));
    }

    function render($mode, Doku_Renderer $R, $pass) {
        if ($mode != 'xhtml') {
            return false;
        }

        global $conf;
        global $INFO;

        $R->info['cache'] = false;

        $ns = utf8_encodeFN(str_replace(':', '/', $pass[0]));

        $data = array();
        search($data, $conf['datadir'], array($this, '_search'), array('ns' => $INFO['id']), $ns, 1, 'natural');

        if ($this->getConf('sortByTitle') == true) {
            $this->_sortByTitle($data, 'id');
        } else {
            if ($this->getConf('sort') == 'ascii') {
                uksort($data, array($this, '_cmp'));
            }
        }

        $R->doc .= $this->buildTree($data);

        return true;
    }

    function buildTree($data) {
        $html = "\n";
        $html .= '<div class="tree">'."\n"; // open tree

        global $INFO;

        $level = 0;

        foreach ($data as $item) {
            if ($item['level'] > $level) {
                $html .= '<div class="list">'."\n"; // open list
            } elseif ($item['level'] < $level) {
                $html .= '</div>'."\n"; // close node
                $html .= '</div>'."\n"; // close list
                $html .= '</div>'."\n"; // close node
            } else {
                $html .= '</div>'."\n"; // close node
            }
            $level = $item['level'];

            $link_class = 'link';
            if ($INFO['id'] == $item['id']) {
                $link_class .= ' selected';
            }

            if ($item['type'] === 'f') {
                $html .= '<div class="node leaf">'."\n"; // open node
                $html .= '<a class="'.$link_class.'" href="'.wl($item['id']).'">'.$this->_title($item['id']).'</a>'."\n";
            } else {
                $html .= '<div class="node branch">'."\n"; // open node
                $html .= '<div class="linkbox">'."\n"; //open linkbox
                $html .= '<a class="fold-button" href="#">+</a>'."\n";
                $html .= '<a class="'.$link_class.'" href="'.wl($item['id']).'">'.$this->_title($item['id']).'</a>'."\n";
                $html .= '</div>'."\n"; // close linkbox
            }
        }
        for (; $level > 0; --$level) {
            $html .= '</div>'."\n"; // close node
            $html .= '</div>'."\n"; // close list
        }

        $html .= '</div>'."\n"; // close tree
        return $html;
    }

    function _search(&$data, $base, $file, $type, $lvl, $opts) {
        global $conf;

        $return = true;

        $item = array();

        $id = pathID($file);

        if ($type == 'd' && !(
            preg_match('#^'.$id.'(:|$)#',$opts['ns']) ||
            preg_match('#^'.$id.'(:|$)#',getNS($opts['ns']))
        )) {
            //add but don't recurse
            $return = false;
        } elseif ($type == 'f' && (!empty($opts['nofiles']) || substr($file,-4) != '.txt')) {
            //don't add
            return false;
        }

        if ($type=='d' && $conf['sneaky_index'] && auth_quickaclcheck($id.':') < AUTH_READ) {
            return false;
        }

        if ($type == 'd') {
            // link directories to their start pages
            $exists = false;
            $id = "$id:";
            resolve_pageid('',$id,$exists);
            $this->startpages[$id] = 1;
        } elseif(!empty($this->startpages[$id])) {
            // skip already shown start pages
            return false;
        } elseif(noNS($id) == $conf['start']) {
            // skip the main start page
            return false;
        }

        //check hidden
        if (isHiddenPage($id)) {
            return false;
        }

        //check ACL
        if ($type=='f' && auth_quickaclcheck($id) < AUTH_READ) {
            return false;
        }

        $data[$id] = array(
            'id'    => $id,
            'type'  => $type,
            'level' => $lvl,
            'open'  => $return);

        return $return;
    }

    function _title($id) {
        global $conf;

        if (useHeading('navigation')) {
            $p = p_get_first_heading($id);
        }
        if (!empty($p)) {
            return $p;
        }

        $p = noNS($id);
        if ($p == $conf['start'] || $p == false) {
            $p = noNS(getNS($id));
            if ($p == false) {
                return $conf['start'];
            }
        }
        return $p;
    }

    function _cmp($a, $b) {
        global $conf;
        $a = preg_replace('/'.preg_quote($conf['start'], '/').'$/', '', $a);
        $b = preg_replace('/'.preg_quote($conf['start'], '/').'$/', '', $b);
        $a = str_replace(':', '/', $a);
        $b = str_replace(':', '/', $b);
        return strcmp($a, $b);
    }

    function _sortByTitle(&$array, $key) {
        $sorter = array();
        $ret = array();
        reset($array);
        foreach ($array as $ii => $va) {
            $sorter[$ii] = $this->_title($va[$key]);
        }
        if ($this->getConf('sort') == 'ascii') {
            uksort($sorter, array($this, '_cmp'));
        } else {
            natcasesort($sorter);
        }
        foreach ($sorter as $ii => $va) {
            $ret[$ii] = $array[$ii];
        }
        $array = $ret;
    }
}

// vim: set ts=4 sw=4 et :
