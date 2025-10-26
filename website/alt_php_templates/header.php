<!DOCTYPE html>
   <html <?php language_attributes(); ?>>
   <head>
       <meta charset="<?php bloginfo('charset'); ?>">
       <meta name="viewport" content="width=device-width, initial-scale=1.0">
       <?php wp_head(); ?>
   </head>
   <body <?php body_class(); ?>>
       <?php wp_body_open(); ?>
       
       <!-- Header -->
       <header>
           <div class="container">
               <div class="header-content">
                   <div class="logo">
                       <?php if(has_custom_logo()): ?>
                           <?php the_custom_logo(); ?>
                       <?php else: ?>
                           <a href="<?php echo esc_url(home_url('/')); ?>">
                               <h1><?php bloginfo('name'); ?></h1>
                           </a>
                       <?php endif; ?>
                   </div>
                   <nav>
                       <?php
                       wp_nav_menu(array(
                           'theme_location' => 'primary-menu',
                           'container' => false,
                           'menu_class' => 'nav-menu'
                       ));
                       ?>
                       <ul class="auth-menu">
                           <li><a href="#" id="loginBtn">Login</a></li>
                           <li><a href="#" id="registerBtn" class="btn btn-primary">Register</a></li>
                       </ul>
                   </nav>
               </div>
           </div>
       </header>