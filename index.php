<?php

// Composerでインストールしたライブラリを一括読み込み
require_once __DIR__ . '/vendor/autoload.php';

touch('userIdList');
$myLocation=array();
$listKey=0;

// アクセストークンを使いCurlHTTPClientをインスタンス化
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));
// CurlHTTPClientとシークレットを使いLINEBotをインスタンス化
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);
// LINE Messaging APIがリクエストに付与した署名を取得
$signature = $_SERVER['HTTP_' . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];



// 署名が正当かチェック。正当であればリクエストをパースし配列へ
// 不正であれば例外の内容を出力
try {
  $events = $bot->parseEventRequest(file_get_contents('php://input'), $signature);
} catch(\LINE\LINEBot\Exception\InvalidSignatureException $e) {
  error_log('parseEventRequest failed. InvalidSignatureException => '.var_export($e, true));
} catch(\LINE\LINEBot\Exception\UnknownEventTypeException $e) {
  error_log('parseEventRequest failed. UnknownEventTypeException => '.var_export($e, true));
} catch(\LINE\LINEBot\Exception\UnknownMessageTypeException $e) {
  error_log('parseEventRequest failed. UnknownMessageTypeException => '.var_export($e, true));
} catch(\LINE\LINEBot\Exception\InvalidEventRequestException $e) {
  error_log('parseEventRequest failed. InvalidEventRequestException => '.var_export($e, true));
}

