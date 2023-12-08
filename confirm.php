<?php
require_once('functions.php');
session_start(); // セッション開始

// 新規登録をしていない場合はログインページへ強制的に移動
if (empty($_SESSION['register'])) {
    header('Location: account_entry.php');
    exit;  
}

// 登録ボタンが押されたら、データベース接続、パスの暗号化、データベースに入力内容を追加(SQLの実行)
if(isset($_POST['register'])) {
    // パスワードのハッシュ化
    $pass_hash = password_hash($_SESSION['register']['regi_pass'], PASSWORD_DEFAULT); 
    $dbh = db_open();
    $sql = 'INSERT INTO members (member_id, name, email, password) 
    VALUES (NULL, :name, :email, :password)';
    $regi_stmt = $dbh->prepare($sql);
    $regi_stmt->bindValue(':name', $_SESSION['register']['user_name'], PDO::PARAM_STR);
    $regi_stmt->bindValue(':email', $_SESSION['register']['regi_email'], PDO::PARAM_STR);
    $regi_stmt->bindValue(':password', $pass_hash, PDO::PARAM_STR);
    $regi_stmt->execute();
    // ユーザーIDをセッションに保存する
    $sql = 'SELECT * FROM members WHERE members.email = :regi_email';
    $login_stmt = $dbh->prepare($sql);
    $login_stmt->bindValue(':regi_email', $_SESSION['register']['regi_email'], PDO::PARAM_STR);
    $login_stmt->execute();
    $login_row = $login_stmt->fetch();
    $_SESSION['user_id'] = $login_row['member_id'];
    header('Location:index.php'); // トップページへ
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>内容確認</title>
        <link rel="stylesheet" href="stylesheet.css">
    </head>
    <body>
        <div class="confirm">
            <div class="container">
                <form action="" method="post">
                    <h2>入力内容をご確認ください</h2>
                    <div>
                        <label>ユーザーネーム</label>
                        <p><?= h($_SESSION['register']['user_name']) ?></p>
                    </div>
                    <div>
                        <label>メールアドレス</label>
                        <p><?= h($_SESSION['register']['regi_email']) ?></p>
                    </div>
                    <div>
                        <label>パスワード</label>
                        <p><?= h($_SESSION['register']['regi_pass']) ?></p>
                    </div>
                    <div class="btn-confirm">
                        <button><a href="account_entry.php">戻る</a></button>
                        <button type="submit" name="register">登録</button>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>