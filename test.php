<?php
// ディレクトリ内のファイル一覧取得
$res = opendir("./img");
while($files[] = readdir($res));
closedir($res);

// 現在時刻-n秒を削除指定時刻に
$dtime = time()-15*60;

for($i=2; $files[$i]; $i++) {                               //"."と".."を省くので$files[2]から検証
    if(($files[$i] != basename($_SERVER['SCRIPT_NAME'])) &&
            ($files[$i] != ".gitignore")) {                 //自分自身を含まないように
        $ctime = filectime("./img/".$files[$i]);            //ファイルの作成日時を取得
        if($ctime < $dtime) unlink("./img/".$files[$i]);    //削除基準と照らし合わせて古いものは削除。
    }
}

require "./include/GD_ImageCreateFromBMP.php";

$max_file_size = 1024;  // 単位：KB

if($_REQUEST["UPLOAD"] != "" && !$flag_err) {
    $flag_err = false;

    $im_tmp  = $_FILES["IMG_PATH"]["tmp_name"];
    $im_name = $_FILES["IMG_PATH"]["name"];
    $im_size = $_FILES["IMG_PATH"]["size"];
    $im_type = $_FILES["IMG_PATH"]["type"];

    $im_codec   = $_POST["CODEC"];
    $im_jpeg_qp = $_POST["JPEG_QSCALE"];
    $im_png_bit = $_POST["PNG_COLOR"];
    $im_dither  = $_POST["DITHER"];
    $resize     = $_POST["RESIZE"];
    $fix_aspect = $_POST["FIX_ASPECT"];

    if($im_tmp == "") {
        $error_message = "<div class=\"error\">EORRO: ファイルを選択してください。</div>";
    } else if($im_size > $max_file_size*1024) {
        $error_message = "<div class=\"error\">EORRO: ファイルサイズが大きすぎます。".$max_file_size."KB以下にしてください。</div>";
    } else if(!($im_jpeg_qp >= 0 && $im_jpeg_qp <= 100)){
        $error_message = "<div class=\"error\">EORRO: JPEG品質は0～100の間で指定してください。</div>";
    } else {
        $im_message = "<table border=1><tr><th colspan=2>UPLORDED FILE</th></tr><tr><td>FILE NAME:</td><td>$im_name</td></tr><tr><td>FILE SIZE:</td><td>$im_size bytes</td></tr><tr><td>MIME TYPE:</td><td>$im_type</td></tr></table><br />";
        $orig_img = "./img/".date("YmdHis")."i.".GetExt($im_name);
        $new_img  = "./img/".date("YmdHis")."o.".$im_codec;

        move_uploaded_file($im_tmp, $orig_img);

        switch(GetExt(strtolower($im_name))) {
            case "jpg":
            case "jepg":
                $im = ImageCreateFromJPEG($orig_img);
                break;
            case "png":
                $im = ImageCreateFromPNG($orig_img);
                break;
            case "gif":
                $im = ImageCreateFromGIF($orig_img);
                break;
            case "bmp":
                $im = ImageCreateFromBMP($orig_img);
                break;
            default:
                $error_message = "<div class=\"error\">EORRO: 未対応の形式です。</div>";
                $flag_err = true;
        }

        switch($resize) {
            case '1':
                $im_out_width  = 480;
                $im_out_height = 272;
                break;
            case '2':
                $im_out_width  = 640;
                $im_out_height = 480;
                break;
            case '9':
                if(!is_numeric($im_out_width  = $_POST["WIDTH"]) || 
                   !is_numeric($im_out_height = $_POST["HEIGHT"])) {
                    $error_message = "<div class=\"error\">EORRO: 不正な値です。</div>";
                    $flag_err = true;
                }
                break;
        }

        if(!$flag_err) {
            if($resize != "0")
                $im = ImageResize($im, $im_out_width, $im_out_height, $fix_aspect);

            switch($im_codec) {
                case "jpg":
                    ImageJPEG($im, $new_img, $im_jpeg_qp);
                    break;
                case "png":
                    if($im_png_bit == "8")
                        ImageTrueColorToPalette($im, $im_dither, 256);
                    ImagePNG($im, $new_img, 9);
                    break;
                case "gif":
                    ImageTrueColorToPalette($im, $im_dither, 256);
                    ImageGIF($im, $new_img);
                    break;
            }
        }

        unlink($orig_img);
    }
}

// ファイルの拡張子を取得
function GetExt($FilePath) {
    $f   = strrev($FilePath);
    $ext = substr($f, 0, strpos($f, "."));
    return strrev($ext);
}

