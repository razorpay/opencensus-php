import GenericEntity from './GenericEntity';
import moment from 'moment';
import { getFixedINRAmount } from 'common/utils/rzp-utils';

export default class Refund extends GenericEntity {
  resourceUrl = 'refunds';

  deserializeProperty(prop, value) {
    if (prop === 'amount') {
      this.amountInINR = getFixedINRAmount(value);
    }
    return super.deserializeProperty(prop, value);
  }

  analyticsPayload() {
    const refund = this;
    return {
      refundId: refund.id,
      refundStatus: refund.status,
      paymentId: refund.paymentId,
      createdAt: moment.unix(refund.created_at),
      amount: refund.amount,
      speed: refund.speed_processed,
    };
  }
}
