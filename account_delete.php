<?php
ini_set("display_errors", "OFF");
require_once('functions.php');
session_start();
if(isset($_POST['user_delete'])) {
    delete_user($_POST['user_delete']);
    $_SESSION = array(); // 初期化
    session_destroy();
    header('Location: goodbye.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>アカウント削除</title>
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
            <div class="delete-account">
                <div class="container">
                    <h3>アカウントを削除しますか？</h3>
                    <form action="" method="post">
                        <input type="hidden" name="user_delete" value=<?= h($_SESSION['login']['member_id']) ?>> 
                        <button type="submit">削除する</button>
                    </form>
                </div>    
            </div>
        </main>  
    </body>    
</html>            