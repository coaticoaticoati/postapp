<?php
// htmlspecialchars()を関数化
function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// データベース接続
function db_open() :PDO {
    try{
        $user = "xxxx";
        $password = "xxxx";
        $opt = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
        ];
        $dbh = new PDO("mysql:host=xxxx;dbname=xxxx", $user, $password, $opt); 
        return $dbh;
    } catch (PDOException $e) {
        echo "データベース接続エラー:{$e->getMessage()}";
    }
}

// ---------投稿---------

// 投稿文のみの登録
function insert_post($content) {
    $dbh = db_open();
    $sql = 'INSERT INTO posts (user_id, content, file_name, file_path) 
    VALUES (:user_id, :content, NULL, NULL)';
    $post_ins_stmt = $dbh->prepare($sql);
    $post_ins_stmt->bindValue(':content', $content);
    $post_ins_stmt->bindValue(':user_id', $_SESSION['login']['member_id']);
    $post_ins_stmt->execute();
}

// 投稿文と画像の登録

function insert_file($content, $file_name, $save_path) {
    $dbh = db_open();
    $sql = 'INSERT INTO posts (content, user_id, file_name, file_path) 
    VALUES (:content, :user_id, :file_name, :file_path)';
    $post_img_ins_stmt = $dbh->prepare($sql);
    $post_img_ins_stmt->bindValue(':content', $content, PDO::PARAM_STR);
    $post_img_ins_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $post_img_ins_stmt->bindValue(':file_name', $file_name, PDO::PARAM_STR);
    $post_img_ins_stmt->bindValue(':file_path', $save_path, PDO::PARAM_STR);
    $post_img_ins_stmt->execute();
}

// 投稿文の取得
function get_posts($get_posts) {
    $dbh = db_open();
    $sql = 'SELECT * FROM posts WHERE post_id = :post_id';
    $post_stmt = $dbh->prepare($sql);
    $post_stmt->bindValue(':post_id', $get_posts, PDO::PARAM_INT);
    $post_stmt->execute();
    $post_row = $post_stmt->fetch();
    return $post_row;
}

// データベースから対象ユーザーの投稿を取得
function get_user_posts($get_user_posts) {
    $dbh = db_open();
    $sql = 'SELECT * FROM posts WHERE user_id = :user_id
    ORDER BY created_at DESC';
    $post_stmt = $dbh->prepare($sql);
    $post_stmt->bindValue(':user_id', $get_user_posts, PDO::PARAM_INT);
    $post_stmt->execute();
    return $post_stmt;
}

// 投稿文の削除
function delete_post($delete_post) { 
    $dbh = db_open();
    $sql = 'DELETE FROM posts WHERE post_id = :post_id';
    $post_del_stmt = $dbh->prepare($sql);
    $post_del_stmt->bindValue(':post_id', $delete_post, PDO::PARAM_INT);
    $post_del_stmt->execute();
}

// --------返信--------

// 親投稿への返信を登録
function insert_reply($post_id, $content) {
    $dbh = db_open();
    $sql = 'INSERT INTO replies (post_id, content, reply_reply_id, user_id)
    VALUES (:post_id, :content, NULL, :user_id)';
    $rep_ins_stmt = $dbh->prepare($sql);
    $rep_ins_stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
    $rep_ins_stmt->bindValue(':content', $content, PDO::PARAM_STR);
    $rep_ins_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $rep_ins_stmt->execute();
}

// 返信への返信を登録
function insert_reply_reply($post_id, $content, $reply_reply_id) {
    $dbh = db_open();
    $sql = 'INSERT INTO replies (post_id, content, reply_reply_id, user_id)
    VALUES (:post_id, :content, :reply_reply_id, :user_id)';
    $rep_rep_ins_stmt = $dbh->prepare($sql);
    $rep_rep_ins_stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
    $rep_rep_ins_stmt->bindValue(':content', $content, PDO::PARAM_STR);
    $rep_rep_ins_stmt->bindValue(':reply_reply_id', $reply_reply_id, PDO::PARAM_INT);
    $rep_rep_ins_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $rep_rep_ins_stmt->execute();
}

