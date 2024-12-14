<?php if (isset($_SESSION['message'])): ?>
    <div class="bg-[#e3f1e4] border border-[#34c759] text-[#1d1d1f] px-6 py-4 rounded-xl mb-6 shadow-sm">
        <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="bg-[#fee2e2] border border-[#ff3b30] text-[#1d1d1f] px-6 py-4 rounded-xl mb-6 shadow-sm">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>