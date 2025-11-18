{% extends "layouts/email.ratio.php" %}
{% block title %}Ämne skapat{% endblock %}

{% block body %}
  <style>
    @media only screen and (max-width:600px) {
      .stack-row { padding-top:6px !important; padding-bottom:6px !important; line-height:1.5 !important; }
      .body-text { font-size:16px !important; line-height:1.65 !important; }
      .meta-label { display:inline-block !important; margin-right:4px !important; }
    }
  </style>

  <p class="body-text" style="margin:0 0 8px 0; color:#111827;">Hej,</p>
  <p class="body-text" style="margin:0 0 12px 0; color:#4B5563;">
    Ett nytt ämne har skapats och registrerats i systemet.
  </p>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 6px 0;">
    <tr>
      <td class="stack-row body-text" style="color:#111827; padding: 2px 0;">
        <strong class="meta-label" style="margin:0; padding:0;">Kategori:</strong> {{ $category ?? '—' }}
      </td>
    </tr>
    <tr>
      <td class="stack-row body-text" style="color:#111827; padding: 2px 0;">
        <strong class="meta-label" style="margin:0; padding:0;">Ämne:</strong> {{ $subject ?? '—' }}
      </td>
    </tr>
  </table>

  <table role="presentation" width="100%" style="margin: 12px 0;">
    <tr><td style="border-top:1px solid #E5E7EB; height:1px; line-height:1px; font-size:0;">&nbsp;</td></tr>
  </table>

  {% if (isset($body) && $body) : %}
  <p class="body-text" style="margin:0 0 6px 0; color:#4B5563; font-size:14px;">
    <strong style="margin:0; padding:0;">Beskrivning:</strong>
  </p>
  <p class="body-text" style="margin:0; color:#111827; white-space:pre-line;">
    {{ $body }}
  </p>
  {% endif %}

  <p class="muted" style="margin-top:12px; font-size:12px; color:#6B7280;">Skapat {{ date('Y-m-d H:i') }}</p>
{% endblock %}