//　親投稿に対する返信全件を取得
function get_reply($get_reply) {
    $dbh = db_open();
    $sql ='SELECT * FROM replies WHERE post_id = :post_id
    ORDER BY created_at ASC';
    $reply_stmt = $dbh->prepare($sql);
    $reply_stmt->bindValue(':post_id', $get_reply, PDO::PARAM_INT);
    $reply_stmt->execute();
    return $reply_stmt;
}

// 返信の削除
function delete_reply($delete_reply) { 
    $dbh = db_open();
    $sql = 'DELETE FROM replies WHERE reply_id = :reply_id';
    $rep_del_stmt = $dbh->prepare($sql);
    $rep_del_stmt->bindValue(':reply_id', $delete_reply, PDO::PARAM_INT);
    $rep_del_stmt->execute();
}

// データベースから対象ユーザーの返信を取得
function get_user_reply($get_user_reply) {
    $dbh = db_open();
    $sql = 'SELECT * FROM replies WHERE user_id = :user_id
    ORDER BY created_at DESC';
    $reply_stmt = $dbh->prepare($sql);
    $reply_stmt->bindValue(':user_id', $get_user_reply, PDO::PARAM_INT);
    $reply_stmt->execute();
    return $reply_stmt;
}

// ---------いいね----------

// 投稿へのいいねを登録(likesテーブル)
function insert_like($insert_like) {
    $dbh = db_open();
    $sql = 'INSERT IGNORE INTO likes (like_id, post_is_liked_id, user_id) 
    VALUES (NULL, :post_is_liked_id, :user_id)'; // 重複不可
    $like_ins_stmt = $dbh->prepare($sql);
    $like_ins_stmt->bindValue(':post_is_liked_id', $insert_like, PDO::PARAM_INT);
    $like_ins_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $like_ins_stmt->execute();
}

// 投稿へのいいねを解除
function delete_like($delete_like) {
    $dbh = db_open();
    $sql = 'DELETE FROM likes WHERE post_is_liked_id = :post_is_liked_id AND user_id = :user_id';
    $like_del_stmt = $dbh->prepare($sql);
    $like_del_stmt->bindValue(':post_is_liked_id', $delete_like, PDO::PARAM_INT);
    $like_del_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $like_del_stmt->execute();
}

// 投稿のいいねの数を取得
function get_likes_number($get_likes_number) {
    $dbh = db_open();
    $sql = 'SELECT post_is_liked_id FROM likes  
    WHERE post_is_liked_id = :post_is_liked_id';
    $likes_number_stmt = $dbh->prepare($sql);
    $likes_number_stmt->bindValue(':post_is_liked_id', $get_likes_number, PDO::PARAM_INT);
    $likes_number_stmt->execute();
    while ($likes_number_row = $likes_number_stmt->fetch()) { 
        $likes_numbers[] = $likes_number_row;
    }
    if (isset($likes_numbers)) {
        $likes_number = count($likes_numbers); 
    } else {
        $likes_number = 0;
    }
    return $likes_number;
    $likes_numbers= [];
    
}

// 投稿に対して、ログインユーザーが行ったいいねを取得
function get_likes($get_likes) {
    $dbh = db_open();
    $sql = 'SELECT post_id, pressed_at, created_at, content, posts.user_id, posts.file_path, post_is_liked_id FROM likes
    INNER JOIN posts ON likes.post_is_liked_id = posts.post_id 
    WHERE likes.user_id = :user_id';
    $post_like_stmt = $dbh->prepare($sql);
    $post_like_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $post_like_stmt->execute();
    while ($post_like_row = $post_like_stmt->fetch()) { 
        $post_likes[] = $post_like_row;
    }
    $post_is_liked_id = false;
    foreach($post_likes as $post_like) {
        if($post_like['post_is_liked_id'] === $get_likes) {
            $post_is_liked_id = true;
            break;
        }
    }
    return $post_is_liked_id;
}

