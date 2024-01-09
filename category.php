<?php
ini_set("display_errors", "OFF");
require_once('functions.php');
session_start();

// ログインしていない場合はログインページへ強制的に移動
if (empty($_SESSION)) {
    header('Location: account_entry.php');
    exit;  
}

// リダイレクト先を変数に代入
$redirect = 'Location: category.php';

// データベース接続
$dbh = db_open();

// カテゴリー（フォルダ）IDを代入
$category_id = $_GET['id'];

// 全てのフォルダ名とそのidをを取得
$category_ids = get_category_ids();

// フォルダ名を取得
$sql ='SELECT name FROM categories
WHERE id = :id';
$category_title_stmt = $dbh->prepare($sql);
$category_title_stmt->bindValue(':id', $category_id, PDO::PARAM_INT);
$category_title_stmt->execute();
$category_title = $category_title_stmt->fetch();


// フォルダに登録済みの投稿、返信を取得
$sql ='SELECT bookmarks.id, bookmarks.post_id, posts.file_path, pressed_at, posts.created_at, content, posts.user_id
FROM bookmarks 
INNER JOIN posts ON bookmarks.post_id = posts.post_id
INNER JOIN categories ON bookmarks.category_id = categories.id
WHERE category_id = :category_id';
$category_post_stmt = $dbh->prepare($sql);
$category_post_stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
$category_post_stmt->execute();
while ($category_post_row = $category_post_stmt->fetch()) {
    $categories[] = $category_post_row;
}

$sql ='SELECT bookmarks.id, bookmarks.reply_id, pressed_at, replies.created_at, content, replies.user_id
FROM bookmarks 
INNER JOIN replies ON bookmarks.reply_id = replies.reply_id
INNER JOIN categories ON bookmarks.category_id = categories.id
WHERE category_id = :category_id';
$category_rep_stmt = $dbh->prepare($sql);
$category_rep_stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
$category_rep_stmt->execute();
while ($category_rep_row = $category_rep_stmt->fetch()) {
    $categories[] = $category_rep_row;
}

// 投稿と返信をまとめた$bookmarksを押された順に並び変える
if (isset($categories)) {
    array_multisort(array_map('strtotime', array_column($categories, 'pressed_at')), SORT_DESC, $categories) ;
}


// 投稿または返信をフォルダから削除
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    foreach ($_POST['ids'] as $bm_id) {
        $sql = 'DELETE FROM bookmarks
        WHERE id = :id';
        $delete_posts_stmt = $dbh->prepare($sql);
        $delete_posts_stmt->bindValue(':id', $bm_id, PDO::PARAM_INT);
        $delete_posts_stmt->execute();
    }
    header($redirect);
    exit;
}

// 現在のページのフォルダとその投稿を削除（投稿はブックマークに残る）
if (isset($_POST['delete_categ'])) {
    $sql = 'DELETE FROM categories
    WHERE id = :id';
    $delete_categ_name_stmt = $dbh->prepare($sql);
    $delete_categ_name_stmt->bindValue(':id', $category_id, PDO::PARAM_INT);
    $delete_categ_name_stmt->execute();

    $sql = 'DELETE FROM bookmarks
    WHERE category_id = :category_id';
    $delete_categ_posts_stmt = $dbh->prepare($sql);
    $delete_categ_posts_stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
    $delete_categ_posts_stmt->execute();
    header($redirect);
    exit;
}

// ログインユーザーがブロックしている、ログインユーザーをブロックしているユーザーを取得
$block_stmt = block_user();
while ($block_row = $block_stmt->fetch()) {
    $blocks[] = $block_row;
}

// 削除ボタンが押された場合
if (isset($_POST['delete_post'])) {
    delete_post((int)$_POST['delete_post']);
    header($redirect);
    exit;  
}

// いいねボタンが押された場合
if (isset($_POST['insert_like'])) {
    insert_like((int)$_POST['insert_like']);
    header($redirect);
    exit;
}

// いいね解除ボタンが押された場合
if (isset($_POST['delete_like'])) {
    delete_like((int)$_POST['delete_like']);
    header($redirect);
    exit;
}

// ブックマークボタンが押された場合
if (isset($_POST['insert_bm'])) {
    insert_bookmark((int)$_POST['insert_bm']);
    header($redirect);
    exit;
}

