<?php
/**
* Plugin Name: Certificates Download
* Plugin URI: https://www.neocon2023nagpur.com/
* Description: This plugin facilitates the addition of a download feature for certificate PDF files.Users can be searched by email or phone, and the plugin provides the corresponding file details for the identified user.
* Version: 0.1
* Author: Shubham Yadav
**/
register_activation_hook(__FILE__, 'my_plugin_activate');
// Deactivation hook
register_uninstall_hook(__FILE__, 'my_plugin_uninstall');

function custom_form_script() {
    ?>
   <script
  src="https://code.jquery.com/jquery-3.7.1.js"
  integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
  crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    jQuery(document).ready(function () {
        let inputValues;
        let onChangeCalled = false;
        window.onChangeFunction = function (event) {
            inputValues = event;
        }
        window.funEventCheck = function () {
            callAjaxForRes(inputValues)
        };
    // HTML content to be appended
    if (window.innerWidth <= 830) {
                // Code to execute for mobile devices
                var newContent = '<div id="myForm"><p id="p-certificates" for="inputValue">Enter Email or Phone to Certificate Download:</p><div id="certificate-sec"><input type="text" onchange="onChangeFunction(this.value)" id="inputValue" name="inputValue" required><input type="button" onclick="return funEventCheck()"  id="submitBtn"  value="Submit"></div></div>';
                jQuery('.ct-container [data-column="end"] [data-items="primary"]').prepend(newContent).fadeIn(1000);
            } else {
                // Code to execute for non-mobile devices
                var newContent = '<div id="myForm"><label for="inputValue">Enter Email or Phone to Certificate Download:</label><div id="certificate-sec"><input type="text" onchange="onChangeFunction(this.value)" id="inputValue" name="inputValue" required><input type="button" onclick="return funEventCheck()"  id="submitBtn"  value="Submit"></div></div>';
                jQuery('.ct-container [data-column="end"]').append(newContent).fadeIn(1000);
            } 
});

    function callAjaxForRes(inputValue) {
        event.preventDefault(); // Prevent the default form submission
        // var inputValue = document.getElementById('inputValue').value;
        if(inputValue){
        // Use AJAX to send the data to a PHP function
        var ajax = new XMLHttpRequest();
        ajax.onreadystatechange = function () {
            if (ajax.readyState == 4 && ajax.status == 200) {
                const jsonResponse = JSON.parse(ajax.response);
                if(jsonResponse.status == 200){
                jQuery('#downloader').attr('href', jsonResponse.filename );
                document.getElementById('downloader').click();
                }else{
                    Swal.fire({
                    title: 'Error!',
                    text: 'Entered value does not exist.',
                    icon: 'error',
                    })
                }
            }
        };
        ajax.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
        ajax.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        ajax.send('action=custom_process_input&input_value=' + inputValue);
        } else{
            Swal.fire({
            title: 'Error!',
            text: 'Please fill some value.',
            icon: 'error',
            })
        }
    }
        
    </script>
    <?php
}

add_action('wp_head', 'custom_form_script'); 
function custom_process_input() {
    if (isset($_POST['input_value'])) {
        // Perform security checks
        // ... Check user session, role, or other security measures

        // Get the file path based on the input value (replace this logic with your own)
        $emailphone = $_POST['input_value'];
        
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}certificates_data
            WHERE email = %s OR phonenumber = %s
            LIMIT 1",
            $emailphone,
            $emailphone
        );
        $user_data = $wpdb->get_row($query);

        if ($user_data) {
            //to_do
            $result = home_url().'/wp-content/uploads/2023/12/'.$user_data->id.'userCertificate.pdf';
            // $result = home_url().'/wp-content/uploads/2023/12/delegate-certificate.pdf';
            $response = array('filename' => $result , 'status' => 200);
            // Output JSON response and end script
            header('Content-Type: application/json');
            echo json_encode($response);
            die();
        } else {
            // File not found, handle the error or provide feedback
            $response = array('error' => 'File not found.', 'status' => 500);
            header('Content-Type: application/json');
            echo json_encode($response);
            die();
        }
    } else {
        // Error: Input value not set
        echo 'Input value not set.';
        die();
    }
}


add_action('wp_ajax_custom_process_input', 'custom_process_input');
add_action('wp_ajax_nopriv_custom_process_input', 'custom_process_input');

// Enqueue styles in the front-end
function enqueue_plugin_styles() {
    // Replace 'your-plugin-style' with a unique handle for your stylesheet
    wp_enqueue_style('your-plugin-style', plugins_url('css/style.css', __FILE__), array(), '1.0', 'all');
}
add_action('wp_enqueue_scripts', 'enqueue_plugin_styles');


function custom_form_in_head() {
    ?>
    <a href="#" id="downloader" download="file.pdf">
                 <button>Download File</button>
        </a>
    
    <?php
}

add_action('wp_head', 'custom_form_in_head');


// Activation callback
function my_plugin_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'certificates_data'; // Replace 'your_custom_table' with your desired table name

    // Check if the table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // Table doesn't exist, so create it
        $sql = "CREATE TABLE $table_name (
            id INT PRIMARY KEY AUTO_INCREMENT,
            fullname VARCHAR(255),
            email VARCHAR(255) NULL,
            phonenumber VARCHAR(20) NULL,
            category VARCHAR(50) NOT NULL
        )";
        $plugin_directory = plugin_dir_path(__FILE__);
        // Include the file using the plugins_url function
        $insert_queries = include_once $plugin_directory . 'insert-queries.php';
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        // Execute each insert query
        foreach ($insert_queries as $query) {
            $wpdb->query($query);
        }
    }
}

function my_plugin_uninstall() {
    // Deactivation code, e.g., drop the database table
    global $wpdb;
    $table_name = $wpdb->prefix . 'certificates_data';

    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);
}