// 返信のいいねの登録
function insert_reply_like($insert_reply_like) {
    $dbh = db_open();
    $sql = 'INSERT IGNORE INTO reply_likes (reply_like_id, reply_is_liked_id, user_id) 
    VALUES (NULL, :reply_is_liked_id, :user_id)'; // 重複不可
    $like_ins_stmt = $dbh->prepare($sql);
    $like_ins_stmt->bindValue(':reply_is_liked_id', $insert_reply_like, PDO::PARAM_INT);
    $like_ins_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $like_ins_stmt->execute();
}

// 返信のいいねの解除
function delete_reply_like($delete_reply_like) {
    $dbh = db_open();
    $sql = 'DELETE FROM reply_likes WHERE user_id = :user_id AND reply_is_liked_id = :reply_is_liked_id';
    $like_del_stmt = $dbh->prepare($sql);
    $like_del_stmt->bindValue(':reply_is_liked_id', $delete_reply_like, PDO::PARAM_INT);
    $like_del_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $like_del_stmt->execute();
}

// 返信へのいいねの数を取得
function get_rep_likes_number($get_rep_likes_number) {
    $dbh = db_open();
    $sql = 'SELECT reply_is_liked_id FROM reply_likes  
    WHERE reply_is_liked_id = :reply_is_liked_id';
    $rep_likes_number_stmt = $dbh->prepare($sql);
    $rep_likes_number_stmt->bindValue(':reply_is_liked_id', $get_rep_likes_number, PDO::PARAM_INT);
    $rep_likes_number_stmt->execute();
    while ($rep_likes_number_row = $rep_likes_number_stmt->fetch()) {
        $rep_likes_numbers[] = $rep_likes_number_row;  
    }
    if (isset($rep_likes_numbers)) {
        $rep_likes_number = count($rep_likes_numbers); 
    } else {
        $rep_likes_number = 0;
    }
    return $rep_likes_number;
    $rep_likes_numbers = [];
}

// 返信に対して、ログインユーザーが行ったいいねを取得
function get_rep_likes($get_rep_likes) {
    
    $dbh = db_open();
    $sql = 'SELECT post_id, reply_id, pressed_at, created_at, content, replies.user_id, reply_is_liked_id 
    FROM reply_likes 
    INNER JOIN replies ON reply_likes.reply_is_liked_id = replies.reply_id 
    WHERE reply_likes.user_id = :user_id';
    $reply_like_stmt = $dbh->prepare($sql);
    $reply_like_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $reply_like_stmt->execute();
    while ($reply_like_row = $reply_like_stmt->fetch()) { 
        $reply_likes[] = $reply_like_row;
    }
    $reply_is_liked_id = false;
    foreach($reply_likes as $reply_like) {
        if($reply_like['reply_is_liked_id'] === $get_rep_likes) {
            $reply_is_liked_id = true;
            break;
        }
    }
    return $reply_is_liked_id;
}

// ログインユーザーの、投稿と返信のいいねを取得
function get_likes_reps($get_likes_reps) {
    $dbh = db_open();
    $sql = 'SELECT post_id, pressed_at, created_at, content, posts.user_id, posts.file_path, post_is_liked_id 
    FROM likes
    INNER JOIN posts ON likes.post_is_liked_id = posts.post_id 
    WHERE likes.user_id = :user_id';
    $post_like_stmt = $dbh->prepare($sql);
    $post_like_stmt->bindValue(':user_id', $get_likes_reps, PDO::PARAM_INT);
    $post_like_stmt->execute();
    while ($post_like_row = $post_like_stmt->fetch()) { 
        $post_likes_reps[] = $post_like_row;
    }
    $sql = 'SELECT post_id, reply_id, pressed_at, created_at, content, replies.user_id, reply_is_liked_id 
    FROM reply_likes 
    INNER JOIN replies ON reply_likes.reply_is_liked_id = replies.reply_id 
    WHERE reply_likes.user_id = :user_id';
    $reply_like_stmt = $dbh->prepare($sql);
    $reply_like_stmt->bindValue(':user_id', $get_likes_reps, PDO::PARAM_INT);
    $reply_like_stmt->execute();
    while ($reply_like_row = $reply_like_stmt->fetch()) { 
        $post_likes_reps[] = $reply_like_row;
    }
    return $post_likes_reps;
}

