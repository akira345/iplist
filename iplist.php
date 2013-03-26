<?php
//IPListテストプログラム

//初期設定
//DB接続定義
$dsn = "mysql://db_user:db_password@localhost/iplist";//文字コードはUTF-8前提
//mb_convert_encoding形式で指定する
//DBサーバがわ文字コード
$db_char = "UTF-8";
//PCページ文字コード
$web_char = "EUC-JP";
//DBパラメタ用
$param = array();
//Pearライブラリインクルード
////Pear MDB2をインクルード
require_once('MDB2.php');
////Pear NET:IPV4をインクルード
require_once('Net/IPv4.php');
////Pear NET:WHOISをインクルード
require_once("Net/Whois.php");

// MDB2のエラー発生時呼び出す関数をセット
//errorHandlerを使うように設定
PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'chk_exec');
//DB接続チェック
$conn =& MDB2::connect($dsn);
if( MDB2::isError($conn) ) {
  echo "データベースに接続できません。処理を中止します。";
  // echo 'Standard Message: ' . $conn->getMessage() . "\n";
  // echo 'Standard Code: ' . $conn->getCode() . "\n";
  // echo 'DBMS/User Message: ' . $conn->getUserInfo() . "\n";
  // echo 'DBMS/Debug Message: ' . $conn->getDebugInfo() . "\n";
  exit;
}
//フェッチモードをオブジェクト形式に設定
$conn->setFetchMode(MDB2_FETCHMODE_OBJECT);

//SQL実行結果チェックサブ
function chk_exec($ret){
  If (PEAR::isError($ret)){
    die($ret->getMessage());
    exit;
  }
}

//文字コード変換サブ
//引数：変換対象文字列、変換後文字コード
function conv($str,$par){
  return mb_convert_encoding($str,$par,"AUTO");
}
//変数存在チェック(配列不可）
//引数：存在チェック対象
//戻値：定義されていないかNULLか空("")：FALSEそれ以外：TRUE
function chk_value($in_value){
  If (isset($in_value) == TRUE){
    //値が存在
    If(empty($in_value) == TRUE && is_numeric($in_value) == FALSE){
      //値が数値以外で空である
      return FALSE;
    }
  return TRUE;
  }else{
    return FALSE;
  }
}
//文字列をきれいにする
//2008/10/20 XSS対策
function clean($str,$charset){
// $tmp = htmlspecialchars($str);
  If (chk_value($charset) == FALSE){
    //文字コードが入っていない場合はエラーとする。
    //$charset = mb_internal_encoding();
    echo "内部エラー";
    exit;
  }
  //念のため文字コードチェック
  If (mb_check_encoding($str,$charset) == FALSE){
    //文字コードがおかしい
    echo "内部エラー";
    exit;
  }
  //2回通すと＆が増えるので一回デコードしてエンコードする
  $str = html_entity_decode($str, ENT_QUOTES, $charset);
  $tmp = htmlentities($str, ENT_QUOTES, $charset);
  return rtrim($tmp);
}
//SQL実行関連
function exec_sql($sql,$param,$db_char,$web_char,$conn){
  //コンバート
  $sql = conv($sql,$db_char);
  mb_convert_variables($db_char,$web_char,$param);
  //実行
  $tmp=$conn->prepare($sql);
  $ret=$tmp->execute($param);
  //結果
  return $ret;
}
//機種判定関数
//参考：Web上の皆様
//戻値：１：DoCoMo 2:Au 3:SoftBank 0:その他(PC)
function chk_mobile(){
  //注意！追加する際、J-PHONE関連はAUより先に判定すること！！
  //      (VodaFoneの一部機種にUP.Browserを返すものがあるため

  //ユーザーエージェント取得
  $IN_AGENT = $_SERVER["HTTP_USER_AGENT"];

  If ( preg_match( "/DoCoMo/", $IN_AGENT) ) {
    //DoCoMo
    Return 1;
    exit;
  } Else If ( preg_match( "/SoftBank/", $IN_AGENT)) {
    //SoftBank
    Return 3;
    exit;
  } Else If ( preg_match( "/J-PHONE/", $IN_AGENT)) {
    //SoftBank
    Return 3;
    exit;
  } Else If ( preg_match ("/Vodafone/", $IN_AGENT)){
    //SoftBank
    Return 3;
    exit;
  } Else If ( preg_match( "/MOT-/",$IN_AGENT)) {
    //SoftBank モトローラ
    Return 3;
    exit;
  } Else If ( preg_match( "/UP\.Browser/", $IN_AGENT)) {
    //Au
    Return 2;
    exit;
  } Else If ( preg_match( "/KDDI-/", $IN_AGENT)){
    //Au
    Return 2;
    exit;
  } Else {
    //その他(PC)
    Return 0;
    Exit;
  }
}
//機種別FEP自動切換関数
//引数：IME_ON:漢字変換ON , IME_OFF:漢字変換OFF，NUMBER：数字のみ（携帯のみ有効）
//戻値：各キャリアに応じたタグ
//注意：当然対応しているブラウザに対してのみ有効
//　　　textボックスに対し使用する。
//　　　キャリア判別関数chk_mobileとペアで使用する。

