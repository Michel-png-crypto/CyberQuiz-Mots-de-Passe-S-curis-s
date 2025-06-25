<?php
// includes/footer.php
// Assurez-vous que functions.php est inclus pour getBasePath()
// La fonction getBasePath() est appelée ici pour s'assurer que les chemins des scripts sont corrects.
require_once dirname(__DIR__) . '/includes/functions.php'; 
$basePath = getBasePath();
?>
    </main> <footer class="fade-in-on-load">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Plateforme de Quiz. Tous droits réservés.</p>
            <div class="footer-links" style="margin-top: 10px;">
                <a href="<?php echo $basePath; ?>pages/about.php" style="color: #fff; margin: 0 10px; text-decoration: none;">À propos</a>
                <a href="<?php echo $basePath; ?>pages/contact.php" style="color: #fff; margin: 0 10px; text-decoration: none;">Contact</a>
                <a href="<?php echo $basePath; ?>pages/privacy.php" style="color: #fff; margin: 0 10px; text-decoration: none;">Politique de confidentialité</a>
            </div>
            </div>
    </footer>
    <script src="<?php echo $basePath; ?>js/script.js"></script>
</body>
</html>