<!doctype html>
<html lang="<?= getenv('APP_LANG'); ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>404</title>
  <link rel="stylesheet" href="/css/app.css">
  <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
  <link rel="manifest" href="/icons/site.webmanifest">
</head>
<body class="flex flex-col h-screen justify-between bg-slate-50 text-slate-500">
  <header class="bg-inherit z-50">
    <div class="container-centered flex justify-between items-center py-3">
      <a href="<?= getenv('APP_URL') ?>" class="text-2xl text-slate-500"><?= getenv('APP_NAME'); ?></a>
      <a href="<?= getenv('APP_URL') ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-7 text-slate-500 hover:text-slate-600 transition-all duration-300">
          <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
          <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
        </svg>
      </a>
    </div>
  </header>

  <main class="flex-grow flex justify-center items-center mb-5">
    <section>
      <h1 class="text-3xl">404 <span class="inline-block text-3xl">|</span> Page Not Found</h1>
    </section>
  </main>

  <footer class="text-center">
    <div class="container-centered py-4">
    <p class="text-xs text-slate-500  font-semibold text-center">&copy;<?= copyright(getenv('APP_COPY'), getenv('APP_COPY_YEAR')); ?></p>
    </div>
  </footer>
</body>
</html>
