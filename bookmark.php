<?php
// ini_set("display_errors", "OFF");
require_once('functions.php');
session_start();

// ログインしていない場合はログインページへ強制的に移動
if (empty($_SESSION)) {
    header('Location: account_entry.php');
    exit;  
}

// データベース接続
$dbh = db_open();

// 新規のカテゴリー名を登録
if (isset($_POST['insert_category'])) {
    insert_category_name($_POST['insert_category']);
    header('Location: bookmark.php');
    exit;
}

// ブックマークに登録済みの投稿と返信を取得
$sql ='SELECT bookmarks.id, posts.post_id, bookmarks.post_id, pressed_at, created_at, content, bookmarks.user_id, posts.file_path
FROM bookmarks 
INNER JOIN posts ON bookmarks.post_id = posts.post_id
WHERE bookmarks.user_id = :user_id
GROUP BY bookmarks.post_id';
$bm_post_stmt = $dbh->prepare($sql);
$bm_post_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
$bm_post_stmt->execute();
while ($bm_post_row = $bm_post_stmt->fetch()) { 
    $bookmarks[] = $bm_post_row;
}
$sql ='SELECT bookmarks.id, replies.reply_id, bookmarks.reply_id, bookmarks.pressed_at, created_at, content, bookmarks.user_id
FROM bookmarks 
INNER JOIN replies ON bookmarks.reply_id = replies.reply_id
WHERE bookmarks.user_id = :user_id
GROUP BY bookmarks.reply_id';
$bm_rep_stmt = $dbh->prepare($sql);
$bm_rep_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
$bm_rep_stmt->execute();
while ($bm_rep_row = $bm_rep_stmt->fetch()) { 
    $bookmarks[] = $bm_rep_row;
}
var_dump($bookmarks);

