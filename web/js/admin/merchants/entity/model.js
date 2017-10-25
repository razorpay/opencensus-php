import { observable, action, transaction } from 'mobx';
import { notifyError } from 'common/modal';

export default class Model {
  @observable
  merchant = {
    details: {},
    terminals: {},
    offers: [],
    pricingPlans: {},
    scheduleTasks: {},
    bankDetails: {},
    hasSettlementSchedule: undefined,
  };

  constructor({ merchantId, fetchFn }) {
    this.fetchFn = fetchFn;
    this.merchantId = merchantId;

    this.pending = observable.shallowBox();

    // fetch if not pre-populated
    this.fetchDetails();
    this.fetchMerchantOffers();
  }

  @action
  fetchDetails() {
    const data = {
      route_name: 'merchant_details_fetch',
      account_id: this.merchantId,
      merchant_id: this.merchantId,
    };

    this.pending.set(true);

    return this._request(
      'fetchMerchantDetails',
      this.fetchFn({
        data,
      })
    ).then(data => {
      transaction(() => {
        if (data && data.success) {
          this.merchant.details = data.data;
        }

        this.pending.set(false);
      });

      this.fetchPricingPlans();
      this.fetchScheduleTasks();
    });
  }

  @action
  fetchMerchantOffers() {
    const data = {
      route_name: 'admin_fetch_entity_multiple',
      url_params: {
        type: 'offer',
      },
      mode: 'live',
    };

    const queryParams = {
      merchant_id: this.merchantId,
    };

    return this._request(
      'fetchMerchantOffers',
      this.fetchFn({
        data: data,
        queryParams,
      })
    ).then(data => {
      if (data && data.success) {
        this.merchant.offers = data.data.items;
      }
    });
  }

  @action
  fetchPricingPlans() {
    const data = {
      route_name: 'merchant_get_pricing',
      url_params: {
        id: this.merchantId,
      },
    };

    return this._request(
      'fetchMerchantPricingPlans',
      this.fetchFn({
        data,
      })
    ).then(data => {
      if (data && data.success) {
        this.merchant.pricingPlans = data.data;
      }
    });
  }

  @action
  fetchScheduleTasks() {
    const data = {
      route_name: 'admin_fetch_entity_multiple',
      url_params: {
        type: 'schedule_task',
      },
    };

    const queryParams = {
      merchant_id: this.merchantId,
    };

    return this._request(
      'fetchMerchantScheduleTasks',
      this.fetchFn({
        data,
        queryParams,
      })
    ).then(data => {
      if (data && data.success) {
        this.merchant.scheduleTasks = data.data;
      }

      // Check if merchant has Settlement Schedule
      if (this.merchant.scheduleTasks) {
        for (let key in this.merchant.scheduleTasks.items) {
          if (this.merchant.scheduleTasks.items[key]['type'] === 'settlement') {
            this.merchant.hasSettlementSchedule = true;
            break;
          }
        }
      }
    });
  }

  // TODO: Can be moved to file fetch.js as compulsory layer for all requests
  _request(name, promise) {
    return promise
      .then(({ data }) => {
        if (!data.success) {
          throw data.errors[0];
        }

        return data;
      })
      .catch(e => {
        notifyError(e);
      });
  }
}
