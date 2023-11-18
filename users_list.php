<?php
// ini_set("display_errors", "OFF");
require_once('functions.php');
session_start();

// ログインしていない場合はログインページへ強制的に移動
if (empty($_SESSION)) {
    header('Location: account_entry.php');
    exit;  
}

// ユーザーの一覧を取得
$dbh = db_open();
$sql = 'SELECT * FROM members';
$members_stmt = $dbh->query($sql);   

// ログインユーザーのfollowテーブルの情報を取得
$dbh = db_open();
$sql = 'SELECT * FROM follows WHERE follow = :follow';
$follows_stmt = $dbh->prepare($sql);
$follows_stmt->bindValue(':follow', $_SESSION['login']['member_id'], PDO::PARAM_INT);
$follows_stmt->execute();
while ($follows_row = $follows_stmt->fetch()) {
    $follows[] = $follows_row;  
}

// フォロー登録
if (isset($_POST['insert_follow'])) {
    insert_follow((int)$_POST['insert_follow']);
    header('Location: users_list.php');
    exit;
}

// フォロー解除
if (isset($_POST['delete_follow'])) {
    delete_follow((int)$_POST['delete_follow']);
    header('Location: users_list.php'); 
}

// アカウントボタンが押された場合
if (isset($_POST['user_page'])) {
    $_SESSION['other_user'] = (int)$_POST['user_page'];
    header('Location: other_user.php');
    exit;
}

// -------ブロック---------

// ログインユーザーがブロックしている、ログインユーザーをブロックしているユーザーを取得
// フォローボタンを表示しないようにする
$block_stmt = block_user();
while ($block_row = $block_stmt->fetch()) {
    $blocks[] = $block_row;
}
?>    

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>ユーザー一覧</title>
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
            <div class="users-list">
                <div class="container">
                    <!--ユーザー一覧を表示し、フォローしているかどうかでボタンの表示を変える-->
                    <?php while ($member_row = $members_stmt->fetch()) : ?>
                        <?php // $member_rowのうち、ログインユーザーであるものはスキップする
                        if ($member_row['member_id'] === $_SESSION['login']['member_id']) { 
                            continue;
                        } ?>
                        <!---アイコン----->
                        <?php 
                        $icon_row = get_icon($member_row['member_id']);
                        ?>
                        <?php if (empty($icon_row)) : ?>
                            <p class="users-list-icon"><img src="images/animalface_tanuki.png" class="icon"><p>
                        <?php else : ?>        
                            <p class="users-list-icon"><img src="<?= h($icon_row['file_path']) ?>" class="icon" ></p>
                        <?php endif; ?>

                        <!---ユーザー名----->
                        <p class="users-list-username"><?= h($member_row['name']) ?></p>

                        <!---プロフィール----->
                        <?php $profile_row = get_profile($member_row['member_id']) ?>
                        <p><?= h($profile_row['profile_content']) ?></p>

                        <div class="users-list-buttons">
                            <ul class="users-list-btn-list">
                                <!-----アカウントボタン------>
                                <form action="" method="post">
                                    <input type="hidden" name="user_page" value=<?= h($member_row['member_id']) ?>>
                                    <li><button type="submit">アカウント</button></li>
                                </form>

                                <!-----フォローボタン------>
                                <form action="" method="post">
                                    <?php // ブロックしている、されているか確認
                                    $block_like = true;
                                    foreach ($blocks as $block) {
                                        if ($block['is_blocked'] === $member_row['member_id'] ) {
                                            $block_like = false;
                                        }
                                    }
                                    if ($block_like === false) { ?>
                                        <li><button type="submit">ブロック</button></li>
                                    <?php }
                                    // ブロックしていない、されていない場合
                                    if ($block_like) :
                                        $is_following = false;
                                        foreach ($follows as $follow_value) { 
                                            if($follow_value['is_followed'] === $member_row['member_id']) { 
                                                $is_following = true;
                                                break;
                                                // $is_followingがtrueになった時点でbreakを入れてループを抜け、以降の不要なループ処理を省略するようにする
                                            }
                                        }
                                        ?>    
                                        <?php if ($is_following) : ?>
                                            <!--一覧でフォローを外したいユーザーのidを渡す-->
                                            <!--フォローしているユーザーには"フォロー解除"を表示-->
                                            <input type="hidden" name="delete_follow" value=<?= h($member_row['member_id']) ?>>
                                            <li><button type="submit">フォロー解除</button></li>
                                        <?php else : ?>
                                            <input type="hidden" name="insert_follow" value=<?= h($member_row['member_id']) ?>>
                                            <li><button type="submit">フォローする</button></li>
                                        <?php endif; ?> 
                                    <?php endif; ?>     
                                </form>
                            </ul>
                        </div>   
                    <?php endwhile; ?> 
                </div>
            </div>
        </main>
</html>