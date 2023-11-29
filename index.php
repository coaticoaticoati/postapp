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

$redirect_back = 'Location: index.php';

// -------投稿-------

// データベースからログインユーザーとフォローユーザーの投稿を取得
$sql = 'SELECT post_id, posts.created_at, content, user_id, file_path
FROM posts INNER JOIN follows ON posts.user_id = follows.is_followed
WHERE follow = :follow
UNION ALL
SELECT post_id, created_at, content, user_id, file_path FROM posts WHERE user_id = :user_id'; 
// INNER JOINでpostsとfollowsを内部結合して、followカラムがログインユーザーである、post_id等を抽出し、
// カラムuser_idがログインユーザーである、post_id等を抽出し、
// 2つの抽出結果を統合
$post_stmt = $dbh->prepare($sql);
$post_stmt->bindValue(':follow', $_SESSION['login']['member_id'], PDO::PARAM_INT);
$post_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);  
$post_stmt->execute();
while ($post_row = $post_stmt->fetch()) { 
    $posts[] = $post_row;
}

// データベースからログインユーザーの返信を取得
$reply_stmt = get_user_reply($_SESSION['login']['member_id']); 
while ($reply_row = $reply_stmt->fetch()) { 
    $posts[] = $reply_row;
}

// 投稿と返信をまとめた$postsをcreated_atで降順に、日時が同じならuser_idで昇順に並び変える
if (isset($posts)) {
    // $postsからcreated_atの値のみを取り出して配列を生成
    $created_at_array = array_column($posts, 'created_at');
    $user_id_array = array_column($posts, 'user_id');

    // 配列の要素全てに対して、strtotime関数を適用させ、戻り値を元に新たな配列を生成
    $created_at_array = array_map('strtotime', $created_at_array);

    // $posts_repliesを並び変える
    array_multisort($created_at_array, SORT_DESC, $user_id_array, SORT_ASC, $posts);
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

// ------投稿文バリデーション-------

// 投稿文が未入力($_POST['content']が空文字)の場合、変数を作成し、適当な値blankを代入
if($_POST['content'] === '') {
    $error['content'] = 'blank';
}
// 投稿文が200字以内か
if(strlen($_POST['content']) > 600) {
    $error['content'] = 'over';
}


// ------投稿＆画像アップロード-------

// 投稿文の送信ボタンが押されたら、データベースに入力内容を追加
// 投稿完了後に動作するように、条件式を「$_POST['content']の中身があるが、$errorに中身がない場合」とする
if (isset($_FILES['image'])) {
    if (!isset($error)) {
        // 投稿文と画像を登録
        if ($_FILES['image']['size'] > 0) {
            // ファイルデータを取得
            $file = $_FILES['image'];
            // データを変数に代入
            $file_name = $file['name'];
            $file_tmp_path = $file['tmp_name'];
            $file_error = $file['error'];
            $file_size = $file['size'];
            
            $file_name = basename($file['name']); // パス名からディレクトリ部分を除いたファイル名を取得
            $save_file_name = date('YmdHis').$file_name; // 保存ファイル名は日時+ファイル名とする
            
            $upload_dir = 'images/'; // 保存先のファイルパスでこの下に保存する指定
            $save_path = $upload_dir.$save_file_name; // 保存先のパス(+日付)

            // ファイルのバリデーション
            // ファイルサイズは1MB未満か
            if ($file_size > 1048576 || $file_error === 2 ) {
                $error['file_size'] = 'over';
            }
            // 拡張は画像形式か
            $file_extentions = ['jpg', 'jpeg', 'png']; // 許容する拡張子
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION); // ファイル名の拡張子のみ抽出
            $file_ext = strtolower($file_ext); // 大文字だった場合小文字に変える
            // 拡張子配列のいずれかと合致しなかった場合
            if (!in_array($file_ext, $file_extentions)) {
                $error['file_ext'] = 'not_match';
            }
            // ファイルがアップロードされているか
            if(!isset($error)) {
                if (is_uploaded_file($file_tmp_path)) {
                    // 一時ディレクトリ($file_tmp_path)からimages($save_pathの場所)に移動
                    if (move_uploaded_file($file_tmp_path, $save_path)) { 
                        // データベースに保存（ ファイル名、ファイルパス、投稿文 ）
                        insert_file($_POST['content'], $file_name, $save_path);
                        header('Location: ./'); // 自動リダイレクト
                        exit;
                    } else {
                        $error['file_save'] = 'not_save';
                    } 
                } else {
                    $error['file_upload'] = 'not_upload';
                }
            }      
        } else {
            // 投稿文のみの登録
            insert_post($_POST['content']);
                header('Location: ./'); //自動リダイレクト
                exit;
        }
    }   
}

