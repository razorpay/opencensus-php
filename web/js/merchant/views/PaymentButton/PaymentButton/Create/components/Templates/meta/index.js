import quickPay from './quick_pay';
import donation from './donation';
import buyNow from './buy_now';
import custom from './custom';

export const templateTypes = {
  quickPay,
  donation,
  buyNow,
  custom,
};

const templateListMETA = [quickPay, donation, buyNow, custom];

export default templateListMETA;
