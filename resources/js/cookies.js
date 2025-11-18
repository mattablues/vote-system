export function getCookie(name) {
  const value = " " + document.cookie;
  const parts = value.split(" " + name + "=");
  return parts.length < 2 ? undefined : parts.pop().split(";").shift();
}

export function setCookie(name, value, expiryDays, domain, path, secure) {
  const expiryDate = new Date();
  expiryDate.setHours(
    expiryDate.getHours() +
      (typeof expiryDays !== "number" ? 365 : expiryDays) * 24
  );
  document.cookie =
    name +
    "=" +
    value +
    ";expires=" +
    expiryDate.toUTCString() +
    ";path=" +
    (path || "/") +
    (domain ? ";domain=" + domain : "") +
    (secure ? ";secure" : "");
}

export function handleCookiesBanner() {
  const $cookiesBanner = document.querySelector(".cookies-eu-banner");
  if (!$cookiesBanner) return; // Returnera om bannern inte finns på sidan

  const $cookiesBannerButton = $cookiesBanner.querySelector("button");
  const cookieName = "accept-cookies";
  const hasCookie = getCookie(cookieName);

  if (!hasCookie) {
    $cookiesBanner.classList.remove("hidden");
  }

  $cookiesBannerButton.addEventListener("click", () => {
    setCookie(cookieName, "accepted", 30);
    $cookiesBanner.remove();
  });
}
