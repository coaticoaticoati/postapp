<?php
ini_set("display_errors", "OFF");
require_once('functions.php');
session_start();

// ------バリデーション-------

// 送信ボタンが押された後に、各項目に未入力があるかどうか確認
// ログイン
if ($_POST['login_email'] === '') {
    $error['login_email'] = 'blank';
} 
if ($_POST['login_pass'] === '') {
    $error['login_pass'] = 'blank';
}
// 新規登録
if ($_POST['user_name'] === '') {
    $error['user_name'] = 'blank';
}
if($_POST['regi_email'] === '') {
    $error['regi_email'] = 'blank';
} 
if ($_POST['regi_pass'] === '') {
    $error['regi_pass'] = 'blank';
}

// 新規登録
// メールアドレスのバリデーション
if (isset($_POST['regi_email'])) {
    $dbh = db_open();
    $sql = 'SELECT * FROM members WHERE members.email = :email';
    $regi_stmt = $dbh->prepare($sql);
    $regi_stmt->bindValue(':email', $_POST['regi_email'], PDO::PARAM_STR);
    $regi_stmt->execute();
    $regi_row = $regi_stmt->fetch();
    // メールアドレスに重複がないかチェック、登録済みであればメッセージ表示
    if ($regi_row !== false) {
        $error['email_value'] = 'registered'; // 変数を作成し、適当な値registeredを代入
    }
}

// パスワードのバリデーション
if(isset($_POST['regi_pass'])) {  
    // マッチしない場合
    if (empty(preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[a-z])[A-Z0-9a-z]{8,100}$/', $_POST['regi_pass']))) {
        $error['pass_value'] = 'not_match'; 
    }
}
// 全てOKなら確認ページへ
if (isset($_POST['user_name'], $_POST['regi_email'], $_POST['regi_pass'])) { 
    // 上記がないとフォーム入力前のトップページも当てはまるので、account_entry.phpを表示できずconfirm.phpに移動してしまう
    if (empty($reg_row) && empty($error)) {
        $_SESSION['register'] = $_POST;
        header('Location: confirm.php');
        exit;
    }      
}

// ログイン
// データベースからユーザー情報を取得

// 入力されたメールアドレスが存在するか確認
if (isset($_POST['login_email'])) {
    $dbh = db_open();
    $sql = 'SELECT * FROM members WHERE members.email = :email';
    $login_stmt = $dbh->prepare($sql);
    $login_stmt->bindValue(':email', $_POST['login_email'], PDO::PARAM_STR);
    $login_stmt->execute();
    $login_row = $login_stmt->fetch();

    // データベース内に登録がない場合
    if ($login_row === false) {
        $error['login'] = 'not_login';
    }
    // パスワードを照合し、マッチしたら、ユーザーIDをセッションに保存し、トップページへ
    if (password_verify($_POST['login_pass'], $login_row['password'])) {
        $_SESSION['user_id'] = $login_row['member_id'];
        header('Location: index.php');
    // パスワードがマッチしない場合
    } else { 
        $error['login'] = 'not_login';
    } 
}
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>ログイン・新規登録</title>
        <link rel="stylesheet" href="stylesheet.css">
    </head>
    <body>
        <div class="login">
            <div class="container">
                <form action="" method="post">
                    <h2>ログインはこちら</h2>
                    <?php if ($error['login'] === 'not_login') : ?>
                        <p>メールアドレスまたはパスワードに誤りがあります。</p>
                    <?php endif; ?>    
                    <div>
                        <p>メールアドレス</p>
                        <input type="text" name="login_email" class="textbox">
                        <?php if ($error['login_email'] === 'blank') : ?>
                            <p>メールアドレスを入力してください。</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p>パスワード</p>
                        <input type="text" name="login_pass" class="textbox">
                        <?php if ($error['login_pass'] === 'blank') : ?>
                            <p class="notice">パスワードを入力してください。</p>
                        <?php endif; ?>
                    </div>    
                    <button type="submit" class="btn-login">ログイン</button>    
                </form>  
            </div>
        </div>
    
        <div class="register-account">
            <div class="container">
                <h2>アカウント作成はこちら</h2>
                <form action="" method="post">
                    <div>
                        <p>ユーザーネーム</p>
                        <input type="text" name="user_name" class="textbox">
                        <?php if ($error['user_name'] === 'blank') : ?>
                            <p>ユーザーネームを入力してください。</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p>メールアドレス</p>
                        <input type ="text" name="regi_email" class="textbox">
                        <?php if ($error['regi_email'] === 'blank') : ?>
                            <p>メールアドレスをを入力してください。</p>
                        <?php endif; ?>
                        <?php if ($error['email_value'] === 'registered') : ?>
                            <p>このメールアドレスは登録済みです。</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p>パスワード</p>
                        <input type="text" name="regi_pass" placeholder="半角英小文字、大文字、数字を使い、8文字以上" class="textbox">
                        <?php if ($error['regi_pass'] === 'blank') : ?>
                            <p>パスワードを入力してください。</p>
                        <?php endif; ?>
                        <?php if ($error['pass_value'] === 'not_match') : ?>
                            <p class="notice">半角英小文字、半角英大文字、半角数字をそれぞれ1文字以上含んだ8文字以上で設定してください。</p>
                        <?php endif; ?>
                    </div>    
                    <button type="submit" class="btn-login">確認</button>
                </form> 
            </div>
        </div>
    </body>
</html>