// --------ブックマーク----------

// 投稿へのブックマークを登録
function insert_bookmark($insert_bookmark) {
    $dbh = db_open();
    $sql = 'INSERT INTO bookmarks (user_id, post_id) 
    VALUES (:user_id, :post_id)';
    $bm_ins_stmt = $dbh->prepare($sql);
    $bm_ins_stmt->bindValue(':post_id', $insert_bookmark, PDO::PARAM_INT);
    $bm_ins_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $bm_ins_stmt->execute();
}

// 投稿へのブックマークを解除
function delete_bookmark($delete_bookmark) {
    $dbh = db_open();
    $sql = 'DELETE FROM bookmarks WHERE post_id = :post_id AND user_id = :user_id';
    $bm_del_stmt = $dbh->prepare($sql);
    $bm_del_stmt->bindValue(':post_id', $delete_bookmark, PDO::PARAM_INT);
    $bm_del_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $bm_del_stmt->execute();
}

// 投稿に対して、ログインユーザーが行ったブックマークを取得
function get_bookmarks($get_bookmarks) {
    $dbh = db_open();
    $sql = 'SELECT DISTINCT(post_id) FROM bookmarks 
    WHERE user_id = :user_id';
    $post_bm_stmt = $dbh->prepare($sql);
    $post_bm_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $post_bm_stmt->execute();
    while ($post_bm_row = $post_bm_stmt->fetch()) { 
        $post_bms[] = $post_bm_row;
    }
    $post_bm_id = false;
    foreach ($post_bms as $post_bm) {
        if($post_bm['post_id'] === $get_bookmarks) {
            $post_bm_id = true;
            break;
        }
    }
    return $post_bm_id; 
    }

// 返信のブックマークの登録
function insert_rep_bookmark($insert_rep_bookmark) {
    $dbh = db_open();
    $sql = 'INSERT INTO bookmarks (reply_id, user_id) 
    VALUES (:reply_id, :user_id)';
    $bm_ins_stmt = $dbh->prepare($sql);
    $bm_ins_stmt->bindValue(':reply_id', $insert_rep_bookmark, PDO::PARAM_INT);
    $bm_ins_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $bm_ins_stmt->execute();
}

// 返信のブックマークの解除
function delete_rep_bookmark($delete_rep_bookmark) {
    $dbh = db_open();
    $sql = 'DELETE FROM bookmarks WHERE reply_id = :reply_id AND user_id = :user_id';
    $bm_del_stmt = $dbh->prepare($sql);
    $bm_del_stmt->bindValue(':reply_id', $delete_rep_bookmark, PDO::PARAM_INT);
    $bm_del_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $bm_del_stmt->execute();
}

// 返信に対して、ログインユーザーが行ったブックマークを取得
function get_rep_bookmarks($get_rep_bookmarks) {
    $dbh = db_open();
    $sql = 'SELECT DISTINCT(reply_id) FROM bookmarks 
    WHERE user_id = :user_id';
    $reply_bm_stmt = $dbh->prepare($sql);
    $reply_bm_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $reply_bm_stmt->execute();
    while ($reply_bm_row = $reply_bm_stmt->fetch()) { 
        $reply_bms[] = $reply_bm_row;
    }
    $reply_bm_id = false;
    foreach ($reply_bms as $reply_bm) {
        if ($reply_bm['reply_id'] === $get_rep_bookmarks) {
            $reply_bm_id = true;
            break;
        }
    }
    return $reply_bm_id;
}

