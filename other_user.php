<?php
ini_set("display_errors", "OFF");
require_once('functions.php');
session_start();

// ログインユーザーの場合、user.phpへ
if ($_SESSION['other_user'] === $_SESSION['login']['member_id']) {
    header('Location: user.php');
    exit;
}

// データベース接続
$dbh = db_open();

// アカウントボタンが押された場合
if (isset($_POST['user_page'])) {
    $_SESSION['other_user'] = (int)$_POST['user_page'];
    header('Location: other_user.php');
    exit;
}

// 返信ボタンが押された場合
if (isset($_POST['reply_btn'])) {
    $_SESSION['reply_btn'] = (int)$_POST['reply_btn'];
    header('Location: reply.php');
    exit;
}

// ----プロフィール----

// アイコンを取得
$icon_row = get_icon($_SESSION['other_user']);

// プロフィール文を取得
$profile_row = get_profile($_SESSION['other_user']);

// -----投稿------

// データベースからユーザーの投稿を取得
$post_stmt = get_user_posts($_SESSION['other_user']);

// いいねが押された場合
if (isset($_POST['insert_like'])) {
    insert_like((int)$_POST['insert_like']);
    header('Location: other_user.php');
    exit;
}

// いいね解除ボタンが押された場合
if (isset($_POST['delete_like'])) {
    delete_like((int)$_POST['delete_like']);
    header('Location: other_user.php');
    exit;
}

// ブックマークボタンが押された場合
if (isset($_POST['insert_bm'])) {
    insert_bookmark((int)$_POST['insert_bm']);
    header('Location: index.php');
    exit;
}

// ブックマーク解除ボタンが押された場合
if (isset($_POST['delete_bm'])) {
    delete_bookmark((int)$_POST['delete_bm']);
    header('Location: index.php');
    exit;
}

// -----返信------

// データベースからユーザーの返信を取得
$reply_stmt = get_user_reply($_SESSION['other_user']); 

// いいねが押された場合
if (isset($_POST['insert_reply_like'])) {
    insert_reply_like((int)$_POST['insert_reply_like']);
    header('Location: other_user.php');
    exit;
}

// いいね解除ボタンが押された場合
if (isset($_POST['delete_reply_like'])) {
    delete_reply_like((int)$_POST['delete_reply_like']);
    header('Location: other_user.php');
    exit;
}

// ブックマークボタンが押された場合
if (isset($_POST['insert_reply_bm'])) {
    insert_rep_bookmark((int)$_POST['insert_reply_bm']);
    header('Location: index.php');
    exit;
}

// ブックマーク解除ボタンが押された場合
if (isset($_POST['delete_reply_bm'])) {
    delete_rep_bookmark((int)$_POST['delete_reply_bm']);
    header('Location: index.php');
    exit;
}


// 投稿と返信に対して、参照中ユーザーが行った、いいね全件を取得

// 投稿のいいね全件を取得
$sql = 'SELECT post_id, pressed_at, created_at, content, posts.user_id, file_path FROM likes
INNER JOIN posts ON likes.post_is_liked_id = posts.post_id
WHERE likes.user_id = :user_id';
$post_like_stmt = $dbh->prepare($sql);
$post_like_stmt->bindValue(':user_id', $_SESSION['other_user'], PDO::PARAM_INT);  
$post_like_stmt->execute();
while ($post_like_row = $post_like_stmt->fetch()) { 
    $post_likes_reps[] = $post_like_row;
}

// 返信のいいね全件を取得
$sql = 'SELECT reply_id, pressed_at, created_at, content, replies.user_id, post_id FROM reply_likes 
INNER JOIN replies ON reply_likes.reply_is_liked_id = replies.reply_id
WHERE reply_likes.user_id = :user_id';
$reply_like_stmt = $dbh->prepare($sql);
$reply_like_stmt->bindValue(':user_id', $_SESSION['other_user'], PDO::PARAM_INT);  
$reply_like_stmt->execute();
while ($reply_like_row = $reply_like_stmt->fetch()) { 
    $post_likes_reps[] = $reply_like_row;
}

