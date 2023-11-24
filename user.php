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

// 
$user_id = (int)$_GET['id'];

//
$redirect_url = 'user.php?id='.$user_id;

// 返信ボタンが押された場合
if (isset($_POST['reply_btn'])) {
    $_SESSION['reply_btn'] = (int)$_POST['reply_btn'];
    header('Location: reply.php');
    exit;
}

// -----プロフィール------

// アイコンを取得
$icon_row = get_icon($user_id);

// 自分のプロフィール文を取得
$profile_row = get_profile($user_id);

// -------投稿-------

// データベースから自分の投稿を取得
$post_stmt = get_user_posts($user_id);

// いいねが押された場合
if (isset($_POST['insert_like'])) {
    insert_like((int)$_POST['insert_like']);
    header('Location:'. $redirect_url);
    exit;
}

// いいね解除ボタンが押された場合
if (isset($_POST['delete_like'])) {
    delete_like((int)$_POST['delete_like']);
    header('Location:'. $redirect_url);
    exit;
}

// 削除ボタンが押された場合
if (isset($_POST['delete_post'])) {
    delete_post($_POST['delete_post']);
    header('Location:'. $redirect_url);
    exit;
}

// ブックマークボタンが押された場合
if (isset($_POST['insert_bm'])) {
    insert_bookmark((int)$_POST['insert_bm']);
    header('Location:'. $redirect_url);
    exit;
}

// ブックマーク解除ボタンが押された場合
if (isset($_POST['delete_bm'])) {
    delete_bookmark((int)$_POST['delete_bm']);
    header('Location:'. $redirect_url);
    exit;
}

// -------返信-------

// データベースから自分の返信を取得
$reply_stmt = get_user_reply($user_id); 

// いいねが押された場合
if (isset($_POST['insert_reply_like'])) {
    insert_reply_like((int)$_POST['insert_reply_like']);
    header('Location:'. $redirect_url);
    exit;
}

// いいね解除ボタンが押された場合
if (isset($_POST['delete_reply_like'])) {
    delete_reply_like((int)$_POST['delete_reply_like']);
    header('Location:'. $redirect_url);
    exit;
}

// 削除ボタンが押された場合
if (isset($_POST['delete_reply'])) {
    delete_reply($_POST['delete_reply']);
    header('Location:'. $redirect_url);
    exit;
}

// ブックマークボタンが押された場合
if (isset($_POST['insert_reply_bm'])) {
    insert_rep_bookmark((int)$_POST['insert_reply_bm']);
    header('Location:'. $redirect_url);
    exit;
}

// ブックマーク解除ボタンが押された場合
if (isset($_POST['delete_reply_bm'])) {
    delete_rep_bookmark((int)$_POST['delete_reply_bm']);
    header('Location:'. $redirect_url);
    exit;
}

// ----- いいね ------

// ログインユーザーの、投稿と返信のいいねを取得
$post_likes_reps = get_likes_reps($user_id);

// 投稿と返信をまとめた$post_likes_repsを日時順に並び変える
if (isset($post_likes_reps)) {
    array_multisort(array_map('strtotime', array_column($post_likes_reps, 'pressed_at')), SORT_DESC, $post_likes_reps) ;
}

// ------フォロー、フォロワー-----

// フォロー解除
if (isset($_POST['delete_follow'])) {
    delete_follow((int)$_POST['delete_follow']);
    header('Location:'. $redirect_url);
    exit; 
}

// フォロー登録
if (isset($_POST['insert_follow'])) {
    insert_follow((int)$_POST['insert_follow']);
    header('Location:'. $redirect_url);
    exit;
}

// ログインユーザーのフォロー一覧を取得
$following_user_list = get_follow($_SESSION['login']['member_id']);

// -------ブロック---------

// ログインユーザーのページの場合

// ログインユーザーがブロックしている、ログインユーザーをブロックしているユーザーを取得
// いいね一覧に表示しないようにする
$block_stmt = block_user();
while ($block_row = $block_stmt->fetch()) {
    $blocks[] = $block_row;
}

// 他ユーザーのページの場合

