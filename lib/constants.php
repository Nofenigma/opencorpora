<?php
define('SEC_PER_DAY', 24 * 60  * 60);
define('MSEC_PER_DAY', SEC_PER_DAY * 1000);

define('PERM_ADMIN', 1);
define('PERM_DICT', 2);
define('PERM_ADDER', 3);
define('PERM_DISAMB', 4);
define('PERM_CHECK_TOKENS', 5);
define('PERM_MORPH_MODER', 6);
define('PERM_MORPH_SUPERMODER', 7);
define('PERM_SYNTAX', 8);
define('PERM_SYNTAX_MODER', 9);
define('PERM_NE_MODER', 10);

// without '$GLOBALS' this won't work within migration for unknown reasons
// TODO remove with other old permissions-related things
$GLOBALS['PERMISSION_MAP'] = array(
    PERM_ADMIN => 'perm_admin',
    PERM_ADDER => 'perm_adder',
    PERM_DICT => 'perm_dict',
    PERM_DISAMB => 'perm_disamb',
    PERM_CHECK_TOKENS => 'perm_check_tokens',
    PERM_MORPH_MODER => 'perm_check_morph',
    PERM_MORPH_SUPERMODER => 'perm_merge',
    PERM_SYNTAX => 'perm_syntax',
    PERM_SYNTAX_MODER => 'perm_check_syntax',
    PERM_NE_MODER => 'perm_check_ne'
);

define('STATS_ANNOTATOR_DIVERGENCE_TOTAL', 34);

define('MA_POOLS_STATUS_FOUND_CANDIDATES', 1);
define('MA_POOLS_STATUS_NOT_STARTED', 2);
define('MA_POOLS_STATUS_IN_PROGRESS', 3);
define('MA_POOLS_STATUS_ANSWERED', 4);
define('MA_POOLS_STATUS_MODERATION', 5);
define('MA_POOLS_STATUS_MODERATED', 6);
define('MA_POOLS_STATUS_TO_MERGE', 7);
define('MA_POOLS_STATUS_MERGING', 8);
define('MA_POOLS_STATUS_ARCHIVED', 9);

define('MA_TOTAL_TASKS_PLAN', 1333000);

define('NE_STATUS_NOT_STARTED', 0);
define('NE_STATUS_IN_PROGRESS', 1);
define('NE_STATUS_FINISHED', 2);
define('NE_STATUS_MODERATED', 3);

define('NE_ANNOT_TIMEOUT', 24 * 60 * 60);  // 24 hours
define('NE_ANNOTATORS_PER_TEXT', 4);
?>