// イメージのリサイズ
function ImageResize($src, $width, $height, $fix_aspect) {
    $src_width   = ImageSX($src);
    $src_height  = ImageSY($src);
    $temp_width  = $src_width;
    $temp_height = $src_height;
    $src_ratio   = $src_width / $src_height;
    $out_ratio   = $width / $height;
    $src_x       = 0;
    $src_y       = 0;

    switch($fix_aspect) {
        case '0': // アスペクト比保持
            if ($out_ratio > $src_ratio) {
                $width = $height * $src_ratio;
            } else {
                $height = $width / $src_ratio;
            }
            break;
        case '1': // 中央を切り抜き
            if ($out_ratio > $src_ratio) {
                $src_height = $src_height * $src_ratio / $out_ratio;
                $src_y      = ($temp_height - $src_height) / 2;
            } else {
                $src_width = $src_width * $out_ratio / $src_ratio;
                $src_x     = ($temp_width - $src_width) / 2;
            }
            break;
    }

    $res = ImageCreateTrueColor($width, $height);
    ImageCopyResampled($res, $src, 0, 0, $src_x, $src_y,
                       $width, $height, $src_width, $src_height);

    return $res;
}
?>
<?php print "<?xml version=\"1.0\" encoding=\"utf-8\"?>" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja" />
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
    <meta http-equiv="Content-Style-Type" content="text/css" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <title>Image Converter</title>
    <link rel="stylesheet" href="./css/style.css" type="text/css" /> 
</head>
<body>
<hr />
<h1 class="form-text">Image Converter</h1>
<?php print $error_message ?>
<?php
if(isset($new_img)) {
    list($width, $height, $type, $attr) = getimagesize($new_img);
    print "<div><img src=\"$new_img\" $attr /></div>";
} else {
    print "<div class=\"form-text\">ここに変換画像が表示されます。</div>";
}
?>
<form name="form" action="./<?php print basename($_SERVER['SCRIPT_NAME']) ?>" method="POST" ENCTYPE="MULTIPART/FORM-DATA">
    <div class="form_field">
        <div class="grid-2">
            <div class="label">ファイル選択(JPEG/PNG/GIF/BMP、<?php print $max_file_size ?>KB以下)</div>
            <input class="form-text" style="width:18em;font-size:1em" name="IMG_PATH" type="file" />
            <input class="form-text" style="width:8em;font-size:1em" name="UPLOAD" type="submit" value="変換" />
        </div>
        <div class="flclear"><hr /></div>
        <div class="grid-2">
            <div class="label">出力形式</div>
            <ul class="form-text">
                <li><input name="CODEC" type="radio" value="jpg" checked />JPEG</li>
                <li><input name="CODEC" type="radio" value="png" />PNG</li>
                <li><input name="CODEC" type="radio" value="gif" />GIF</li>
            </ul>
        </div>
        <div class="grid-2">
            <div class="label">JPEG品質(0-100)</div>
            <input class="form-text align-right" style="width:5em;font-size:1em;padding:0.25em;margin:0" name="JPEG_QSCALE" type="text" value="80" />
        </div>
        <div class="grid-2">
            <div class="label">PNG色数</div>
            <ul class="form-text">
                <li><input name="PNG_COLOR" type="radio" value="24" checked />TrueColor</li>
                <li><input name="PNG_COLOR" type="radio" value="8" />256</li>
            </ul>
        </div>
        <div class="grid-2">
            <div class="label">減色ディザ</div>
            <ul class="form-text">
                <li><input name="DITHER" type="radio" value="false" checked />無効</li>
                <li><input name="DITHER" type="radio" value="true" />有効</li>
            </ul>
        </div>
        <div class="flclear"><hr /></div>
        <div class="grid-2">
            <div class="label">リサイズ</div>
            <ul class="form-text">
                <li><input name="RESIZE" type="radio" value="0" checked />無し</li>
                <li><input name="RESIZE" type="radio" value="1" />480x272</li>
                <li><input name="RESIZE" type="radio" value="2" />640x480</li>
                <li><input name="RESIZE" type="radio" value="9" />その他
                    <input class="align-right" style="width:3em;font-size:1em;padding:0;margin:0" name="WIDTH" type="text" value="480" />x<input class="align-right" style="width:3em;font-size:1em;padding:0;margin:0" name="HEIGHT" type="text" value="272" /></li>
            </ul>
        </div>
        <div class="grid-2">
            <div class="label">縦横比が変わるときの設定</div>
            <ul class="form-text">
                <li><input name="FIX_ASPECT" type="radio" value="0" checked />元の比率を保持</li>
                <li><input name="FIX_ASPECT" type="radio" value="1" />中央を切り抜き</li>
                <li><input name="FIX_ASPECT" type="radio" value="2" />全体に引き伸ばす</li>
            </ul>
        </div>
        <div class="flclear"><hr /></div>
        <div class="grid-1">
            <input class="form-text" style="width:70%;font-size:1em" name="UPLOAD" type="submit" value="変換" />
        </div>
    </div>
</form>
</body>
</html>