// ログインユーザーが参照中ユーザーをブロックしているか確認し、していればblock.phpへ
$redirect_block_url = 'block.php?id='.$user_id;

$block_user = get_block_user($user_id);
if (!empty($block_user)) {
    header('Location:'.$redirect_block_url);
    exit;   
}

// ログインユーザーが参照中ユーザーにブロックされてるか確認し、されていればblock.phpへ
$blocked_user = get_blocked_user($user_id);
if (!empty($blocked_user)) {
    header('Location:'.$redirect_block_url);
    exit;   
}

// ブロック登録ボタンを押した場合

if (isset($_POST['block_user'])) {
    // データベース登録
    $sql = 'INSERT INTO blocks (block, is_blocked) 
    VALUES (:block, :is_blocked)';
    $block_ins_stmt = $dbh->prepare($sql);
    $block_ins_stmt->bindValue(':block', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $block_ins_stmt->bindValue(':is_blocked', $user_id, PDO::PARAM_INT);
    $block_ins_stmt->execute();

    // フォロー解除
    foreach ($following_user_list as $following_user) {
        if ($following_user['is_followed'] === $user_id) {
            delete_follow($user_id);
            break;
        }    
    }
    // ブロックされたユーザーがログインユーザーをフォローしている場合は解除
    $other_user_follows = get_follow($user_id);

    //
    foreach ($other_user_follows as $other_user_follow) {
        if ($other_user_follow['is_followed'] === $_SESSION['login']['member_id']) {
            $sql = 'DELETE FROM follows WHERE follow = :follow AND is_followed = :is_followed'; // followかつis_followedであるものを削除する
            $follow_del_stmt = $dbh->prepare($sql);
            $follow_del_stmt->bindValue(':follow', $user_id, PDO::PARAM_INT );
            $follow_del_stmt->bindValue(':is_followed', $_SESSION['login']['member_id'], PDO::PARAM_INT);
            $follow_del_stmt->execute();
            break;
        }   
    }
    header('Location:'. $redirect_url);
    exit;   
}
?>    

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title><?= h($_SESSION['login']['name'])?>さんのプロフィール</title>
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
            <div class="user">
                <div class="container">
                    <div class="user-profile">
                        <?php if (empty($icon_row)) : ?>
                            <p><img src="images/animalface_tanuki.png" class="icon"><p>
                        <?php else : ?>        
                            <p><img src="<?= h($icon_row) ?>" class="icon" ></p>
                        <?php endif; ?>                        
                        <h3><?= h(get_user_name($user_id)) ?></h3>

                        <p><?= h($profile_row) ?></p>

                        <?php if ($user_id === $_SESSION['login']['member_id']) : ?>
                            <button><a href="profile_edit.php">プロフィールを編集</a></button>
                        <?php else : ?>
                            <div class="follow-block">
                                <ul class="follow-block-list">
                                <!-- フォロー、アンフォロー -->
                                <form action="" method="post">
                                    <?php
                                    $is_following = false;
                                    // $following_user_list：ログインユーザーにフォローされているユーザー全件の配列
                                    // ログインユーザーにフォローされていれば、trueとする
                                    foreach ($following_user_list as $following_user) {
                                        if($following_user['is_followed'] === $user_id) { 
                                            $is_following = true;
                                            break;
                                        }
                                    }
                                    ?> 
                                    <?php 
                                    if ($is_following) : ?>
                                        <input type="hidden" name="delete_follow" value=<?= h($user_id) ?>>
                                        <li><button type="submit">フォロー解除</button></li>
                                    <?php else : ?>
                                        <input type="hidden" name="insert_follow" value=<?= h($user_id) ?>>
                                        <li><button type="submit">フォローする</button></li>
                                    <?php endif; ?>
                                </form>
                                <!-- ブロック -->
                                <form action="" method="post">
                                    <input type="hidden" name="block_user" value=<?= h($user_id) ?>>  
                                    <li><button type="submit">ブロックする</button></li>
                                </form>
                            </div>   
                        <?php endif ?>    
                    </div>

                    <!-- 投稿一覧 -->
                    <div class="user-posts">
                        <h3 class="user-title">投稿一覧</h3>
                        <?php while ($post_row = $post_stmt->fetch()) : ?>
                            <div class="user-post">
                                <!-- アイコン -->
                                <?php if (empty($icon_row)) : ?>
                                    <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                <?php else : ?>        
                                    <p><img src="<?= h($icon_row) ?>" class="icon" ></p>
                                <?php endif; ?>                                
                                <!-- ユーザー名 -->
                                <p class="user-username"><?= h(get_user_name($post_row['user_id'])) ?></p>
                            </div>  

                            <!-- 投稿文 -->
                            <p class="user-post-stmt"><?= h($post_row['content']) ?></p>

                            <!-- 画像 -->
                            <?php if(isset($post_row['file_path'])) : ?>
                                <p class="user-post-image"><img src="<?= h($post_row['file_path']) ?>" class="image"></p>  
                            <?php endif; ?>

                            <!-- 投稿日時 -->
                            <p class="user-date"><?= h($post_row['created_at']) ?></p>

                            <!-- いいねの数 -->
                            <p class="user-likes"><img src="images/heart.png"> <?= h(get_likes_number($post_row['post_id'])) ?></p>
                            
                            <!-- ボタン -->
                            <div class="user-post-buttons">
                                <ul class="user-post-btn-list">
                                    <!-- 返信ボタン -->
                                    <form action="" method="post">
                                        <input type="hidden" name="reply_btn" value=<?= h($post_row['post_id']) ?>>  
                                        <li><input type="submit" value="返信" class="user-button"></li>
                                    </form>

                                    <!-- アカウントボタン -->
                                    <li><button class="user-button"><a href="user.php?id=<?= h($post_row['user_id']) ?>">アカウント</a></button></li>
                
                                    <!-- いいねボタン -->
                                    <!-- ログインユーザーのいいねをひとつずつを、投稿と照合する。合致したら「いいね解除」とする-->
                                    <?php $post_is_liked_id = get_likes($post_row['post_id']); ?>
                                    <form action="" method="post">
                                        <!--$post_likeはforeach内で回されているので、$is_likedがfalseだった場合、最後尾の要素が使われる -->
                                        <?php if ($post_is_liked_id) : ?>
                                            <input type="hidden" name="delete_like" value=<?= h($post_row['post_id']) ?>>
                                            <li><input type="submit" value="いいね解除" class="user-button"></li>
                                        <?php else : ?>
                                            <input type="hidden" name="insert_like" value=<?= h($post_row['post_id']) ?>>
                                            <li><input type="submit" value="いいね" class="user-button"></li>
                                        <?php endif; ?> 
                                    </form>

                                    <!-- ブックマークボタン -->
                                    <?php
                                    $post_bm_id = get_bookmarks($post_row['post_id']); ?>
                                    <form action="" method="post">
                                        <?php if ($post_bm_id) : ?>
                                            <input type="hidden" name="delete_bm" value=<?= h($post_row['post_id']) ?>>
                                            <li><button type="submit" class="user-button">ブックマーク解除</button></li>
                                        <?php else : ?>
                                            <input type="hidden" name="insert_bm" value=<?= h($post_row['post_id']) ?>>
                                            <li><button type="submit" class="user-button">ブックマーク</button></li>
                                        <?php endif; ?>                         
                                    </form>

                                    <!-- 削除ボタン -->
                                    <form action="" method="post">
                                        <input type="hidden" name="delete_post" value=<?= h($post_row['post_id']) ?>> 
                                        <li><input type="submit" value="削除" class="user-button"></li>
                                    </form>
                                </ul>
                            </div>
                        <?php endwhile; ?> 
                    </div>

                    <!-- 返信一覧 -->
                    <div class="user-replies">
                        <h3 class="user-title">返信一覧</h3>
                        <?php while ($reply_row = $reply_stmt->fetch()) : ?>
                            <div class="user-post">
                                <!-- アイコン -->
                                <?php $icon_row = get_icon($reply_row['user_id']) ?>
                                <?php if (empty($icon_row)) : ?>
                                    <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                <?php else : ?>        
                                    <p><img src="<?= h($icon_row) ?>" class="icon" ></p>
                                <?php endif; ?>
                                <!-- ユーザー名 -->
                                <p class="user-username"><?= h(get_user_name($reply_row['user_id'])) ?></p>
                            </div>

                            <!-- 投稿文 -->
                            <p class="user-post-stmt"><?= h($reply_row['content']) ?></p>
                            <!-- 画像------>
                            <?php if(isset($reply_row['file_path'])) : ?>
                                <p class="user-post-image"><img src="<?= h($reply_row['file_path']) ?>" class="image"></p>
                            <?php endif; ?> 
                            
                            <!-- 投稿日時 -->
                            <p class="user-date"><?= h($reply_row['created_at']) ?></p>

                            <!-- いいねの数 -->
                            <p class="user-likes"><img src="images/heart.png"> <?= h(get_rep_likes_number($reply_row['reply_id'])) ?></p>

                            <!-- ボタン -->
                            <div class="user-post-buttons">
                                <ul class="user-post-btn-list">
                                    <!-- 返信ボタン -->
                                    <form action="" method="post">
                                        <input type="hidden" name="reply_btn" value=<?= h($reply_row['post_id']) ?>>  
                                        <li><input type="submit" value="返信" class="user-button"></li>
                                    </form>

                                    <!-- アカウントボタン -->
                                    <li><button class="user-button"><a href="user.php?id=<?= h($reply_row['user_id']) ?>">アカウント</a></button></li>

                                    <!-- いいねボタン -->
                                    <?php $post_is_liked_id = get_rep_likes($reply_row['reply_id']) ?>
                                    <form action="" method="post">
                                        <?php if ($post_is_liked_id) : ?>
                                            <input type="hidden" name="delete_reply_like" value=<?= h($reply_row['reply_id']) ?>>
                                            <li><input type="submit" value="いいね解除" class="user-button"></li>
                                        <?php else : ?>
                                            <input type="hidden" name="insert_reply_like" value=<?= h($reply_row['reply_id']) ?>>
                                            <li><input type="submit" value="いいね" class="user-button"></li>
                                        <?php endif; ?> 
                                    </form>

                                    <!-- ブックマークボタン -->
                                    <?php $reply_bm_id = get_rep_bookmarks($reply_row['reply_id']) ?>
                                    <form action="" method="post">
                                        <?php if ($reply_bm_id) : ?>
                                            <input type="hidden" name="delete_reply_bm" value=<?= h($reply_row['reply_id']) ?>>
                                            <li><button type="submit" class="user-button">ブックマーク解除</button></li>
                                        <?php else : ?>
                                            <input type="hidden" name="insert_reply_bm" value=<?= h($reply_row['reply_id']) ?>>
                                            <li><button type="submit" class="user-button">ブックマーク</button></li>
                                        <?php endif; ?>                         
                                    </form>
                                    <!-- 削除ボタン -->
                                    <form action="" method="post">
                                        <input type="hidden" name="delete_reply" value=<?= h($reply_row['reply_id']) ?>> 
                                        <li><input type="submit" value="削除" class="user-button"></li>
                                    </form>
                                </ul>
                            </div>    
                        <?php endwhile; ?>       

                    <!-- いいね一覧 -->
                    <div class="user-goods">
                        <h3 class="user-title">いいね一覧</h3>
                        <?php
                        foreach ($post_likes_reps as $post_like_rep) : 
                            // ブロックしている、されているか確認
                            $block_like = true;
                            foreach ($blocks as $block) {
                                if ($block['is_blocked'] === $post_like_rep['user_id'] ) {
                                    $block_like = false;
                                }
                            }
                            // ブロックしていない、されていない場合
                            if ($block_like) :
                        ?>
                                <div class="user-post">
                                    <!-- アイコン -->
                                    <?php $icon_row = get_icon($post_like_rep['user_id']) ?>
                                    <?php if (empty($icon_row)) : ?>
                                        <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                    <?php else : ?>        
                                        <p><img src="<?= h($icon_row) ?>" class="icon" ></p>
                                    <?php endif; ?>                                    
                                    <!-- ユーザー名 -->
                                    <p class="user-username"><?= h(get_user_name($post_like_rep['user_id'])) ?></p>
                                </div>

                                <!-- 投稿文 -->
                                <?php if (isset($post_like_rep['reply_id'])) : ?>  
                                    <p class="user-post-stmt">RE: <?= h($post_like_rep['content']) ?></p>
                                <?php else : ?>
                                    <p class="user-post-stmt"><?= h($post_like_rep['content']) ?></p>
                                <?php endif; ?>
                                <!-- 画像 -->
                                <?php if(isset($post_like_rep['file_path'])) : ?>
                                    <p class="user-post-image"><img src="<?= h($post_like_rep['file_path']) ?>" class="image"></p>
                                <?php endif; ?> 
                                
                                <!-- 投稿日時 -->
                                <p class="user-date"><?= h($post_like_rep['created_at']) ?></p>

                                <!-- いいねの数 -->
                                <?php if (isset($post_like_rep['reply_id'])) : ?>
                                    <p class="user-likes"><img src="images/heart.png"> <?= h(get_rep_likes_number($post_like_rep['reply_id'])) ?></p>
                                <?php else : ?>
                                    <p class="user-likes"><img src="images/heart.png"> <?= h(get_likes_number($post_like_rep['post_id'])) ?></p>
                                <?php endif; ?>

                                <!-- ボタン -->
                                <div class="user-post-buttons">
                                    <ul class="user-post-btn-list">
                                        <!-- 返信ボタン -->
                                        <form action="" method="post">
                                            <input type="hidden" name="reply_btn" value=<?= h($post_like_rep['post_id']) ?>>  
                                            <li><input type="submit" value="返信" class="user-button"></li>
                                        </form>

                                        <!-- アカウントボタン -->
                                        <li><button class="user-button"><a href="user.php?id=<?= h($post_like_rep['user_id']) ?>">アカウント</a></button></li>
       
                                        <!-- いいねボタン -->
                                        <?php if (isset($post_like_rep['reply_id'])) : ?>
                                            <form action="" method="post">
                                                <input type="hidden" name="delete_reply_like" value=<?= h($post_like_rep['reply_id']) ?>>
                                                <li><input type="submit" value="いいね解除" class="user-button"></li>
                                            </form>
                                        <?php else : ?>
                                            <form action="" method="post">
                                                <input type="hidden" name="delete_like" value=<?= h($post_like_rep['post_id']) ?>>
                                                <li><input type="submit" value="いいね解除" class="user-button"></li>
                                            </form>
                                        <?php endif; ?>

                                        <!-- ブックマークボタン -->
                                        <!-- 返信に対するブックマークボタン -->
                                        <?php if (isset($post_like_rep['reply_id'])) : ?>
                                            <?php $reply_bm_id = get_rep_bookmarks($post_like_rep['reply_id']) ?>
                                            <form action="" method="post">
                                                <?php if ($reply_bm_id) : ?>
                                                    <input type="hidden" name="delete_reply_bm" value=<?= h($post_like_rep['reply_id']) ?>>
                                                    <li><button type="submit" class="user-button">ブックマーク解除</button></li>
                                                <?php else : ?>
                                                    <input type="hidden" name="insert_reply_bm" value=<?= h($post_like_rep['reply_id']) ?>>
                                                    <li><button type="submit" class="user-button">ブックマーク</button></li>
                                                <?php endif; ?>                         
                                            </form>
                                        <?php else : ?>
                                            <!-- 投稿に対するブックマークボタン -->
                                            <?php
                                            $post_bm_id = get_bookmarks($post_like_rep['post_id']) ?>
                                            <form action="" method="post">
                                                <?php if ($post_bm_id) : ?>
                                                    <input type="hidden" name="delete_bm" value=<?= h($post_like_rep['post_id']) ?>>
                                                    <li><button type="submit" class="user-button">ブックマーク解除</button></li>
                                                <?php else : ?>
                                                    <input type="hidden" name="insert_bm" value=<?= h($post_like_rep['post_id']) ?>>
                                                    <li><button type="submit" class="user-button">ブックマーク</button></li>
                                                <?php endif; ?>                         
                                            </form>
                                        <?php endif; ?>

                                        <!-- 削除ボタン -->
                                        <!-- ログインユーザーの投稿or返信いいねのみ表示する -->
                                        <?php if ($post_like_rep['user_id'] === $user_id) : ?>
                                            <!-- 返信に対する削除ボタン-->
                                            <?php if (isset($post_like_rep['reply_id'])) : ?>
                                                <form action="" method="post">
                                                    <input type="hidden" name="delete_reply" value=<?= h($post_like_rep['reply_id']) ?>>
                                                    <li><input type="submit" value="削除" class="user-button"></li>
                                                </form>
                                            <!-- 投稿に対する削除ボタン -->            
                                            <?php else : ?>
                                                <form action="" method="post">
                                                    <input type="hidden" name="delete_post" value=<?= h($post_like_rep['post_id']) ?>>
                                                    <li><input type="submit" value="削除" class="user-button"></li>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </ul>
                                </div>       
                            <?php endif; ?> 
                        <?php endforeach; ?> 

                    <!-- フォロー一覧 -->
                    <div class="user-follows">
                        <h3 class="user-title">フォロー一覧</h3>
                        <?php $follows = get_follow($user_id) ?>
                        <?php foreach ($follows as $follow) : ?>
                            <div class="user-follow">
                                <!-- アイコン -->
                                <?php $icon_row = get_icon($follow['is_followed'])?>
                                <?php if (empty($icon_row)) : ?>
                                    <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                <?php else : ?>        
                                    <p><img src="<?= h($icon_row) ?>" class="icon" ></p>
                                <?php endif; ?>                                
                                <!-- ユーザー名 -->
                                <p class="user-username"><?= h(get_user_name($follow['is_followed'])) ?></p>
                            </div>

                            <!-- ボタン -->
                            <div class="user-follow-buttons">
                                <ul class="user-follow-btn-list">
                                    <!-- アカウントボタン -->
                                    <li><button class="user-button"><a href="user.php?id=<?= h($follow['is_followed']) ?>">アカウント</a></button></li>
                                    <!-- フォロー解除 -->
                                    <form action="" method="post">
                                        <input type="hidden" name="delete_follow" value=<?= h($follow['is_followed']) ?>>
                                        <li><input type="submit" value="フォロー解除" class="user-button"></li>
                                    </form>
                                </ul>
                            </div>
                        <?php endforeach; ?> 
                    </div>

                    <!-- フォロワー一覧 -->
                    <div class="user-followers">
                        <h3 class="user-title">フォロワー一覧</h3>
                        <?php 
                        // フォロワー一覧を取得 
                        $is_followed_stmt = get_follower($user_id);
                        while ($is_followed_row = $is_followed_stmt->fetch()) : 
                        ?>
                            <div class="user-follower">
                                <!-- アイコン -->
                                <?php $icon_row = get_icon($is_followed_row['follow']) ?>
                                <?php if (empty($icon_row)) : ?>
                                    <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                <?php else : ?>        
                                    <p><img src="<?= h($icon_row) ?>" class="icon" ></p>
                                <?php endif; ?>                                
                                <!-- ユーザー名 -->
                                <p class="user-username"><?= h(get_user_name($is_followed_row['follow'])) ?></p>
                            </div>

                            <!-- ボタン -->
                            <div class="user-follower-buttons">
                                <ul class="user-follower-btn-list">    
                                    <!-- アカウントボタン -->
                                    <li><button class="user-button"><a href="user.php?id=<?= h($is_followed_row['follow']) ?>">アカウント</a></button></li>
                                    
                                    <!-- フォロー、アンフォロー -->
                                    <form action="" method="post">
                                        <?php
                                        $is_followed = false;
                                        // $follows：ログインユーザーにフォローされているユーザー全件の配列
                                        // フォローされていれば、trueとする
                                        foreach ($follows as $follow) {
                                            if($follow['is_followed'] === $is_followed_row['member_id']) { 
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
                                </ul>
                            </div>
                        <?php endwhile; ?>
                    </div> 
                </div>
            </div>       
        </main>           
    </body>
</html>    