<?php
ini_set("display_errors", "OFF");
require_once('functions.php');
session_start();

// ログインしていない場合はログインページへ強制的に移動
if (empty($_SESSION)) {
    header('Location: account_entry.php');
    exit;  
}

$redirect_back = 'Location: category.php';

// -------投稿-------

// 投稿文の返信ボタンが押された場合
if (isset($_POST['reply_btn_post'])) {
    $_SESSION['reply_btn'] = (int)$_POST['reply_btn_post'];
    header('Location: reply.php#reply');
    exit;
}

// 削除ボタンが押された場合
if (isset($_POST['delete_post'])) {
    delete_post((int)$_POST['delete_post']);
    header($redirect_back);
    exit;  
}

// いいねボタンが押された場合
if (isset($_POST['insert_like'])) {
    insert_like((int)$_POST['insert_like']);
    header($redirect_back);
    exit;
}

// いいね解除ボタンが押された場合
if (isset($_POST['delete_like'])) {
    delete_like((int)$_POST['delete_like']);
    header($redirect_back);
    exit;
}

// ブックマークボタンが押された場合
if (isset($_POST['insert_bm'])) {
    insert_bookmark((int)$_POST['insert_bm']);
    header($redirect_back);
    exit;
}

// ブックマーク解除ボタンが押された場合
if (isset($_POST['delete_bm'])) {
    delete_bookmark((int)$_POST['delete_bm']);
    header($redirect_back);
    exit;
}

// -------返信-------

// 返信文の返信ボタンが押された場合
if (isset($_POST['reply_btn_reply'])) {
    $_SESSION['reply_btn'] = (int)$_POST['reply_btn_reply'];
    $_SESSION['reply_btn_reply_id'] = (int)$_POST['reply_btn_reply_id'];
    header('Location: reply.php#reply');
    exit;
}

// いいねが押された場合
if (isset($_POST['insert_reply_like'])) {
    insert_reply_like((int)$_POST['insert_reply_like']);
    header($redirect_back);
    exit;
}

// いいね解除ボタンが押された場合
if (isset($_POST['delete_reply_like'])) {
    delete_reply_like((int)$_POST['delete_reply_like']);
    header($redirect_back);
    exit;
}

// 削除ボタンが押された場合
if (isset($_POST['delete_reply'])) {
    delete_reply($_POST['delete_reply']);
    header($redirect_back);
    exit;
}

// ブックマークボタンが押された場合
if (isset($_POST['insert_reply_bm'])) {
    insert_rep_bookmark((int)$_POST['insert_reply_bm']);
    header($redirect_back);
    exit;
}

// ブックマーク解除ボタンが押された場合
if (isset($_POST['delete_reply_bm'])) {
    delete_rep_bookmark((int)$_POST['delete_reply_bm']);
    header($redirect_back);
    exit;
}

// データベース接続
$dbh = db_open();

// カテゴリーIDを代入
$category_id =  $_GET['id'];

// 新規のカテゴリー名を登録
if (isset($_POST['insert_category'])) {
    insert_category_name($_POST['insert_category']);
    header('Location: bookmark.php');
    exit;
}

// カテゴリー名を取得
$sql ='SELECT name FROM categories
WHERE id = :id';
$category_title_stmt = $dbh->prepare($sql);
$category_title_stmt->bindValue(':id', $category_id, PDO::PARAM_INT);
$category_title_stmt->execute();
$category_title = $category_title_stmt->fetch();

// カテゴリーに登録済みの投稿、返信を取得
$sql ='SELECT bookmarks.user_id, bookmarks.post_id, bookmarks.reply_id, posts.file_path,
posts.content AS p_content, posts.created_at AS p_created_at, 
replies.content AS r_content, replies.created_at AS r_created_at
FROM bookmarks
INNER JOIN categories ON bookmarks.category_id = categories.id
LEFT OUTER JOIN posts ON bookmarks.post_id = posts.post_id
LEFT OUTER JOIN replies ON bookmarks.reply_id = replies.reply_id
WHERE categories.id = :id
ORDER BY bookmarks.pressed_at ASC';
$category_stmt = $dbh->prepare($sql);
$category_stmt->bindValue(':id', $category_id, PDO::PARAM_INT);
$category_stmt->execute();

// 投稿または返信をカテゴリーから削除
if (isset($_POST['del_posts_from_categ'])) {
    $sql = 'DELETE FROM bookmarks WHERE reply_id = :reply_id AND user_id = :user_id';
    $bm_del_stmt = $dbh->prepare($sql);
    $bm_del_stmt->bindValue(':reply_id', $delete_rep_bookmark, PDO::PARAM_INT);
    $bm_del_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $bm_del_stmt->execute();
}