// 投稿と返信をまとめた$post_likes_repsを日付順に並び変える
if (isset($post_likes_reps)) {
    array_multisort(array_map( 'strtotime', array_column($post_likes_reps, 'pressed_at')), SORT_DESC, $post_likes_reps) ;
}

// ------フォロー、フォロワー-----

// フォロー解除
if (isset($_POST['delete_follow'])) {
    delete_follow((int)$_POST['delete_follow']);  
    header('Location: other_user.php');
    exit; 
}

// 参照中ユーザーのフォロワー一覧を取得 
$is_followed_stmt = get_follower($_SESSION['other_user']);

// フォロー登録
if (isset($_POST['insert_follow'])) {
    insert_follow((int)$_POST['insert_follow']);
    header('Location: other_user.php');
    exit;
}

// ログインユーザーのフォロー一覧を取得
$following_user_list = get_follow($_SESSION['login']['member_id']);

// -------ブロック--------

// ブロック情報を取得

// ログインユーザーが参照中ユーザーをブロックしているか確認し、していればblock.phpへ
$block_stmt = get_block_user($_SESSION['other_user']);
$block_user = $block_stmt->fetch();
if (!empty($block_user)) {
    header('Location: block.php');
    exit;   
}

// ログインユーザーが参照中ユーザーにブロックされてるか確認し、されていればblock.phpへ
$is_blocked_stmt = get_blocked_user($_SESSION['other_user']);
$blocked_user = $is_blocked_stmt->fetch();
if (!empty($blocked_user)) {
    header('Location: block.php');
    exit;   
}

// ブロック登録ボタンが押された場合

