        </section>
    </div>
</div>

<div class="footer-main">
    <?php foreach ($languages as $language): ?>
        <a href="<?= $menuHelper->getCurrentUrl([], ['language' => strtolower($language)]); ?>"
           class="language-item <?= ($selectedLanguage === $language) ? 'is--active' : ''; ?>">
            <?= strtoupper($language); ?>
        </a>
    <?php endforeach; ?>
</div>
<script type="text/javascript" src="<?= $baseUrl; ?>../common/assets/javascript/legacy-browser.js"></script>
<script type="text/javascript" src="<?= $baseUrl; ?>../common/assets/javascript/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="<?= $baseUrl; ?>assets/javascript/jquery.installer.js"></script>
</body>
</html>
