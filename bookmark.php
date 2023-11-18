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

// カテゴリーを登録
if (isset($_POST['insert_category'])) {
    $sql = 'INSERT INTO categories (user_id, name)
    VALUES (:user_id, :name)';
    $category_ins_stmt = $dbh->prepare($sql);
    $category_ins_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $category_ins_stmt->bindValue(':name', $_POST['insert_category'], PDO::PARAM_STR);
    $category_ins_stmt->execute();
    header('Location: bookmark.php');
    exit;
}

// カテゴリー名を取得
$sql ='SELECT id, name FROM categories
WHERE user_id = :user_id
ORDER BY created_at ASC';
$category_name_stmt = $dbh->prepare($sql);
$category_name_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
$category_name_stmt->execute();
while ($category_name_row = $category_name_stmt->fetch()) { 
    $category_names[] = $category_name_row;
}

// 取得
/*
$sql ='SELECT * FROM bookmarks
INNER JOIN categories ON bookmarks.category_id = categories.id
WHERE user_id = :user_id
ORDER BY bookmarks.pressed_at ASC';
$reply_stmt = $dbh->prepare($sql);
$reply_stmt->bindValue(':user_id', $_SESSION['login']['member_id'], PDO::PARAM_INT);
$reply_stmt->execute();

*/

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
                    <li><a href="user.php">プロフィール</a></li>
                    <li><a href="users_list.php">ユーザー一覧</a></li>
                    <li><a href="search.php">検索</a></li>
                </ul>
            </div>
        </header>    
        <main>



        <div class="containera">
      <div class="side">
        <h3>h3の目次</h3>
        <ul>
        <?php foreach ($category_names as $category_name) : ?>
        
          <li><a href="category.php?id=<?= h($category_name['id']) ?>"><?= h($category_name['name']) ?></a></li>
        <?php endforeach ?>  
        </ul>

        <form action="" method="post">
            <p>新規追加</p>
            <input type="text" name="insert_category">
            <button type="submit">登録</button>
        </form>
      </div>
      <div class="aaaa">
        <h2>h2のタイトルメインコンテンツ</h2>
        <p>pのテキスト</p>
      </div>
    </div>

    
    </main>
    </body>    
</html>     