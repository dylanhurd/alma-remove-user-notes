# Remove Alma user notes based on string in note_text field 


**Caveat:** Because this script requires an API with write access to Alma production, it should not be publicly accessible.

## API key
 
For the $apikey variable at the top of the script, create an API key at https://developers.exlibrisgroup.com/manage/keys/. The key should have read/write access on the Users area.

## Target

The other variable you'll supply at the top of the script is $target. This is the string contained in the notes you want to remove. It can be a partial string. For example, $target = "Sierra P Type:" will remove notes containing "Sierra P Type: 3", "Sierra P Type: 4", etc.

## Timeout

Because this script runs on PHP on a webserver, you'll be limited by the timeouts of both PHP and the webserver. PHP's default timeout, for example, is 30 seconds -- which will process only a few records before timing out. You'll want to increase your timeout limits accordingly.

