{% extends "layouts/main.ratio.php" %}
{% block title %}Cookies{% endblock %}
{% block pageId %}cookies{% endblock %}
{% block body %}
        <section class="py-6">
          <div class="container-centered-sm">
            <h1 class="text-3xl font-semibold mb-6">Cookies</h1>

            <article class="mb-6">
              <h3 class="text-xl font-semibold mb-2">Vad använder vi cookies till?</h3>
              <p class="mb-2 text-gray-700">Den här webbplatsen innehåller cookies för att ge dig tillgång till alla funktioner på webbplatsen, anpassa din användarupplevelse och göra analyser av hur väl webbplatsen fungerar. Till exempel genom att mäta antalet besökare på webbplatsen.</p>
              <p class="text-gray-700">Vi använder temporära sessionscookies samt en vanlig cookie för att spara undan login och lösenord om besökaren vill. Det som sparas i denna cookie är inte själva lösenordet utan en unik identitet som ej kan användas för att ta reda på lösenord.</p>
            </article>

            <article class="mb-6">
              <h3 class="text-xl font-semibold mb-2">Vad är cookies?</h3>

              <p class="mb-2 text-gray-700">En cookie är en liten textfil som skickas från vår webbserver och som sparas av din webbläsare. Det finns två typer av cookies, vanliga cookies och session cookies. Sessionscookies försvinner när du stänger din webbläsare och sparas därför inte, medan vanliga cookies lagras på din dator under en längre tid.</p>

              <p class="mb-2 text-gray-700">Vissa cookies är valfria och vissa cookies är nödvändiga för att webbplatsen ska fungera. Cookies delas ofta in i fyra kategorier:</p>

              <h6 class="text-lg font-medium mb-0.5">Strikt nödvändiga cookies</h6>
              <p class="mb-2 text-gray-700">Dessa cookies är nödvändiga för att användaren ska kunna surfa på webbplatsen och för att kunna använda dess funktioner, såsom att spara varor i kassan.</p>

              <h6 class="text-lg font-medium mb-0.5">Funktionella cookies</h6>
              <p class="mb-2 text-gray-700">Cookies som gör det möjligt för webbplatsen att tillhandahålla förbättrad funktionalitet och personalisering är funktionella cookies. Dessa cookies kommer att tillåta en webbplats att till exempel komma ihåg val som användaren har gjort tidigare.</p>

              <h6 class="text-lg font-medium mb-0.5">Analytiska cookies</h6>
              <p class="mb-2 text-gray-700">Även kända som prestandacookies, dessa cookies samlar in information om hur användare använder en webbplats, som vilka sidor de besöker och vilka länkar de klickade på. Deras enda syfte är att förbättra webbplatsens funktioner.</p>

              <h6 class="text-lg font-medium mb-0.5">Marknadsföringscookies</h6>
              <p class="text-gray-700">Dessa cookies spårar besökares onlineaktivitet för att hjälpa annonsörer att leverera mer relevant reklam eller för att begränsa hur många gånger de ser en annons.</p>
            </article>

            <article>
              <h3 class="text-xl font-semibold mb-2">Så kan du undvika cookies</h3>
              <h6 class="text-lg font-medium mb-0.5">Inställningar i din webbläsare</h6>
              <p class="mb-2 text-gray-700">Genom att använda webbläsarinställningarna kan du blockera cookies. Det kan dock påverka webbplatsernas beteende. Här är länkar till information om hur du gör detta i några av de vanligaste webbläsarna:</p>

              <ul class="list-disc list-inside space-y-1 text-gray-700">
                <li><a href="https://support.microsoft.com/sv-se/microsoft-edge/ta-bort-cookies-i-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank" class="text-blue-600 hover:text-blue-800 transition-colors">Microsoft Edge</a></li>
                <li><a href="https://support.google.com/chrome/answer/95647?hl=sv" target="_blank" class="text-blue-600 hover:text-blue-800 transition-colors">Google Chrome</a></li>
                <li><a href="https://support.mozilla.org/sv/kb/kakor-information-webbplatser-lagrar-pa-din-dator" target="_blank" class="text-blue-600 hover:text-blue-800 transition-colors">Mozilla Firefox</a></li>
                <li><a href="https://support.apple.com/sv-se/guide/safari/sfri11471/mac" target="_blank" class="text-blue-600 hover:text-blue-800 transition-colors">Safari</a></li>
              </ul>
            </article>

            <p class="my-3 text-gray-700">Läs mer om lagen om elektronisk kommunikation på <a href="https://www.pts.se/" class="text-blue-600 hover:text-blue-800 transition-colors" target="_blank">www.pts.se</a>.</p>
          </div>
        </section>
{% endblock %}