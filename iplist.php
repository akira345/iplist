<?php
//IPList�ƥ��ȥץ����

//�������
//DB��³���
$dsn = "mysql://db_user:db_password@localhost/iplist";//ʸ�������ɤ�UTF-8����
//mb_convert_encoding�����ǻ��ꤹ��
//DB�����Ф���ʸ��������
$db_char = "UTF-8";
//PC�ڡ���ʸ��������
$web_char = "EUC-JP";
//DB�ѥ�᥿��
$param = array();
//Pear�饤�֥�ꥤ�󥯥롼��
////Pear MDB2�򥤥󥯥롼��
require_once('MDB2.php');
////Pear NET:IPV4�򥤥󥯥롼��
require_once('Net/IPv4.php');
////Pear NET:WHOIS�򥤥󥯥롼��
require_once("Net/Whois.php");

// MDB2�Υ��顼ȯ�����ƤӽФ��ؿ��򥻥å�
//errorHandler��Ȥ��褦������
PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'chk_exec');
//DB��³�����å�
$conn =& MDB2::connect($dsn);
if( MDB2::isError($conn) ) {
  echo "�ǡ����١�������³�Ǥ��ޤ��󡣽�������ߤ��ޤ���";
  // echo 'Standard Message: ' . $conn->getMessage() . "\n";
  // echo 'Standard Code: ' . $conn->getCode() . "\n";
  // echo 'DBMS/User Message: ' . $conn->getUserInfo() . "\n";
  // echo 'DBMS/Debug Message: ' . $conn->getDebugInfo() . "\n";
  exit;
}
//�ե��å��⡼�ɤ򥪥֥������ȷ���������
$conn->setFetchMode(MDB2_FETCHMODE_OBJECT);

//SQL�¹Է�̥����å�����
function chk_exec($ret){
  If (PEAR::isError($ret)){
    die($ret->getMessage());
    exit;
  }
}

//ʸ���������Ѵ�����
//�������Ѵ��о�ʸ�����Ѵ���ʸ��������
function conv($str,$par){
  return mb_convert_encoding($str,$par,"AUTO");
}
//�ѿ�¸�ߥ����å�(�����Բġ�
//������¸�ߥ����å��о�
//���͡��������Ƥ��ʤ���NULL����("")��FALSE����ʳ���TRUE
function chk_value($in_value){
  If (isset($in_value) == TRUE){
    //�ͤ�¸��
    If(empty($in_value) == TRUE && is_numeric($in_value) == FALSE){
      //�ͤ����Ͱʳ��Ƕ��Ǥ���
      return FALSE;
    }
  return TRUE;
  }else{
    return FALSE;
  }
}
//ʸ����򤭤줤�ˤ���
//2008/10/20 XSS�к�
function clean($str,$charset){
// $tmp = htmlspecialchars($str);
  If (chk_value($charset) == FALSE){
    //ʸ�������ɤ����äƤ��ʤ����ϥ��顼�Ȥ��롣
    //$charset = mb_internal_encoding();
    echo "�������顼";
    exit;
  }
  //ǰ�Τ���ʸ�������ɥ����å�
  If (mb_check_encoding($str,$charset) == FALSE){
    //ʸ�������ɤ���������
    echo "�������顼";
    exit;
  }
  //2���̤��ȡ���������Τǰ��ǥ����ɤ��ƥ��󥳡��ɤ���
  $str = html_entity_decode($str, ENT_QUOTES, $charset);
  $tmp = htmlentities($str, ENT_QUOTES, $charset);
  return rtrim($tmp);
}
//SQL�¹Դ�Ϣ
function exec_sql($sql,$param,$db_char,$web_char,$conn){
  //����С���
  $sql = conv($sql,$db_char);
  mb_convert_variables($db_char,$web_char,$param);
  //�¹�
  $tmp=$conn->prepare($sql);
  $ret=$tmp->execute($param);
  //���
  return $ret;
}
//����Ƚ��ؿ�
//���͡�Web��γ���
//���͡�����DoCoMo 2:Au 3:SoftBank 0:����¾(PC)
function chk_mobile(){
  //��ա��ɲä���ݡ�J-PHONE��Ϣ��AU������Ƚ�ꤹ�뤳�ȡ���
  //      (VodaFone�ΰ��������UP.Browser���֤���Τ����뤿��

  //�桼��������������ȼ���
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
    //SoftBank ��ȥ���
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
    //����¾(PC)
    Return 0;
    Exit;
  }
}
//������FEP��ư�ڴ��ؿ�
//������IME_ON:�����Ѵ�ON , IME_OFF:�����Ѵ�OFF��NUMBER�������Τߡʷ��ӤΤ�ͭ����
//���͡��ƥ���ꥢ�˱���������
//��ա������б����Ƥ���֥饦�����Ф��ƤΤ�ͭ��
//������text�ܥå������Ф����Ѥ��롣
//����������ꥢȽ�̴ؿ�chk_mobile�ȥڥ��ǻ��Ѥ��롣

