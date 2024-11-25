<?php

$apikey = "";

$target = ""; // string which makes up part of the user_notes->user_note->note_text value -- can be partial

if(isset($_POST['barcodes'])):

  $barcodesfiltered = filter_var($_POST['barcodes'],FILTER_UNSAFE_RAW) ?? ""; // filter malicious input
  $barcodesfiltered = preg_replace('/\s+/', '', $barcodesfiltered); // strip spaces
  $barcodesarr = explode(",", $barcodesfiltered); // comma separated string to array

  foreach($barcodesarr as $key => $barcode) {

    // retrieve data for each barcode
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/' . $barcode . '?apikey=' . $apikey,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FAILONERROR => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $response = curl_exec($curl);
    if (curl_errno($curl)) { // connection was unsuccessful or barcode was not found
        echo "<p>" . $barcode . " query error: " . curl_error($curl) . "</p>";
    }
    curl_close($curl);


    libxml_use_internal_errors(true);
    $responseisxml = simplexml_load_string($response); // check that we're dealing with valid xml
    if ($responseisxml) {

      $xml = new SimpleXMLElement($response);  // xml to object

      if((!empty($xml->user_notes->user_note->note_text) && str_contains($xml->user_notes->user_note->note_text,$target) || count($xml->user_notes->user_note) > 1)){
        if(count($xml->user_notes->user_note) > 1) { // If there is more than one note, iterate through each looking for target string
          $notekey = 0; // have to manually increment a key here because each note has the key "user_note"
          $notekeytoremove = "";
          foreach($xml->user_notes->user_note as $note){
            if (str_contains($note->note_text,$target)) {
              $notekeytoremove = $notekey;
            }
            $notekey++;
          }
          if($notekeytoremove !== "") {
            unset($xml->user_notes->user_note[$notekeytoremove]);
          }
        } else {
          unset($xml->user_notes->user_note); // there was only one note and it contained the target string
        }
        
        // send edited data back to Alma
        $returncurl = curl_init();

        curl_setopt_array($returncurl, array(
          CURLOPT_URL => 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/' . $barcode . '?apikey=' . $apikey,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'PUT',
          CURLOPT_POSTFIELDS => $xml->asXML(), // object back to xml
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/xml'
          ),
        ));

        $returnresponse = curl_exec($returncurl);
        curl_close($returncurl);
        
        $returnresponseisxml = simplexml_load_string($returnresponse); // check that we're dealing with valid xml

        if ($returnresponseisxml) {
          $returnxml = new SimpleXMLElement($returnresponse);
          if(isset($returnxml->primary_id)){ // if the response XML contains a primary ID, the removal was successful
            echo "<p class=\"removed\">" . $barcode . " removed</p>";
          } elseif(isset($returnxml->errorsExist)) {
            echo "<p class=\"notremoved\"><strong>" . $barcode . " error: " . $returnxml->errorList->error->errorMessage . "</strong></p>";
          }
        }
        
      } else {
        echo "<p class=\"notremoved\">" . $barcode . " note not found</p>";
      }

    }

    if ($key === array_key_last($barcodesarr)) {
        echo "<p style=\" font-weight: bold; font-size: 2em; margin: 30px;\">COMPLETE</p>";
    }
  }
else:
?>
<form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
<label for="barcodes">comma-separated list of barcodes or Alma ID emails</label>
<textarea name="barcodes" style="height: 300px; width: 500px; display: block;"></textarea>
<input type="submit" value="remove notes" style="margin-top: 10px;">
</form>
<?php
endif;
?>