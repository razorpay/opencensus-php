import GenericEntity from './GenericEntity';
import { getFixedINRAmount } from 'common/utils/rzp-utils';
import ajax from 'merchant/utils/ajax';

export default class Settlement extends GenericEntity {
  resourceUrl = 'settlements';

  fetchBreakupDetails() {
    let Klass = this.constructor;

    const url = `${this.resourceUrl}/${this.id}/details`;
    return this.makeGenericAjaxCall({ url }).then((response) => {
      response.data.items = response.data.items.map((item) => new Klass(item).deserialize());
      return response;
    });
  }

  fetchSettlementSchedule() {
    let Klass = this.constructor;
    const url = `schedule_tasks/settlement`;
    return this.makeGenericAjaxCall({ url }).then((response) => {
      response.data = response.data.map((item) => new Klass(item).deserialize());
      return response;
    });
  }

  analyticsPayload() {
    const settlement = this;
    return {
      settlementId: settlement.id,
      settlementStatus: settlement.status,
      settlementMethod: settlement.method,
      settlementStatus: settlement.status,
      createdAt: settlement.created_at,
      description: settlement.description,
      totalFee: settlement.fee,
      orderId: settlement.order_id,
      amount: settlement.amount,
    };
  }

  deserializeProperty(prop, value) {
    if (prop === 'amount') {
      this.amountInINR = getFixedINRAmount(value);
    }
    return super.deserializeProperty(prop, value);
  }
}
