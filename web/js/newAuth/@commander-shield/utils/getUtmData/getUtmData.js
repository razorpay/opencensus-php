import readCookie from '../readCookie';

export const getAttribUtmData = () => {
  const rzpUtmCookie = readCookie('rzp_utm');

  let firstUtm = '';
  let lastUtm = '';
  let firstPage = '';
  let finalPage = '';
  let website = '';
  let isNewUser = false;

  try {
    const parsedUtmCookie = JSON.parse(rzpUtmCookie);

    // Populate first and last utm
    // Put first utm object to firstUtm key
    // and second utm object to lastUtm key
    // if second utm object is not present
    // populate first utm object to lastUtm key
    if (parsedUtmCookie?.attributions?.[0]) {
      const attributions = parsedUtmCookie.attributions;
      firstUtm = attributions[0];
      if (attributions[1]) {
        lastUtm = attributions[1];
      } else {
        lastUtm = attributions[0];
      }
    }
    firstPage = parsedUtmCookie?.first_page || '';
    finalPage = parsedUtmCookie?.final_page || '';
    website = parsedUtmCookie?.website || '';
    isNewUser = parsedUtmCookie?.new_user || false;
  } catch (e) {
    throw new Error('rzp_utm cookie is malformed');
  }

  return {
    firstUtm,
    lastUtm,
    firstPage,
    finalPage,
    website,
    isNewUser,
  };
};

export const getRawUtmData = () => {
  if (window.razorpayAnalytics) {
    return window.razorpayAnalytics.utils.getLandingParams();
  }
  return null;
};

export const getGclid = () => {
  if (window.razorpayAnalytics) {
    return window.razorpayAnalytics.utils.getCookie('gclid');
  }
  return null;
};