?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>トップ</title>
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
            <div class="post-form">
                <div class="container">
                    <h3>こんにちは、<?= h($_SESSION['login']['name']) ?>さん</h3>
                    <form action="" method="post" enctype="multipart/form-data">
                        <textarea name="content" class="textarea-post"></textarea>    
                        <input type="hidden" name="MAX_FILE_SIZE" value="1048576"><!---ファイルの最大サイズを指定--->
                        <div class="form">
                            <input type="file" name="image" accept="image/*" class="image-upload">
                            <button type="submit" class="btn-post-form">ポストする</button>
                        </div>
                    </form>
                
                    <!-- エラーメッセージの表示 -->
                    <?php if ($error['content'] === 'blank') : ?>
                        <p>投稿文は必ず入力してください。</p>
                    <?php endif; ?>

                    <?php if ($error['content'] === 'over') : ?>
                        <p>200字以内で入力してください。</p>
                    <?php endif; ?>

                    <?php if ($error['file_size'] === 'over') : ?>
                        <p>ファイルサイズは1MB未満にしてください。</p>
                    <?php endif; ?>

                    <?php if ($error['file_ext'] === 'not_match') : ?>
                        <p>拡張子がjpg, jpeg, pngの画像ファイルを選択してください。</p>
                    <?php endif; ?>

                    <?php if ($error['file_save'] === 'not_save') : ?>
                        <p>画像ファイルを保存できませんでした。</p>
                    <?php endif; ?>

                    <?php if ($error['file_upload'] === 'not_upload') : ?>
                        <p>画像ファイルをアップロードできませんでした。</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="timeline">
                <div class="container">
                    <div class="timeline-contents">
                        <!-- フォローしているユーザーの投稿、ログインユーザーの投稿と返信をタイムラインに表示 -->
                        <?php foreach ($posts as $post ) : ?> 
                            <!-- アイコン -->
                            <div class="timeline-icon">
                                <?php
                                    $icon_row = get_icon($post['user_id']);
                                    if (empty($icon_row)) : ?>
                                        <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                    <?php else : ?>        
                                        <p><img src="<?= h($icon_row) ?>" class="icon" ></p>
                                    <?php endif; ?>
                            </div>

                            <!-- ユーザー名 -->
                            <div class="timeline-username">
                                <p><?= h(get_user_name($post['user_id'])) ?></p>
                            </div>

                            <div class="timeline-post">
                                <!-- 返信文 -->
                                <?php if (isset($post['reply_id'])) : ?>  
                                    <p>RE: <?= h($post['content']) ?></p>
                                <!-- 投稿文 -->
                                <?php else : ?>
                                    <p><?= h($post['content']) ?></p>   
                                <?php endif; ?>
                            </div>
                                
                            <!-- 画像 -->
                            <div> 
                                <?php if(isset($post['file_path'])) : ?>
                                    <p><img src="<?= h($post['file_path']) ?>" class="image"></p>
                                <?php endif; ?>                         
                            </div>

                            <!-- 投稿日時 -->
                            <div class="timeline-date">
                                <p><?= h($post['created_at']) ?></p>
                            </div>

                            <!-- いいねの数 -->
                            <?php if (isset($post['reply_id'])) : ?>
                                <p class="timeline-likes"><img src="images/heart.png"> <?= h(get_rep_likes_number($post['reply_id'])) ?></p>
                            <?php else : ?>
                                <p class="timeline-likes"><img src="images/heart.png"> <?= h(get_likes_number($post['post_id'])) ?></p>
                            <?php endif; ?>

                            <!-- ボタン -->
                            <div class="timeline-buttons">
                                <ul class="timeline-btn-list">
                                    <!-- 返信ボタン -->
                                    <?php if (isset($post['reply_id'])) : ?>
                                        <li><button><a href="reply.php?r_id=<?= h($post['reply_id']) ?>#reply">返信</a></button></li>
                                    <?php else : ?>
                                        <li><button><a href="reply.php?p_id=<?= h($post['post_id']) ?>#reply">返信</a></button></li>
                                    <?php endif; ?>

                                    <!-- アカウントボタン -->
                                    <li><button><a href="user.php?id=<?= h($post['user_id']) ?>">アカウント</a></button></li>
    
                                    <!-- いいねボタン -->
                                    <!-- 返信に対するいいねボタン -->
                                    <?php if (isset($post['reply_id'])) : ?>
                                        <?php $reply_is_liked_id = get_rep_likes($post['reply_id']) ?>
                                        <form action="" method="post">
                                            <?php if ($reply_is_liked_id) : ?>
                                                <input type="hidden" name="delete_reply_like" value=<?= h($post['reply_id']) ?>>
                                                <li><button type="submit">いいね解除</button></li>
                                            <?php else : ?>
                                                <input type="hidden" name="insert_reply_like" value=<?= h($post['reply_id']) ?>>
                                                <li><button type="submit">いいね</button></li>
                                            <?php endif; ?>                         
                                        </form>
                                    <?php else : ?>
                                        <!-- 投稿に対するいいねボタン -->
                                        <?php $post_is_liked_id = get_likes($post['post_id']) ?>
                                        <form action="" method="post">
                                            <?php if ($post_is_liked_id) : ?>
                                                <input type="hidden" name="delete_like" value=<?= h($post['post_id']) ?>>
                                                <li><button type="submit">いいね解除</button></li>
                                            <?php else : ?>
                                                <input type="hidden" name="insert_like" value=<?= h($post['post_id']) ?>>
                                                <li><button type="submit">いいね</button></li>
                                            <?php endif; ?>                         
                                        </form>
                                    <?php endif; ?>   

                                    <!-- ブックマークボタン -->
                                    <!-- 返信に対するブックマークボタン -->
                                    <?php if (isset($post['reply_id'])) : ?>
                                        <?php $reply_bm_id = get_rep_bookmarks($post['reply_id']) ?>
                                        <form action="" method="post">
                                            <?php if ($reply_bm_id) : ?>
                                                <input type="hidden" name="delete_reply_bm" value=<?= h($post['reply_id']) ?>>
                                                <li><button type="submit">ブックマーク解除</button></li>
                                            <?php else : ?>
                                                <input type="hidden" name="insert_reply_bm" value=<?= h($post['reply_id']) ?>>
                                                <li><button type="submit">ブックマーク</button></li>
                                            <?php endif; ?>                         
                                        </form>
                                    <?php else : ?>
                                        <!-- 投稿に対するブックマークボタン -->
                                        <?php $post_bm_id = get_bookmarks($post['post_id']) ?>
                                        <form action="" method="post">
                                            <?php if ($post_bm_id) : ?>
                                                <input type="hidden" name="delete_bm" value=<?= h($post['post_id']) ?>>
                                                <li><button type="submit">ブックマーク解除</button></li>
                                            <?php else : ?>
                                                <input type="hidden" name="insert_bm" value=<?= h($post['post_id']) ?>>
                                                <li><button type="submit">ブックマーク</button></li>
                                            <?php endif; ?>                         
                                        </form>
                                    <?php endif; ?>

                                    <!-- 削除ボタン-->
                                    <!-- ログインユーザーの投稿or返信のみ表示する -->            
                                    <?php if($post['user_id'] === $_SESSION['login']['member_id']) : ?>
                                        <!-- 返信に対する削除ボタン -->
                                        <?php if (isset($post['reply_id'])) : ?>
                                            <form action="" method="post">
                                                <input type="hidden" name="delete_reply" value=<?= h($post['reply_id']) ?>>
                                                <li><button type="submit">削除</button></li>
                                            </form>
                                        <!-- 投稿に対する削除ボタン -->            
                                        <?php else : ?>
                                            <form action="" method="post">
                                                <input type="hidden" name="delete_post" value=<?= h($post['post_id']) ?>>
                                                <li><button type="submit">削除</button></li>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </ul>    
                            </div>
                        <?php endforeach; ?>
                    </div>    
                </div>
            </div>
        </main>
    </body>    
</html>            