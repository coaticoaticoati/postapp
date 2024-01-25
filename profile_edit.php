<?php
ini_set("display_errors", "OFF");
require_once('functions.php');
session_start();

// データベース接続
$dbh = db_open(); 

// ----ユーザー名------

// ユーザー名の更新
if (isset($_POST['update_name'])) {
    $sql = 'UPDATE members SET name = :name WHERE member_id = :member_id';
    $post_stmt = $dbh->prepare($sql);
    $post_stmt->bindValue(':name', $_POST['update_name'], PDO::PARAM_STR);
    $post_stmt->bindValue(':member_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $post_stmt->execute();
    $_SESSION['login']['name'] = $_POST['update_name'];
}

// -------アイコン------

// アイコンのファイルデータを取得
if (isset($_FILES['image'])) {
    $file = $_FILES['image'];
    // データを変数に代入
    $file_name = $file['name'];
    $file_tmp_path = $file['tmp_name'];
    $file_error = $file['error'];
    $file_size = $file['size'];
    
    $file_name = basename($file['name']); //パス名からディレクトリ部分を除いたファイル名を取得
    $save_file_name = date('YmdHis').$file_name; //保存ファイル名は日時+ファイル名とする
    
    $upload_dir = 'images/'; //保存先のファイルパスでこの下に保存する指定
    $save_path = $upload_dir.$save_file_name; //保存先のパス(+日付)

    // ファイルのバリデーション
    // ファイルサイズは2MB未満か
    if ($file_size > 2097152 || $file_error === 2 ) {
        $error['file_size'] = 'over';
    }
    // 拡張は画像形式か
    $file_extentions = ['jpg', 'jpeg', 'png']; //許容する拡張子
    $file_ext = pathinfo($file_name, PATHINFO_EXTENSION); //ファイル名の拡張子のみ抽出
    $file_ext = strtolower($file_ext); //大文字だった場合小文字に変える
    // 拡張子配列のいずれとも合致しなかった場合
    if (!in_array($file_ext, $file_extentions)) {
        $error['file_ext'] = 'not_match';
    }
    // ファイルがアップロードされているか
    if(!isset($error)) {
        if (is_uploaded_file($file_tmp_path)) { // ファイルがアップロードされているか
            if (move_uploaded_file($file_tmp_path, $save_path)) { //一時ディレクトリ($file_tmp_path)からimages($save_path)に移動
                //DBに保存（ファイル名、ファイルパス、投稿文）
                insert_icon($file_name, $save_path);
            } else {
                $error['file_save'] = 'not_save';
            } 
        } else {
            $error['file_upload'] = 'not_upload';
        }
    }
}

// アイコンを削除
if (isset($_POST['delete_icon'])) {
    delete_icon($_SESSION['user_id']);
    header('Location: profile_edit.php');
    exit;
}  

// ----プロフィール文------

// バリデーション
if(strlen($_POST['profile_content']) > 200) {
    $error['profile_content'] = 'over';
}

// プロフィール文をデータベースに追加、あれば更新
if (isset($_POST['insert_profile'])) {
    if (!isset($error)) {
        $sql = 'INSERT INTO profiles (profile_content, user_id)
        VALUES (:profile_content, :user_id) ON DUPLICATE KEY UPDATE 
        profile_content = VALUES (profile_content)';
        $pro_ins_stmt = $dbh->prepare($sql);
        $pro_ins_stmt->bindValue(':profile_content', $_POST['insert_profile'], PDO::PARAM_STR);
        $pro_ins_stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $pro_ins_stmt->execute();
    }
}

// プロフィール文を削除
if (isset($_POST['delete_profile'])) {
    delete_profile($_SESSION['user_id']);
    header('Location: profile_edit.php');
    exit;   
}
?>
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>プロフィール編集</title>
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
            <div class="edit">
                <div class="container">   
                    <!-- ユーザー名を編集 -->
                    <div class="edit-user">
                        <h3>ユーザー名を編集</h3>
                        <form action="" method="post">    
                            <input type="text" name="update_name" value="<?= h(get_user_name($_SESSION['user_id'])) ?>" class="textbox">
                            <button type="submit">送信</button>
                        </form>
                    </div>

                    <!-- アイコンを編集 -->
                    <div class="edit-icon">
                        <h3>アイコンを編集</h3>
                        <P><img src="<?= h(get_icon($_SESSION['user_id'])) ?>"></P>
                        <form action="" method="post" enctype="multipart/form-data">    
                            <input type="hidden" name="MAX_FILE_SIZE" value="2097152"><!-----ファイルの最大サイズを指定----->
                            <input type="file" name="image" accept="image/*">
                            <button type="submit">送信</button> 
                        </form>
                        
                        <!-- エラーメッセージの表示 -->
                        <?php if ($error['file_size'] === 'over') : ?>
                            <p>ファイルサイズは2MB未満にしてください。</p>
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
                    
                    <!-- アイコンを削除 -->
                        <form action="" method="post">
                            <input type="hidden" name="delete_icon">
                            <button type="submit" class="delete_icon">アイコンを削除する</button>
                        </form>
                    </div>

                    <!-- プロフィール文を編集 -->
                    <div class="edit-profile-stmt">
                        <h3>プロフィール文を編集</h3>
                        <div class="profile-form">
                            <form action="" method="post">    
                                <textarea name="insert_profile" class="profile-box"><?= h(get_profile($_SESSION['user_id'])) ?></textarea>
                                <div><button type="submit">送信</button></div>
                            </form>
                        </div>
                        <div class="delete-profile">
                            <form action="" method="post">
                                <input type="hidden" name="delete_profile">
                                <button type="submit">プロフィール文を削除する</button>
                            </form>
                        </div>    
                        <?php if ($error['profile_content'] === 'over') : ?>
                            <p>200字以内で入力してください。</p>
                        <?php endif; ?>
                    </div>

                    <!-- ブロック中のアカウントを表示するボタン -->
                    <div class="show-block-account">
                     <button><a href="block_account.php">ブロック中のアカウントを見る</a></button>
                    </div>

                    <!-- アカウント削除 -->
                    <div class="delete_account">
                        <button><a href="account_delete.php">アカウントを削除する</a></button>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>            