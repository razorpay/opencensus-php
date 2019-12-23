import card from './card';
import emi from './emi';
import netbanking from './netbanking';
import upi from './upi';
import wallet from './wallet';
import qr from './qr';

function getIconFn(iconName) {
  switch (iconName) {
    case 'card':
      return card;

    case 'netbanking':
      return netbanking;

    case 'upi':
      return upi;

    case 'emi':
      return emi;

    case 'wallet':
      return wallet;

    case 'qr':
      return qr;
  }
}

export const getIcon = (iconName, color = {}) => {
  const iconFn = getIconFn(iconName);
  let { foregroundColor = '#072654', backgroundColor = '#3F71D7' } = color;

  return iconFn && iconFn(foregroundColor, backgroundColor);
};