// カテゴリー名を登録
function insert_category_name($insert_category_name) {
    $dbh = db_open();
    $sql = 'INSERT INTO categories (user_id, name)
    VALUES (:user_id, :name)';
    $category_ins_stmt = $dbh->prepare($sql);
    $category_ins_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $category_ins_stmt->bindValue(':name', $insert_category_name, PDO::PARAM_STR);
    $category_ins_stmt->execute();
}

// ログインユーザーの全てのカテゴリー名を取得
function get_category_names() {
    $dbh = db_open();
    $sql ='SELECT id, name FROM categories
    WHERE user_id = :user_id
    ORDER BY created_at ASC';
    $category_name_stmt = $dbh->prepare($sql);
    $category_name_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $category_name_stmt->execute();
    while ($category_name_row = $category_name_stmt->fetch()) { 
        $category_names[] = $category_name_row;
    }
    return $category_names;
}

// ----------フォロー、フォロワー、アンフォロー-------

// フォロー登録
// フォローを実施したユーザーと、されたユーザーをfollowテーブルに追加
function insert_follow($insert_follow) {
    $dbh = db_open();
    $sql = 'INSERT IGNORE INTO follows (follow, is_followed)
    VALUES (:follow, :is_followed)';
    $follow_ins_stmt = $dbh->prepare($sql);
    $follow_ins_stmt->bindValue(':follow', $_SESSION['login']['member_id'], PDO::PARAM_INT); // フォローを実施したユーザー(今ログインしているユーザー)
    $follow_ins_stmt->bindValue(':is_followed', $insert_follow, PDO::PARAM_INT); // フォローされたユーザー
    $follow_ins_stmt->execute();
}

// フォロー解除
function delete_follow($delete_follow) {
    $dbh = db_open();
    $sql = 'DELETE FROM follows WHERE follow = :follow AND is_followed = :is_followed'; // followかつis_followedであるものを削除する
    $follow_del_stmt = $dbh->prepare($sql);
    $follow_del_stmt->bindValue(':follow', $_SESSION['login']['member_id'], PDO::PARAM_INT );
    $follow_del_stmt->bindValue(':is_followed', $delete_follow, PDO::PARAM_INT);
    $follow_del_stmt->execute();
}

// フォロー一覧を取得
function get_follow($get_follow) {
    $dbh = db_open();
    $sql ='SELECT follows.follow, follows.is_followed, members.member_id, members.name 
    FROM follows INNER JOIN members ON follows.is_followed = members.member_id
    WHERE follow = :follow';
    // follows.is_followedとmembers.idを内部結合し、followがログインユーザーのものを検索し、is_followedとその名前を抽出する
    $follow_stmt = $dbh->prepare($sql);
    $follow_stmt->bindValue(':follow', $get_follow, PDO::PARAM_INT);
    $follow_stmt->execute();
    while ($follow_row = $follow_stmt->fetch()) {
        $follows[] = $follow_row;
    }
    return $follows;
}

// フォロワー一覧を取得 
function get_follower($get_follower) {
    $dbh = db_open();
    $sql ='SELECT follows.follow, follows.is_followed, members.member_id, members.name 
    FROM follows INNER JOIN members ON follows.follow = members.member_id 
    WHERE is_followed = :is_followed';
    // follows.followとmembers.idを内部結合し、is_followedがログインユーザーのものを検索し、followとその名前を抽出する
    $is_followed_stmt = $dbh->prepare($sql);
    $is_followed_stmt->bindValue(':is_followed', $get_follower, PDO::PARAM_INT);
    $is_followed_stmt->execute();
    return $is_followed_stmt;
}

