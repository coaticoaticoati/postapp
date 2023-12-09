<?php
ini_set("display_errors", "OFF");
require_once('functions.php');
session_start();

// ログインしていない場合はログインページへ強制的に移動
if (empty($_SESSION)) {
    header('Location: account_entry.php');
    exit;  
}

// データベース接続
$dbh = db_open();

// post_id、post_idを代入
$post_id = $_GET['p_id'];
$reply_id = $_GET['r_id'];

// 返信ボタンが押されてこのページに飛んできた場合、親投稿のid（$post_id）はパラメータに付与されていないため、取得する
if (empty($post_id)) {
    $sql = 'SELECT post_id FROM replies WHERE reply_id = :reply_id';
    $post_id_stmt = $dbh->prepare($sql);
    $post_id_stmt->bindValue(':reply_id', $_SESSION['reply_btn_reply_id'], PDO::PARAM_INT);
    $post_id_stmt->execute();
    $post_id = $post_id_stmt->fetch();
    $post_id = $post_id['post_id'];
}

// リダイレクト先を変数に代入
$redirect_back = 'Location: reply.php';

// ------- 投稿 -------

// いいねが押された場合
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

// 削除ボタンが押された場合
if (isset($_POST['delete_post'])) {
    delete_post($_POST['delete_post']); 
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

// ------- 返信 -------

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

// 親投稿に対する返信全件を取得し、ツリー構造を構築

$reply_stmt = get_reply($post_id);
$all_replies = $reply_stmt->fetchAll();
$reply_tree = build_reply_tree($all_replies);
function build_reply_tree($all_replies, $parent_id = null) {
    $tree = [];
    foreach ($all_replies as $reply) {
        if ($reply['reply_reply_id'] === $parent_id) {
            $reply['replies'] = build_reply_tree($all_replies, $reply['reply_id']);
            $tree[] = $reply;
        }
    }
    return $tree;
}

// ------ いいね -----

// 投稿へログインユーザーが行った、いいね全件を取得
$sql = 'SELECT post_is_liked_id FROM likes INNER JOIN posts ON likes.post_is_liked_id = posts.post_id
WHERE likes.user_id = :user_id';
$post_like_stmt = $dbh->prepare($sql);
$post_like_stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$post_like_stmt->execute();
while ($post_like_row = $post_like_stmt->fetch()) { 
    $post_likes[] = $post_like_row;
}

// 返信へログインユーザーが行った、いいね全件を取得
$sql = 'SELECT reply_is_liked_id FROM reply_likes INNER JOIN replies ON reply_likes.reply_is_liked_id = replies.reply_id
WHERE reply_likes.user_id = :user_id';
$reply_like_stmt = $dbh->prepare($sql);
$reply_like_stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$reply_like_stmt->execute();
while ($reply_like_row = $reply_like_stmt->fetch()) { 
    $reply_likes[] = $reply_like_row;
}

// ------ バリデーション -------

// 親投稿への返信フォーム

// 空文字の場合
if($_POST['reply_form'] === '') {
    $reply_error['reply_form'] = 'blank';
}

// 文章が200字以内か
if(strlen($_POST['reply_form']) > 600) {
    $reply_error['reply_form'] = 'over';
}

// ボタンが押された場合
if (isset($_POST['reply_form'])) {
    if(empty($reply_error)) { 
        insert_reply((int)$_POST['post_id'], $_POST['reply_form']);
        header('Location: reply.php');
        exit;
    }
}

// 返信への返信フォーム

// 空文字の場合
if($_POST['reply_reply_form'] === '') {
    $reply_reply_error['reply_reply_form'] = 'blank';
    $reply_reply_error['reply_id'] = $_POST['reply_id'];
}

// 文章が200字以内か
if(strlen($_POST['reply_reply_form']) > 200) {
    $reply_reply_error['reply_reply_form'] = 'over';
    $reply_reply_error['reply_id'] = $_POST['reply_id'];
}


// ボタンが押された場合
if (isset($_POST['reply_reply_form'])) {
    if(empty($reply_reply_error)) { 
        insert_reply_reply((int)$_POST['post_id'], $_POST['reply_reply_form'], (int)$_POST['reply_id']);
        header('Location: reply.php');
        exit;
    }
}

// ------- ブロック ---------

// ログインユーザーがブロックしている、ログインユーザーをブロックしているユーザーを取得
// 投稿を表示せず、「表示できません」のメッセージをつける
$block_stmt = block_user();
while ($block_row = $block_stmt->fetch()) {
    $blocks[] = $block_row;
}

?>    

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>投稿文詳細</title>
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
            <div class="reply">
                <div class="container">
                    <!-- 親投稿 -->
                    <div class="reply-parent">
                        <?php
                        // 親投稿を取得
                        $post_row = get_posts($post_id);
                        // ブロックしている、されているか確認
                        $block_reply = true;
                        foreach ($blocks as $block) {
                            if ($block['is_blocked'] === $post_row['user_id']) {
                                $block_reply = false;
                            }
                        }
                        // ブロックしている、されている場合
                        if ($block_reply === false) : 
                        ?>    
                            <p class="block-post">このポストは表示できません。</p>

                        <!-- ブロックしていない、されていない場合 -->    
                        <?php else : ?>
                            <!-- 親投稿が削除された場合 -->    
                            <?php if (empty($post_row)) : ?>
                                <p class="block-post">このポストは削除されました。</P>

                            <!-- 親投稿が削除されていない場合 -->    
                            <?php else : ?>
                                <!-- アイコン -->
                                <?php $icon_row = get_icon($post_row['user_id']) ?>
                                <?php if (empty($icon_row)) : ?>
                                    <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                <?php else : ?>    
                                    <p><img src="<?= h($icon_row) ?>" class="icon"></p>
                                <?php endif; ?>

                                <!-- ユーザー名 -->
                                <p class="reply-username"><?= h(get_user_name($post_row['user_id'])) ?></p>
                                
                                <!-- 投稿文 -->
                                <p><?= h($post_row['content']) ?></p>

                                <!-- 画像 -->
                                <?php if(isset($post_row['file_path'])) : ?>
                                    <P><img src="<?= h($post_row['file_path']) ?>" class="image"></P>
                                <?php endif; ?>

                                <!-- 投稿日時 -->
                                <p><?= h($post_row['created_at']) ?></p>

                                <!-- いいねの数 -->
                                <p class="timeline-likes"><img src="images/heart.png"> <?= h(get_likes_number($post_row['post_id'])) ?></p>
                                
                                <!-- ボタン -->
                                <div class="reply-parent-buttons">      
                                    <ul class="reply-parent-btn-list">
                                        <!-- アカウントボタン -->
                                        <li><button><a href="user.php?id=<?= h($post_row['user_id']) ?>">アカウント</a></button></li>

                                        <!-- いいねボタン -->
                                        <form action="" method="post">
                                            <?php
                                            $is_liked = false;
                                            foreach ($post_likes as $post_like) {
                                                if($post_like['post_is_liked_id'] === $post_row['post_id']) {
                                                    $is_liked = true;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <?php if ($is_liked) : ?>
                                                <input type="hidden" name="delete_like" value=<?= h($post_row['post_id']) ?>>
                                                <li><button type="submit">いいね解除</button></li>
                                            <?php else : ?>
                                                <input type="hidden" name="insert_like" value=<?= h($post_row['post_id']) ?>>
                                                <li><button type="submit">いいね</button></li>
                                            <?php endif; ?> 
                                        </form>

                                        <!-- ブックマークボタン -->
                                        <?php $post_bm_id = get_bookmarks($post_row['post_id']) ?>
                                        <form action="" method="post">
                                            <?php if ($post_bm_id) : ?>
                                                <input type="hidden" name="delete_bm" value=<?= h($post_row['post_id']) ?>>
                                                <li><button type="submit">ブックマーク解除</button></li>
                                            <?php else : ?>
                                                <input type="hidden" name="insert_bm" value=<?= h($post_row['post_id']) ?>>
                                                <li><button type="submit">ブックマーク</button></li>
                                            <?php endif; ?>                         
                                        </form>
                                        
                                        <!-- 削除ボタン -->
                                        <?php if($post_row['user_id'] === $_SESSION['user_id']) : ?>
                                            <form action="" method="post">
                                                <input type="hidden" name="delete_post" value=<?= h($post_row['post_id']) ?>>
                                                <li><button type="submit">削除</button></li>
                                            </form>
                                        <?php endif; ?>
                                    </ul>
                                </div>

                                <!-- 返信フォーム -->
                                <div>
                                    <form action="" method="post" class="reply-parent-form">
                                        <textarea name="reply_form"></textarea>
                                        <input type="hidden" name="post_id" value=<?= h($post_row['post_id']) ?>>
                                        <button type="submit">返信</button>
                                    </form>

                                    <!--エラーメッセージの表示 -->
                                    <?php if ($reply_error['reply_form'] === 'blank') : ?>
                                        <p>入力してください。</p>
                                    <?php endif; ?>

                                    <?php if ($reply_error['reply_form'] === 'over') : ?>
                                        <p>200字以内で入力してください。</p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>         
                        <?php endif; ?> 
                    </div>
                </div>

                <div class="container">
                    <!-- 返信一覧 -->                 
                    <?php
                    display_reply_tree($reply_tree);

                    function display_reply_tree($tree, $depth = 0) {
                        foreach ($tree as $reply) :
                            $block_reply = true;
                            global $blocks;
                            foreach ($blocks as $block) {
                                if ($block['is_blocked'] === $reply['user_id']) {
                                    $block_reply = false;
                                }
                            }
                    ?>
                                <div class="reply-children">
                                    <?php if ($block_reply === false) : // ブロックしている、されている場合 ?>    
                                        <p class="block-reply">このポストは表示できません。</p>
                                    <?php else : // ブロックない場合 ?>
                                        <div class="reply-child">
                                            <!-- アイコン -->
                                            <?php $icon_row = get_icon($reply['user_id']) ?>
                                            <!-- index.php等にて返信ボタンを押すと対象の投稿へ飛ぶようにする-->
                                            <?php if ($reply_id === $reply['reply_id']) : ?>
                                                <?php if (empty($icon_row)) : ?>
                                                    <p id="reply"><img src="images/animalface_tanuki.png" class="icon"><p>
                                                    <?php else : ?>    
                                                        <p id="reply"><img src="<?= h($icon_row) ?>" class="icon"></p>
                                                    <?php endif; ?>
                                            <?php else : ?>
                                                <?php if (empty($icon_row)) : ?>
                                                    <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                                <?php else : ?>        
                                                    <p><img src="<?= h($icon_row) ?>" class="icon" ></p>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <!-- ユーザー名 -->
                                            <p class="reply-username"><?= h(get_user_name($reply['user_id'])) ?></p>
                                        </div>

                                        <!-- 返信文 -->
                                        <p class="reply-content"><?= h(str_repeat('→ ', $depth). $reply['content']) ?></p>

                                        <!-- 日時 -->
                                        <p class="reply-date"><?= h($reply['created_at']) ?></p>

                                        <!--　いいねの数　-->
                                        <p class="timeline-likes"><img src="images/heart.png"> <?= h(get_rep_likes_number($reply['reply_id'])) ?></p>
                            
                                        <div class="reply-child-buttons">
                                            <ul class="reply-child-btn-list">
                                                <!-- アカウントボタン -->
                                                <li><button class="user-button"><a href="user.php?id=<?= h($reply['user_id']) ?>">アカウント</a></button></li>

                                                <!-- いいねボタン -->
                                                <form action="" method="post">
                                                    <?php
                                                    $is_liked = false;
                                                    global $reply_likes;
                                                    foreach ($reply_likes as $reply_like) {
                                                        if($reply_like['reply_is_liked_id'] === $reply['reply_id']) {
                                                            $is_liked = true;
                                                            break;
                                                        }
                                                    }
                                                    ?>
                                                    <?php if ($is_liked) : ?>
                                                        <input type="hidden" name="delete_reply_like" value=<?= h($reply['reply_id']) ?>>
                                                        <li><button type="submit" class="user-button">いいね解除</button></li>
                                                    <?php else : ?>
                                                        <input type="hidden" name="insert_reply_like" value=<?= h($reply['reply_id']) ?>>
                                                        <li><button type="submit" class="user-button">いいね</button></li>
                                                    <?php endif; ?> 
                                                </form>

                                                <!-- ブックマークボタン -->
                                                <?php $reply_bm_id = get_rep_bookmarks($reply['reply_id']) ?>
                                                <form action="" method="post">
                                                    <?php if ($reply_bm_id) : ?>
                                                        <input type="hidden" name="delete_reply_bm" value=<?= h($reply['reply_id']) ?>>
                                                        <li><button type="submit" class="user-button">ブックマーク解除</button></li>
                                                    <?php else : ?>
                                                        <input type="hidden" name="insert_reply_bm" value=<?= h($reply['reply_id']) ?>>
                                                        <li><button type="submit" class="user-button">ブックマーク</button></li>
                                                    <?php endif; ?>                         
                                                </form>

                                                <!-- 削除ボタン -->
                                                <?php if($reply['user_id'] === $_SESSION['user_id']) : ?>
                                                    <form action="" method="post">
                                                        <input type="hidden" name="delete_reply" value=<?= h($reply['reply_id']) ?>>
                                                        <li><button type="submit" class="user-button">削除</button></li>
                                                    </form>
                                                <?php endif; ?>
                                            </ul>
                                        </div>

                                        <!-- 返信フォーム -->
                                        <form action="" method="post" class="reply-child-form">
                                            <textarea name="reply_reply_form" ></textarea>
                                            <input type="hidden" name="post_id" value=<?= h($reply['post_id']) ?>>
                                            <input type="hidden" name="reply_id" value=<?= h($reply['reply_id']) ?>>
                                            <button type="submit">返信</button>
                                        </form>
                                        
                                        <!-- エラーメッセージの表示 -->
                                        <?php
                                        global $reply_reply_error;
                                        if ($reply['reply_id'] == $reply_reply_error['reply_id']) :
                                            if ($reply_reply_error['reply_reply_form'] === 'blank') : 
                                        ?>
                                            <p>入力してください。</p>
                                            <?php endif; ?>
                                            <?php if ($reply_reply_error['reply_reply_form'] == 'over') : ?>
                                                <p>200字以内で入力してください。</p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>                
                            <?php    
                            if (!empty($reply['replies'])) {
                                display_reply_tree($reply['replies'], $depth + 1);
                            }
                            ?>
                        <?php endforeach; ?>
                    <?php } ?>
                </div>
            </div>
        </main>  
    </body>
</html>    