function set_ime($par){
  //IME����
  //����ꥢȽ��
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
    //DoCoMo��AU
    switch ($par) {
    case "IME_ON":
      return "istyle=\"1\"";
      break;
    case "IME_OFF":
      return "istyle=\"3\"";//PC�Ǥˤ��碌�뤿�ᡢȾ�ѱѻ��ˤ��롣
      break;
    case "NUMBER":
      return "istyle=\"4\"";//���ӤΤߡ�
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
	return "mode=\"alphabet\"";//PC�Ǥˤ��碌�뤿�ᡢȾ�ѱѻ��ˤ��롣
	break;
      case "NUMBER":
	return "mode=\"numeric\"";//���ӤΤߡ�
	break;
      default:
	break;
    }
  }
}
//�ͥåȥ֥�å�����
function fix_netblock($in_str){
//ipcount������ͤ�1.0.0�ȺǸ��.0��ȴ�����礬����Τǽ�������
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
///�����ޤ�
//�ѥ�᥿����
If ($_GET['in_ip']){
  $in_ip = clean($_GET['in_ip'],$web_char);
  $in_ip = gethostbyname($in_ip);
  If (Net_IPv4::validateIP($in_ip)){ 
      //���ꤵ�줿IP�Ǹ���
    $sql = "SELECT l.*,(select c.country_name from country c "
    . "where l.country = c.country_cd) country_name "
    . "FROM iplist l "
    . "WHERE "
    . "inet_aton(?) between inet_aton(l.ip) and (inet_aton(l.ip) + (l.kosu -1))";
      $param=array($in_ip);
      $ret = exec_sql($sql,$param,$db_char,$web_char,$conn);
      $data_flg="NG";
      while ( $row = $ret->fetchRow() ) {
	      //�ǡ�������
	      $wariate = clean(conv($row->wariate,$web_char),$web_char);
	      $country = clean(conv($row->country,$web_char),$web_char);
	      $ip      = clean(conv($row->ip,$web_char),$web_char);
	      $kosu    = clean(conv($row->kosu,$web_char),$web_char);
	      $wariate_year      = clean(conv($row->wariate_year,$web_char),$web_char);
	      $jyokyo      = clean(conv($row->jyokyo,$web_char),$web_char);
	      $netblock      = clean(conv($row->netblock,$web_char),$web_char);
	      $netblock = fix_netblock($netblock);
	      $country_name = clean(conv($row->country_name,$web_char),$web_char);
	      //�ɤΥͥåȥ֥�å��˽�°���Ƥ��뤫��
	      If (Net_IPv4::ipInNetwork($in_ip,$netblock)) {
			//ɽ��
			$data_flg="OK";
			////Whois�����������Ƥߤ�
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
IP���ɥ쥹������Ƹ����ץ����
</title>
<meta http-equiv="Content-Type" content="text/html; charset=EUC-JP">
</head>
<body>
IP�����Ϥ��Ƥ�����������IPV4�Τߡ����֥ͥåȥޥ�����������ʤ���
<form action=./iplist.php method=get>
<input type=text name=in_ip value="<?=$in_ip?>" <?=set_ime("IME_OFF")?> size="15">
<input type=submit name=submit value="����">
</form>
<br>
<br>
<?php
If ($data_flg == "OK"){
?>
<TABLE BORDER=0>
	<TBODY>
		<TR>
			<TD COLSPAN=2 ALIGN=CENTER>������̡�<?=$in_ip?>��</TD>
		</TR>
		<TR>
			<TD>Ip���ɥ쥹</TD>
			<TD><?=$in_ip?></TD>
		</TR>
		<TR>
			<TD>�ۥ���̾</TD>
			<TD><?=gethostbyaddr($in_ip)?></TD>
		</TR>
		<TR>
			<TD>�ͥåȥ֥�å�</TD>
			<TD><?=$netblock?>(<?=number_format($kosu)?>�ĤΥ��ɥ쥹)</TD>
		</TR>
		<TR>
			<TD>������ƥ֥�å�</TD>
			<TD><?=$ip?> - <?=$kosu?> addresses</TD>
		</TR>
		<TR>
			<TD>�������</TD>
<?php
If ($wariate_year != 0){
?>
			<TD><?=strtoupper($wariate)?>/<?=date("Y/m/d",strtotime($wariate_year))?> </TD>
<?php
}else{
?>
			<TD><?=strtoupper($wariate)?>/���� </TD>
<?php
}
?>
		</TR>
		<TR>
			<TD>��/�ϰ�</TD>
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
�ǡ���������ޤ���
<?php
}
?>
</body>

</html>
