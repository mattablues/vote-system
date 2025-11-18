    <div class="alert alert-{{ $type }} {{ $class ?? '' }}" style="{{ $style ?? '' }}">
    {% if(isset($header)) : %}
      <div class="alert-header {{ $headerClass ?? '' }}">
        <h3 class="text-2xl">{{ $header }}</h3>
      </div>
    {% endif; %}
      <div class="alert-body {{ $bodyClass ?? '' }}">
        <p>{{ $slot }}</p>
        <button class="px-3 py-2 rounded cursor-pointer {{ $buttonClass ?? '' }}">Test the button</button>
      </div>
    {% if(isset($footer)) : %}
      <div class="alert-footer {{ $footerClass ?? '' }}">
        {{ $footer }}
      </div>
    {% endif; %}
    </div>