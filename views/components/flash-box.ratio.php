{% if ($session->has('flash_notification')) : %}
    {% $message = $session->flashMessage(); %}
{% endif; %}
{% if (isset($message) && is_array($message)) : %}
  <div class="animate-fade-in-top">
    <p class="flash-box flash-box-{{ $message['type'] }} text-xs">{{ $message['body'] }}</p>
  </div>
{% endif; %}