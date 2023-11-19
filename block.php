<?php
ini_set("display_errors", "OFF");
require_once('functions.php');
session_start();

// データベース接続
$dbh = db_open(); 

// ブロック解除が押された場合

// データベース削除
if (isset($_POST['unblock_user'])) {
    $sql = 'DELETE FROM blocks WHERE block = :block AND is_blocked = :is_blocked';
    $block_del_stmt = $dbh->prepare($sql);
    $block_del_stmt->bindValue(':block', $_SESSION['login']['member_id'], PDO::PARAM_INT);
    $block_del_stmt->bindValue(':is_blocked', $_SESSION['other_user'], PDO::PARAM_INT);
    $block_del_stmt->execute();
    header('Location: other_user.php');
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
            <div class="block">
                <div class="container">
                    <?php
                    // ログインユーザーが参照中ユーザーをブロックしている、ログインユーザーが参照中ユーザーにブロックされてるか確認
                    $block_user = get_block_user($_SESSION['other_user']);
                    $blocked_user = get_blocked_user($_SESSION['other_user']);

                    // ブロックしているか、されているかで表示を変える
                    if (!empty($block_user)) :
                    ?>
                        <form action="" method="post">
                            <input type="hidden" name="unblock_user" value=<?= h($_SESSION['other_user']) ?>>  
                            <h4><?= h(get_user_name($_SESSION['other_user'])) ?>さんをブロックしています。ブロックを解除しますか。</h4>
                            <button type="submit">解除する</button>
                        </form>
                    <?php elseif (!empty($blocked_user)) : ?>
                        <h4><?= h(get_user_name($_SESSION['other_user'])) ?>さんはあなたをブロックしています。</h4>
                    <?php endif; ?>  
                </div>
            </div>
        </main>    
    </body>
</html>      