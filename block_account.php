<?php
ini_set("display_errors", "OFF");
require_once('functions.php');
session_start();

// データベース接続
$dbh = db_open(); 

// ログインユーザーがブロックしているユーザーを取得
$sql ='SELECT block, is_blocked, name FROM blocks 
INNER JOIN members ON blocks.is_blocked = members.member_id
LEFT JOIN icons ON blocks.is_blocked = icons.user_id
WHERE block = :block';
$block_stmt = $dbh->prepare($sql);
$block_stmt->bindValue(':block', $_SESSION['user_id'], PDO::PARAM_INT);
$block_stmt->execute();
while ($blocked_user = $block_stmt->fetch()) {
    $blocked_users[] = $blocked_user;
}
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>ブロック一覧</title>
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
            <div class="block-account">
                <div class="container">
                    <?php if (empty($blocked_users)) : ?>
                        <h3>ブロック中のユーザーはいません</h3>
                        <div class="no-block-account-btn">
                            <button><a href="profile_edit.php">戻る</a></button>
                        </div>
                    <?php else : ?>
                        <?php foreach ($blocked_users as $blocked_user) : ?>
                            <!-- アイコン -->
                            <div class="timeline-icon">
                                <?php
                                    $icon_row = get_icon($blocked_user['is_blocked']);
                                    if (empty($icon_row)) : ?>
                                        <p><img src="images/animalface_tanuki.png" class="icon"><p>
                                    <?php else : ?>        
                                        <p><img src="<?= h($icon_row) ?>" class="icon" ></p>
                                    <?php endif; ?>
                            </div>

                            <!-- ユーザー名 -->
                            <p><?= h($blocked_user['name']) ?></p>

                            <!-- ボタン -->
                            <div class="block-account-btn">
                                <button><a href="user.php?id=<?= h($blocked_user['is_blocked']) ?>">ブロック解除</a></button>
                            </div>
                        <?php endforeach ?>
                    <?php endif ?>    
                </div>
            </div>
        </main>
    </body>
</html>            

