function getIconFn(iconName) {
  switch (iconName) {
    case 'airtelmoney':
      return 'airtelmoney';
    case 'amazonpay':
      return 'amazonpay';
    case 'amex':
      return 'amex';
    case 'diners':
      return 'diners';
    case 'earlysalary':
      return 'earlysalary';
    case 'epaylater':
      return 'epaylater';
    case 'freecharge':
      return 'freecharge';
    case 'icici':
      return 'icici';
    case 'instacred':
      return 'instacred';
    case 'jiomoney':
      return 'jiomoney';
    case 'maestro':
      return 'maestro';
    case 'masterCard':
      return 'masterCard';
    case 'mobikwik':
      return 'mobikwik';
    case 'mpesa':
      return 'mpesa';
    case 'olamoney':
      return 'olamoney';
    case 'paypal':
      return 'paypal';
    case 'paytm':
      return 'paytm';
    case 'payumoney':
      return 'payumoney';
    case 'payzapp':
      return 'payzapp';
    case 'phonepe':
      return 'phonepe';
    case 'rupay':
      return 'rupay';
    case 'sbibuddy':
      return 'sbibuddy';
    case 'getsimpl':
      return 'simpl';
    case 'visa':
      return 'visa';
    case 'zestmoney':
      return 'zestmoney';
    case 'itzcash':
      return 'itzcash';
    case 'paycash':
      return 'paycash';
    case 'citibankrewards':
      return 'citibankrewards';
    case 'sezzle':
      return 'sezzle';
    case 'walnut369':
      return 'walnut369';
    default:
      return '';
  }
}

export const getIcon = (iconName) => {
  const iconFn = getIconFn(iconName);
  return iconName.includes('https://')
    ? iconName
    : `https://cdn.razorpay.com/static/assets/instrument-request/${iconFn}.png`;
};
