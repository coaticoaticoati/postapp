<?php
ini_set("display_errors", "OFF");
require_once('functions.php');
session_start();

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

// -------検索------

if (isset($_POST['search'])) {
    //検索フォームの内容を取得
    $search = $_POST['search'];
    //空白を半角スペースに置換
    $convert_space = mb_convert_kana($search, 's');
    //検索ワードを配列に格納
    $explode_words = explode(" ", $convert_space);
    // 検索ワードの数だけ$search_wordsを作り、検索ワードひとつずつを$search_arrayに格納
    foreach ($explode_words as $explode_word) {
        $search_post[] = '(posts.content LIKE ? OR members.name LIKE ?)';
        $search_profile[] = '(members.name LIKE ? OR profile.profile_content LIKE ?)';
        $search_array[] = '%'.$explode_word.'%';
        $search_array[] = '%'.$explode_word.'%';
    }

    // 投稿内容とユーザー名を検索
    $sql = 'SELECT post_id, content, posts.created_at, user_id, file_path, name FROM members 
            LEFT OUTER JOIN posts ON members.member_id = posts.user_id
            WHERE'. implode(' AND ', $search_post). 
            'ORDER BY posts.created_at DESC';
    $search_post_stmt = $dbh->prepare($sql);
    $search_post_stmt->execute($search_array);
    while ($search_post_result = $search_post_stmt->fetch()) {
        $search_post_results[] = $search_post_result;
    }

    // ユーザー名とプロフィール文を検索
    $sql = 'SELECT name, profile_content, user_id, members.created_at FROM members
            LEFT OUTER JOIN profile ON members.member_id = profile.user_id
            WHERE'. implode(' AND ', $search_profile). 
            'ORDER BY members.created_at DESC';
    $search_prof_stmt = $dbh->prepare($sql);
    $search_prof_stmt->execute($search_array);
    while ($search_prof_result = $search_prof_stmt->fetch()) {
    $search_prof_results[] = $search_prof_result;
    }

}

// -------投稿-------

// 削除ボタンが押された場合
if (isset($_POST['delete_post'])) {
    delete_post((int)$_POST['delete_post']);   
}

// いいねボタンが押された場合
if (isset($_POST['insert_like'])) {
    insert_like((int)$_POST['insert_like']);
}

// いいね解除ボタンが押された場合
if (isset($_POST['delete_like'])) {
    delete_like((int)$_POST['delete_like']);
}

// -------いいね---------

// 投稿に対して、ログインユーザーが行ったいいね全件を取得
$sql = 'SELECT post_is_liked_id FROM likes
INNER JOIN posts ON likes.post_is_liked_id = posts.post_id 
WHERE likes.user_id = :user_id';
$posts_like_stmt = $dbh->prepare($sql);
$posts_like_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
$posts_like_stmt->execute();
while ($post_like_row = $posts_like_stmt->fetch()) { 
    $post_likes[] = $post_like_row;
}

// -------ブロック---------

// ログインユーザーがブロックしている、ログインユーザーをブロックしているユーザーを取得
$block_stmt = block_user();
while ($block_row = $block_stmt->fetch()) {
    $blocks[] = $block_row;
}

