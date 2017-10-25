import { observable, action, transaction } from 'mobx';
import { notifyError } from 'common/modal';
import BaseModel from 'model/base';

export default class Model extends BaseModel {
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
    super();
    this.fetchFn = fetchFn;
    this.merchantId = merchantId;

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

    return this.request(
      'fetchMerchantDetails',
      this.fetchFn(data)
    ).then(data => {
      if (data) {
        this.merchant.details = data;
      }

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

    return this.request(
      'fetchMerchantOffers',
      this.fetchFn({
        ...data,
        queryParams,
      })
    ).then(data => {
      if (data) {
        this.merchant.offers = data.items;
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

    return this.request(
      'fetchMerchantPricingPlans',
      this.fetchFn(data)
    ).then(data => {
      if (data) {
        this.merchant.pricingPlans = data;
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

    return this.request(
      'fetchMerchantScheduleTasks',
      this.fetchFn({
        ...data,
        queryParams,
      })
    ).then(data => {
      if (data) {
        this.merchant.scheduleTasks = data;
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
}