// 配列に格納された各イベントをループで処理
foreach ($events as $event) {

  if ($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage) {
    $locationId = $event->getText();
    if(preg_match('/地震/',$event->getText())){
      $bot->replyText($event->getReplyToken(),'キーワード「地震」に関する情報を表示します。以下の情報が見つかりました。'."\n".'http://www.jma.go.jp/jp/quake/');
    }else if(preg_match('/被災状況/',$event->getText())){
      replyImageMessage($bot, $event->getReplyToken(), 'https://' . $_SERVER['HTTP_HOST'] . '/imgs/original.jpg', 'https://' . $_SERVER['HTTP_HOST'] . '/imgs/preview.jpg');
      $bot->replyText($event->getReplyToken(),'キーワード「被災状況」に関する情報を表示します。');
    }else if(preg_match('/登録/',$event->getText())){
      $testMessage = mb_substr($event->getText(),3);
      //replyTextMessage($bot,$event->getReplyToken(),$testMessage);
      $file_name = $event->getUserId();
      $fp=fopen($file_name,'a');
      fwrite($fp,$testMessage);
      fclose($fp);
      $fp=fopen($file_name,'r');
      $txt1=fgets($fp);
      $txt2=fgets($fp);
      $txt3=fgets($fp);
      replyTextMessage($bot,$event->getReplyToken(),'メッセージを登録しました'."\n".'「'.$txt2.'」');
      fclose($fp);

    }else if(preg_match('/確認/',$event->getText())){
      $file_name = $event->getUserId();
      if (file_exists($file_name)){
        $fp=fopen($file_name,'r');
        $txt=fgets($fp);
        $txt2=fgets($fp);
        replyTextMessage($bot,$event->getReplyToken(),'メッセージ'. "\n". '「'. $txt2.'」');
        fclose($fp);
        }else{
          replyTextMessage($bot,$event->getReplyToken(),'位置情報が登録されていません。');
        }
      }else if( preg_match('/メッセージ/',$event->getText()) || preg_match('/最近傍/',$event->getText()) ){
        //比較用に自分の位置情報を自分のユーザーIDから取得(テキストメッセージイベントではgetlatitudeが使用不可のため)
        $fp=fopen($event->getUserId(),'r');
        $myLocation=explode(',',fgets($fp));
        $myId=$event->getUserId();
        fclose($fp);

        //ユーザーIDリスト読み込み
        $fp=fopen('userIdList','r');
        //配列に全ユーザーIDを格納
        $userIdArray=explode(',',fgets($fp));
        fclose($fp);
        $closest=10;

        //IDのそれぞれに対して位置情報を比較する
        if(preg_match('/メッセージ/',$event->getText())){
          foreach($userIdArray as $value){
            $fp2=fopen($value,'r');
            $theirLocation=explode(',',fgets($fp2));
            $theirMessage=fgets($fp2);
            fclose($fp2);

            if ( abs($myLocation[0]-$theirLocation[0]) < 0.001 ){
              if( abs($myLocation[1]-$theirLocation[1]) < 0.001 ){
                if($myId != $value){
                  replyTextMessage($bot,$event->getReplyToken(),$theirMessage);
                }
              }
            }
          }
        }

        else if(preg_match('/最近傍/',$event->getText())){
          foreach($userIdArray as $value){
            $fp2=fopen($value,'r');
            $theirLocation=explode(',',fgets($fp2));
            $theirMessage=fgets($fp2);
            fclose($fp2);

            $diff = abs($myLocation[0]-$theirLocation[0]) + abs($myLocation[1]-$theirLocation[1]);
            if ($closest > $diff && $myId != $value){
              $closest = $diff;
              $theirId=$value;
            }
          }
          $fp2=fopen($theirId,'r');
          $firstLine=fgets($fp2);
          $secondLine=fgets($fp2);
          replyTextMessage($bot,$event->getReplyToken(),'最も近い位置にいるユーザーのメッセージを表示します'."\n".'「'.$secondLine.'」');
          /*
          if($myLocation==$location){
            //現在のユーザーIDのメッセージを配列に格納
            //$messageList[$listKey]=fgets($fp);
            replyTextMessage($bot,$event->getReplyToken(),$location[0].' '. $location[1].' '.fgets($fp2));
            $listKey++;
          */
        }





    }else if(preg_match('/test/',$event->getText())){
      $file_name=$event->getUserID();
      $fp=fopen($file_name,'r');
      $txt1=fgets($fp);
      $txt2=fgets($fp);
      $txt3=fgets($fp);
      replyTextMessage($bot,$event->getReplyToken(),$txt2);
      fclose($fp);
    }
  }
}

  if ($event instanceof \LINE\LINEBot\Event\MessageEvent\LocationMessage){
    //$latitude=round($event->getLatitude(),4,PHP_ROUND_HALF_EVEN);
    //$longitude=round($event->getLongitude(),4,PHP_ROUND_HALF_EVEN);

    $latitude=$event->getLatitude();
    $longitude=$event->getLongitude();

    //ユーザーIDリストに追記
    $fp=fopen('userIdList','a');
    fputs($fp,$event->getUserId().',');
    fclose($fp);

    //ユーザー個別ファイルに位置情報を記録
    $file_name = $event->getUserId();
    touch($file_name);
    $fp=fopen($file_name,'a');
    fwrite($fp,$latitude .','. $longitude ."\n");




    //ファイルから一行ずつ読み出し
    fclose($fp);
    if (file_exists($file_name)){
      $fp=fopen($file_name,'r');
      $txt=fgets($fp);
      $txt2=fgets($fp);
      replyTextMessage($bot,$event->getReplyToken(),'緯度：'.$latitude."\n". '経度：'.$longitude);
      fclose($fp);
    }
  }


