<?php
/**
 * Plugin Name: WP CRUD API'S
 * Description: Custom plugin for CRUD operation related to student table
 * Version: 1.0
 * Author: Hardik Parmar
 */

if ( ! defined('WPINC') ) {
    die;
}

/* ================================
   CREATE TABLE ON ACTIVATION
================================ */

register_activation_hook(__FILE__, 'wpcp_create_students_table');

function wpcp_create_students_table() {

    global $wpdb;

    $table_name = $wpdb->prefix . "students";
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(80) NOT NULL,
        phone_no VARCHAR(30) DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

/* ================================
   DROP TABLE ON DEACTIVATE
================================ */

register_deactivation_hook(__FILE__, 'wpcp_drop_students_table');

function wpcp_drop_students_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . "students";
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

/* ================================
   REST API
================================ */

add_action('rest_api_init', function () {

    // GET students API - GET (name,email,phone_no) -> id - URL
    register_rest_route('students/v1', '/student', [
        'methods'  => 'GET',
        'callback' => 'wp_handle_student_list',
    ]);

    // CREATE student - POST (name,email,phone_no) 
    register_rest_route('students/v1', '/student', [
        'methods'  => 'POST',
        'callback' => 'wp_handle_create_student',
        'args' => [
            'name' => [
                'required' => true,
                'type' => 'string',
            ],
            'email' => [
                'required' => true,
                'type' => 'string',
            ],
            'phone_no' => [
                'required' => false,
                'type' => 'string',
            ],
        ],
    ]);

    //Update student API - PUT (name,email,phone_no) -> id - URL
    register_rest_route('students/v1', '/student/update', [
        'methods'  => 'PUT',
        'callback' => 'wp_handle_update_student',
    ]);

    //Delete student API - Delete (name,email,phone_no) -> id - URL
     register_rest_route('students/v1', '/student/delete', [
        'methods'  => 'DELETE',
        'callback' => 'wp_handle_delete_student',
    ]);
});



//List Data
function wp_handle_student_list() {
    global $wpdb;
    $table = $wpdb->prefix . "students";

    $students = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);

    return rest_ensure_response([
        'status' => true,
        'data' => $students
    ]);
}


//Create Data

function wp_handle_create_student($request) {

    global $wpdb;
    $tableName = $wpdb->prefix . "students";

    $name  = $request->get_param("name");
    $email = $request->get_param("email");
    $phone = $request->get_param("phone_no");

    $wpdb->insert($tableName, [
        "name"     => $name,
        "email"    => $email,
        "phone_no" => $phone
    ]);

    return rest_ensure_response([
        "status"  => true,
        "message" => "Student saved successfully"
    ]);
}

//Update Data

function wp_handle_update_student($request) {

    global $wpdb;
    $tableName = $wpdb->prefix . "students";

    $student_id = $request->get_param("id");

    if (empty($student_id)) {
        return rest_ensure_response([
            "status" => false,
            "message" => "Student ID is required"
        ]);
    }

    // Get existing student
    $studentData = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $tableName WHERE id = %d",
            $student_id
        ),
        ARRAY_A
    );

    if (empty($studentData)) {
        return rest_ensure_response([
            "status" => false,
            "message" => "Student not found"
        ]);
    }

    // Update data
    $wpdb->update(
        $tableName,
        [
            "name"     => $request->get_param("name") ?? $studentData['name'],
            "email"    => $request->get_param("email") ?? $studentData['email'],
            "phone_no" => $request->get_param("phone_no") ?? $studentData['phone_no'],
        ],
        [
            "id" => $student_id
        ]
    );

    return rest_ensure_response([
        "status" => true,
        "message" => "Student updated successfully"
    ]);
}

// Delete Student

function wp_handle_delete_student($request){

    global $wpdb;
    $tableName = $wpdb->prefix . "students";

    $student_id = $request->get_param("id");

    // FIX 1: Fetch student by ID
    $studentData = $wpdb->get_row(
        "SELECT * FROM {$tableName} WHERE id = {$student_id}",
        ARRAY_A
    );

    // FIX 2: Correct if condition
    if (!empty($studentData)) {

        $wpdb->delete($tableName, [
            "id" => $student_id
        ]);

        return rest_ensure_response([
            "status" => true,
            "message" => "Data Deleted"
        ]); // ✅ semicolon added

    } else {

        return rest_ensure_response([
            "status" => false,
            "message" => "Failed to Delete"
        ]);
    }
}
