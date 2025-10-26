<!-- Footer -->
   <footer>
       <div class="container">
           <div class="footer-content">
               <div>
                   <div class="footer-logo">
                       <h2><?php bloginfo('name'); ?></h2>
                   </div>
                   <p class="footer-description"><?php bloginfo('description'); ?></p>
                   <div class="social-links">
                       <a href="#"><span>f</span></a>
                       <a href="#"><span>t</span></a>
                       <a href="#"><span>in</span></a>
                       <a href="#"><span>ig</span></a>
                   </div>
               </div>
               
               <div>
                   <h3 class="footer-title">Quick Links</h3>
                   <?php
                   wp_nav_menu(array(
                       'theme_location' => 'footer-menu',
                       'container' => false,
                       'menu_class' => 'footer-links'
                   ));
                   ?>
               </div>
               
               <div>
                   <h3 class="footer-title">Categories</h3>
                   <ul class="footer-links">
                       <?php
                       $categories = get_terms(array(
                           'taxonomy' => 'program_category',
                           'hide_empty' => true
                       ));
                       
                       foreach($categories as $category) {
                           echo '<li><a href="' . get_term_link($category) . '">' . $category->name . '</a></li>';
                       }
                       ?>
                   </ul>
               </div>
               
               <div>
                   <h3 class="footer-title">Contact Us</h3>
                   <ul class="footer-links">
                       <li>Email: info@kidssmart.com</li>
                       <li>Phone: (03) 1234 5678</li>
                       <li>Address: Melbourne, VIC 3000</li>
                   </ul>
               </div>
           </div>
           
           <div class="footer-bottom">
               <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All Rights Reserved.</p>
           </div>
       </div>
   </footer>

   <!-- Login Modal -->
   <div id="loginModal" class="modal">
       <!-- Modal content as in HTML template -->
   </div>

   <!-- Register Modal -->
   <div id="registerModal" class="modal">
       <!-- Modal content as in HTML template -->
   </div>

   <?php wp_footer(); ?>
   </body>
   </html>