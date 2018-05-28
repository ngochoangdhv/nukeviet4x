<?php

/**
 * @Project NUKEVIET 4.x
 * @Author VINADES.,JSC <contact@vinades.vn>
 * @Copyright (C) 2014 VINADES ., JSC. All rights reserved
 * @License GNU/GPL version 2 or any later version
 * @Createdate Jan 17, 2011 11:34:27 AM
 */
if (!defined('NV_MAINFILE')) {
    die('Stop!!!');
}

if (!nv_function_exists('nv_block_menu_site')) {

    function nv_block_config_menu_site($module, $data_block, $nv_Lang)
    {
        global $nv_Cache;
        //print_r($data_block); die("ok");
        $html = '';
        $html .= "<div class=\"form-group\">";
        $html .= "	<label class=\"control-label col-sm-6\">" . $nv_Lang->getBlock('menu') . ":</label>";
        $html .= "	<div class=\"col-sm-9\"><select name=\"menuid\" class=\"form-control\">\n";

        $sql = "SELECT * FROM " . NV_PREFIXLANG . "_menu ORDER BY id DESC";
        // Module menu của hệ thống không ảo hóa, do đó chỉ định cache trực tiếp vào module tránh lỗi khi gọi file từ giao diện
        $list = $nv_Cache->db($sql, 'id', 'menu');
        foreach ($list as $l) {
            $sel = ($data_block['menuid'] == $l['id']) ? ' selected' : '';
            $html .= "<option value=\"" . $l['id'] . "\" " . $sel . ">" . $l['title'] . "</option>\n";
        }

        $html .= "	</select></div>\n";
        $html .= "</div>";


        return $html;
    }


    function nv_block_config_menu_site_submit($module, $nv_Lang)
    {
        global $nv_Request;
        $return = array();
        $return['error'] = array();
        $return['config'] = array();
        $return['config']['menuid'] = $nv_Request->get_int('menuid', 'post', 0);

        return $return;
    }


    /**
     * nv_block_menu_site()
     *
     * @param mixed $block_config
     * @return
     */







    function nv_block_menu_site($block_config)
    {

       global $db, $global_config, $nv_Lang;
         $list_cats = array();
        $sql = 'SELECT id, parentid, title, link, icon, note, subitem, groups_view, module_name, op, target, css, active_type FROM ' . NV_PREFIXLANG . '_menu_rows WHERE status=1 AND mid = ' . $block_config['menuid'] . ' ORDER BY weight ASC';
        $stmt = $db->query($sql);
        $list= $stmt->fetchAll();
        foreach ($list as $row) {
            if (nv_user_in_groups($row['groups_view'])) {
                if ($row['link'] != '' and $row['link'] != '#') {
                    $row['link'] = nv_url_rewrite(nv_unhtmlspecialchars($row['link']), true);
                    switch ($row['target']) {
                        case 1:
                            $row['target'] = '';
                            break;
                        case 3:
                            $row['target'] = ' onclick="window.open(this.href,\'targetWindow\',\'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,\');return false;"';
                            break;
                        default:
                            $row['target'] = ' onclick="this.target=\'_blank\'"';
                    }
                } else {
                    $row['target'] = '';
                }
                if (!empty($row['icon']) and file_exists(NV_UPLOADS_REAL_DIR . '/menu/' . $row['icon'])) {
                    $row['icon'] = NV_BASE_SITEURL . NV_UPLOADS_DIR . '/menu/' . $row['icon'];
                } else {
                    $row['icon'] = '';
                }
                $list_cats[$row['parentid']][$row['id']] = array(
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'title_trim' => nv_clean60($row['title'], $block_config['title_length']),
                    'target' => $row['target'],
                    'note' => empty($row['note']) ? $row['title'] : $row['note'],
                    'link' => nv_url_rewrite(nv_unhtmlspecialchars($row['link']), true),
                    'icon' => (empty($row['icon'])) ? '' : NV_BASE_SITEURL . NV_UPLOADS_DIR . '/menu/' . $row['icon'],
                    'css' => $row['css'],
                    'active_type' => $row['active_type']
                );

            }
        }
        if (file_exists(NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/blocks/global.menu_site.tpl')) {
            $block_theme = $global_config['module_theme'];
        } elseif (file_exists(NV_ROOTDIR . '/themes/' . $global_config['site_theme'] . '/blocks/global.menu_site.tpl')) {
            $block_theme = $global_config['site_theme'];
        } else {
            $block_theme = 'default';
        }

        $tpl = new \NukeViet\Template\Smarty();
        $tpl->setTemplateDir(NV_ROOTDIR . '/themes/' . $block_theme . '/blocks');
        $tpl->assign('NV_BASE_TEMPLATE', NV_BASE_SITEURL . 'themes/' . $block_theme);
        $size = @getimagesize(NV_ROOTDIR . '/' . $global_config['site_logo']);
        $logo = preg_replace('/\.[a-z]+$/i', '.svg', $global_config['site_logo']);
        if (!file_exists(NV_ROOTDIR . '/' . $logo)) {
            $logo = $global_config['site_logo'];
        }
        $_logo = array(
            'src' => NV_BASE_SITEURL . $logo,
            'link' => NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA,
            'width' => $size[0],
            'height' => $size[1]
        );
        $tpl->assign('logo', $_logo);
        if (!empty($list_cats)) {
            $menu_rest = array();
            $title_menu_rest = array();
            foreach ($list_cats[0] as $id => $item) {
                if($item['css']=='tabs'){

                    $menutab = array();
                    $menutab = nv_get_bootstrap_submenu($id, $list_cats);
                    $menu_tabs = array();
                    $menu_tabs = nv_get_bootstrap_submenu1($id, $list_cats);
                    $tpl->assign('titletab',$item);
                    $tpl->assign('menutab',$menutab);
                    $tpl->assign('menu_tab',$menu_tabs);
                } elseif($item['css']=='drop'){

                    $menudrop = array();
                    $menudrop = nv_get_bootstrap_submenu($id, $list_cats);
                    $menu_drop = array();
                    $menu_drop = nv_get_bootstrap_submenu1($id, $list_cats);
                    $tpl->assign('titledrop',$item);
                    $tpl->assign('menudrop',$menudrop);
                    $tpl->assign('menu_drop',$menu_drop);

                }elseif($item['css']=='animate'){
                    $menuanimate = array();
                    $menuanimate = nv_get_bootstrap_submenu($id, $list_cats);
                    $menu_animate = array();
                    $menu_animate = nv_get_bootstrap_submenu1($id, $list_cats);
                    $tpl->assign('titleanimate',$item);
                    $tpl->assign('menuanimate',$menuanimate);
                    $tpl->assign('menu_animate',$menu_animate);
                }
                else {
                    foreach ($list_cats[0] as $id1 => $item1) {
                        if( $id1 == $id){
                            $title_menu_rest[] = $item1['title'];
                            $menurest = array();
                            $menurest = nv_get_bootstrap_submenu($id1, $list_cats);
                            $menu_rest[] = $menurest;
                        }
                    }

                }

            }

            $tpl->assign('title_menu_rest',$title_menu_rest);
            $tpl->assign('menu_rest',$menu_rest);
        }
        return $tpl->fetch('global.menu_site.tpl');
    }
}

function nv_get_bootstrap_submenu($id, $array_menu)
{
    if (!empty($array_menu[$id])) {
            $result1 = array();
            foreach ($array_menu[$id] as $sid => $smenu) {
                    $result1[] = $smenu;
         }
    }
    return $result1;

}

function nv_get_bootstrap_submenu1($id, $array_menu)
{

    if (!empty($array_menu[$id])) {
        $result2 = array();
        foreach ($array_menu[$id] as $sid => $smenu) {

            if (isset($array_menu[$sid])) {
                $result = array();
                foreach ($array_menu[$sid] as $ssid => $ssmenu) {

                    $result[]= $ssmenu;
                }
                $result2[]= $result;


            }



        }
        return $result2;

    }


}

if (defined('NV_SYSTEM')) {
    $content = nv_block_menu_site($block_config);
}

/**
 * nv_menu_check_current()
 *
 * @param mixed $url
 * @param integer $type
 * @return
 *
 */
function nv_menu_check_current($url, $type = 0)
{
    global $home, $client_info, $global_config;

    if ($client_info['selfurl'] == $url) {
        return true;
    }
    // Chinh xac tuyet doi

    $_curr_url = NV_BASE_SITEURL . str_replace($global_config['site_url'] . '/', '', $client_info['selfurl']);
    $_url = nv_url_rewrite($url, true);

    if ($home and ($_url == nv_url_rewrite(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA) or $_url == NV_BASE_SITEURL . 'index.php' or $_url == NV_BASE_SITEURL)) {
        return true;
    } elseif ($_url != NV_BASE_SITEURL) {
        if ($type == 2) {
            if (preg_match('#' . preg_quote($_url, '#') . '#', $_curr_url)) {
                return true;
            }
            return false;
        } elseif ($type == 1) {
            if (preg_match('#^' . preg_quote($_url, '#') . '#', $_curr_url)) {
                return true;
            }
            return false;
        } elseif ($_curr_url == $_url) {
            return true;
        }
    }

    return false;
}