// テキストを返信。引数はLINEBot、返信先、テキスト
function replyTextMessage($bot, $replyToken, $text) {
  // 返信を行いレスポンスを取得
  // TextMessageBuilderの引数はテキスト
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text));
  // レスポンスが異常な場合
  if (!$response->isSucceeded()) {
    // エラー内容を出力
    error_log('Failed! '. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// 画像を返信。引数はLINEBot、返信先、画像URL、サムネイルURL
function replyImageMessage($bot, $replyToken, $originalImageUrl, $previewImageUrl) {
  // ImageMessageBuilderの引数は画像URL、サムネイルURL
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($originalImageUrl, $previewImageUrl));
  if (!$response->isSucceeded()) {
    error_log('Failed!'. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// 位置情報を返信。引数はLINEBot、返信先、タイトル、住所、
// 緯度、経度
function replyLocationMessage($bot, $replyToken, $title, $address, $lat, $lon) {
  // LocationMessageBuilderの引数はダイアログのタイトル、住所、緯度、経度
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\LocationMessageBuilder($title, $address, $lat, $lon));
  if (!$response->isSucceeded()) {
    error_log('Failed!'. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// スタンプを返信。引数はLINEBot、返信先、
// スタンプのパッケージID、スタンプID
function replyStickerMessage($bot, $replyToken, $packageId, $stickerId) {
  // StickerMessageBuilderの引数はスタンプのパッケージID、スタンプID
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\StickerMessageBuilder($packageId, $stickerId));
  if (!$response->isSucceeded()) {
    error_log('Failed!'. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// 動画を返信。引数はLINEBot、返信先、動画URL、サムネイルURL
function replyVideoMessage($bot, $replyToken, $originalContentUrl, $previewImageUrl) {
  // VideoMessageBuilderの引数は動画URL、サムネイルURL
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\VideoMessageBuilder($originalContentUrl, $previewImageUrl));
  if (!$response->isSucceeded()) {
    error_log('Failed! '. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// オーディオファイルを返信。引数はLINEBot、返信先、
// ファイルのURL、ファイルの再生時間
function replyAudioMessage($bot, $replyToken, $originalContentUrl, $audioLength) {
  // AudioMessageBuilderの引数はファイルのURL、ファイルの再生時間
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\AudioMessageBuilder($originalContentUrl, $audioLength));
  if (!$response->isSucceeded()) {
    error_log('Failed! '. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// 複数のメッセージをまとめて返信。引数はLINEBot、
// 返信先、メッセージ(可変長引数)
function replyMultiMessage($bot, $replyToken, ...$msgs) {
  // MultiMessageBuilderをインスタンス化
  $builder = new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder();
  // ビルダーにメッセージを全て追加
  foreach($msgs as $value) {
    $builder->add($value);
  }
  $response = $bot->replyMessage($replyToken, $builder);
  if (!$response->isSucceeded()) {
    error_log('Failed!'. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// Buttonsテンプレートを返信。引数はLINEBot、返信先、代替テキスト、
// 画像URL、タイトル、本文、アクション(可変長引数)
function replyButtonsTemplate($bot, $replyToken, $alternativeText, $imageUrl, $title, $text, ...$actions) {
  // アクションを格納する配列
  $actionArray = array();
  // アクションを全て追加
  foreach($actions as $value) {
    array_push($actionArray, $value);
  }
  // TemplateMessageBuilderの引数は代替テキスト、ButtonTemplateBuilder
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
    $alternativeText,
    // ButtonTemplateBuilderの引数はタイトル、本文、
    // 画像URL、アクションの配列
    new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder ($title, $text, $imageUrl, $actionArray)
  );
  $response = $bot->replyMessage($replyToken, $builder);
  if (!$response->isSucceeded()) {
    error_log('Failed!'. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// Confirmテンプレートを返信。引数はLINEBot、返信先、代替テキスト、
// 本文、アクション(可変長引数)
function replyConfirmTemplate($bot, $replyToken, $alternativeText, $text, ...$actions) {
  $actionArray = array();
  foreach($actions as $value) {
    array_push($actionArray, $value);
  }
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
    $alternativeText,
    // Confirmテンプレートの引数はテキスト、アクションの配列
    new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder ($text, $actionArray)
  );
  $response = $bot->replyMessage($replyToken, $builder);
  if (!$response->isSucceeded()) {
    error_log('Failed!'. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

// Carouselテンプレートを返信。引数はLINEBot、返信先、代替テキスト、
// ダイアログの配列
function replyCarouselTemplate($bot, $replyToken, $alternativeText, $columnArray) {
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
  $alternativeText,
  // Carouselテンプレートの引数はダイアログの配列
  new \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder (
   $columnArray)
  );
  $response = $bot->replyMessage($replyToken, $builder);
  if (!$response->isSucceeded()) {
    error_log('Failed!'. $response->getHTTPStatus . ' ' . $response->getRawBody());
  }
}

?>
