const readCookie = (name) => {
  const cookieName = `${name}=`;
  const cookieArray = document.cookie.split(';');
  let cookieValue = null;
  for (let i = 0; i < cookieArray.length; i++) {
    let cookie = cookieArray[i];
    while (cookie.charAt(0) === ' ') cookie = cookie.substring(1, cookie.length);
    if (cookie.indexOf(cookieName) === 0) {
      cookieValue = cookie.substring(cookieName.length, cookie.length);
      break;
    }
  }

  if (!cookieValue) {
    return cookieValue;
  }

  try {
    return decodeURIComponent(cookieValue);
  } catch (err) {
    return cookieValue;
  }
};

export default readCookie;
