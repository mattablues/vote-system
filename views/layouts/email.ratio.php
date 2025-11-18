<!DOCTYPE html>
<html lang="{{ getenv('APP_LANG') }}">
<head>
  <meta charset="UTF-8">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
  <title>{% yield title %}</title>
  <style>
    html, body { width:100% !important; height:100% !important; }
    body { margin:0; padding:0; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; font-size:16px; line-height:1.55; letter-spacing:0.2px; }
    table { border-collapse: collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; }
    img { border:0; line-height:100%; outline:none; text-decoration:none; -ms-interpolation-mode:bicubic; }
    a { text-decoration: underline; color:#2563EB; }
    .btn { display:inline-block; background:#2563EB; color:#fff; font-weight:700; font-size:15px; padding:14px 20px; border-radius:6px; text-decoration:none !important; }
    .btn-outline { display:inline-block; background:transparent; color:#2563EB; border:1px solid #2563EB; font-weight:700; font-size:15px; padding:13px 20px; border-radius:6px; }
    .muted { color:#6B7280; }
    .body-text { font-size:15px; line-height:1.6; }
    h1.hero-title { font-size:24px; line-height:28px; margin:0 0 8px 0; font-weight:600; }

    @media only screen and (max-width: 600px) {
      .container { width:100% !important; max-width:100% !important; }
      .card { width:100% !important; max-width:100% !important; border-radius:0 !important; }
      .px { padding-left:16px !important; padding-right:16px !important; }
      .outer-pad { padding:12px !important; }
      h1.hero-title { font-size:20px !important; line-height:26px !important; }
      .body-text { font-size:16px !important; line-height:1.65 !important; }
      a { text-decoration: underline !important; }
    }

    @media (prefers-color-scheme: dark) {
      body, .bg-body { background:#0B0B0C !important; color:#ECEDEE !important; }
      .card { background:#141518 !important; border-color:#2A2C2F !important; }
      .muted { color:#B2B3B5 !important; }
      a { color:#7CB3FF !important; }
      .btn { background:#3B82F6 !important; color:#fff !important; }
      .btn-outline { color:#ECEDEE !important; border-color:#3B82F6 !important; }
    }

    @media (hover:hover) {
      a:hover { color:#1E40AF; }
      .btn:hover { background:#2F6AD9; }
      .btn-outline:hover { background:rgba(59,130,246,0.1); }
    }
  </style>
</head>
<body class="bg-body" style="margin:0; padding:0; background:#F3F4F6; font-family: Arial, Helvetica, sans-serif; color:#111827; line-height:1.5; -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#F3F4F6; min-width:320px;">
    <tr>
      <td align="center" class="outer-pad" style="padding:24px;">
        <!-- Wrapper: fluid -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="container" style="width:100%; max-width:100%;">
          <!-- Header: 600px centrerad -->
          <tr>
            <td class="px" align="center" style="padding:0 24px 16px 24px;">
              <table role="presentation" width="100%" style="max-width:600px; margin:0 auto; text-align:left;">
                <tr>
                  <td align="left" valign="middle" style="padding:0; margin:0;">
                    <a href="{{ getenv('APP_URL') ?: '#' }}" style="display:inline-block;">
                      <img src="{{ getenv('APP_URL') ? getenv('APP_URL').'/images/graphics/logo.png' : 'https://dummyimage.com/120x32/111827/ffffff&text=LOGO' }}"
                           width="auto" height="32"
                           alt="{{ getenv('APP_NAME') ?: 'App' }}"
                           style="display:block; height:32px; width:auto;">
                    </a>
                  </td>
                </tr>
                <tr>
                  <td align="left" valign="middle" style="padding-top:6px;">
                    <span class="muted" style="display:block; color:#6B7280; font-size:12px;">
                      {% yield title %}
                    </span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Card: 600px centrerad -->
          <tr>
            <td style="padding:0 24px 0 24px;">
              <table role="presentation" width="100%" align="center" class="card" style="background:#FFFFFF; border:1px solid #E5E7EB; border-radius:8px; overflow:hidden; max-width:600px; margin:0 auto;">
                <!-- Hero -->
                <tr>
                  <td class="px" style="padding:24px 24px 0 24px;">
                    <h1 class="hero-title" style="margin:0 0 8px 0; font-size:24px; line-height:1.25; color:#111827;">{{ getenv('APP_NAME') ?: 'Välkommen' }}</h1>
                    <span style="display:block; margin:0; color:#4B5563; font-size:14px;">{% yield title %}</span>
                  </td>
                </tr>
                <!-- Body -->
                <tr>
                  <td class="px" style="padding:16px 24px 24px 24px; font-size:15px; color:#111827;">
                    {% yield body %}
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer: 600px centrerad -->
          <tr>
            <td class="px" align="center" style="padding:16px 24px 0 24px;">
              <table role="presentation" width="100%" style="max-width:600px; margin:0 auto; text-align:left;">
                <tr>
                  <td>
                    <p class="muted" style="margin:0 0 4px 0; font-size:12px; color:#6B7280;">
                      Skickat av {{ getenv('APP_NAME') ?: 'Vår tjänst' }} • {{ getenv('APP_URL') ?: '' }}
                    </p>
                    <p class="muted" style="margin:0; font-size:12px; color:#6B7280;">
                      Detta meddelande är avsett för mottagaren. Om du fått det av misstag, vänligen radera det.
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="height:24px;">&nbsp;</td>
          </tr>
        </table>
        <!-- /Wrapper -->
      </td>
    </tr>
  </table>
</body>
</html>