// データベース登録
if (isset($_POST['block_user'])) {
    $sql = 'INSERT INTO blocks (block, is_blocked) 
    VALUES (:block, :is_blocked)';
    $block_ins_stmt = $dbh->prepare($sql);
    $block_ins_stmt->bindValue(':block', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $block_ins_stmt->bindValue(':is_blocked', $_SESSION['other_user'], PDO::PARAM_INT);
    $block_ins_stmt->execute();

    // フォロー解除
    // 
    foreach ($following_user_list as $following_user) {
        if ($following_user['is_followed'] === $_SESSION['other_user']) {
            delete_follow($_SESSION['other_user']);
            break;
        }    
    }
    // 
    foreach ($other_user_follows as $other_user_follow) {
        if ($other_user_follow['is_followed'] === $_SESSION['login']['member_id']) {
            $sql = 'DELETE FROM follows WHERE follow = :follow AND is_followed = :is_followed'; // followかつis_followedであるものを削除する
            $follow_del_stmt = $dbh->prepare($sql);
            $follow_del_stmt->bindValue(':follow', $_SESSION['other_user'], PDO::PARAM_INT );
            $follow_del_stmt->bindValue(':is_followed', $_SESSION['login']['member_id'], PDO::PARAM_INT);
            $follow_del_stmt->execute();
            break;
        }   
    }
    header('Location: block.php');
    exit;   
}
?>    
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title><?= h(get_user_name($_SESSION['other_user'])) ?>さんのプロフィール</title>
        <link rel="stylesheet" href="stylesheet.css">
    </head>
    <body>
        <header>
            <div class="navbar-sitename"><a href="index.php">postapp</div>
            <div class="navbar-list">
                <ul>
                    <li class="navbar-item"><a href="logout.php">ログアウト</a></li>
                    <li><a href="user.php">プロフィール</a></li>
                    <li><a href="users_list.php">ユーザー一覧</a></li>
                    <li><a href="search.php">検索</a></li>
                </ul>
            </div>
        </header>    
        <main>
            <div class="other-user">
                <div class="container">
                    <div class="user-profile">
                        <!-----アイコン----->
                        <?php if (empty($icon_row)) : ?>
                            <p><img src="images/animalface_tanuki.png" class="icon"><p>
                        <?php else : ?>    
                            <p><img src="<?= h($icon_row['file_path']) ?>" class="icon"></p>
                        <?php endif; ?>
                        <!-----ユーザー名------>
                        <h3><?= h($user_name_row['name']) ?></h3>
                        <!----プロフィール文---->
                        <p><?= h($profile_row['profile_content']) ?></p>
                        <div class="follow-block">
                            <!-----フォロー、アンフォロー----->
                            <form action="" method="post">
                                <?php
                                $is_following = false;
                                // $following_user_list：ログインユーザーにフォローされているユーザー全件の配列
                                // ログインユーザーにフォローされていれば、trueとする
                                foreach ($following_user_list as $following_user) {
                                    if($following_user['is_followed'] === $_SESSION['other_user']) { 
                                        $is_following = true;
                                        break;
                                    }
                                }
                                ?> 
                                <?php 
                                if ($is_following) : ?>
                                    <input type="hidden" name="delete_follow" value=<?= h($_SESSION['other_user']) ?>>
                                    <button type="submit">フォロー解除</button>
                                <?php else : ?>
                                    <input type="hidden" name="insert_follow" value=<?= h($_SESSION['other_user']) ?>>
                                    <button type="submit">フォローする</button>
                                <?php endif; ?>
                            </form>
                            <!-----ブロック----->
                            <form action="" method="post">
                                <input type="hidden" name="block_user" value=<?= h($_SESSION['other_user']) ?>>  
                                <button type="submit">ブロックする</button>
                            </form>
                        </div>    
                    </div>    

                    <!---------投稿一覧--------->
                    <div class="user-posts">
                        <h3>投稿一覧</h3>
                        <?php while ($post_row = $post_stmt->fetch()) : ?>
                            <div class="user-post">
                                <!-----アイコン----->
                                <?php if (empty($icon_row)) : ?>
                                    <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                <?php else : ?>    
                                    <p><img src="<?= h($icon_row['file_path']) ?>" class="icon"></p>
                                <?php endif; ?>                                
                                <!-----ユーザー名----->
                                <p class="user-username"><?= h(get_user_name($post_row['user_id'])) ?></p>
                            </div>    

                            <!-----投稿文----->
                            <p class="user-post-stmt"><?= h($post_row['content']) ?></p>
                            
                            <!-----画像------>
                            <?php if(isset($post_row['file_path'])) : ?>
                                <p class="user-post-image"><img src="<?= h($post_row['file_path']) ?>" class="image"></p>
                            <?php endif; ?>
                            
                            <!-----いいねの数----->
                            <p class=""><img src="images/heart.png"> <?= h(get_likes_number($post_row['post_id'])) ?></p>
                        
                            <!-----投稿日時----->
                            <p><?= h($post_row['created_at']) ?></p>

                            <!-----ボタン---->
                            <div class="user-post-buttons">
                                <ul class="user-post-btn-list">
                                    <!-----返信ボタン---->
                                    <form action="" method="post">
                                        <input type="hidden" name="reply_btn" value=<?= h($post_row['post_id']) ?>>  
                                        <li><input type="submit" value="返信" class="user-button"></li>
                                    </form>
                        
                                    <!-----いいねボタン------>
                                    <?php $post_is_liked_id = get_likes($post_row['post_id']) ?>
                                    <form action="" method="post">
                                        <?php if ($post_is_liked_id) : ?>
                                            <input type="hidden" name="delete_like" value=<?= h($post_row['post_id']) ?>>
                                            <li><input type="submit" value="いいね解除" class="user-button"></li>
                                        <?php else : ?>
                                            <input type="hidden" name="insert_like" value=<?= h($post_row['post_id']) ?>>
                                            <li><input type="submit" value="いいね" class="user-button"></li>
                                        <?php endif; ?> 
                                    </form>

                                    <!-----ブックマークボタン------>
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
                                </ul>
                            </div>    
                        <?php endwhile; ?>
                    </div>

                    <!---------返信一覧--------->
                    <div class="user-replies">
                        <h3>返信一覧</h3>
                        <?php while ($reply_row = $reply_stmt->fetch()) : ?>
                            <div class="user-reply">
                                <!-----アイコン----->
                                <?php
                                $icon_stmt = get_icon($reply_row['user_id']);
                                $icon_row = $icon_stmt->fetch();
                                ?>
                                <?php if (empty($icon_row)) : ?>
                                    <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                <?php else : ?>    
                                    <p><img src="<?= h($icon_row['file_path']) ?>" class="icon"></p>
                                <?php endif; ?>
                                <!-----ユーザー名----->
                                <p class="user-username"><?= h(get_user_name($reply_row['user_id'])) ?></p>
                            </div>

                            <!-----投稿文----->
                            <p class="user-post-stmt"><?= h($reply_row['content']) ?></p>
                            
                            <!-----画像------>
                            <?php if(isset($reply_row['file_path'])) : ?>
                                <p><img src="<?= h($reply_row['file_path']) ?>" class="image"></p>
                            <?php endif; ?>
                            
                            <!-----いいねの数----->
                            <p class=""><img src="images/heart.png"> <?= h(get_rep_likes_number($reply_row['reply_id'])) ?></p>

                            <!-----投稿日時----->
                            <p><?= h($reply_row['created_at']) ?></p>
                            
                            <!-----ボタン---->
                            <div class="user-reply-buttons">
                                <ul class="user-reply-btn-list">
                                    <!-----返信ボタン---->
                                    <form action="" method="post">
                                        <input type="hidden" name="reply_btn" value=<?= h($reply_row['post_id']) ?>>  
                                        <li><input type="submit" value="返信" class="user-button"></li>
                                    </form>

                                    <!-----いいねボタン------>
                                    <?php $reply_is_liked_id = get_rep_likes($reply_row['reply_id']) ?>
                                    <form action="" method="post">
                                        <?php if ($reply_is_liked_id) : ?>
                                            <input type="hidden" name="delete_reply_like" value=<?= h($reply_row['reply_id']) ?>>
                                            <li><input type="submit" value="いいね解除" class="user-button"></li>
                                        <?php else : ?>
                                            <input type="hidden" name="insert_reply_like" value=<?= h($reply_row['reply_id']) ?>>
                                            <li><input type="submit" value="いいね" class="user-button"></li>
                                        <?php endif; ?> 
                                    </form>

                                    <!-----ブックマークボタン------>
                                    <?php $reply_bm_id = get_rep_bookmarks($reply_row['reply_id']) ?>
                                    <form action="" method="post">
                                        <?php if ($reply_bm_id) : ?>
                                            <input type="hidden" name="delete_reply_bm" value=<?= h($reply_row['reply_id']) ?>>
                                            <li><button type="submit">ブックマーク解除</button></li>
                                        <?php else : ?>
                                            <input type="hidden" name="insert_reply_bm" value=<?= h($reply_row['reply_id']) ?>>
                                            <li><button type="submit">ブックマーク</button></li>
                                        <?php endif; ?>                         
                                    </form>
                                </ul>
                            </div>
                        <?php endwhile; ?> 
                    </div>    

                    <!---------いいね一覧---------->
                    <div class="user-goods">
                    <h3>いいね一覧</h3>                
                        <?php foreach ($post_likes_reps as $post_like_rep) : ?>
                            <div class="user-good">
                                <!-----アイコン----->
                                <?php
                                $icon_stmt = get_icon($post_like_rep['user_id']);
                                $icon_row = $icon_stmt->fetch();
                                ?>
                                <?php if (empty($icon_row)) : ?>
                                    <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                <?php else : ?>    
                                    <p><img src="<?= h($icon_row['file_path']) ?>" class="icon"></p>
                                <?php endif; ?>  

                                <!-----ユーザー名----->
                                <p class="user-username"><?= h(get_user_name($post_like_rep['user_id'])) ?></p>
                            </div>

                            <!-----投稿文----->
                            <?php if (isset($post_like_rep['reply_id'])) : ?>  
                                <p class="user-post-stmt">RE:<?= h($post_like_rep['content']) ?></p>
                            <?php else : ?>
                                <p class="user-post-stmt"><?= h($post_like_rep['content']) ?></p>
                            <?php endif; ?>
                            
                            <!-----画像------>
                            <?php if(isset($post_like_rep['file_path'])) : ?>
                                <p><img src="<?= h($post_like_rep['file_path']) ?>" class="image"></p>
                            <?php endif; ?>
                            
                            <!-----いいねの数----->
                            <?php if (isset($post_like_rep['reply_id'])) : ?>
                                <p class=""><img src="images/heart.png"> <?= h(get_rep_likes_number($post_like_rep['reply_id'])) ?></p>
                            <?php else : ?>
                                <p class=""><img src="images/heart.png"> <?= h(get_likes_number($post_like_rep['post_id'])) ?></p>
                            <?php endif; ?>
                        
                            <!-----投稿日時----->
                            <p><?= h($post_like_rep['created_at']) ?></p>

                            <!-----ボタン---->
                            <div class="user-good-buttons">
                                <ul class="user-good-btn-list">
                                    <!-----返信ボタン---->
                                    <form action="" method="post">
                                        <input type="hidden" name="reply_btn" value=<?= h($post_like_rep['post_id']) ?>>  
                                        <li><input type="submit" value="返信" class="user-button"></li>
                                    </form>

                                    <!-----アカウントボタン----->
                                    <?php if ($post_like_rep['user_id'] != $_SESSION['other_user']) : ?>
                                    <form action="" method="post">
                                        <input type="hidden" name="user_page" value=<?= h($post_like_rep['user_id']) ?>>
                                        <li><input type="submit" value="アカウント" class="user-button"></li>
                                    </form>
                                    <?php endif ?>

                                    <!-----いいねボタン------>
                                    <!-----返信に対するいいねボタン------>
                                    <?php if (isset($post_like_rep['reply_id'])) : ?>
                                        <form action="" method="post">
                                            <?php
                                            $reply_is_liked = false;
                                            foreach ($reply_likes as $reply_like) {
                                                if($reply_like['reply_is_liked_id'] === $post_like_rep['reply_id']) {
                                                    $reply_is_liked = true;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <?php if ($reply_is_liked) : ?>
                                                <input type="hidden" name="delete_reply_like" value=<?= h($post_like_rep['reply_id']) ?>>
                                                <li><input type="submit" value="いいね解除" class="user-button"></li>
                                            <?php else : ?>
                                                <input type="hidden" name="insert_reply_like" value=<?= h($post_like_rep['reply_id']) ?>>
                                                <li><input type="submit" value="いいね" class="user-button"></li>
                                            <?php endif; ?>                         
                                        </form>
                                    <?php else : ?>
                                        <!-----投稿に対するいいねボタン------>
                                        <form action="" method="post">
                                            <?php
                                            $post_is_liked = false;
                                            foreach($post_likes as $post_like) {
                                                if($post_like['post_is_liked_id'] === $post_like_rep['post_id']) {
                                                    $post_is_liked = true;
                                                    break;
                                                }
                                            }
                                            ?>
                                            <?php if ($post_is_liked) : ?>
                                                <input type="hidden" name="delete_like" value=<?= h($post_like_rep['post_id']) ?>>
                                                <li><input type="submit" value="いいね解除" class="user-button"></li>
                                            <?php else : ?>
                                                <input type="hidden" name="insert_like" value=<?= h($post_like_rep['post_id']) ?>>
                                                <li><input type="submit" value="いいね" class="user-button"></li>
                                            <?php endif; ?>                         
                                        </form>
                                    <?php endif; ?>
                                        <!-----返信に対するブックマークボタン------>
                                        <?php if (isset($post_like_rep['reply_id'])) : ?>
                                            <?php $reply_bm_id = get_rep_bookmarks($post_like_rep['reply_id']) ?>
                                            <form action="" method="post">
                                                <?php if ($reply_bm_id) : ?>
                                                    <input type="hidden" name="delete_reply_bm" value=<?= h($post_like_rep['reply_id']) ?>>
                                                    <li><button type="submit">ブックマーク解除</button></li>
                                                <?php else : ?>
                                                    <input type="hidden" name="insert_reply_bm" value=<?= h($post_like_rep['reply_id']) ?>>
                                                    <li><button type="submit">ブックマーク</button></li>
                                                <?php endif; ?>                         
                                            </form>

                                        <?php else : ?>

                                            <!-----投稿に対するブックマークボタン------>
                                            <?php
                                            $post_bm_id = get_bookmarks($post_like_rep['post_id']) ?>
                                            <form action="" method="post">
                                                <?php if ($post_bm_id) : ?>
                                                    <input type="hidden" name="delete_bm" value=<?= h($post_like_rep['post_id']) ?>>
                                                    <li><button type="submit">ブックマーク解除</button></li>
                                                <?php else : ?>
                                                    <input type="hidden" name="insert_bm" value=<?= h($post_like_rep['post_id']) ?>>
                                                    <li><button type="submit">ブックマーク</button></li>
                                                <?php endif; ?>                         
                                            </form>
                                        <?php endif; ?>
                        
                                    <!-----削除ボタン------>
                                    <!---ログインユーザーの投稿or返信いいねのみ表示する------>
                                    <?php if ($post_like_rep['user_id'] === $_SESSION['login']['member_id']) : ?>
                                        <!-----返信に対する削除ボタン------>
                                        <?php if (isset($post_like_rep['reply_id'])) : ?>
                                            <form action="" method="post">
                                                <input type="hidden" name="delete_reply" value=<?= h($post_like_rep['reply_id']) ?>>
                                                <li><input type="submit" value="削除" class="user-button"></li>
                                            </form>
                                        <!-----投稿に対する削除ボタン------>            
                                        <?php else : ?>
                                            <form action="" method="post">
                                                <input type="hidden" name="delete_post" value=<?= h($post_like_rep['post_id']) ?>>
                                                <li><input type="submit" value="削除" class="user-button"></li>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?> 

                    <!---------フォロー一覧---------->
                    <div class="user-follows">
                        <h3>フォロー一覧</h3>
                        <?php $other_user_follows = get_follow($_SESSION['other_user']) ?>
                        <?php foreach ($other_user_follows as $other_user_follow) : ?>
                            <div class="user-follow">
                                <!-----アイコン----->      
                                <?php 
                                $icon_stmt = get_icon($other_user_follow['is_followed']);
                                $icon_row = $icon_stmt->fetch();
                                ?>
                                <?php if (empty($icon_row)) : ?>
                                    <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                <?php else : ?>    
                                    <p><img src="<?= h($icon_row['file_path']) ?>" class="icon"></p>
                                <?php endif; ?>
                                <!-----ユーザー名----->
                                <p class="user-username"><?= h(get_user_name($other_user_follow['is_followed'])) ?></p>
                            </div>

                            <div class="user-follow-buttons">
                                <ul class="user-follow-btn-list">
                                <!-----アカウントボタン----->
                                    <form action="" method="post">
                                        <input type="hidden" name="user_page" value=<?= h($other_user_follow['is_followed']) ?>>
                                        <li><input type="submit" value="アカウント" class="user-button"></li>
                                    </form>
                         
                                <!-----フォロー、アンフォロー----->
                                <!---$other_user_follow（参照中ユーザーにフォローされているユーザー全件の配列）のうち、ログインユーザーであるものはスキップする---->
                                    <?php if ($other_user_follow['member_id'] !== $_SESSION['login']['member_id']) : ?>
                                        <form action="" method="post">
                                            <?php
                                            $is_following = false;
                                            // $following_user_list：ログインユーザーにフォローされているユーザー全件の配列
                                            // ログインユーザーにフォローされていれば、trueとする
                                            foreach ($following_user_list as $following_user) {
                                                if($following_user['is_followed'] === $other_user_follow['member_id']) { 
                                                    $is_following = true;
                                                    break;
                                                }
                                            }
                                            ?> 
                                            <?php 
                                            if ($is_following) : ?>
                                                <input type="hidden" name="delete_follow" value=<?= h($other_user_follow['member_id']) ?>>
                                                <li><input type="submit" value="フォロー解除" class="user-button"></li>
                                            <?php else : ?>
                                                <input type="hidden" name="insert_follow" value=<?= h($other_user_follow['member_id']) ?>>
                                                <li><input type="submit" value="フォローする" class="user-button"></li>
                                            <?php endif; ?>  
                                        
                                        </form>
                                    <?php endif; ?> 
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!---------フォロワー一覧--------->
                    <div class="user-followers">
                        <h3>フォロワー一覧</h3>
                        <?php while ($is_followed_row = $is_followed_stmt->fetch()) : ?>
                        <div class="user-follower">
                            <!-----アイコン----->
                            <?php 
                            $icon_stmt = get_icon($is_followed_row['follow']);
                            $icon_row = $icon_stmt->fetch();
                            ?>
                            <?php if (empty($icon_row)) : ?>
                                <p><img src="images/animalface_tanuki.png" class="icon"><p>
                            <?php else : ?>    
                                <p><img src="<?= h($icon_row['file_path']) ?>" class="icon"></p>
                            <?php endif; ?>
                            <!-----ユーザー名----->
                            <p class="user-username"><?= h(get_user_name($is_followed_row['follow'])) ?></p>
                        </div>

                        <div class="user-follower-buttons">
                            <ul class="user-follower-btn-list"> 
                                <!-----アカウントボタン----->
                                <form action="" method="post">
                                    <input type="hidden" name="user_page" value=<?= h($is_followed_row['follow']) ?>>
                                    <li><input type="submit" value="アカウント" class="user-button"></li>
                                </form>
                                <!-----フォロー、アンフォロー----->
                                <!---$is_followed_rowがログインユーザーであるものはスキップする---->
                                <?php if ($is_followed_row['member_id'] !== $_SESSION['login']['member_id']) : ?>
                                    <form action="" method="post">
                                        <?php
                                    $is_followed = false;
                                        // $following_user_list：ログインユーザーにフォローされているユーザー全件の配列
                                        // ログインユーザーにフォローされていれば、trueとする
                                        foreach ($following_user_list as $following_user) {
                                            if($following_user['is_followed'] === $is_followed_row['member_id']) { 
                                                $is_followed = true;
                                                break;
                                            }
                                        }
                                        ?>    
                                        <?php if ($is_followed) : ?>
                                            <input type="hidden" name="delete_follow" value=<?= h($is_followed_row['member_id']) ?>>
                                            <li><input type="submit" value="フォロー解除" class="user-button"></li>
                                        <?php else : ?>
                                            <input type="hidden" name="insert_follow" value=<?= h($is_followed_row['member_id']) ?>>
                                            <li><input type="submit" value="フォローする" class="user-button"></li>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endwhile; ?> 
                </div>
            </div>        
        </main>
    </body>
</html>    