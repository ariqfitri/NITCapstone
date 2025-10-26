  <?php
    require_once 'config/db_config.php';
    
   // Register Custom Post Types and Taxonomies
   function kidssmart_register_post_types() {
       // Program Post Type
       register_post_type('program', array(
           'labels' => array(
               'name' => 'Programs',
               'singular_name' => 'Program'
           ),
           'public' => true,
           'has_archive' => true,
           'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
           'menu_icon' => 'dashicons-calendar-alt',
           'rewrite' => array('slug' => 'programs')
       ));
       
       // Program Categories Taxonomy
       register_taxonomy('program_category', 'program', array(
           'labels' => array(
               'name' => 'Program Categories',
               'singular_name' => 'Program Category'
           ),
           'hierarchical' => true,
           'rewrite' => array('slug' => 'program-category')
       ));
       
       // Age Groups Taxonomy
       register_taxonomy('age_group', 'program', array(
           'labels' => array(
               'name' => 'Age Groups',
               'singular_name' => 'Age Group'
           ),
           'hierarchical' => true,
           'rewrite' => array('slug' => 'age-group')
       ));
       
       // Locations Taxonomy
       register_taxonomy('location', 'program', array(
           'labels' => array(
               'name' => 'Locations',
               'singular_name' => 'Location'
           ),
           'hierarchical' => true,
           'rewrite' => array('slug' => 'location')
       ));
   }
   add_action('init', 'kidssmart_register_post_types');
   
   // Register Custom Meta Fields for Programs
   function kidssmart_register_meta_fields() {
       register_post_meta('program', 'provider_name', array(
           'type' => 'string',
           'single' => true,
           'show_in_rest' => true
       ));
       
       register_post_meta('program', 'provider_phone', array(
           'type' => 'string',
           'single' => true,
           'show_in_rest' => true
       ));
       
       register_post_meta('program', 'provider_email', array(
           'type' => 'string',
           'single' => true,
           'show_in_rest' => true
       ));
       
       register_post_meta('program', 'provider_website', array(
           'type' => 'string',
           'single' => true,
           'show_in_rest' => true
       ));
       
       register_post_meta('program', 'cost', array(
           'type' => 'number',
           'single' => true,
           'show_in_rest' => true
       ));
       
       register_post_meta('program', 'address', array(
           'type' => 'string',
           'single' => true,
           'show_in_rest' => true
       ));
   }
   add_action('init', 'kidssmart_register_meta_fields');
   
   // Register Navigation Menus
   function kidssmart_register_menus() {
       register_nav_menus(array(
           'primary-menu' => __('Primary Menu', 'kidssmart'),
           'footer-menu' => __('Footer Menu', 'kidssmart')
       ));
   }
   add_action('init', 'kidssmart_register_menus');
   
   // Add Theme Support
   function kidssmart_theme_setup() {
       // Add featured image support
       add_theme_support('post-thumbnails');
       
       // Add title tag support
       add_theme_support('title-tag');
       
       // Add HTML5 support
       add_theme_support('html5', array(
           'search-form',
           'comment-form',
           'comment-list',
           'gallery',
           'caption'
       ));
   }
   add_action('after_setup_theme', 'kidssmart_theme_setup');
   
   // Enqueue Scripts and Styles
   function kidssmart_scripts() {
       // Enqueue your styles and scripts here
       wp_enqueue_style('kidssmart-style', get_stylesheet_uri());
       wp_enqueue_script('kidssmart-script', get_template_directory_uri() . '/js/main.js', array(), '1.0', true);
   }
   add_action('wp_enqueue_scripts', 'kidssmart_scripts');
   
   // Register Widget Areas
   function kidssmart_widgets_init() {
       register_sidebar(array(
           'name'          => 'Sidebar',
           'id'            => 'sidebar-1',
           'description'   => 'Main sidebar area',
           'before_widget' => '<div id="%1$s" class="widget %2$s">',
           'after_widget'  => '</div>',
           'before_title'  => '<h3 class="widget-title">',
           'after_title'   => '</h3>'
       ));
   }
   add_action('widgets_init', 'kidssmart_widgets_init');
   
   // Add Admin Metabox for Program Details
   function kidssmart_add_metaboxes() {
       add_meta_box(
           'kidssmart_program_details',
           'Program Details',
           'kidssmart_program_details_callback',
           'program',
           'normal',
           'high'
       );
   }
   add_action('add_meta_boxes', 'kidssmart_add_metaboxes');
   
   // Callback for Program Details Metabox
   function kidssmart_program_details_callback($post) {
       // Add nonce for security
       wp_nonce_field('kidssmart_save_program_data', 'kidssmart_program_nonce');
       
       // Get existing values
       $provider_name = get_post_meta($post->ID, 'provider_name', true);
       $provider_phone = get_post_meta($post->ID, 'provider_phone', true);
       $provider_email = get_post_meta($post->ID, 'provider_email', true);
       $provider_website = get_post_meta($post->ID, 'provider_website', true);
       $cost = get_post_meta($post->ID, 'cost', true);
       $address = get_post_meta($post->ID, 'address', true);
       
       // Output fields
       ?>
       <div style="margin: 10px 0;">
           <label for="provider_name" style="display: block; font-weight: bold; margin-bottom: 5px;">Provider Name:</label>
           <input type="text" id="provider_name" name="provider_name" value="<?php echo esc_attr($provider_name); ?>" style="width: 100%;">
       </div>
       
       <div style="margin: 10px 0;">
           <label for="provider_phone" style="display: block; font-weight: bold; margin-bottom: 5px;">Provider Phone:</label>
           <input type="text" id="provider_phone" name="provider_phone" value="<?php echo esc_attr($provider_phone); ?>" style="width: 100%;">
       </div>
       
       <div style="margin: 10px 0;">
           <label for="provider_email" style="display: block; font-weight: bold; margin-bottom: 5px;">Provider Email:</label>
           <input type="email" id="provider_email" name="provider_email" value="<?php echo esc_attr($provider_email); ?>" style="width: 100%;">
       </div>
       
       <div style="margin: 10px 0;">
           <label for="provider_website" style="display: block; font-weight: bold; margin-bottom: 5px;">Provider Website:</label>
           <input type="url" id="provider_website" name="provider_website" value="<?php echo esc_attr($provider_website); ?>" style="width: 100%;">
       </div>
       
       <div style="margin: 10px 0;">
           <label for="cost" style="display: block; font-weight: bold; margin-bottom: 5px;">Cost ($):</label>
           <input type="number" id="cost" name="cost" value="<?php echo esc_attr($cost); ?>" style="width: 100%;">
       </div>
       
       <div style="margin: 10px 0;">
           <label for="address" style="display: block; font-weight: bold; margin-bottom: 5px;">Address:</label>
           <input type="text" id="address" name="address" value="<?php echo esc_attr($address); ?>" style="width: 100%;">
       </div>
       <?php
   }
   
   // Save Program Details
   function kidssmart_save_program_data($post_id) {
       // Check if nonce is set
       if (!isset($_POST['kidssmart_program_nonce'])) {
           return;
       }
       
       // Verify nonce
       if (!wp_verify_nonce($_POST['kidssmart_program_nonce'], 'kidssmart_save_program_data')) {
           return;
       }
       
       // Check autosave
       if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
           return;
       }
       
       // Check permissions
       if ('program' === $_POST['post_type'] && !current_user_can('edit_post', $post_id)) {
           return;
       }
       
       // Save data
       if (isset($_POST['provider_name'])) {
           update_post_meta($post_id, 'provider_name', sanitize_text_field($_POST['provider_name']));
       }
       
       if (isset($_POST['provider_phone'])) {
           update_post_meta($post_id, 'provider_phone', sanitize_text_field($_POST['provider_phone']));
       }
       
       if (isset($_POST['provider_email'])) {
           update_post_meta($post_id, 'provider_email', sanitize_email($_POST['provider_email']));
       }
       
       if (isset($_POST['provider_website'])) {
           update_post_meta($post_id, 'provider_website', esc_url_raw($_POST['provider_website']));
       }
       
       if (isset($_POST['cost'])) {
           update_post_meta($post_id, 'cost', floatval($_POST['cost']));
       }
       
       if (isset($_POST['address'])) {
           update_post_meta($post_id, 'address', sanitize_text_field($_POST['address']));
       }
   }
   add_action('save_post', 'kidssmart_save_program_data');
   
   // Create REST API Endpoint for Programs Search
   function kidssmart_register_rest_routes() {
       register_rest_route('kidssmart/v1', '/search', array(
           'methods' => 'GET',
           'callback' => 'kidssmart_search_programs',
           'permission_callback' => '__return_true'
       ));
   }
   add_action('rest_api_init', 'kidssmart_register_rest_routes');
   
   // API Search Function
   function kidssmart_search_programs($request) {
       $location = $request->get_param('location');
       $category = $request->get_param('category');
       $age_group = $request->get_param('age_group');
       
       $args = array(
           'post_type' => 'program',
           'posts_per_page' => 20,
           'tax_query' => array('relation' => 'AND')
       );
       
       if ($location) {
           $args['tax_query'][] = array(
               'taxonomy' => 'location',
               'field' => 'slug',
               'terms' => $location
           );
       }
       
       if ($category) {
           $args['tax_query'][] = array(
               'taxonomy' => 'program_category',
               'field' => 'slug',
               'terms' => $category
           );
       }
       
       if ($age_group) {
           $args['tax_query'][] = array(
               'taxonomy' => 'age_group',
               'field' => 'slug',
               'terms' => $age_group
           );
       }
       
       $query = new WP_Query($args);
       $programs = array();
       
       if ($query->have_posts()) {
           while ($query->have_posts()) {
               $query->the_post();
               
               $program = array(
                   'id' => get_the_ID(),
                   'title' => get_the_title(),
                   'description' => get_the_excerpt(),
                   'thumbnail' => get_the_post_thumbnail_url(),
                   'link' => get_permalink(),
                   'provider_name' => get_post_meta(get_the_ID(), 'provider_name', true),
                   'provider_phone' => get_post_meta(get_the_ID(), 'provider_phone', true),
                   'provider_email' => get_post_meta(get_the_ID(), 'provider_email', true),
                   'provider_website' => get_post_meta(get_the_ID(), 'provider_website', true),
                   'cost' => get_post_meta(get_the_ID(), 'cost', true),
                   'address' => get_post_meta(get_the_ID(), 'address', true),
                   'categories' => wp_get_post_terms(get_the_ID(), 'program_category', array('fields' => 'names')),
                   'location' => wp_get_post_terms(get_the_ID(), 'location', array('fields' => 'names')),
                   'age_group' => wp_get_post_terms(get_the_ID(), 'age_group', array('fields' => 'names'))
               );
               
               $programs[] = $program;
           }
           wp_reset_postdata();
       }
       
       return rest_ensure_response($programs);
   }
   
   // Add Import Function for Scraped Data
   function kidssmart_import_scraped_data() {
       // Only allow admin users
       if (!current_user_can('manage_options')) {
           return;
       }
       
       // Handle form submission
       if (isset($_POST['kidssmart_import_submit']) && isset($_FILES['kidssmart_import_file'])) {
           $file = $_FILES['kidssmart_import_file'];
           
           // Check for errors
           if ($file['error'] > 0) {
               add_settings_error('kidssmart_import', 'import_error', 'Error uploading file.', 'error');
               return;
           }
           
           // Check file type
           $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
           if ($file_ext != 'json') {
               add_settings_error('kidssmart_import', 'import_error', 'Please upload a JSON file.', 'error');
               return;
           }
           
           // Read file content
           $file_content = file_get_contents($file['tmp_name']);
           $data = json_decode($file_content, true);
           
           if (!$data) {
               add_settings_error('kidssmart_import', 'import_error', 'Invalid JSON format.', 'error');
               return;
           }
           
           // Process data
           $imported = 0;
           foreach ($data as $item) {
               if (!isset($item['provider_name'])) {
                   continue;
               }
               
               // Check if program already exists
               $existing_program = get_page_by_title($item['provider_name'], OBJECT, 'program');
               if ($existing_program) {
                   // Update existing program
                   $program_id = $existing_program->ID;
                   wp_update_post(array(
                       'ID' => $program_id,
                       'post_content' => isset($item['description']) ? $item['description'] : '',
                       'post_status' => 'publish'
                   ));
               } else {
                   // Create new program
                   $program_id = wp_insert_post(array(
                       'post_title' => $item['provider_name'],
                       'post_content' => isset($item['description']) ? $item['description'] : '',
                       'post_type' => 'program',
                       'post_status' => 'publish'
                   ));
               }
               
               if ($program_id) {
                   // Save provider details
                   if (isset($item['contact']['phone'])) {
                       update_post_meta($program_id, 'provider_phone', $item['contact']['phone']);
                   }
                   
                   if (isset($item['contact']['email'])) {
                       update_post_meta($program_id, 'provider_email', $item['contact']['email']);
                   }
                   
                   if (isset($item['contact']['website'])) {
                       update_post_meta($program_id, 'provider_website', $item['contact']['website']);
                   }
                   
                   // Save address
                   if (isset($item['addresses']) && is_array($item['addresses']) && !empty($item['addresses'])) {
                       $address = $item['addresses'][0]['full_address'];
                       update_post_meta($program_id, 'address', $address);
                       
                       // Set location taxonomy
                       if (isset($item['addresses'][0]['suburb']) && !empty($item['addresses'][0]['suburb'])) {
                           $suburb = $item['addresses'][0]['suburb'];
                           wp_set_object_terms($program_id, $suburb, 'location', false);
                       }
                   }
                   
                   // Set category
                   if (isset($item['category'])) {
                       wp_set_object_terms($program_id, $item['category'], 'program_category', false);
                   }
                   
                   $imported++;
               }
           }
           
           add_settings_error('kidssmart_import', 'import_success', "Successfully imported {$imported} programs.", 'success');
       }
   }
   add_action('admin_init', 'kidssmart_import_scraped_data');
   
   // Add Import Page to Admin Menu
   function kidssmart_add_admin_menu() {
       add_submenu_page(
           'edit.php?post_type=program',
           'Import Programs',
           'Import Programs',
           'manage_options',
           'kidssmart-import',
           'kidssmart_import_page'
       );
   }
   add_action('admin_menu', 'kidssmart_add_admin_menu');
   
   // Import Page Callback
   function kidssmart_import_page() {
       ?>
       <div class="wrap">
           <h1>Import Programs</h1>
           <?php settings_errors('kidssmart_import'); ?>
           
           <div class="card">
               <h2>Import Programs from Scraped Data</h2>
               <p>Upload a JSON file containing scraped program data. The file should be in the format produced by the KidsSmart scraper.</p>
               
               <form method="post" enctype="multipart/form-data">
                   <table class="form-table">
                       <tr>
                           <th scope="row"><label for="kidssmart_import_file">JSON File</label></th>
                           <td>
                               <input type="file" name="kidssmart_import_file" id="kidssmart_import_file" accept=".json">
                           </td>
                       </tr>
                   </table>
                   
                   <p class="submit">
                       <input type="submit" name="kidssmart_import_submit" class="button button-primary" value="Import Programs">
                   </p>
               </form>
           </div>
       </div>
       <?php
    }

    // Add AJAX handlers for login, registration, and reviews
    // Handle Login
    function kidssmart_login_callback() {
        // Check nonce for security
        check_ajax_referer('ajax-login-nonce', 'security');
        
        $info = array();
        $info['user_login'] = $_POST['email'];
        $info['user_password'] = $_POST['password'];
        $info['remember'] = true;
        
        $user_signon = wp_signon($info, false);
        if (is_wp_error($user_signon)) {
            wp_send_json_error(array('message' => 'Invalid email or password.'));
        } else {
            wp_send_json_success(array('message' => 'Login successful'));
        }
    }
    add_action('wp_ajax_nopriv_kidssmart_login', 'kidssmart_login_callback');

    // Handle Registration
    function kidssmart_register_callback() {
        // Check nonce for security
        check_ajax_referer('ajax-register-nonce', 'security');
        
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $suburb = sanitize_text_field($_POST['suburb']);
        
        // Check if user exists
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Email already in use.'));
            return;
        }
        
        // Create user
        $user_id = wp_create_user($email, $password, $email);
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => 'Registration failed.'));
            return;
        }
        
        // Update user meta
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, 'suburb', $suburb);
        
        // Set user role
        $user = new WP_User($user_id);
        $user->set_role('subscriber');
        
        wp_send_json_success(array('message' => 'Registration successful.'));
    }
    add_action('wp_ajax_nopriv_kidssmart_register', 'kidssmart_register_callback');

    // Handle Review Submission
    function kidssmart_submit_review_callback() {
        // Check nonce
        check_ajax_referer('submit_review_nonce', 'review_nonce');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in to submit a review.'));
            return;
        }
        
        $user_id = get_current_user_id();
        $post_id = intval($_POST['comment_post_ID']);
        $rating = intval($_POST['rating']);
        $comment = wp_kses_post($_POST['comment']);
        
        // Validate rating
        if ($rating < 1 || $rating > 5) {
            wp_send_json_error(array('message' => 'Invalid rating.'));
            return;
        }
        
        // Check comment length
        if (strlen($comment) > 500) {
            wp_send_json_error(array('message' => 'Review text must be less than 500 characters.'));
            return;
        }
        
        // Check if user has already reviewed this program
        $existing_comments = get_comments(array(
            'user_id' => $user_id,
            'post_id' => $post_id,
            'count' => true
        ));
        
        if ($existing_comments > 0) {
            wp_send_json_error(array('message' => 'You have already reviewed this program.'));
            return;
        }
        
        // Add the comment
        $comment_data = array(
            'user_id' => $user_id,
            'comment_post_ID' => $post_id,
            'comment_content' => $comment,
            'comment_type' => 'comment',
            'comment_approved' => 1
        );
        
        $comment_id = wp_insert_comment($comment_data);
        
        if ($comment_id) {
            // Add rating as comment meta
            add_comment_meta($comment_id, 'rating', $rating);
            wp_send_json_success(array('message' => 'Review submitted successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to submit review.'));
        }
    }
    add_action('wp_ajax_kidssmart_submit_review', 'kidssmart_submit_review_callback');

    // Localize script with AJAX URL and other parameters
    function kidssmart_enqueue_scripts() {
        wp_localize_script('kidssmart-script', 'kidssmart_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'programs_url' => get_post_type_archive_link('program'),
            'home_url' => home_url()
        ));
    }
    add_action('wp_enqueue_scripts', 'kidssmart_enqueue_scripts', 20);

   ?>