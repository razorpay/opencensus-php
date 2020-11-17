import GenericEntity from './GenericEntity';
import { getFixedINRAmount } from 'common/utils/rzp-utils';

export default class InstantSettlement extends GenericEntity {
  resourceUrl = 'settlements/ondemand';

  deserializeProperty(prop, value) {
    if (prop === 'amount') {
      this.amountInINR = getFixedINRAmount(value);
    }
    return super.deserializeProperty(prop, value);
  }
}