// ------ブロック---------

// ログインユーザーがブロックしている、ログインユーザーをブロックしているユーザーを取得
function block_user() {
    $dbh = db_open();
    $sql ='SELECT is_blocked FROM blocks 
    WHERE block = :block
    UNION ALL
    SELECT block FROM blocks 
    WHERE is_blocked = :is_blocked';
    $block_stmt = $dbh->prepare($sql);
    $block_stmt->bindValue(':block', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $block_stmt->bindValue(':is_blocked', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $block_stmt->execute();
    return $block_stmt;
}

// ログインユーザーが参照中ユーザーをブロックしている情報を取得
function get_block_user($get_block_user) {
    $dbh = db_open();
    $sql ='SELECT block, is_blocked FROM blocks 
    WHERE block = :block AND is_blocked = :is_blocked';
    $block_stmt = $dbh->prepare($sql);
    $block_stmt->bindValue(':block', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $block_stmt->bindValue(':is_blocked', $get_block_user, PDO::PARAM_INT);
    $block_stmt->execute();
    $block_user = $block_stmt->fetch();
    return $block_user;
}

// ログインユーザーが参照中ユーザーにブロックされてる情報を取得
function get_blocked_user($get_blocked_user) {
    $dbh = db_open();
    $sql ='SELECT block, is_blocked FROM blocks 
    WHERE block = :block AND is_blocked = :is_blocked';
    $is_blocked_stmt = $dbh->prepare($sql);
    $is_blocked_stmt->bindValue(':block', $get_blocked_user, PDO::PARAM_INT);
    $is_blocked_stmt->bindValue(':is_blocked', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $is_blocked_stmt->execute();
    $blocked_user = $is_blocked_stmt->fetch();
    return $blocked_user;
}

// ------アイコン、プロフィール------

// アイコンをデータベースに追加、あれば更新
function insert_icon($file_name, $save_path) {
    $dbh = db_open();
    $sql = 'INSERT INTO icons (file_name, file_path, user_id)
    VALUES (:file_name, :file_path, :user_id) ON DUPLICATE KEY UPDATE 
    file_name = VALUES (file_name), file_path = VALUES (file_path)';
    $icon_ins_stmt = $dbh->prepare($sql);
    $icon_ins_stmt->bindValue(':file_name', $file_name, PDO::PARAM_STR);
    $icon_ins_stmt->bindValue(':file_path', $save_path, PDO::PARAM_STR);
    $icon_ins_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $icon_ins_stmt->execute();
}

// アイコンの取得
function get_icon($get_icon) {
    $dbh = db_open();
    $sql ='SELECT file_path FROM icons WHERE user_id = :user_id';
    $icon_stmt = $dbh->prepare($sql);
    $icon_stmt->bindValue(':user_id', $get_icon, PDO::PARAM_INT);
    $icon_stmt->execute();
    $icon_row = $icon_stmt->fetch();
    return $icon_row['file_path'];
} 

// アイコンの削除
function delete_icon($delete_icon) {
    $dbh = db_open();
    $sql = 'DELETE FROM icons WHERE user_id = :user_id';
    $icon_del_stmt = $dbh->prepare($sql);
    $icon_del_stmt->bindValue(':user_id', $delete_icon, PDO::PARAM_INT);
    $icon_del_stmt->execute();
}

// プロフィール文の取得
function get_profile($get_profile) {
    $dbh = db_open();
    $sql ='SELECT profiles.profile_content
    FROM profiles INNER JOIN members ON profiles.user_id = members.member_id 
    WHERE member_id = :member_id';
    $profile_stmt = $dbh->prepare($sql);
    $profile_stmt->bindValue(':member_id', $get_profile, PDO::PARAM_INT);
    $profile_stmt->execute();
    $profile_row = $profile_stmt->fetch();
    return $profile_row['profile_content'];
} 

// プロフィール文の削除
function delete_profile($delete_profile) {
    $dbh = db_open();
    $sql = 'DELETE FROM profiles WHERE user_id = :user_id';
    $profile_del_stmt = $dbh->prepare($sql);
    $profile_del_stmt->bindValue(':user_id', $delete_profile, PDO::PARAM_INT);
    $profile_del_stmt->execute();
}

//  ユーザー名の取得
function get_user_name($get_user_name) {
    $dbh = db_open();
    $sql = 'SELECT name FROM members WHERE members.member_id = :member_id
    ORDER BY created_at DESC';
    $user_name_stmt = $dbh->prepare($sql);
    $user_name_stmt->bindValue(':member_id', $get_user_name);
    $user_name_stmt->execute();
    $user_name_row = $user_name_stmt->fetch();
    return $user_name_row['name'];
}

// ----アカウント削除-----

function delete_user($delete_user) {

    $dbh = db_open();

    // ユーザー情報削除
    $sql = 'DELETE FROM members WHERE member_id = :member_id';
    $user_del_stmt = $dbh->prepare($sql);
    $user_del_stmt->bindValue(':member_id', $delete_user, PDO::PARAM_INT);
    $user_del_stmt->execute();

    // 投稿文削除
    $sql = 'DELETE FROM posts WHERE user_id = :user_id';
    $posts_del_stmt = $dbh->prepare($sql);
    $posts_del_stmt->bindValue(':user_id', $delete_user, PDO::PARAM_INT);
    $posts_del_stmt->execute();

    // フォロー情報削除
    $sql = 'DELETE FROM follows WHERE follow = :follow
    OR is_followed = :is_followed';
    $follow_del_stmt = $dbh->prepare($sql);
    $follow_del_stmt->bindValue(':follow', $delete_user, PDO::PARAM_INT);
    $follow_del_stmt->bindValue(':is_followed', $delete_user, PDO::PARAM_INT);
    $follow_del_stmt->execute();

    // いいね情報削除

    // ユーザーがいいねしたものを削除
    $sql = 'DELETE FROM likes WHERE user_id = :user_id';
    $like_del_stmt = $dbh->prepare($sql);
    $like_del_stmt->bindValue(':user_id', $delete_user, PDO::PARAM_INT);
    $like_del_stmt->execute();

    // 他ユーザーにいいねされたものを削除
    $sql = 'DELETE likes FROM likes INNER JOIN posts ON likes.post_is_liked_id = posts.post_id
    WHERE posts.user_id = :user_id';
    $rep_del_stmt = $dbh->prepare($sql);
    $rep_del_stmt->bindValue(':user_id', $delete_user, PDO::PARAM_INT);
    $rep_del_stmt->execute();

    // 返信削除
    $sql = 'DELETE FROM replies WHERE user_id = :user_id';
    $rep_del_stmt = $dbh->prepare($sql);
    $rep_del_stmt->bindValue(':user_id', $delete_user, PDO::PARAM_INT);
    $rep_del_stmt->execute();
    
    // 返信いいね削除

    // ユーザーがいいねしたものを削除
    $sql = 'DELETE FROM reply_likes WHERE user_id = :user_id';
    $rep_del_stmt = $dbh->prepare($sql);
    $rep_del_stmt->bindValue(':user_id', $delete_user, PDO::PARAM_INT);
    $rep_del_stmt->execute();

    // 他ユーザーにいいねされたものを削除
    $sql = 'DELETE reply_likes FROM reply_likes INNER JOIN replies ON reply_likes.reply_is_liked_id = replies.reply_id
    WHERE replies.user_id = :user_id';
    $rep_del_stmt = $dbh->prepare($sql);
    $rep_del_stmt->bindValue(':user_id', $delete_user, PDO::PARAM_INT);
    $rep_del_stmt->execute();

    // アイコン削除
    delete_icon($delete_user);

    // プロフィール文削除
    delete_profile($delete_user);
}





