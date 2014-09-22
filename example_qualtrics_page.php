<!DOCTYPE html>
<html>
<head>


</head>

<body>

    <?php // This just says that this is the start of a block of PHP code.

        require "qualtrics_graph_helper.php"; // This loads the code from the file.
        
        load_highcharts_code(); // This takes the file loaded above and prints out a bit of code necessary to make the charts work later on.


/////////////////////////////////////////////////////////////
// A few notes, before we get started:
    // The language we're using here is called PHP.
    // Variables in PHP have a dollar sign in front of them. For example, $example is a variable.
    // Every line of code has to have a semicolon (;) at the end of it, to show that that's the end of the line.
    // We're going to use the code that we loaded above and use it to create a variable. We can set the data within that variable with an arrow. For example, $variable_name->option_name = "whatever"
    // Your Qualtrics survey needs to be set up to redirect the user to this page upon survey completion, and for that redirect to also tell this page what the user's Qualtrics Response ID is. To do this, go in to edit the survey within Qualtrics, and click "Survey Flow." Add an "End of Survey" element at the end of the survey flow. In that "End of Survey" element, click "Customize," and choose "Redirect to a URL". The URL should be, e.g., (what's important is at the end) http://www.example.com/example_qualtrics_page.php?id=${e://Field/ResponseID}, where "http://www.example.com/example_qualtrics_page.php" is replaced with the address of this page.
// Ok, we're ready to start!
/////////////////////////////////////////////////////////////


        // First, we'll set up a new container for the options for our Qualtrics data (this draws on the code loaded above, which spells out what "QualtricsDataProcessor" is):
        $example = new QualtricsDataProcessor;

        // You should now edit a few Qualtrics-related options from our new holder variable:
        $example->qualtrics_options["User"] = 'your_Qualtrics_username_goes_here'; // This is your official Qualtrics username. You can find it by logging into Qualtrics, then clicking on your username, and then "Account Settings." You'll see your username under "Recent Logins"
            // NOTE: Once you start using this code in your web page, you may see extra records under "Recent Logins." If you see that happening, it's just from your web page going to get the data from Qualtrics.

        $example->qualtrics_options["Token"] = "your_token_goes_here"; // This is like a password for being able to go and get data from Qualtrics for surveys that you created with your Qualtrics account. Qualtrics generates it randomly. You can get your Qualtrics Token by going in Qualtrics through Account Settings -> Qualtrics IDs -> Generate Token. NOTE that if you code ever gets released accidentally, you can go get a new token through that Qualtrics page.

        $example->qualtrics_options["SurveyID"] = "your_survey_id_goes_here"; // You can get the ID number of your survey by going in Qualtrics through Account Settings -> Qualtrics IDs -> Generate Token.

        // Additional Note: You can edit whatever other Qualtrics options from https://survey.qualtrics.com/WRAPI/ControlPanel/docs.php#getLegacyResponseData_2.3 that you like, using the format above.


        // Now we'll go and actually get the data from Qualtrics:
        $data_from_qualtrics = $example->get_qualtrics_data(); // It's as easy as that!

        // Now that we have the data, let's define some subscales. You can indicate question names from the Quatrics survey by enclosing the names in curly braces (for example, "{Question1} + {Question5}"). You can define multiple subscales using brackets, like this: $example->subscale_equations["subscale_name"] = "{Question1} + {Question5}";

        $example->subscale_equations["Alpha"] = "{SDI_46_9} + {SDI_46_16} + {SDI_46_22} + {SDI_46_32} - {SDI_46_3} - {SDI_46_18} - {SDI_46_25} - {SDI_46_37}";

        $example->subscale_equations["Beta"] = "{SDI_46_6} + {SDI_46_10} + {SDI_46_23} + {SDI_46_41} + {SDI_46_43} + {SDI_46_45} - {SDI_46_14} - {SDI_46_31} - {SDI_46_33} - {SDI_46_39}";

        $example->subscale_equations["Gamma"] = "{SDI_46_5} + {SDI_46_11} + {SDI_46_13} + {SDI_46_17} + {SDI_46_21} + {SDI_46_24} + {SDI_46_26} - {SDI_46_28} - {SDI_46_30} - {SDI_46_34}";

        $example->subscale_equations["Delta"] = "{SDI_46_2} + {SDI_46_19} + {SDI_46_29} + {SDI_46_38} - {SDI_46_7} - {SDI_46_15} - {SDI_46_35} - {SDI_46_42}";

        $example->subscale_equations["Epsilon"] = "{SDI_46_4} + {SDI_46_8} + {SDI_46_12} + {SDI_46_20} + {SDI_46_27} + {SDI_46_40} - {SDI_46_1} - {SDI_46_36} - {SDI_46_44} - {SDI_46_46}";


        // Now we can have Qualtrics actually score everything. This step just processes the subscale equations that we defined above:

        $scored_subscales = $example->score_data_subscales($data_from_qualtrics, $example->subscale_equations);


        // Having scored the data, we can now get the data of the current user, if we want (the column name that we want to look for is called "ResponseID," which is what Qualtrics calls the ID number of each participant. The ID number of the current user (sent over from the Qualtrics survey) is automatically saved in $example->response_id_of_current_user):

        $scored_data_of_specific_current_user = $example->get_specific_row($scored_subscales,"ResponseID", $example->response_id_of_current_user);


        // We can break the data down into subscales:

        $Alpha_data_of_current_user = $scored_data_of_specific_current_user['Alpha'];
        $Beta_data_of_current_user = $scored_data_of_specific_current_user['Beta'];
        $Gamma_data_of_current_user = $scored_data_of_specific_current_user['Gamma'];
        $Delta_data_of_current_user = $scored_data_of_specific_current_user['Delta'];
        $Epsilon_data_of_current_user = $scored_data_of_specific_current_user['Epsilon'];


        // We can also get the average across all users for a column (if you like, you can also use get_sum_of_column... instead of get_average_of_column...):

        $average_Alpha_subscale = $example->get_average_of_column($scored_subscales, "Alpha");
        $average_Beta_subscale = $example->get_average_of_column($scored_subscales, "Beta");
        $average_Gamma_subscale = $example->get_average_of_column($scored_subscales, "Gamma");
        $average_Delta_subscale = $example->get_average_of_column($scored_subscales, "Delta");
        $average_Epsilon_subscale = $example->get_average_of_column($scored_subscales, "Epsilon");

