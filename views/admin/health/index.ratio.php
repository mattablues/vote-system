{% extends "layouts/admin.ratio.php" %}
{% block title %}Health{% endblock %}
{% block pageId %}health{% endblock %}
{% block searchId %}search-users{% endblock %}
{% block body %}
      <section class="py-8">
        <h1 class="text-3xl font-semibold mb-8">Systemstatus</h1>

        <div class="grid gap-4 md:grid-cols-2">
          <div class="p-4 border border-gray-200 rounded-xl">
            <h2 class="text-xl font-semibold mb-2">Runtime</h2>
            <ul class="space-y-1">
              <li><strong>PHP:</strong> {{ $checks['php']|raw }}</li>
              <li><strong>Time:</strong> {{ $checks['time']|raw }}</li>
              <li><strong>Environment:</strong> {{ getenv('APP_ENV') ?: 'production' }}</li>
            </ul>
          </div>

          <div class="p-4 border border-gray-200 rounded-xl">
            <h2 class="text-xl font-semibold mb-2">Kontroller</h2>
            <ul class="space-y-1">
              <li><strong>DB:</strong> {{ (isset($checks['db']) && $checks['db'] === 'ok') ? 'ok' : 'fail' }}</li>
              <li><strong>FS:</strong> {{ (isset($checks['fs']) && $checks['fs'] === 'ok') ? 'ok' : 'fail' }}</li>
            </ul>
          </div>
        </div>

        <div class="mt-6">
          <a href="/api/v1/health" class="text-blue-600 underline">Visa API-health (JSON)</a>
        </div>
      </section>
{% endblock %}