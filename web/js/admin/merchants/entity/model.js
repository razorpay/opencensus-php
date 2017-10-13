import { observable, action, transaction } from 'mobx';
import { notifyError } from 'common/modal';

export default class Model {
  @observable
  merchant = {
    details: {},
    terminals: {},
    pricingPlans: {},
    bankDetails: {},
  };

  constructor({ data, fetchFn }) {
    this.fetchFn = fetchFn;
    this.data = data;

    this.pending = observable.box();

    // fetch if not pre-populated
    this.fetch();
  }

  @action
  fetch() {
    return this._request(
      'fetchMerchantDetails',
      this.fetchFn({
        data: this.data,
      })
    );
  }

  _request(name, promise) {
    this.pending.set(true);

    return promise
      .then(({ data }) => {
        if (!data.success) {
          throw data.errors[0];
        }
        transaction(() => {
          this.merchant.details = data.data;
          this.pending.set(false);
        });
      })
      .catch(e => {
        notifyError(e);
      });
  }
}