/* 
Now we're ready to make the actual graph! To do that, we'll be using HighCharts (http://www.highcharts.com/demo/). HighCharts is easy to use, and there are even tools to automatically create most of the code that we'll need.

To create HighCharts code, I recommend doing three things:
    1) Go to http://www.highcharts.com/demo/, look up the type of graph that you want to create (pie chart, bar graph, etc.), and click "View Options." Copy the code that pops up.
    2) Paste that example code below, between the quotation marks for the $code_for_our_graph variable. 
    3) Go through the code that you pasted below and just change the options in it. For the data, you can use the variables that we defined above!
       This might seem daunting, but HighCharts is actually really easy to understand just by looking at it.
*/

$code_for_our_graph = "
        $(function () {
                $('#graph_from_qualtrics_data').highcharts({
                    chart: {
                        type: 'bar'
                    },
                    title: {
                        text: 'Your Score vs. the Average Score'
                    },
                    subtitle: {
                        text: 'for the SDI-46'
                    },
                    xAxis: {
                        categories: ['Tradition-oriented Religiousness (Alpha)', 'Unmitigated Self-Interest (Beta)', 'Communal Rationalism (Gamma)', 'Subjective Spirituality (Delta)', 'Egalitarianism (Epsilon)'],
                        title: {
                            text: null
                        }
                    },
                    yAxis: {
                        min: -10,
                        max: 15,
                        title: {
                            text: 'SDI-46 Subscale Score',
                            align: 'middle'
                        },
                        labels: {
                            overflow: 'justify'
                        }
                    },
                    tooltip: {
                        valueSuffix: ' SDI-46 scale points'
                    },
                    plotOptions: {
                        bar: {
                            dataLabels: {
                                enabled: false
                            }
                        }
                    },
                    legend: {
                        layout: 'vertical',
                        align: 'right',
                        verticalAlign: 'top',
                        x: -40,
                        y: 100,
                        floating: true,
                        borderWidth: 1,
                        backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor || '#FFFFFF'),
                        shadow: true
                    },
                    credits: {
                        enabled: false
                    },
                    series: [{
                        name: 'Average Score',
                        data: [$average_Alpha_subscale, $average_Beta_subscale, $average_Gamma_subscale, $average_Delta_subscale, $average_Epsilon_subscale]
                    }, {
                        name: 'Your Score',
                        data: [$Alpha_data_of_current_user, $Beta_data_of_current_user, $Gamma_data_of_current_user, $Delta_data_of_current_user, $Epsilon_data_of_current_user]
                    }]
                });
            });
";



// Tell PHP to print out the text below (the user's web browser will see this code and turn it into a graph):
print "<script>$code_for_our_graph</script>";


// NOTE: Now, with all of this code written, just put the following code wherever in your webpage you want your graph to show up (for this example, I've just put it below -- this will produce a page that is blank aside from the graph):
//    <div id="graph_from_qualtrics_data" style="min-width: 310px; max-width: 800px; height: 400px; margin: 0 auto"></div>

// Close the PHP part of the code:
    ?>

    <div id="graph_from_qualtrics_data" style="min-width: 310px; max-width: 800px; height: 400px; margin: 0 auto"></div>



</body>
</html>
