const zipCodeRegex = /^\d{4,6}$/;
const isZipCodeHardCoded = (_value) => {
  const valueTokensList = _value.toLowerCase().split(' ');
  if (valueTokensList.some((token) => zipCodeRegex.test(token))) {
    return 'Avoid hardcoded "Pincodes/Zipcodes" as this feature may not be supported in all countries. If necessary, use a feature flag to enable it selectively. If this warning seems incorrect, feel free to ignore it.';
  } else if (valueTokensList.includes('pincode') || valueTokensList.includes('pin')) {
    return 'Avoid using the term "pincode or pin" for pincode. As pincode considered as postcode in malaysia, use the appropriate local name.';
  }
};

module.exports = isZipCodeHardCoded;
