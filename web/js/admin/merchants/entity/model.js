import { observable, action, transaction } from 'mobx';
import { notifySuccess } from 'common/modal';
import BaseModel from 'model/base';

import { adminDelete } from 'util/fetch';
import { titleCase, removeFromArray } from 'util/index';

export default class Model extends BaseModel {
  @observable
  merchant = {
    details: {},
    gatewayRules: {},
    terminals: { items: [], count: 0 },
    offers: [],
    pricingPlans: {},
    scheduleTasks: {},
    features: {},
    bankDetails: {},
    creditsLogs: {},
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

      // TODO: Ensure rendering happpens on resolve of each below otherwise data will update but not merchant object, hence no re-rendering. Or take out each property instead of putting inside merchant object
      this.fetchPricingPlans();
      this.fetchScheduleTasks();
      this.fetchGatewayRules();

      this.fetchTerminals('live');
      this.fetchTerminals('test');

      this.fetchFeatures('live');
      this.fetchFeatures('test');
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
  fetchTerminals(mode) {
    const data = {
      route_name: 'merchant_get_terminals',
      url_params: {
        id: this.merchantId,
      },
      mode,
    };

    return this.request(
      'fetchMerchantTerminals',
      this.fetchFn(data)
    ).then(data => {
      if (data) {
        this.merchant.terminals.items = this.merchant.terminals.items.concat(
          data.items
        );
        this.merchant.terminals.count += data.count;
      }
    });
  }

  @action
  fetchFeatures(mode) {
    const data = {
      route_name: 'feature_get_multiple',
      url_params: {
        entityId: this.merchantId,
      },
      mode: mode,
    };

    return this.request(
      'fetchMerchantFeatures',
      this.fetchFn(data)
    ).then(data => {
      if (data) {
        data.assigned_features = data.assigned_features.map(
          feature => feature.name
        );
        this.merchant.features = { ...this.merchant.features, [mode]: data }; // To allow re-render when 2nd api request modifies features.
      }
    });
  }

  @action
  fetchGatewayRules() {
    const data = {
      route_name: 'admin_fetch_entity_multiple',
      url_params: {
        type: 'gateway_rule',
      },
      query_params: {
        merchant_id: this.merchantId,
      },
      mode: 'live',
    };

    return this.request('fetchGatewayRules', this.fetchFn(data)).then(data => {
      if (data) {
        this.merchant.gatewayRules = data.items;
      }
    });
  }

  @action
  fetchCreditsLogs = mode => {
    const data = {
      route_name: 'credits_fetch_multiple',
      merchant_id: this.merchantId,
      mode,
    };

    return this.request('fetchGatewayRules', this.fetchFn(data)).then(data => {
      if (data) {
        this.merchant.creditsLogs = {
          ...this.merchant.creditsLogs,
          [mode]: data.items,
        }; // This syntax is needed for allow re-render. Simple assigning won't re-render
      }
    });
  };

  @action
  deleteCreditLogs = (creditId, mode) => {
    creditId = creditId.split('_')[1];

    const data = {
      route_name: 'credits_delete',
      url_params: {
        mid: this.merchantId,
        id: creditId,
      },
      mode,
    };

    return this.request(
      'deleteCreditLogs',
      adminDelete(data).then(data => {
        if (data.success) {
          notifySuccess('Credit Log deleted successfully');
          this.fetchCreditsLogs(mode);
        }
      })
    );
  };

  @action
  deleteFeature = (featureName, featureMode) => {
    const data = {
      route_name: 'feature_delete',
      url_params: {
        entityId: this.merchantId,
        featureName,
      },
      mode: featureMode,
    };

    return this.request(
      'deleteFeature',
      adminDelete(data).then(data => {
        console.log('DATA...', data);

        notifySuccess(
          `${titleCase(
            featureMode
          )} feature '${featureName}' removed successfully`
        );

        // Update assigned_features for that mode and allow re-render
        const modeFeatures = this.merchant.features[featureMode]
          .assigned_features;
        const index = modeFeatures.indexOf(featureName);

        if (index > -1) {
          // If item is not found then re-render won't happen
          const modeUpdatedFeatures = removeFromArray(modeFeatures, index);
          const newFeatures = {
            assigned_features: modeUpdatedFeatures,
            all_features: this.merchant.features[featureMode].all_features,
          };

          this.merchant.features = {
            ...this.merchant.features,
            [featureMode]: newFeatures,
          }; // To allow re-render
        }
      })
    );
  };

  updateFeatures(mode, features) {
    this.merchant.features[mode].assigned_features = this.merchant.features[
      mode
    ].assigned_features.concat(features);

    this.merchant.features = { ...this.merchant.features };
  }

  updateMerchantDetails(data) {
    // Value is changed and view is re-rendered
    this.merchant.details.merchant_details = data;
    this.merchant = { ...this.merchant }; // To force re-render the view
  }

  updateDetails(data) {
    // Value is changed and view is re-rendered
    this.merchant.details = { ...this.merchant.details, ...data };
    this.merchant = { ...this.merchant }; // To force re-render the view
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