?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title><?= h($category_title['name']) ?></title>
        <link rel="stylesheet" href="stylesheet.css">
    </head>
    <body>
        <header>
            <div class="navbar-sitename"><a href="index.php">postapp</div>
            <div class="navbar-list">
                <ul>
                    <li class="navbar-item"><a href="logout.php">ログアウト</a></li>
                    <li><a href="bookmark.php">ブックマーク</a></li>
                    <li><a href="user.php?id=<?= h($_SESSION['login']['member_id']) ?>">プロフィール</a></li>
                    <li><a href="users_list.php">ユーザー一覧</a></li>
                    <li><a href="search.php">検索</a></li>
                </ul>
            </div>
        </header>    
        <main>
            <div class="bm-main">
                <!-- 左サイドバー -->
                <div class="left-side">
                    <!-- カテゴリー一覧 -->
                    <?php
                    // 全てのカテゴリー名を取得
                    $category_names = get_category_names();
                    ?>
                    <div class="left-side-bar">
                        <p>カテゴリーリスト</p>
                        <ul class="category-name-list">
                            <?php foreach ($category_names as $category_name) : ?>
                                <li><a href="category.php?id=<?= h($category_name['id']) ?>">●<?= h($category_name['name']) ?></a></li>
                            <?php endforeach ?>  
                        </ul>
                    </div>    
                </div>

                <!-- ブックマーク一覧 -->
                <div class="bm-contents">
                    <p><?= h($category_title['name']) ?></p>
                    <?php while ($category = $category_stmt->fetch()) : ?>
                        
                        <!-- チェックボックス -->
                        <input type="hidden" form="category" name="delete_bm" value="<?= h($category['post_id']) ?>">
                        <input type="hidden" form="category" name="delete_rep_bm" value="<?= h($category['reply_id']) ?>">
                        <input type="checkbox" form="category" name="id" value="<?= h($category['id']) ?>">
                        
                        <!-- アイコン -->
                        <div class="bm-icon">
                            <?php
                            $icon_row = get_icon($category['user_id']);
                            if (empty($icon_row)) : ?>
                                <p><img src="images/animalface_tanuki.png" class="icon"><p>
                            <?php else : ?>        
                                <p><img src="<?= h($icon_row) ?>" class="icon" ></p>
                            <?php endif; ?>
                        </div>

                        <!-- ユーザー名 -->
                        <div class="timeline-username">
                            <p><?= h(get_user_name($category['user_id'])) ?></p>
                        </div>

                        <div class="timeline-post">
                            <!-- 返信文 -->
                            <?php if (empty($category['post_id'])) : ?>  
                                <p>RE: <?= h($category['r_content']) ?></p>
                            <!-- 投稿文 -->
                            <?php else : ?>
                                <p><?= h($category['p_content']) ?></p>   
                            <?php endif; ?>
                        </div>
                            
                        <!-- 画像 -->
                        <div> 
                            <?php if(isset($category['file_path'])) : ?>
                                <p><img src="<?= h($category['file_path']) ?>" class="image"></p>
                            <?php endif; ?>                         
                        </div>

                        <!-- 投稿日時 -->
                        <div class="timeline-date">
                            <?php if (empty($category['post_id'])) : ?>
                                <p><?= h($category['r_created_at']) ?></p>
                            <?php else : ?>
                                <p><?= h($category['p_created_at']) ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- いいねの数 -->
                        <?php if (empty($category['post_id'])) : ?>
                            <p class="timeline-likes"><img src="images/heart.png"> <?= h(get_rep_likes_number($category['reply_id'])) ?></p>
                        <?php else : ?>
                            <p class="timeline-likes"><img src="images/heart.png"> <?= h(get_likes_number($category['post_id'])) ?></p>
                        <?php endif; ?>

                        <!-- ボタン -->
                        <div class="timeline-buttons">
                            <ul class="timeline-btn-list">
                                <!-- 返信ボタン -->
                                <?php if (isset($category['reply_id'])) : ?>
                                    <!---post_idとreply_idをセッションに保存する -->
                                    <form action="" method="post">
                                        <input type="hidden" name="reply_btn_reply" value=<?= h($category['post_id']) ?>>
                                        <input type="hidden" name="reply_btn_reply_id" value=<?= h($category['reply_id']) ?>>
                                        <li><button type="submit">返信</button></li>
                                    </form>
                                <?php else : ?>
                                    <!-- post_idをセッションに保存する -->
                                    <form action="" method="post">
                                        <input type="hidden" name="reply_btn_post" value=<?= h($category['post_id']) ?>>
                                        <li><button type="submit">返信</button></li>
                                    </form>
                                <?php endif; ?>

                                <!-- アカウントボタン -->
                                <form action="" method="post">
                                    <input type="hidden" name="user_page" value=<?= h($category['user_id']) ?>>
                                    <li><button type="submit">アカウント</button></li>
                                </form>

                                <!-- いいねボタン -->
                                <!-- 返信に対するいいねボタン -->
                                <?php if (isset($category['reply_id'])) : ?>
                                    <?php $reply_is_liked_id = get_rep_likes($category['reply_id']) ?>
                                    <form action="" method="post">
                                        <?php if ($reply_is_liked_id) : ?>
                                            <input type="hidden" name="delete_reply_like" value=<?= h($category['reply_id']) ?>>
                                            <li><button type="submit">いいね解除</button></li>
                                        <?php else : ?>
                                            <input type="hidden" name="insert_reply_like" value=<?= h($category['reply_id']) ?>>
                                            <li><button type="submit">いいね</button></li>
                                        <?php endif; ?>                         
                                    </form>
                                <?php else : ?>
                                    <!-- 投稿に対するいいねボタン -->
                                    <?php $post_is_liked_id = get_likes($category['post_id']) ?>
                                    <form action="" method="post">
                                        <?php if ($post_is_liked_id) : ?>
                                            <input type="hidden" name="delete_like" value=<?= h($category['post_id']) ?>>
                                            <li><button type="submit">いいね解除</button></li>
                                        <?php else : ?>
                                            <input type="hidden" name="insert_like" value=<?= h($category['post_id']) ?>>
                                            <li><button type="submit">いいね</button></li>
                                        <?php endif; ?>                         
                                    </form>
                                <?php endif; ?>   

                                <!-- ブックマークボタン -->
                                <!-- 返信に対するブックマークボタン -->
                                <?php if (isset($category['reply_id'])) : ?>
                                    <?php $reply_bm_id = get_rep_bookmarks($category['reply_id']) ?>
                                    <form action="" method="post">
                                        <?php if ($reply_bm_id) : ?>
                                            <input type="hidden" name="delete_reply_bm" value=<?= h($category['reply_id']) ?>>
                                            <li><button type="submit">ブックマーク解除</button></li>
                                        <?php else : ?>
                                            <input type="hidden" name="insert_reply_bm" value=<?= h($category['reply_id']) ?>>
                                            <li><button type="submit">ブックマーク</button></li>
                                        <?php endif; ?>                         
                                    </form>
                                <?php else : ?>
                                    <!-- 投稿に対するブックマークボタン -->
                                    <?php $post_bm_id = get_bookmarks($category['post_id']) ?>
                                    <form action="" method="post">
                                        <?php if ($post_bm_id) : ?>
                                            <input type="hidden" name="delete_bm" value=<?= h($category['post_id']) ?>>
                                            <li><button type="submit">ブックマーク解除</button></li>
                                        <?php else : ?>
                                            <input type="hidden" name="insert_bm" value=<?= h($category['post_id']) ?>>
                                            <li><button type="submit">ブックマーク</button></li>
                                        <?php endif; ?>                         
                                    </form>
                                <?php endif; ?>

                                <!-- 削除ボタン-->
                                <!-- ログインユーザーの投稿or返信のみ表示する -->            
                                <?php if($category['user_id'] === $_SESSION['login']['member_id']) : ?>
                                    <!-- 返信に対する削除ボタン -->
                                    <?php if (isset($post['reply_id'])) : ?>
                                        <form action="" method="post">
                                            <input type="hidden" name="delete_reply" value=<?= h($category['reply_id']) ?>>
                                            <li><button type="submit">削除</button></li>
                                        </form>
                                    <!-- 投稿に対する削除ボタン -->            
                                    <?php else : ?>
                                        <form action="" method="post">
                                            <input type="hidden" name="delete_post" value=<?= h($category['post_id']) ?>>
                                            <li><button type="submit">削除</button></li>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </ul>    
                        </div>
                    <?php endwhile ?>
                </div>

                <!-- 右サイドバー -->
                <div class="right-side">
                    <div class="right-side-bar">
                        <div class="">
                            <!-- カテゴリー名を追加 -->
                            <form action="" method="post">
                                <p>カテゴリーを追加する</p>
                                <input type="text" name="insert_category">
                                <button type="submit">登録</button>
                            </form>
                        </div>            

                        <!-- 投稿または返信をカテゴリーから削除 -->
                        <form action="" method="post" id="category">
                            <p>リストから削除する</p>
                            <select name="del_posts_from_categ">
                                <option value=""></option>
                                <?php foreach ($category_names as $category_name) : ?>
                                    <option value="<?= h($category_name['id']) ?>"><?= h($category_name['name']) ?></option>
                                <?php endforeach ?>    
                            </select>
                            <button type="submit">削除</button>
                        </form>
                    </div>    
                </div>        

        </main>
    </body>    
</html>     