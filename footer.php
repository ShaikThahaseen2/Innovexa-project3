<?php $base = '/FS project3'; ?>
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="<?php echo $base; ?>/index.php" class="navbar-brand" style="display:inline-flex;margin-bottom:.5rem;">
                    <div class="logo-icon">🎓</div>
                    <span>InnovExa</span>
                </a>
                <p>The premier platform for online learning. Unlock your potential with world-class courses taught by industry experts.</p>
            </div>
            <div>
                <p class="footer-heading">Platform</p>
                <ul class="footer-links">
                    <li><a href="<?php echo $base; ?>/courses.php">Browse Courses</a></li>
                    <li><a href="<?php echo $base; ?>/auth/register.php">Become an Instructor</a></li>
                    <li><a href="#">Pricing</a></li>
                    <li><a href="#">Blog</a></li>
                </ul>
            </div>
            <div>
                <p class="footer-heading">Support</p>
                <ul class="footer-links">
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
            <div>
                <p class="footer-heading">Connect</p>
                <ul class="footer-links">
                    <li><a href="#">🐦 Twitter</a></li>
                    <li><a href="#">💼 LinkedIn</a></li>
                    <li><a href="#">📘 Facebook</a></li>
                    <li><a href="#">📸 Instagram</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© <?php echo date('Y'); ?> InnovExa LMS. All rights reserved.</span>
            <span>Built with ❤️ using PHP & MySQL on XAMPP</span>
        </div>
    </div>
</footer>
<script src="<?php echo $base; ?>/assets/js/main.js"></script>
</body>
</html>
