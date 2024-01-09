<?php
ini_set("display_errors", "OFF");
require_once('functions.php');
session_start();

// データベース接続
$dbh = db_open();

// -------検索------

if (isset($_POST['search'])) {
    // 検索フォームの内容を取得
    $search = $_POST['search'];
    // 全角スペースを半角スペースに置換
    $convert_space = mb_convert_kana($search, 's');
    // 検索ワードを配列に格納
    $explode_words = explode(" ", $convert_space);
    // 検索ワードの数だけ$search_wordsを作り、検索ワードひとつずつを$search_arrayに格納
    foreach ($explode_words as $explode_word) {
        $search_post[] = '(posts.content LIKE ? OR members.name LIKE ?)';
        $search_profile[] = '(members.name LIKE ? OR profiles.profile_content LIKE ?)';
        $search_array[] = '%'.$explode_word.'%';
        $search_array[] = '%'.$explode_word.'%';
    }
    
    // 投稿内容とユーザー名を検索
    $sql = 'SELECT post_id, content, posts.created_at, members.member_id, file_path, name FROM members 
            LEFT OUTER JOIN posts ON members.member_id = posts.user_id
            WHERE'. implode(' AND ', $search_post). 
            'ORDER BY posts.created_at DESC';
    $search_post_stmt = $dbh->prepare($sql);
    $search_post_stmt->execute($search_array);
    while ($search_post_result = $search_post_stmt->fetch()) {
        $search_post_results[] = $search_post_result;
    }

    // ユーザー名とプロフィール文を検索
    $sql = 'SELECT name, profile_content, members.member_id, members.created_at FROM members
            LEFT OUTER JOIN profiles ON members.member_id = profiles.user_id
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

// ブックマークボタンが押された場合
if (isset($_POST['insert_bm'])) {
    insert_bookmark((int)$_POST['insert_bm']);
}

// ブックマーク解除ボタンが押された場合
if (isset($_POST['delete_bm'])) {
    delete_bookmark((int)$_POST['delete_bm']);
}

// -------いいね---------

// 投稿に対して、ログインユーザーが行ったいいね全件を取得
$sql = 'SELECT post_is_liked_id FROM likes
INNER JOIN posts ON likes.post_is_liked_id = posts.post_id 
WHERE likes.user_id = :user_id';
$posts_like_stmt = $dbh->prepare($sql);
$posts_like_stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
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
                    <li><a href="bookmark.php">ブックマーク</a></li>
                    <li><a href="user.php?id=<?= h($_SESSION['user_id']) ?>">プロフィール</a></li>
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
                    
                    <!-- ポストの検索結果 -->
                    <div class="post-results">
                        <h3>ポストの検索結果</h3>
                            <?php
                            // ユーザー名で検索にヒットしたユーザーが1つ以上投稿している場合は表示する
                            if ($search_post_results[0]['content'] !== NULL) :
                                foreach ($search_post_results as $search_post) :
                                    // ブロックしている、されている場合、false となり、検索結果を表示しない
                                    $block_search = true;
                                    foreach ($blocks as $block) {
                                        if ($block['is_blocked'] === $search_post['member_id']) {
                                            $block_search = false;
                                        }
                                    }  // $block_searchがtrueの場合、検索結果を表示する
                                    if ($block_search) :
                                ?>      
                                        <!-- アイコン -->
                                        <div>      
                                            <?php $icon_row = get_icon($search_post['member_id']) ?>
                                            <?php if (empty($icon_row)) : ?>
                                                <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                            <?php else : ?>        
                                                <p><img src="<?= h($icon_row) ?>" class="icon" ></p>
                                            <?php endif; ?>
                                        </div>

                                        <!-- ユーザー名 -->
                                        <div class="search-username">
                                            <p><?= h(get_user_name($search_post['member_id'])) ?></p>
                                        </div>

                                        <!-- 投稿文 -->
                                        <div class="">
                                            <p><?= h($search_post['content']) ?></p>
                                        </div>

                                        <!-- 画像を表示 -->
                                        <div>                                    
                                            <?php if(isset($row['file_path'])) : ?>
                                                <p><img src="<?= h($search_post['file_path']) ?>" class="image"></p>
                                            <?php endif; ?>
                                        </div>    

                                        <!-- 投稿日時 -->
                                        <div>
                                            <p><?= h($search_post['created_at']) ?></p>
                                        </div>

                                        <!-- いいねの数 -->
                                        <p class="timeline-likes"><img src="images/heart.png"><?= h(get_likes_number($post['post_id'])) ?></p>
                                        
                                        <div class="search-buttons">
                                            <ul class="search-btn-list">
                                                <!-- 返信ボタン -->
                                                <li><button><a href="reply.php?p_id=<?= h($search_post['post_id']) ?>">返信</a></button></li>

                                                <!-- アカウントボタン -->
                                                <li><button><a href="user.php?id=<?= h($search_post['member_id']) ?>">アカウント</a></button></li>
                                                
                                                <!-- いいねボタン -->
                                                <!-- 投稿に対するいいねボタン -->
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

                                                <!-- ブックマークボタン -->
                                                <?php $post_bm_id = get_bookmarks($search_post['post_id']) ?>
                                                <form action="" method="post">
                                                    <?php if ($post_bm_id) : ?>
                                                        <input type="hidden" name="delete_bm" value=<?= h($search_post['post_id']) ?>>
                                                        <li><button type="submit">ブックマーク解除</button></li>
                                                    <?php else : ?>
                                                        <input type="hidden" name="insert_bm" value=<?= h($search_post['post_id']) ?>>
                                                        <li><button type="submit">ブックマーク</button></li>
                                                    <?php endif; ?>                         
                                                </form>
                                
                                                <!-- 削除ボタン -->
                                                <!-- ログインユーザーの投稿のみ表示する -->            
                                                <?php if($search_post['member_id'] === $_SESSION['user_id']) : ?>
                                                    <!-- 投稿に対する削除ボタン -->  
                                                    <form action="" method="post">
                                                        <input type="hidden" name="delete_post" value=<?= h($search_post['post_id']) ?>>
                                                        <li><button type="submit">削除</button></li>
                                                    </form>
                                                <?php endif; ?>
                                            </ul> 
                                        </div>        
                                    <?php endif;
                                endforeach; 
                            endif;  ?>  
                    </div>

                    <!-- ユーザーの検索結果 -->
                    <div class="user-results">                     
                        <h3>ユーザーの検索結果</h3>
                        <?php foreach ($search_prof_results as $search_prof) : ?> 
                            <!-- アイコン -->
                            <div>      
                                <?php $icon_row = get_icon($search_prof['member_id']) ?>
                                <?php if (empty($icon_row)) : ?>
                                    <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                <?php else : ?>        
                                    <p><img src="<?= h($icon_row) ?>" class="icon" ></p>
                                <?php endif; ?>
                            </div>

                            <!-- ユーザー名 -->
                            <div class="search-username">
                                <p><?= h(get_user_name($search_prof['member_id'])) ?></p>
                            </div>  

                            <!-- プロフィール文 -->
                            <div>
                                <p><?= h(get_profile($search_prof['member_id'])) ?></p>
                            </div>

                            <!-- アカウントボタン -->
                            <div class="search-buttons">
                                <button type="submit" class="search-users-btn-account">
                                    <a href="user.php?id=<?= h($search_prof['member_id']) ?>">アカウント</a></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div> 
            </div>               
        </main>   
    </body>    
</html>          