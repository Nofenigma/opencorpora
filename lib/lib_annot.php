<?php
function get_sentence($sent_id) {
    $r = sql_fetch_array(sql_query("SELECT `check_status`, source FROM sentences WHERE sent_id=$sent_id LIMIT 1"));
    $out = array(
        'id' => $sent_id,
        'status' => $r['check_status'],
        'source' => $r['source']
    );
    //counting comments
    $r = sql_fetch_array(sql_query("SELECT COUNT(comment_id) comm_cnt FROM sentence_comments WHERE sent_id=$sent_id"));
    $out['comment_count'] = $r['comm_cnt'];
    //looking for source name
    $r = sql_fetch_array(sql_query("
        SELECT book_id
        FROM books
        WHERE book_id = (
            SELECT book_id
            FROM paragraphs
            WHERE par_id = (
                SELECT par_id
                FROM sentences
                WHERE sent_id=$sent_id
                LIMIT 1
            )
        )
    "));
    $out['book_id'] = $book_id = $r['book_id'];
    $r = sql_fetch_array(sql_query("
        SELECT book_name
        FROM books
        WHERE book_id = (
            SELECT parent_id
            FROM books
            WHERE book_id = $book_id
            LIMIT 1
        )
    "));
    $out['book_name'] = $r['book_name'];
    //looking for url
    $res = sql_query("
        SELECT tag_name
        FROM book_tags
        WHERE book_id = ".$book_id
    );
    while ($r = sql_fetch_array($res)) {
        if (substr($r['tag_name'], 0, 4) == 'url:') {
            $out['url'] = substr($r['tag_name'], 4);
            break;
        }
    }
    $tf_text = array();
    $res = sql_query("SELECT tf_id, tf_text, dict_updated FROM text_forms WHERE sent_id=$sent_id ORDER BY `pos`");
    $j = 0; //token position, for further highlighting
    $gram_descr = array();  //associative array to keep info about grammemes
    while($r = sql_fetch_array($res)) {
        array_push ($tf_text, '<span id="src_token_'.($j++).'">'.$r['tf_text'].'</span>');
        $rev = sql_fetch_array(sql_query("SELECT rev_text FROM tf_revisions WHERE tf_id=".$r['tf_id']." ORDER BY rev_id DESC LIMIT 1"));
        $arr = xml2ary($rev['rev_text']);

        $out['tokens'][] = array(
            'tf_id'        => $r['tf_id'],
            'tf_text'      => $r['tf_text'],
            'dict_updated' => $r['dict_updated'],
            'variants'     => get_morph_vars($arr['tfr']['_c']['v'], $gram_descr)
        );
    }
    $out['fulltext'] = typo_spaces(implode(' ', $tf_text), 1);
    return $out;
}
function get_morph_vars($xml_arr, &$gram_descr) {
    if (isset($xml_arr['_c']) && is_array($xml_arr['_c'])) {
        //the only variant
        $var = get_morph_vars_inner($xml_arr, 1);
        $t = array();
        foreach($var['gram_list'] as $gr) {
            if (!isset($gram_descr[$gr['inner']])) {
                $r = sql_fetch_array(sql_query("SELECT outer_id, gram_descr FROM gram WHERE inner_id='".$gr['inner']."' LIMIT 1"));
                $gram_descr[$gr['inner']] = array($r[0], $r[1]);
            }
            $t[] = array('inner' => $gr['inner'], 'outer' => $gram_descr[$gr['inner']][0], 'descr' => $gram_descr[$gr['inner']][1]);
        }
        $var['gram_list'] = $t;
        return array($var);
    } else {
        //multiple variants
        $out = array();
        $i = 1;
        if (is_array($xml_arr)) {
            foreach($xml_arr as $xml_var_arr) {
                $var = get_morph_vars_inner($xml_var_arr, $i++);
                $t = array();
                foreach($var['gram_list'] as $gr) {
                    if (!isset($gram_descr[$gr['inner']])) {
                        $r = sql_fetch_array(sql_query("SELECT outer_id, gram_descr FROM gram WHERE inner_id='".$gr['inner']."' LIMIT 1"));
                        $gram_descr[$gr['inner']] = array($r[0], $r[1]);
                    }
                    $t[] = array('inner' => $gr['inner'], 'outer' => $gram_descr[$gr['inner']][0], 'descr' => $gram_descr[$gr['inner']][1]);
                }
                $var['gram_list'] = $t;
                $out[] = $var;
            }
        }
        return $out;
    }
}
function get_morph_vars_inner($xml_arr, $num) {
    $lemma_grm = $xml_arr['_c']['l']['_c']['g'];
    $grm_arr = array();
    if (isset ($lemma_grm['_a']) && is_array($lemma_grm['_a'])) {
        //$r = sql_fetch_array(sql_query("SELECT outer_id, gram_descr FROM gram WHERE inner_id='$inner_id' LIMIT 1"));
        //array_push($grm_arr, array('inner' => $inner_id, 'outer' => $r[0], 'descr' => $r[1]));
        $grm_arr[] = array('inner' => $lemma_grm['_a']['v']);
    } elseif(is_array($lemma_grm)) {
        foreach($lemma_grm as $t) {
            //$r = sql_fetch_array(sql_query("SELECT outer_id, gram_descr FROM gram WHERE inner_id='$inner_id' LIMIT 1"));
            //array_push($grm_arr, array('inner' => $inner_id, 'outer' => $r[0], 'descr' => $r[1]));
            $grm_arr[] = array('inner' => $t['_a']['v']);
        }
    }
    return array(
        'num'        => $num,
        'lemma_id'   => $xml_arr['_c']['l']['_a']['id'],
        'lemma_text' => $xml_arr['_c']['l']['_a']['t'],
        'gram_list'  => $grm_arr
    );
}
function sentence_save($sent_id) {
    if (!$sent_id) return false;
    $flag = $_POST['var_flag'];  //what morphovariants are checked as possible (array of arrays)
    $dict = $_POST['dict_flag']; //whether this token has been reloaded from the dictionary (array)
    $res = sql_query("SELECT tf_id, tf_text, `pos` FROM text_forms WHERE sent_id=$sent_id ORDER BY `pos`");
    while($r = sql_fetch_array($res)) {
        $rev = sql_fetch_array(sql_query("SELECT rev_text FROM tf_revisions WHERE tf_id=".$r['tf_id']." ORDER BY rev_id DESC LIMIT 1"));
        $tokens[$r['tf_id']] = array($r['tf_text'], $rev['rev_text']);
    }
    $matches = array();
    $all_changes = array();
    if(count($flag) != count($tokens)) {
        print("Internal error 1: Cannot save");
        if (is_admin()) {
            print "\nflag:\n".print_r($flag, 1);
            print "\ntokens:\n".print_r($tokens, 1);
        }
        exit(0);
    }
    sql_begin();
    foreach($tokens as $tf_id=>$v) {
        list($tf_text, $base_xml) = $v;
        //substitute the last revision's xml for one from dictionary if relevant
        if ($dict[$tf_id] == 1) {
            $xml = generate_tf_rev($tf_text);
            //and reset the flag! perhaps it would be better to reset all of them by one query, but seems the case is rather rare
            if (!sql_query("UPDATE text_forms SET dict_updated='0' WHERE tf_id=$tf_id LIMIT 1")) {
                die("Internal error 5: cannot save");
            }
        } else {
            $xml = $base_xml;
        }
        $new_xml = "<tfr t=\"$tf_text\">";
        //let's find all vars inside tf_text
        if (preg_match_all("/<v>(.+?)<\/v>/", $xml, $matches) !== false) {
            //flags quantity check
            if (count($matches[1]) != count($flag[$tf_id])) {
                print "Internal error 3: Cannot save\n";
                if (is_admin()) {
                    print "matches:\n".print_r($matches[1], true);
                    print "flag:\n".print_r($flag[$tf_id], true);
                }
                exit(0);
            }
            $not_empty = 0;
            foreach($flag[$tf_id] as $k=>$f) {
                if ($f == 1) {
                    $not_empty = 1;
                    $new_xml .= '<v>'.$matches[1][$k-1].'</v>'; //attention to -1
                }
            }
            //inserting UnknownPOS if no variants present
            if (!$not_empty) {
                $new_xml .= '<v><l id="0" t="'.htmlspecialchars(mb_strtolower($tf_text, 'UTF-8')).'"><g v="UNKN"/></l></v>';
            }
            $new_xml .= '</tfr>';
            if ($base_xml != $new_xml) {
                //something's changed
                array_push($all_changes, array($tf_id, $new_xml));
            }
        } else {
            die ("Internal error 2: Cannot save");
        }
    }
    if (count($all_changes)>0) {
        $revset_id = create_revset($_POST['comment']);
        if (!$revset_id)
            die ("Cannot create revset");
        foreach ($all_changes as $v) {
            if (!sql_query("INSERT INTO `tf_revisions` VALUES(NULL, '$revset_id', '$v[0]', '".mysql_real_escape_string($v[1])."')"))
                die ("Internal error 4: Cannot save");
        }
    }
    if (sql_query("UPDATE sentences SET check_status='1' WHERE sent_id=$sent_id LIMIT 1")) {
        sql_commit();
        return true;
    }
    return false;
}
?>
