<?php
require 'vendor/autoload.php';
use Google\Cloud\Vision\V1\ImageAnnotatorClient;

# Testing Purposes Only
# $front_image = 'testImages/front2.jpeg';
# $back_image = 'testImages/back2.jpeg';

# The front image of a UBC card is taken in as input
# The output is an array of their id, first name, and last name
function front_detect($front_image)
{
    # Create image annotator 
    $imageAnnotator = new ImageAnnotatorClient();

    $time_pre = microtime(true);

    # Annotate the image
    $image = file_get_contents($front_image);
    $response = $imageAnnotator->textDetection($image);
    $texts = $response->getTextAnnotations();

    $time_post = microtime(true);
    $exec_time = $time_post - $time_pre;
    print($exec_time . "\n");

    $returnArray = array("id", "firstName", "lastName");

    foreach($texts as $index=>$text) {
        # The text after 'Undergraduate' should be the user's first name
        if(strcmp($text->getDescription(), "Undergraduate") == 0) {
            $returnArray[1] = $texts[$index+1]->getDescription();
        }

        if(ctype_digit($text->getDescription())) {
            # If the text is an all digit number, it should be the user's student id
            $returnArray[0] = $text->getDescription();

            # If the the index two away is not the user's first, name it must be part of their second name
            # This is to handle 2 string last names
            if(strcmp($texts[$index-2]->getDescription(), $returnArray[1]) != 0) {
                $returnArray[2] = $texts[$index-2]->getDescription() . ' ' . $texts[$index-1]->getDescription();
            }
            # Else, the user must have a 1 string last name
            else {
                $returnArray[2] = $texts[$index-1]->getDescription();
            }
        }
    }

    $imageAnnotator->close();
    return $returnArray;
}

# The front image of a UBC card is taken in as input
# The output is an array of their student id, and the expiry date
function back_detect($back_image)
{
    # Create image annotator 
    $imageAnnotator = new ImageAnnotatorClient();

    # Annotate the image
    $image = file_get_contents($back_image);
    $response = $imageAnnotator->textDetection($image);
    $texts = $response->getTextAnnotations();

    $returnArray = array("id", "validDate");

    foreach($texts as $index=>$text) {
        # Search for No.: in the texts array. If there are more values past No.:, that should be the student number
        # The next value must be the student number
        if(strstr($text->getDescription(), "No.:") == TRUE) {
            if(strlen($text->getDescription()) > 4) {
                # -8 to grab last 8 chars of the str, which is the student number
                $id = substr($text->getDescription(), -8);
                $returnArray[0] = $id;
            }
            else {
                $returnArray[0] = $texts[$index+1]->getDescription();
            }
        }

        # Search the To: in the texts array. The next value must be the expiry date of the student id.
        if(strcmp($text->getDescription(), "To:") == 0) {
            $returnArray[1] = $texts[$index+1]->getDescription();
            # echo "Found To:\n";
        }
    }

    # For Testing Purposes Only
#    foreach($texts as $text) {
#        print($text->getDescription() . "\n");
#    }

    $imageAnnotator->close();
    return $returnArray;
}

$front_text = front_detect($front_image);
$back_text = back_detect($back_image);

echo join(', ', $front_text);
echo "\n";
echo join(', ', $back_text);
?>