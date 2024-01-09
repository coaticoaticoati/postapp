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
$redirect = 'Location: bookmark.php';

// ログインユーザーの全てのフォルダ名とそのidを取得
$category_ids = get_category_ids();

// データベース接続
$dbh = db_open();

// フォルダ名のバリデーション
// 投稿文が未入力か
if($_POST['category_name'] === '') {
    $error['category_name'] = 'blank';
}
// 投稿文が200字以内か
if(strlen($_POST['category_name']) > 60) {
    $error['category_name'] = 'over';
}

// 新規のフォルダ名を登録
if (isset($_POST['category_name'])) {
    if(empty($error)) { 
        insert_category_name($_POST['category_name']);
        header($redirect);
        exit;
    }
}

// ブックマークに登録済みの投稿と返信を取得
$sql ='SELECT bookmarks.id, posts.post_id, bookmarks.post_id, pressed_at, created_at, content, posts.user_id, posts.file_path
FROM bookmarks 
INNER JOIN posts ON bookmarks.post_id = posts.post_id
WHERE bookmarks.user_id = :user_id
GROUP BY bookmarks.post_id';
$bm_post_stmt = $dbh->prepare($sql);
$bm_post_stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$bm_post_stmt->execute();
while ($bm_post_row = $bm_post_stmt->fetch()) { 
    $bookmarks[] = $bm_post_row;
}
$sql ='SELECT bookmarks.id, replies.reply_id, bookmarks.reply_id, pressed_at, created_at, content, replies.user_id
FROM bookmarks 
INNER JOIN replies ON bookmarks.reply_id = replies.reply_id
WHERE bookmarks.user_id = :user_id
GROUP BY bookmarks.reply_id';
$bm_rep_stmt = $dbh->prepare($sql);
$bm_rep_stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$bm_rep_stmt->execute();
while ($bm_rep_row = $bm_rep_stmt->fetch()) { 
    $bookmarks[] = $bm_rep_row;
}

// 投稿と返信をまとめた$bookmarksを押された順に並び変える
if (isset($bookmarks)) {
    array_multisort(array_map('strtotime', array_column($bookmarks, 'pressed_at')), SORT_DESC, $bookmarks) ;
}

// 投稿または返信をフォルダに追加
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    foreach ($_POST['ids'] as $bm_id) {
        $sql = 'SELECT post_id, reply_id FROM bookmarks
        WHERE id = :id';
        $bm_stmt = $dbh->prepare($sql);
        $bm_stmt->bindValue(':id', $bm_id, PDO::PARAM_INT);
        $bm_stmt->execute();
        $post_rep_id = $bm_stmt->fetch();

        if (empty($post_rep_id['post_id'])) {
            $sql = 'INSERT IGNORE INTO bookmarks (reply_id, user_id, category_id) 
            VALUES (:reply_id, :user_id, :category_id)';
            $ins_rep_stmt = $dbh->prepare($sql);
            $ins_rep_stmt->bindValue(':reply_id', $post_rep_id['reply_id'], PDO::PARAM_INT);
            $ins_rep_stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $ins_rep_stmt->bindValue(':category_id', $_POST['category_id'], PDO::PARAM_INT);
            $ins_rep_stmt->execute();
        } else {
            $sql = 'INSERT IGNORE INTO bookmarks (post_id, user_id, category_id) 
            VALUES (:post_id, :user_id, :category_id)';
            $ins_post_stmt = $dbh->prepare($sql);
            $ins_post_stmt->bindValue(':post_id', $post_rep_id['post_id'], PDO::PARAM_INT);
            $ins_post_stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $ins_post_stmt->bindValue(':category_id', $_POST['category_id'], PDO::PARAM_INT);
            $ins_post_stmt->execute();
        }
    }
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
                                <li><a href="category.php?id=<?= h($category_id['id']) ?>"><span>●</span><?= h($category_id['name']) ?></a></li>
                            <?php endforeach ?>  
                        </ul>
                        <!-- フォルダ名を追加  -->
                        <div class="">
                            <form action="" method="post">
                                <p>フォルダを追加する</p>
                                <input type="text" name="category_name">
                                <button type="submit">登録</button>
                            </form>
                        </div>
                        <?php if ($error['category_name'] === 'blank') : ?>
                            <p>入力してください。</p>
                        <?php endif; ?>

                        <?php if ($error['category_name'] === 'over') : ?>
                            <p>20字以内で入力してください。</p>
                        <?php endif; ?>
                    </div>   
                </div>

                <!-- ブックマーク一覧 -->
                <div class="bm-contents">
                    <?php foreach ($bookmarks as $bookmark) :
                        $block_bookmark = true;
                        foreach ($blocks as $block) {
                            if ($block['is_blocked'] === $bookmark['user_id'] ) {
                                $block_bookmark = false;
                            }
                        }
                        // ブロックしていない、されていない場合
                        if ($block_bookmark) :
                    ?>
                            <!-- チェックボックス -->
                            <div class="check-box">
                                <input type="checkbox" form="category" name="ids[]" value="<?= h($bookmark['id']) ?>">
                            </div>

                            <!-- アイコン -->
                            <div class="bm-icon">
                                <?php
                                $icon_row = get_icon($bookmark['user_id']);
                                if (empty($icon_row)) : ?>
                                    <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                <?php else : ?>        
                                    <p><img src="<?= h($icon_row) ?>" class="icon" ></p>
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
                                <p class="timeline-likes"><img src="images/heart.png"> <?= h(get_rep_likes_number($bookmark['reply_id'])) ?></p>
                            <?php else : ?>
                                <p class="timeline-likes"><img src="images/heart.png"> <?= h(get_likes_number($bookmark['post_id'])) ?></p>
                            <?php endif; ?>

                            <!-- ボタン -->
                            <div class="timeline-buttons">
                                <ul class="timeline-btn-list">
                                    <!-- 返信ボタン -->
                                    <?php if (isset($bookmark['reply_id'])) : ?>
                                        <li><button><a href="reply.php?r_id=<?= h($bookmark['reply_id']) ?>#reply">返信</a></button></li>
                                    <?php else : ?>
                                        <li><button><a href="reply.php?p_id=<?= h($bookmark['post_id']) ?>#reply">返信</a></button></li>
                                    <?php endif; ?>

                                    <!-- アカウントボタン -->
                                    <li><button><a href="user.php?id=<?= h($bookmark['user_id']) ?>">アカウント</a></button></li>

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
                                    <?php if($bookmark['user_id'] === $_SESSION['user_id']) : ?>
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
                        <?php endif; ?>
                    <?php endforeach ?>
                </div>

                <!-- 右サイドバー -->
                <div class="right-side">
                    <div class="right-side-bar">
                        <!-- 投稿または返信をフォルダリストに追加 -->
                        <form action="" method="post" id="category">
                            <label>リストに追加する</label>
                            <button type="submit">登録</button>
                            <div>
                                <select name="category_id" class="category-list">
                                    <option value="">選択してください</option>
                                    <?php foreach ($category_ids as $category_id) : ?>
                                        <option value="<?= h($category_id['id']) ?>"><?= h($category_id['name']) ?></option>
                                    <?php endforeach ?>    
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </body>    
</html>     