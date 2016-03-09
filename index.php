<!DOCTYPE html>
<html lang="ja">
<head>
<title>Amazon APIテスト</title>
<meta charset="utf-8">
</head>

<body>
<form action="." method="post">
    商品名<input type="text" name="name">
    <input type="submit" value="送信">
</form>
<?php

require("config.php");

if($_SERVER["REQUEST_METHOD"] == "POST"){
    ItemSearch("Electronics", $_POST["name"]); // Videosを選択、「アナと雪の女王」は好きなキーワードを選んでください。
}

//Set up the operation in the request
function ItemSearch($SearchIndex, $Keywords){

    $baseurl = "https://webservices.amazon.co.jp/onca/xml";

    // リクエストのパラメータ作成
    $params = array();
    $params["Service"]          = "AWSECommerceService";
    $params["AWSAccessKeyId"]   = Access_Key_ID;
    $params["Version"]          = "2013-08-01";
    $params["Operation"]        = "ItemSearch";
    $params["SearchIndex"]      = $SearchIndex;
    $params["Keywords"]         = $Keywords;
    $params["AssociateTag"]     = Associate_tag;
    $params["ResponseGroup"]    = "ItemAttributes,Offers";
    $params["Sort"]       = "price";
    $params["MinimumPrice"]     = "100";
    $params["ItemPage"]         = "1";

    /* 0.元となるリクエスト */
    $base_request = "";
    foreach ($params as $k => $v) {
        $base_request .= "&" . $k . "=" . $v;
    }
    $base_request = $baseurl . "?" . substr($base_request, 1);

    /* 1.タイムスタンプを付ける */
    $params["Timestamp"] = gmdate("Y-m-d\TH:i:s\Z");
    $base_request .= "&Timestamp=" . $params['Timestamp'];

    /* 2.「RFC 3986」形式でエンコーディング */
    $base_request = "";
    foreach ($params as $k => $v) {
        $base_request .= '&' . $k . '=' . rawurlencode($v);
        $params[$k] = rawurlencode($v);
    }
    $base_request = $baseurl . "?" . substr($base_request, 1);

    /* 3.「&」とか消して改行 */
    $base_request = preg_replace("/.*\?/", "", $base_request);
    $base_request = str_replace("&", "\n", $base_request);

    /* 4.パラメーター名で昇順ソート */
    ksort($params);

    $base_request = "";
    foreach ($params as $k => $v) {
        $base_request .= "&" . $k . "=" . $v;
    }
    $base_request = substr($base_request, 1);
    $base_request = str_replace("&", "\n", $base_request);

    /* 5.もう一度「&」でつなぐ */
    $base_request = str_replace("\n", "&", $base_request);

    /* 6.3行を頭に追加 */
    $parsed_url = parse_url($baseurl);
    $base_request = "GET\n" . $parsed_url['host'] . "\n" . $parsed_url['path'] . "\n" . $base_request;

    /* 7.よく分からんエンコーディング */
    $signature = base64_encode(hash_hmac('sha256', $base_request, Secret_Access_Key, true));
    $signature = rawurlencode($signature);

    /* 8.「Signature」として最後に追加 */
    $base_request = "";
    foreach ($params as $k => $v) {
        $base_request .= "&" . $k . "=" . $v;
    }
    $base_request = $baseurl . "?" . substr($base_request, 1) . "&Signature=" . $signature;

    echo "<a href=\"" . $base_request . "\" target=\"_blank\">結果</a>";

    $amazon_xml=simplexml_load_string(file_get_contents($base_request));
    foreach($amazon_xml->Items->Item as $item) {

        $item_title = $item->ItemAttributes->Title; // 商品名
        $item_url = $item->DetailPageURL; // 商品へのURL
        $item_new_price = $item->OfferSummary->LowestNewPrice->FormattedPrice; // 新品商品の価格
        $item_used_price = $item->OfferSummary->LowestUsedPrice->FormattedPrice; // 中古商品の価格
        $item_price = $item->Offers->Offer->OfferListing->Price->FormattedPrice; // Amazon商品の価格

        echo "<p><a href=\"" . $item_url . "\" target=\"_blank\">" . $item_title . "</a><br>";
        echo "（Amazon）" . $item_price . "<br>";
        echo "（新品）" . $item_new_price . "<br>";
        echo "（中古）" . $item_used_price . "</p>";
    }
}

?>

</body>
</html>