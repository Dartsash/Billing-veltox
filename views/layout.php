<?php
/** @var string $content */
/** @var array<int, array{type:string,message:string}>|null $flash */
/** @var array<string,mixed>|null $user */
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Veltox Billing</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="/veltox.png">

<style>
  .navbar .navbar-brand,
  .navbar .nav-link,
  .navbar .navbar-text {
    color: rgba(234,240,255,.92) !important;
  }
  .navbar .navbar-brand:hover,
  .navbar .nav-link:hover {
    color: #fff !important;
  }
</style>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="/assets/app.css?v=<?= time() ?>">
</head>
<body>
<div class="app-bg"></div>

<?php if (!empty($user)): ?>
    <div class="a-shell">
        <?php include __DIR__ . '/partials/sidebar_full.php'; ?>

        <main class="a-main">
            <div class="a-container">

                <div class="mt-4">
                    <?php require __DIR__ . '/partials/flash.php'; ?>
                    <?= $content ?>
                </div>

                <div class="text-center text-muted small py-4">
                    <span class="opacity-75">Veltоx Hosting • 2026</span>
                </div>
            </div>
        </main>
    </div>

    <?php
      include __DIR__ . '/partials/profile_modal.php';
    ?>
<?php else: ?>
    <div class="container py-4">

        <div class="mt-4">
            <?php require __DIR__ . '/partials/flash.php'; ?>
            <?= $content ?>
        </div>

        <div class="text-center text-muted small py-4">
            <span class="opacity-75">Veltоx Hosting • 2026</span>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
(function(){
  const btn = document.querySelector('[data-toggle-full-sidebar]');
  if(!btn) return;
  btn.addEventListener('click', function(e){
    e.preventDefault();
    document.body.classList.toggle('b-side-open');
  });
})();

</script>
</body>
</html>
