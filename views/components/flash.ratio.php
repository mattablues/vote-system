{% if ($session->has('flash_notification')) : %}
    {% $message = $session->flashMessage(); %}
{% endif; %}
{% if (isset($message) && is_array($message)) : %}
  <div class="animate-fade-in-top">
    <p class="flash flash-{{ $message['type'] }} text-xs">{{ $message['body'] }}</p>
  </div>
{% endif; %}