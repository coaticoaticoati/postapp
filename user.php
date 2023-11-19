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


// -----プロフィール------

// アイコンを取得
$icon_row = get_icon($_SESSION['login']['member_id']);

// 自分のプロフィール文を取得
$profile_row = get_profile($_SESSION['login']['member_id']);

// -------投稿-------

// データベースから自分の投稿を取得
$post_stmt = get_user_posts($_SESSION['login']['member_id']);

// いいねが押された場合
if (isset($_POST['insert_like'])) {
    insert_like((int)$_POST['insert_like']);
    header('Location: user.php');
    exit;
}

// いいね解除ボタンが押された場合
if (isset($_POST['delete_like'])) {
    delete_like((int)$_POST['delete_like']);
    header('Location: user.php');
    exit;
}

// 削除ボタンが押された場合
if (isset($_POST['delete_post'])) {
    delete_post($_POST['delete_post']);
    header('Location: user.php');
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


// -------返信-------

// データベースから自分の返信を取得
$reply_stmt = get_user_reply($_SESSION['login']['member_id']); 

// いいねが押された場合
if (isset($_POST['insert_reply_like'])) {
    insert_reply_like((int)$_POST['insert_reply_like']);
    header('Location: user.php');
    exit;
}

// いいね解除ボタンが押された場合
if (isset($_POST['delete_reply_like'])) {
    delete_reply_like((int)$_POST['delete_reply_like']);
    header('Location: user.php');
    exit;
}

// 削除ボタンが押された場合
if (isset($_POST['delete_reply'])) {
    delete_reply($_POST['delete_reply']);
    header('Location: user.php');
    exit;
}

// 投稿と返信をまとめた$post_likes_repsを日時順に並び変える
if (isset($post_likes_reps)) {
    array_multisort(array_map('strtotime', array_column($post_likes_reps, 'pressed_at')), SORT_DESC, $post_likes_reps) ;
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

// ------フォロー、フォロワー-----

// フォロー解除
if (isset($_POST['delete_follow'])) {
    delete_follow((int)$_POST['delete_follow']);
    header('Location: user.php');
    exit; 
}

// フォロワー一覧を取得 
$is_followed_stmt = get_follower($_SESSION['login']['member_id']);

// フォロー登録
if (isset($_POST['insert_follow'])) {
    insert_follow((int)$_POST['insert_follow']);
    header('Location: user.php');
    exit;
}

// -------ブロック---------

// ログインユーザーがブロックしている、ログインユーザーをブロックしているユーザーを取得
// いいね一覧に表示しないようにする
$block_stmt = block_user();
while ($block_row = $block_stmt->fetch()) {
    $blocks[] = $block_row;
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
                    <li><a href="user.php">プロフィール</a></li>
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
                            <p><img src="<?= h($icon_row['file_path']) ?>" class="icon" ></p>
                        <?php endif; ?>                        
                        <h3><?= h($_SESSION['login']['name']) ?></h3>
                        <p><?= h($profile_row['profile_content']) ?></p>
                        <button><a href="profile_edit.php">プロフィールを編集</a></button>
                    </div>

                    <!-- 投稿一覧 -->
                    <div class="user-posts">
                        <h3>投稿一覧</h3>
                        <?php while ($post_row = $post_stmt->fetch()) : ?>
                            <div class="user-post">
                                <!-- アイコン -->
                                <?php if (empty($icon_row)) : ?>
                                    <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                <?php else : ?>        
                                    <p><img src="<?= h($icon_row['file_path']) ?>" class="icon" ></p>
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

                            <!-- いいねの数 -->
                            <p class=""><img src="images/heart.png"> <?= h(get_likes_number($post_row['post_id'])) ?></p>

                            <!-- 投稿日時 -->
                            <p><?= h($post_row['created_at']) ?></p>
                            
                            <!-- ボタン -->
                            <div class="user-post-buttons">
                                <ul class="user-post-btn-list">
                                    <!-- 返信ボタン -->
                                    <form action="" method="post">
                                        <input type="hidden" name="reply_btn" value=<?= h($post_row['post_id']) ?>>  
                                        <li><input type="submit" value="返信" class="user-button"></li>
                                    </form>
                
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
                                            <li><button type="submit">ブックマーク解除</button></li>
                                        <?php else : ?>
                                            <input type="hidden" name="insert_bm" value=<?= h($post_row['post_id']) ?>>
                                            <li><button type="submit">ブックマーク</button></li>
                                        <?php endif; ?>                         
                                    </form>

                                    <!-- 削除ボタン -->
                                    <form action="" method="post">
                                        <input type="hidden" name="delete_post" value=<?= h($post_row['post_id']) ?>> 
                                        <!--hiddenで$_POST['delete_post']=>$post_row['post_id']として値を渡すことができる-->
                                        <li><input type="submit" value="削除" class="user-button"></li>
                                    </form>
                                </ul>
                            </div>
                        <?php endwhile; ?> 
                    </div>

                    <!-- 返信一覧 -->
                    <div class="user-replies">
                        <h3>返信一覧</h3>
                        <?php while ($reply_row = $reply_stmt->fetch()) : ?>
                            <div class="user-reply">
                                <!-- アイコン -->
                                <?php $icon_row = get_icon($reply_row['user_id']) ?>
                                <?php if (empty($icon_row)) : ?>
                                    <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                <?php else : ?>        
                                    <p><img src="<?= h($icon_row['file_path']) ?>" class="icon" ></p>
                                <?php endif; ?>
                                <!-- ユーザー名 -->
                                <p class="user-username"><?= h(get_user_name($reply_row['user_id'])) ?></p>
                            </div>

                            <!-- 投稿文 -->
                            <p class="user-post-stmt"><?= h($reply_row['content']) ?></p>
                            <!-- 画像------>
                            <?php if(isset($reply_row['file_path'])) : ?>
                                <p><img src="<?= h($reply_row['file_path']) ?>" class="image"></p>
                            <?php endif; ?> 
                            
                            <!-- いいねの数 -->
                            <p class=""><img src="images/heart.png"> <?= h(get_rep_likes_number($reply_row['reply_id'])) ?></p>

                            <!-- 投稿日時 -->
                            <p><?= h($reply_row['created_at']) ?></p>

                            <!-- ボタン -->
                            <div class="user-reply-buttons">
                                <ul class="user-reply-btn-list">
                                    <!-- 返信ボタン -->
                                    <form action="" method="post">
                                        <input type="hidden" name="reply_btn" value=<?= h($reply_row['post_id']) ?>>  
                                        <li><input type="submit" value="返信" class="user-button"></li>
                                    </form>

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
                                            <li><button type="submit">ブックマーク解除</button></li>
                                        <?php else : ?>
                                            <input type="hidden" name="insert_reply_bm" value=<?= h($reply_row['reply_id']) ?>>
                                            <li><button type="submit">ブックマーク</button></li>
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
                        <h3>いいね一覧</h3>
                        <?php
                        $post_likes_reps = get_likes_reps();
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
                                <div class="user-good">
                                    <!-- アイコン -->
                                    <?php $icon_row = get_icon($post_like_rep['user_id']) ?>
                                    <?php if (empty($icon_row)) : ?>
                                        <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                    <?php else : ?>        
                                        <p><img src="<?= h($icon_row['file_path']) ?>" class="icon" ></p>
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
                                    <p><img src="<?= h($post_like_rep['file_path']) ?>" class="image"></p>
                                <?php endif; ?> 
                                
                                <!-- いいねの数 -->
                                <?php if (isset($post_like_rep['reply_id'])) : ?>
                                    <p class=""><img src="images/heart.png"> <?= h(get_rep_likes_number($post_like_rep['reply_id'])) ?></p>
                                <?php else : ?>
                                    <p class=""><img src="images/heart.png"> <?= h(get_likes_number($post_like_rep['post_id'])) ?></p>
                                <?php endif; ?>

                                <!-- 投稿日時 -->
                                <p><?= h($post_like_rep['created_at']) ?></p>

                                <!-- ボタン -->
                                <div class="user-good-buttons">
                                    <ul class="user-good-btn-list">
                                        <!-- 返信ボタン -->
                                        <form action="" method="post">
                                            <input type="hidden" name="reply_btn" value=<?= h($post_like_rep['post_id']) ?>>  
                                            <li><input type="submit" value="返信" class="user-button"></li>
                                        </form>

                                        <!-- アカウントボタン -->
                                        <?php if ($post_like_rep['user_id'] != $_SESSION['login']['member_id']) : ?>
                                        <form action="" method="post">
                                            <input type="hidden" name="user_page" value=<?= h($post_like_rep['user_id']) ?>>
                                            <li><input type="submit" value="アカウント" class="user-button"></li>
                                        </form>
                                        <?php endif ?>

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
                                                    <li><button type="submit">ブックマーク解除</button></li>
                                                <?php else : ?>
                                                    <input type="hidden" name="insert_reply_bm" value=<?= h($post_like_rep['reply_id']) ?>>
                                                    <li><button type="submit">ブックマーク</button></li>
                                                <?php endif; ?>                         
                                            </form>
                                        <?php else : ?>
                                            <!-- 投稿に対するブックマークボタン -->
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

                                        <!-- 削除ボタン -->
                                        <!-- ログインユーザーの投稿or返信いいねのみ表示する -->
                                        <?php if ($post_like_rep['user_id'] === $_SESSION['login']['member_id']) : ?>
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
                        <h3>フォロー一覧</h3>
                        <?php $follows = get_follow($_SESSION['login']['member_id']) ?>
                        <?php foreach ($follows as $follow) : ?>
                            <div class="user-follow">
                                <!-- アイコン -->
                                <?php
                                $icon_stmt = get_icon($follow['is_followed']);
                                $icon_row = $icon_stmt->fetch();
                                ?>
                                <?php if (empty($icon_row)) : ?>
                                    <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                <?php else : ?>        
                                    <p><img src="<?= h($icon_row['file_path']) ?>" class="icon" ></p>
                                <?php endif; ?>                                
                                <!-- ユーザー名 -->
                                <p class="user-username"><?= h(get_user_name($follow['is_followed'])) ?></p>
                            </div>

                            <!-- ボタン -->
                            <div class="user-follow-buttons">
                                <ul class="user-follow-btn-list">
                                    <!-- アカウントボタン -->
                                    <form action="" method="post">
                                        <input type="hidden" name="user_page" value=<?= h($follow['is_followed']) ?>>
                                        <li><input type="submit" value="アカウント" class="user-button"></li>
                                    </form>
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
                        <h3>フォロワー一覧</h3>
                        <?php while ($is_followed_row = $is_followed_stmt->fetch()) : ?>
                            <div class="user-follower">
                                <!-- アイコン -->
                                <?php 
                                $icon_stmt = get_icon($is_followed_row['follow']);
                                $icon_row = $icon_stmt->fetch();
                                ?>
                                <?php if (empty($icon_row)) : ?>
                                    <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                <?php else : ?>        
                                    <p><img src="<?= h($icon_row['file_path']) ?>" class="icon" ></p>
                                <?php endif; ?>                                
                                <!-- ユーザー名 -->
                                <p class="user-username"><?= h(get_user_name($is_followed_row['follow'])) ?></p>
                            </div>

                            <!-- ボタン -->
                            <div class="user-follower-buttons">
                                <ul class="user-follower-btn-list">    
                                    <!-- アカウントボタン -->
                                    <form action="" method="post">
                                        <input type="hidden" name="user_page" value=<?= h($is_followed_row['follow']) ?>>
                                        <li><input type="submit" value="アカウント" class="user-button"></li>
                                    </form>
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