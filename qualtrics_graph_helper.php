<?php

##################################################
# Qualtrics Graph Helper
# Jacob Levernier (adunumdatum.org)
# April 2014
# Distributed under the MIT License
##################################################

function load_highcharts_code() { // This just prints out the code for HighCharts (the idea here is to make it easier for non-coders to use).
    echo '
        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
        <script src="http://code.highcharts.com/highcharts.js"></script>
        ';
    return TRUE;
}

class QualtricsDataProcessor
{

    public $qualtrics_options = array(
      "Request" => "getLegacyResponseData",
      "User" => "", // Your Qualtrics username (see Account Settings -> Qualtrics IDs -> Recent Logins).
      "API_SELECT" => "ControlPanel",
      "Version" => "2.3",
      "Token" => "", // You can get your Qualtrics Token through Account Settings -> Qualtrics IDs -> Generate Token.
      "Format" => "JSON",
      "SurveyID" => "" // Your Survey ID (see Account Settings -> Qualtrics IDs -> Surveys)
    );


    public $response_id_of_current_user;
    function __construct() // This is here because of the advice of http://stackoverflow.com/a/16761755/1940466 (cf. http://stackoverflow.com/a/455929/1940466), which notes that within a PHP class, dynamic variables must be defined within functions (this solves an error that I received otherwise, either using `$response_id_of_current_user = $_GET['id'];` or `var $response_id_of_current_user = $_GET['id'];`)
        {
            $this->response_id_of_current_user = $_GET['id']; // This is supplied from Qualtrics through a $_GET variable. To get Qualtrics to provide it, edit the survey, then edit Survey Flow. Add an "End of Survey" block, click "Customize," and choose "Redirect to a URL". The URL should be, e.g., (what's important is at the end) http://www.example.com/scratchpad.php?id=${e://Field/ResponseID}
        }


    function get_qualtrics_data($qualtrics_parameters=NULL) // See the beginning of the function for the reason for the NULL default here.
     {
        // Following the advice of http://stackoverflow.com/a/5859412/1940466, if the user hasn't explicitly passed any parameters, we'll assign a default (we do it this way here because default options can't be variables):
        if(is_null($qualtrics_parameters))
            {
                $qualtrics_parameters = $this->qualtrics_options;
            };
    
        // NOTE: curl must be *enabled* in PHP before it can be used. Check that it is with:
        //phpinfo();

        $base_api_url = "https://survey.qualtrics.com/WRAPI/ControlPanel/api.php"; // This should NOT have a question mark or any GET parameters at the end of it. Those will be added below.

        // The array elements below should be "key"=>"value" where "key" is a GET request parameter for the Qualtrics API (e.g., "User" or "Token"):
        if(!isset($qualtrics_parameters) || !is_array($qualtrics_parameters)) // If the user didn't give URL parameters to use.
            {
                echo nl2br('
                <b>Error:</b> When using the get_qualtrics_data() function, you must insert an array of options in the parentheses. Here\'s an example:

                    $parameters = array(
                      "Request" => "getLegacyResponseData",
                      "User" => "jleverni%23oregon", // Enter your UO username in place of \'jleverni\'
                      "API_SELECT" => "ControlPanel",
                      "Version" => "2.3",
                      "Token" => "M8yw5LoGf0X1oQL5RxmWi6tjU80TAScNG4PaeNVl", // You can get your Qualtrics Token through Account Settings -> Qualtrics IDs -> Generate Token.
                      "Format" => "JSON",
                      "SurveyID" => "SV_eaK7DqH8d7DvDYV"
                    );
                    
                $data = get_qualtrics_data($parameters);

                ');
                    
                    return FALSE; // Exit the function.
            }; // End if statement

        // If nothing has caused us to exit so far, we'll keep going:
        
        $combined_url_GET_parameters = ""; // Just giving this an initial value here.
        // Combine the URL GET parameters to be suitable for a URL:
        // Following the advice at http://stackoverflow.com/a/13789336, set a marker to tell us later whether we're seeing the first element of the array:
            $first = true;

        foreach($qualtrics_parameters as $key => $value) {
            if($first) { // If we're looking at the first GET parameter (i.e., the first element of the array), add a "?" to the beginning (this is how GET requests work). If not, we'll add "&" to the beginning.
                $combined_url_GET_parameters = $combined_url_GET_parameters . "?";
                $first = false;
            }
            else {
                $combined_url_GET_parameters = $combined_url_GET_parameters . "&";
            }

            $combined_url_GET_parameters = $combined_url_GET_parameters . "$key=" . urlencode($value);
        };

        $full_qualtrics_url_including_get_parameters = $base_api_url . $combined_url_GET_parameters;

        // echo "Full url is $full_qualtrics_url_including_get_parameters <br />"; // Good for debugging purposes.

        $curl_request = curl_init();
        curl_setopt($curl_request, CURLOPT_URL, $full_qualtrics_url_including_get_parameters);
        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1); // This gets curl to return (instead of print out) the response.

        // Execute the curl request:
        $curl_response = curl_exec($curl_request);
        curl_close($curl_request);

        // var_dump($curl_response); // Good for diagnostic purposes.

        // Parse the curl_response object into an associative array (this assumes that the curl object "Format" API parameter above is set to "JSON":
        $parsed_curl_response = json_decode($curl_response, $assoc = true);

        // Return the data:
        return $parsed_curl_response;

    } // End function definition