// ブックマーク解除ボタンが押された場合
if (isset($_POST['delete_bm'])) {
    delete_bookmark((int)$_POST['delete_bm']);
    header($redirect);
    exit;
}

// -------返信-------

// いいねが押された場合
if (isset($_POST['insert_reply_like'])) {
    insert_reply_like((int)$_POST['insert_reply_like']);
    header($redirect);
    exit;
}

// いいね解除ボタンが押された場合
if (isset($_POST['delete_reply_like'])) {
    delete_reply_like((int)$_POST['delete_reply_like']);
    header($redirect);
    exit;
}

// 削除ボタンが押された場合
if (isset($_POST['delete_reply'])) {
    delete_reply($_POST['delete_reply']);
    header($redirect);
    exit;
}

// ブックマークボタンが押された場合
if (isset($_POST['insert_reply_bm'])) {
    insert_rep_bookmark((int)$_POST['insert_reply_bm']);
    header($redirect);
    exit;
}

// ブックマーク解除ボタンが押された場合
if (isset($_POST['delete_reply_bm'])) {
    delete_rep_bookmark((int)$_POST['delete_reply_bm']);
    header($redirect);
    exit;
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
                    <li><a href="user.php?id=<?= h($_SESSION['user_id']) ?>">プロフィール</a></li>
                    <li><a href="users_list.php">ユーザー一覧</a></li>
                    <li><a href="search.php">検索</a></li>
                </ul>
            </div>
        </header>    
        <main>
            <div class="bm-main">
                <!-- 左サイドバー -->
                <div class="left-side">
                    <!-- フォルダ一覧 -->
                    <div class="left-side-bar">
                        <p>フォルダリスト</p>
                        <ul class="category-name-list">
                            <?php foreach ($category_ids as $category_id) : ?>
                                <li><a href="category.php?id=<?= h($category_id['id']) ?>">●<?= h($category_id['name']) ?></a></li>
                            <?php endforeach ?>  
                        </ul>
                    </div>    
                </div>

                <!-- ブックマーク一覧 -->
                <div class="bm-contents">
                    <h4><?= h($category_title['name']) ?></h4>
                    <?php foreach ($categories as $category) :
                        $block_bookmark = true;
                        foreach ($blocks as $block) {
                            if ($block['is_blocked'] === $category['user_id'] ) {
                                $block_bookmark = false;
                            }
                        }
                        // ブロックしていない、されていない場合
                        if ($block_bookmark) :
                    ?>
                            <!-- チェックボックス -->
                            <div class="check-box">
                                <input type="checkbox" form="category" name="ids[]" value="<?= h($category['id']) ?>">
                            </div>

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
                                    <p>RE: <?= h($category['content']) ?></p>
                                <!-- 投稿文 -->
                                <?php else : ?>
                                    <p><?= h($category['content']) ?></p>   
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
                                    <p><?= h($category['created_at']) ?></p>
                                <?php else : ?>
                                    <p><?= h($category['created_at']) ?></p>
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
                                        <li><button><a href="reply.php?r_id=<?= h($category['reply_id']) ?>#reply">返信</a></button></li>
                                    <?php else : ?>
                                        <li><button><a href="reply.php?p_id=<?= h($category['post_id']) ?>#reply">返信</a></button></li>
                                    <?php endif; ?>

                                    <!-- アカウントボタン -->
                                    <li><button><a href="user.php?id=<?= h($category['user_id']) ?>">アカウント</a></button></li>

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
                                    <?php if($category['user_id'] === $_SESSION['user_id']) : ?>
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
                        <?php endif; ?>    
                    <?php endforeach ?>
                </div>

                <!-- 右サイドバー -->
                <div class="right-side">
                    <div class="right-side-bar">
                        <!-- 投稿または返信をフォルダから削除 -->
                        <div class="">
                            <form action="" method="post" id="category">
                                <label>投稿をリストから削除する</label>
                                <button type="submit">削除</button>
                            </form>
                        </div>    
                        <!-- フォルダを削除する -->
                        <div class="categ-delete">
                            <form action="" method="post">
                                <label><?= h($category_title['name']) ?>フォルダを削除する</label>
                                <button type="submit" name="delete_categ">削除</button>
                            </form>  
                        </div>             

                    </div>    
                </div>        

        </main>
    </body>    
</html>     