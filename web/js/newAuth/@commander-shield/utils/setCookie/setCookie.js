const setCookie = (name, value, expiryDays = 10) => {
  const date = new Date();
  date.setDate(date.getDate() + expiryDays);
  const expires = date.toUTCString();
  const domain = SHIELD_STAGE === 'production' ? 'razorpay.com' : 'razorpay.in';
  document.cookie = `${name}=${value};domain=${domain};expires=${expires};`;
};

export default setCookie;