?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>検索</title>
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
            <div class="search">
                <div class="container">
                    <div class="search-box">
                        <form action="" method="post">
                            <input type="text" name="search" class="textbox">
                            <button type="submit">検索</button>
                        </form>
                    </div> 
                    
                    <div class="post-results">
                        <h3>ポストの検索結果</h3>
                            <?php 
                            foreach ($search_post_results as $search_post) :
                                // ブロックしている、されている場合、false となり、検索結果を表示しない
                                $block_search = true;
                                foreach ($blocks as $block) {
                                    if ($block['is_blocked'] === $search_post['user_id']) {
                                        $block_search = false;
                                    }
                                }  // $block_search が true の場合、検索結果を表示する
                                if ($block_search) :
                            ?>      
                                    <!---アイコン----->
                                    <div>      
                                        <?php
                                        $icon_stmt = get_icon($search_post['user_id']);
                                        $icon_row = $icon_stmt->fetch();
                                        ?>
                                        <?php if (empty($icon_row)) : ?>
                                            <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                        <?php else : ?>        
                                            <p><img src="<?= h($icon_row['file_path']) ?>" class="icon" ></p>
                                        <?php endif; ?>
                                    </div>

                                    <!---ユーザー名----->
                                    <div class="search-username">
                                        <?php
                                        $user_name_stmt = get_user_name($search_post['user_id']);
                                        $user_name_row = $user_name_stmt->fetch();
                                        ?>
                                        <p><?= h($user_name_row['name']) ?></p>
                                    </div>

                                    <!------投稿文------>
                                    <div class="">
                                        <p><?= h($search_post['content']) ?></p>
                                    </div>

                                    <!-----画像を表示------>
                                    <div>                                    
                                        <?php if(isset($row['file_path'])) : ?>
                                            <p><img src="<?= h($search_post['file_path']) ?>" class="image"></p>
                                        <?php endif; ?>
                                    </div>    

                                    <!-----投稿日時----->
                                    <div>
                                        <p><?= h($search_post['created_at']) ?></p>
                                    </div>
                                    
                                    <div class="search-buttons">
                                        <ul class="search-btn-list">
                                            <!------返信ボタン------>
                                            <form action="" method="post">
                                                <input type="hidden" name="reply_btn" value=<?= h($search_post['post_id']) ?>>
                                                <li><button type="submit">返信</button></li>
                                            </form>
                                    
                                            <!-----アカウントボタン------>
                                            <form action="" method="post">
                                                <input type="hidden" name="user_page" value=<?= h($search_post['user_id']) ?>>
                                                <li><button type="submit">アカウント</button></li>
                                            </form>
                                    
                                            <!-----いいねボタン------>
                                            <!-----投稿に対するいいねボタン------>
                                            <form action="" method="post">
                                                <?php
                                                $post_is_liked_id = false;
                                                foreach($post_likes as $post_like) {
                                                    if($post_like['post_is_liked_id'] === $search_post['post_id']) {
                                                        $post_is_liked_id = true;
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <?php if ($post_is_liked_id) : ?>
                                                    <input type="hidden" name="delete_like" value=<?= h($search_post['post_id']) ?>>
                                                    <li><button type="submit">いいね解除</button></li>
                                                <?php else : ?>
                                                    <input type="hidden" name="insert_like" value=<?= h($search_post['post_id']) ?>>
                                                    <li><button type="submit">いいね</button></li>
                                                <?php endif; ?>                         
                                            </form>  
                            
                                            <!-----削除ボタン------>
                                            <!-----ログインユーザーの投稿のみ表示する------>            
                                            <?php if($search_post['user_id'] === $_SESSION['login']['member_id']) : ?>
                                                <!-----投稿に対する削除ボタン------>  
                                                <form action="" method="post">
                                                    <input type="hidden" name="delete_post" value=<?= h($search_post['post_id']) ?>>
                                                    <li><button type="submit">削除</button></li>
                                                </form>
                                            <?php endif; ?>
                                        </ul> 
                                    </div>        
                                <?php endif; ?>
                            <?php endforeach; ?>
                    </div>

                    <div class="user-results">                     
                        <h3>ユーザーの検索結果</h3>
                            <?php foreach ($search_prof_results as $search_prof) : ?> 
                                <!---アイコン----->
                                <div>      
                                    <?php
                                    $icon_stmt = get_icon($search_prof['user_id']);
                                    $icon_row = $icon_stmt->fetch();
                                    ?>
                                    <?php if (empty($icon_row)) : ?>
                                        <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                    <?php else : ?>        
                                        <p><img src="<?= h($icon_row['file_path']) ?>" class="icon" ></p>
                                    <?php endif; ?>
                                </div>

                                <!---ユーザー名----->
                                <div class="search-username">
                                    <?php
                                    $user_name_stmt = get_user_name($search_prof['user_id']);
                                        $user_name_row = $user_name_stmt->fetch();
                                    ?>
                                    <p><?= h($user_name_row['name']) ?></p>
                                </div>  

                                <!-----プロフィール文----->
                                <div>
                                    <?php
                                    $profile_stmt = get_profile($search_prof['user_id']);
                                    $profile_row = $profile_stmt->fetch();
                                    ?>
                                    <p><?= h($profile_row['profile_content']) ?></p>
                                </div>

                                <!-----アカウントボタン------>
                                <div class="search-buttons">
                                    <form action="" method="post">
                                        <input type="hidden" name="user_page" value=<?= h($search_prof['user_id']) ?>>
                                        <button type="submit" class="search-users-btn-account">アカウント</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                    </div>
                </div> 
            </div>               
        </main>
        <footer>

        </footer>    
    </body>    
</html>          