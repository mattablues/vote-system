<!doctype html>
<html lang="{{ getenv('APP_LANG') }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <title>{% yield title %}</title>
  <link rel="stylesheet" href="{{ versioned_file('/css/app.css') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
  <link rel="manifest" href="/icons/site.webmanifest">
</head>
<body id="{% yield pageId %}" class="flex flex-col min-h-screen {% yield pageClass %}">
  <header class="sticky top-0 z-50 w-full bg-white shadow-xs [--header-h:60px]">
    {% yield headerContainer %}
    <div class="container-centered h-15 flex items-center justify-between">
    {% include "layouts/partials/header-inner.ratio.php" %}
    </div>
    {% endyield headerContainer %}
  </header>

  {% yield content %}
  <main class="flex-grow">
    {% include "components/flash.ratio.php" %}
    {% include "components/noscript.ratio.php" %}
    {% yield body %}
  </main>
  {% endyield content %}

  <footer class="text-center [--footer-h:60px]">
    {% yield footerContainer %}
    <div class="container-centered py-4">
    {% include "layouts/partials/footer-inner.ratio.php" %}
    </div>
    {% endyield footerContainer %}
  </footer>

  {% include "components/cookie-consent.ratio.php" %}
  {% yield alpinejs %}
  <script src="{{ versioned_file('/js/app.js') }}"></script>
  {% yield script %}
</body>
</html>