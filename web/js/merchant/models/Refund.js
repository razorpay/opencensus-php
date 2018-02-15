import GenericEntity from './GenericEntity';
import { getFixedINRAmount } from 'rzp/utils/rzp-utils';
import ajax from 'merchant/utils/ajax';

export default class Refund extends GenericEntity {
  resourceUrl = 'refunds';

  deserializeProperty(prop, value) {
    if (prop === 'amount') {
      this.amountInINR = getFixedINRAmount(value);
    }
    return super.deserializeProperty(prop, value);
  }
}
