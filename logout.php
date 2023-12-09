<?php
session_start();
$_SESSION = array();
session_destroy();
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>ログアウト</title>
        <link rel="stylesheet" href="stylesheet.css">
    </head>
    <body>  
        <div class="logout">
                <div class="container">
                    <h3>ログアウトしました</h3>
                    <button><a href="account_entry.php">ログインページへ</a></button>
                </div>
        </div>
    </body>