import GenericEntity from './GenericEntity';
import { getFixedINRAmount } from 'common/utils/rzp-utils';
import moment from 'moment';

export default class Settlement extends GenericEntity {
  resourceUrl = 'settlements';

  fetchBreakupDetails() {
    const Klass = this.constructor;

    const url = `${this.resourceUrl}/${this.id}/details`;
    return this.makeGenericAjaxCall({ url }).then((response) => {
      response.data.items = response.data.items.map((item) => new Klass(item).deserialize());
      return response;
    });
  }

  fetchSettlementSchedule() {
    const Klass = this.constructor;
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
      createdAt: moment.unix(settlement.created_at),
      fee: settlement.fees,
      tax: settlement.tax,
      utr: settlement.utr,
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