    // From http://www.php.net/manual/en/function.array-column.php -- array_column() requires PHP 5.5 or higher. Otherwise, one can define a homemade function like this:
        // From http://stackoverflow.com/a/19188062
    function array_column($array, $column){
        $a2 = array();
        array_map(function ($a1) use ($column, &$a2){
            array_push($a2, $a1[$column]);
        }, $array);
        return $a2;

    // SOME EXAMPLES:
    // If we wanted to get the average for column $foo, we could use this:
        // $full_foo_column = array_column($parsed_curl_response, 'foo');
        // $foo_average = array_sum($full_foo_column) / count($full_foo_column);

    // To exclude blank responses, we can follow the advice at http://www.php.net/manual/en/function.array-filter.php#111091 and use array_filter( $array, 'strlen' );

        // An example of the above:
            //    $full_SDI_46_1_column_excluding_blank_responses = array_filter(array_column($parsed_curl_response, 'SDI_46_1'), 'strlen');
            //    $SDI_46_1_average = array_sum($full_SDI_46_1_column_excluding_blank_responses) / count($full_SDI_46_1_column_excluding_blank_responses);
            //    echo "The average of the columns is $SDI_46_1_average";

    } // End function definition


    function score_data_subscales($data_from_qualtrics, $array_of_subscale_equations) {

        if(!isset($data_from_qualtrics)) // If the user hasn't supplied any data...
            {
                echo nl2br('
                    <br>Error:</b> When you use the score_data_subscales() function, you must include the data from Qualtrics as the first parameter. Here\'s an example:
                    
                    scored_subscales = score_data_subscales($data_from_qualtrics);
                    
                ');
                
                return FALSE;
            };


        if(!isset($array_of_subscale_equations) || !is_array($array_of_subscale_equations)) // If the user hasn't defined what the subscales are...
            {
                echo nl2br('
                    <br>Error:</b> When you use the score_data_subscales() function, you must insert an array of options in the parentheses. Here\'s an example (put the Qualtrics variable names you want to use in curly brackets):

                    $subscale_equations = array(
                    
                        "Alpha" => "{SDI_46_9} + {SDI_46_16} + {SDI_46_22} + {SDI_46_32} - {SDI_46_3} - {SDI_46_18} - {SDI_46_25} - {SDI_46_37}",

                        "Beta" => "{SDI_46_6} + {SDI_46_10} + {SDI_46_23} + {SDI_46_41} + {SDI_46_43} + {SDI_46_45} - {SDI_46_14} - {SDI_46_31} - {SDI_46_33} - {SDI_46_39}",

                        "Gamma" => "{SDI_46_5} + {SDI_46_11} + {SDI_46_13} + {SDI_46_17} + {SDI_46_21} + {SDI_46_24} + {SDI_46_26} - {SDI_46_28} - {SDI_46_30} - {SDI_46_34}",

                        "Delta" => "{SDI_46_2} + {SDI_46_19} + {SDI_46_29} + {SDI_46_38} - {SDI_46_7} - {SDI_46_15} - {SDI_46_35} - {SDI_46_42}",

                        "Epsilon" => "{SDI_46_4} + {SDI_46_8} + {SDI_46_12} + {SDI_46_20} + {SDI_46_27} + {SDI_46_40} - {SDI_46_1} - {SDI_46_36} - {SDI_46_44} - {SDI_46_46}"
                    );
                    
                    scored_subscales = score_data_subscales($data_from_qualtrics, $subscale_equations);
                
                ');
                
                return FALSE;
            }
            else // If we DO have definitions for the subscales, add an additional key-value pair:
                {
                    if(!empty($array_of_subscale_equations["ResponseID"]))
                        {
                            echo "<b>Error:</b> Please do not use 'ResponseID' as a key in your array. The script uses that name automatically.<br/>";
                            
                            exit(); // This is potentially a catastrophic error, so we'll exit the whole script.
                        };

            }; // End if statement.

        $array_of_scored_questionnaire_data = array(); // Create a blank array (we'll populate it in a moment).

        foreach ($data_from_qualtrics as $key => $row) {
            // To get the SDI_46_1 column for this row, we would use $row["SDI_46_1"]

            $filled_in_equation_for_this_row = array(); // Wipe this variable so that it's a blank slate below.

            foreach($array_of_subscale_equations as $equation_key => $equation_row) {

                $filled_in_equation_for_this_row["$equation_key"] = preg_replace_callback(
                    '|{(.*?)}|',
                    function($variable_name) use($key, $row, $equation_key, $equation_row) {
                        $variable_name_of_interest = $variable_name[1];

                        if($row["$variable_name_of_interest"] != "") // If this is not blank
                            {
                                $value_of_variable_of_interest = $row["$variable_name_of_interest"];
                            }
                        else // If this IS blank, set it to 0
                            {
                                $value_of_variable_of_interest = 0;
                            };
                        
                        return $value_of_variable_of_interest;
                    },
                    $equation_row);
                
                    $scored_data_for_this_row["$equation_key"] = eval("return " . $filled_in_equation_for_this_row["$equation_key"] . ";"); // NOTE WELL: This uses eval(), which can be very dangerous if it ever touches user input (since the user could give a malicious string to be evaluated). However, in this case, the user *already is expected to have access to the PHP code,* so we're not giving them any more power than they already have. **But DO NOT** make this class accessible through a form or something like that, since eval() would then be very dangerous to use.

                };

            $scored_data_for_this_row["ResponseID"] = $key; // This makes sense with the preg_replace_callback() below. NOTE WELL that the way that Qualtrics\' API seems to work is to return an array NAMED BY ResponseID, with all of the data within each ResponseID-named row. That\'s why this works.

            // Add the scored subscales to the full array of all participants' scored subscales:
            array_push($array_of_scored_questionnaire_data, $scored_data_for_this_row);

            };

        // If nothing else has stopped us at this point, we'll return the scored subscales:

        return $array_of_scored_questionnaire_data;

    } // End function definition.





    // AN IDEA FOR ABSTRACTING THIS:
    // I could make it so that the array above is all that needs to be set by the user (using an include() statement).
    // Then automatically calculate average subscale results using a foreach() loop over that array.
    // So the user would just need to set that array, and, say, the graph model below (again, I can use an include() statement there) (since the user will probably want to customize it).
    // I could, though, also do a loop within the model creation script. Or just have example text for model creation.





    function get_average_of_column($data, $column_name) {

        if(!isset($data)) // If the user hasn't supplied any data...
            {
                echo nl2br('
                    <br>Error:</b> When you use the get_average_of_column() function, you must include some data (either the raw data, or the scored data) from Qualtrics as the first parameter. Here\'s an example:
                    
                    scored_subscales = score_data_subscalesget_sum_of_column($scored_data_from_qualtrics, "Column_name_from_Qualtrics");
                    
                ');
                
                return FALSE;
            };
        
        
            if(!isset($column_name)) // If the user hasn't supplied any data...
            {
                echo nl2br('
                    <br>Error:</b> When you use the get_average_of_column() function, you must include the column name from Qualtrics as the second parameter. Here\'s an example:
                    
                    scored_subscales = score_data_subscales($data_from_qualtrics, "Column_name_in_quotes");
                    
                ');
                
                return FALSE;
            };
            
        $computed_average = array_sum($this->array_column($data, "$column_name")) / count($this->array_column($data, "$column_name"));
        
        return $computed_average;

    } // End function definition


    function get_sum_of_column($data, $column_name) {

        if(!isset($data_from_qualtrics)) // If the user hasn't supplied any data...
            {
                echo nl2br('
                    <br>Error:</b> When you use the get_sum_of_column() function, you must include some data (either the raw data, or the scored data) from Qualtrics as the first parameter. Here\'s an example:
                    
                    scored_subscales = get_sum_of_column($scored_data_from_qualtrics, "Column_name_from_Qualtrics");
                    
                ');
                
                return FALSE;
            };
        
        
            if(!isset($column_name)) // If the user hasn't supplied any data...
            {
                echo nl2br('
                    <br>Error:</b> When you use the get_sum_of_column() function, you must include the column name from Qualtrics as the second parameter. Here\'s an example:
                    
                    scored_subscales = score_data_subscales($data_from_qualtrics, "Column_name_in_quotes");
                    
                ');
                
                return FALSE;
            };
            
        $computed_sum = array_sum($this->array_column($data, "$column_name"));
        
        return $computed_sum;

    } // End function definition

    // Get the current user's data:
    // Per http://stackoverflow.com/a/12377045, there doesn't seem to be a built-in PHP function for searching an associative array, so we'll build one:
    function get_specific_row($array, $field_name_to_match, $search_term) {
        foreach ($array as $row)
            {
                if ($row[$field_name_to_match] == $search_term) {
                    return $row;
                };
            };
    }

} // End class definition

?>