function set_ime($par){
  //IME制御
  //キャリア判別
  $kyaria = chk_mobile();
  If ($kyaria===0){
    //PC
    switch ($par) {
    case "IME_ON":
      return "style=\"ime-mode:active\"";
      break;
    case "IME_OFF":
    case "NUMBER":
      return "style=\"ime-mode:disabled\"";
      break;
    default:
      break;
    }
  }
  If ($kyaria === 1 || $kyaria === 2){
    //DoCoMoとAU
    switch ($par) {
    case "IME_ON":
      return "istyle=\"1\"";
      break;
    case "IME_OFF":
      return "istyle=\"3\"";//PC版にあわせるため、半角英字にする。
      break;
    case "NUMBER":
      return "istyle=\"4\"";//携帯のみ。
      break;
    default:
      break;
    }
  }
  If ($kyaria === 3){
    //SoftBank
    switch ($par) {
      case "IME_ON":
	return "mode=\"hiragana\"";
	break;
      case "IME_OFF":
	return "mode=\"alphabet\"";//PC版にあわせるため、半角英字にする。
	break;
      case "NUMBER":
	return "mode=\"numeric\"";//携帯のみ。
	break;
      default:
	break;
    }
  }
}
//ネットブロック修正
function fix_netblock($in_str){
//ipcountの戻り値が1.0.0と最後の.0が抜ける場合があるので修正する
  $tmp_count = substr_count($in_str,".");
  If ($tmp_count != 3){
    $tmp_length = strlen($in_str);
    $tmp_sra_point = strpos($in_str,"/");
    $tmp_str = substr($in_str,0,$tmp_sra_point);
    for ($i=$tmp_count;$i<3;$i++){
      $tmp_str .= ".0";
    }
    $tmp_str .= substr($in_str,$tmp_sra_point,$tmp_length - $tmp_sra_point);
    return $tmp_str;
  }else{
    return $in_str;
  }
}
///ここまで
//パラメタ取得
If ($_GET['in_ip']){
  $in_ip = clean($_GET['in_ip'],$web_char);
  $in_ip = gethostbyname($in_ip);
  If (Net_IPv4::validateIP($in_ip)){ 
      //指定されたIPで検索
    $sql = "SELECT l.*,(select c.country_name from country c "
    . "where l.country = c.country_cd) country_name "
    . "FROM iplist l "
    . "WHERE "
    . "inet_aton(?) between inet_aton(l.ip) and (inet_aton(l.ip) + (l.kosu -1))";
      $param=array($in_ip);
      $ret = exec_sql($sql,$param,$db_char,$web_char,$conn);
      $data_flg="NG";
      while ( $row = $ret->fetchRow() ) {
	      //データ取得
	      $wariate = clean(conv($row->wariate,$web_char),$web_char);
	      $country = clean(conv($row->country,$web_char),$web_char);
	      $ip      = clean(conv($row->ip,$web_char),$web_char);
	      $kosu    = clean(conv($row->kosu,$web_char),$web_char);
	      $wariate_year      = clean(conv($row->wariate_year,$web_char),$web_char);
	      $jyokyo      = clean(conv($row->jyokyo,$web_char),$web_char);
	      $netblock      = clean(conv($row->netblock,$web_char),$web_char);
	      $netblock = fix_netblock($netblock);
	      $country_name = clean(conv($row->country_name,$web_char),$web_char);
	      //どのネットブロックに所属しているか？
	      If (Net_IPv4::ipInNetwork($in_ip,$netblock)) {
			//表示
			$data_flg="OK";
			////Whois情報を取得してみる
			//switch ($wariate){
			//  case "afrinic":
			//    $whois_srv = "whois.afrinic.net";
			//    break;
			//  case "apnic":
			//    $whois_srv = "whois.apnic.net";
			//    break;
			//  case "arin":
			//    $whois_srv = "whois.arin.net";
			//    break;
			//  case "lacnic":
			//    $whois_srv = "whois.lacnic.net";
			//    break;
			//  case "ripencc":
			//    $whois_srv = "whois.ripe.net";
			//  break;
			//}
			//$whois = new Net_Whois;
			//$whois_data = $whois->query($in_ip, $whois_srv);
			//$whois_data = shell_exec("whois " . escapeshellcmd($in_ip));
			break;
	      }
      }
  }
}

?> 
<html>
<head>
<title>
IPアドレス割り当て検索プログラム
</title>
<meta http-equiv="Content-Type" content="text/html; charset=EUC-JP">
</head>
<body>
IPを入力してください。（IPV4のみ。サブネットマスク等は入れない）
<form action=./iplist.php method=get>
<input type=text name=in_ip value="<?=$in_ip?>" <?=set_ime("IME_OFF")?> size="15">
<input type=submit name=submit value="検索">
</form>
<br>
<br>
<?php
If ($data_flg == "OK"){
?>
<TABLE BORDER=0>
	<TBODY>
		<TR>
			<TD COLSPAN=2 ALIGN=CENTER>検索結果（<?=$in_ip?>）</TD>
		</TR>
		<TR>
			<TD>Ipアドレス</TD>
			<TD><?=$in_ip?></TD>
		</TR>
		<TR>
			<TD>ホスト名</TD>
			<TD><?=gethostbyaddr($in_ip)?></TD>
		</TR>
		<TR>
			<TD>ネットブロック</TD>
			<TD><?=$netblock?>(<?=number_format($kosu)?>個のアドレス)</TD>
		</TR>
		<TR>
			<TD>割り当てブロック</TD>
			<TD><?=$ip?> - <?=$kosu?> addresses</TD>
		</TR>
		<TR>
			<TD>割り当て</TD>
<?php
If ($wariate_year != 0){
?>
			<TD><?=strtoupper($wariate)?>/<?=date("Y/m/d",strtotime($wariate_year))?> </TD>
<?php
}else{
?>
			<TD><?=strtoupper($wariate)?>/不明 </TD>
<?php
}
?>
		</TR>
		<TR>
			<TD>国/地域</TD>
			<TD><?=$country?>:<?=$country_name?></TD>
		</TR>
	</TBODY>
</TABLE>
<p>
<?=nl2br($whois_data)?>
</p>
<?php
}
if($data_flg=="NG"){
?>
データがありません
<?php
}
?>
</body>

</html>
