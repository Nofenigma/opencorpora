<?php
require('lib/header.php');
require_once('lib/lib_xml.php');
require_once('lib/lib_annot.php');

if (!is_logged()) {
    show_error($config['msg']['notadmin']);
    return;
}

$action = isset($_GET['act']) ? $_GET['act'] : '';
if (game_is_on())
    $smarty->assign('user_rating', get_user_rating($_SESSION['user_id']));

$smarty->assign('active_page','tasks');

switch ($action) {
    case 'annot':
        if (!isset($_GET['pool_id']) || !$_GET['pool_id']) {
            show_error('Wrong pool_id');
            break;
        }

        $pool_size = 5;
        if (isset($_SESSION['options'][3])) {
            switch ($_SESSION['options'][3]) {
                case 2:
                    $pool_size = 10;
                    break;
                case 3:
                    $pool_size = 20;
                    break;
                case 4:
                    $pool_size = 50;
            }
        }

        if ($t = get_annotation_packet((int)$_GET['pool_id'], $pool_size)) {
            $smarty->assign('packet', $t);
            $smarty->display('qa/morph_annot.tpl');
        } else {
            $smarty->assign('next_pool_id', (int)get_next_pool($_SESSION['user_id'], (int)$_GET['pool_id']));
            $smarty->display('qa/morph_annot_thanks.tpl');
        }
        break;
    case 'my':
        if (!isset($_GET['pool_id']) || !$_GET['pool_id']) {
            show_error('Wrong pool_id');
            break;
        }
        if ($t = get_my_answers((int)$_GET['pool_id'], 0)) {
            $smarty->assign('packet', $t);
            $smarty->display('qa/morph_annot.tpl');
        } else {
            show_error("Не нашлось примеров.");
        }
        break;
    default:
        $smarty->assign('available', get_available_tasks($_SESSION['user_id']));
        $smarty->display('qa/tasks.tpl');
}
?>