// 投稿または返信をカテゴリーに追加
if (isset($_POST['ins_posts_to_categ'])) {
    $sql = 'INSERT IGNORE INTO bookmarks (post_id, reply_id, user_id, category_id) 
    VALUES (:post_id, :reply_id, :user_id, :category_id)';
    $bm_ins_stmt = $dbh->prepare($sql);
    $bm_ins_stmt->bindValue(':category_id', $_POST['ins_posts_to_categ'], PDO::PARAM_INT);
    $bm_ins_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $bm_ins_stmt->bindValue(':post_id', $_POST['insert_bm'], PDO::PARAM_INT);
    $bm_ins_stmt->bindValue(':reply_id', $_POST['insert_rep_bm'], PDO::PARAM_INT);
    $bm_ins_stmt->execute();
}
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>ブックマーク一覧</title>
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



        <!-- カテゴリー一覧 -->
        <?php
        // カテゴリー名を取得
        $category_names = get_category_names();
        ?>
        <ul>
            <?php foreach ($category_names as $category_name) : ?>
                <li><a href="category.php?id=<?= h($category_name['id']) ?>"><?= h($category_name['name']) ?></a></li>
            <?php endforeach ?>  
        </ul>

        <!-- カテゴリー追加を追加 -->
        <form action="" method="post">
            <p>新規追加</p>
            <input type="text" name="insert_category">
            <button type="submit">登録</button>
        </form>
      </div>

      <!-- 投稿または返信をカテゴリーに追加 -->
    <form action="" method="post" id="category">
        <select name="ins_posts_to_categ">
            <option value=""></option>
            <?php foreach ($category_names as $category_name) : ?>
                <option value="<?= h($category_name['id']) ?>"><?= h($category_name['name']) ?></option>
            <?php endforeach ?>    
        </select>
            <button type="submit">登録</button>
    </form>

            

    <!-- ブックマーク一覧 -->
    <div class="timeline">
        <div class="container">
            <div class="timeline-contents">
                <?php foreach ($bookmarks as $bookmark) : ?>
                  
                    <!-- チェックボックス -->
                    <input type="hidden" form="category" name="insert_bm" value="<?= h($bookmark['post_id']) ?>">
                    <input type="hidden" form="category" name="insert_rep_bm" value="<?= h($bookmark['reply_id']) ?>">
                    <input type="checkbox" form="category" name="id" value="<?= h($bookmark['id']) ?>">
                    
                    <!-- アイコン -->
                    <div class="timeline-icon">
                        <?php
                        $icon_row = get_icon($bookmark['user_id']);
                        if (empty($icon_row)) : ?>
                            <p><img src="images/animalface_tanuki.png" class="icon"><p>
                        <?php else : ?>        
                            <p><img src="<?= h($icon_row['file_path']) ?>" class="icon" ></p>
                        <?php endif; ?>
                    </div>

                    <!-- ユーザー名 -->
                    <div class="timeline-username">
                        <p><?= h(get_user_name($bookmark['user_id'])) ?></p>
                    </div>

                    <div class="timeline-post">
                        <!-- 返信文 -->
                        <?php if (isset($bookmark['reply_id'])) : ?>  
                            <p>RE: <?= h($bookmark['content']) ?></p>
                        <!-- 投稿文 -->
                        <?php else : ?>
                            <p><?= h($bookmark['content']) ?></p>   
                        <?php endif; ?>
                    </div>
                        
                    <!-- 画像 -->
                    <div> 
                        <?php if(isset($bookmark['file_path'])) : ?>
                            <p><img src="<?= h($bookmark['file_path']) ?>" class="image"></p>
                        <?php endif; ?>                         
                    </div>

                    <!-- 投稿日時 -->
                    <div class="timeline-date">
                        <p><?= h($bookmark['created_at']) ?></p>
                    </div>

                    <!-- いいねの数 -->
                    <?php if (isset($bookmark['reply_id'])) : ?>
                        <p class="timeline-likes"><img src="images/heart.png"><?= h(get_rep_likes_number($bookmark['reply_id'])) ?></p>
                    <?php else : ?>
                        <p class="timeline-likes"><img src="images/heart.png"><?= h(get_likes_number($bookmark['post_id'])) ?></p>
                    <?php endif; ?>

                    <!-- ボタン -->
                    <div class="timeline-buttons">
                        <ul class="timeline-btn-list">
                            <!-- 返信ボタン -->
                            <?php if (isset($bookmark['reply_id'])) : ?>
                                <!---post_idとreply_idをセッションに保存する -->
                                <form action="" method="post">
                                    <input type="hidden" name="reply_btn_reply" value=<?= h($bookmark['post_id']) ?>>
                                    <input type="hidden" name="reply_btn_reply_id" value=<?= h($bookmark['reply_id']) ?>>
                                    <li><button type="submit">返信</button></li>
                                </form>
                            <?php else : ?>
                                <!-- post_idをセッションに保存する -->
                                <form action="" method="post">
                                    <input type="hidden" name="reply_btn_post" value=<?= h($bookmark['post_id']) ?>>
                                    <li><button type="submit">返信</button></li>
                                </form>
                            <?php endif; ?>

                            <!-- アカウントボタン -->
                            <li><button type="submit"><a href="user.php?id=<?= h($bookmark['user_id']) ?>">アカウント</a></button></li>

                            <!-- いいねボタン -->
                            <!-- 返信に対するいいねボタン -->
                            <?php if (isset($bookmark['reply_id'])) : ?>
                                <?php $reply_is_liked_id = get_rep_likes($bookmark['reply_id']) ?>
                                <form action="" method="post">
                                    <?php if ($reply_is_liked_id) : ?>
                                        <input type="hidden" name="delete_reply_like" value=<?= h($bookmark['reply_id']) ?>>
                                        <li><button type="submit">いいね解除</button></li>
                                    <?php else : ?>
                                        <input type="hidden" name="insert_reply_like" value=<?= h($bookmark['reply_id']) ?>>
                                        <li><button type="submit">いいね</button></li>
                                    <?php endif; ?>                         
                                </form>
                            <?php else : ?>
                                <!-- 投稿に対するいいねボタン -->
                                <?php $post_is_liked_id = get_likes($bookmark['post_id']) ?>
                                <form action="" method="post">
                                    <?php if ($post_is_liked_id) : ?>
                                        <input type="hidden" name="delete_like" value=<?= h($bookmark['post_id']) ?>>
                                        <li><button type="submit">いいね解除</button></li>
                                    <?php else : ?>
                                        <input type="hidden" name="insert_like" value=<?= h($bookmark['post_id']) ?>>
                                        <li><button type="submit">いいね</button></li>
                                    <?php endif; ?>                         
                                </form>
                            <?php endif; ?>   

                            <!-- ブックマークボタン -->
                            <!-- 返信に対するブックマークボタン -->
                            <?php if (isset($bookmark['reply_id'])) : ?>
                                <?php $reply_bm_id = get_rep_bookmarks($bookmark['reply_id']) ?>
                                <form action="" method="post">
                                    <?php if ($reply_bm_id) : ?>
                                        <input type="hidden" name="delete_reply_bm" value=<?= h($bookmark['reply_id']) ?>>
                                        <li><button type="submit">ブックマーク解除</button></li>
                                    <?php else : ?>
                                        <input type="hidden" name="insert_reply_bm" value=<?= h($bookmark['reply_id']) ?>>
                                        <li><button type="submit">ブックマーク</button></li>
                                    <?php endif; ?>                         
                                </form>
                            <?php else : ?>
                                <!-- 投稿に対するブックマークボタン -->
                                <?php $post_bm_id = get_bookmarks($bookmark['post_id']) ?>
                                <form action="" method="post">
                                    <?php if ($post_bm_id) : ?>
                                        <input type="hidden" name="delete_bm" value=<?= h($bookmark['post_id']) ?>>
                                        <li><button type="submit">ブックマーク解除</button></li>
                                    <?php else : ?>
                                        <input type="hidden" name="insert_bm" value=<?= h($bookmark['post_id']) ?>>
                                        <li><button type="submit">ブックマーク</button></li>
                                    <?php endif; ?>                         
                                </form>
                            <?php endif; ?>

                            <!-- 削除ボタン-->
                            <!-- ログインユーザーの投稿or返信のみ表示する -->            
                            <?php if($bookmark['user_id'] === $_SESSION['login']['member_id']) : ?>
                                <!-- 返信に対する削除ボタン -->
                                <?php if (isset($post['reply_id'])) : ?>
                                    <form action="" method="post">
                                        <input type="hidden" name="delete_reply" value=<?= h($bookmark['reply_id']) ?>>
                                        <li><button type="submit">削除</button></li>
                                    </form>
                                <!-- 投稿に対する削除ボタン -->            
                                <?php else : ?>
                                    <form action="" method="post">
                                        <input type="hidden" name="delete_post" value=<?= h($bookmark['post_id']) ?>>
                                        <li><button type="submit">削除</button></li>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </ul>    
                    </div>

                <?php endforeach ?>

    
    </main>
    </body>    
</html>     