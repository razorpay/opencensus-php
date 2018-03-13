import { observable, action } from 'mobx';
import { notifySuccess } from 'common/modal';
import BaseModel from 'model/base';

import user from 'admin/user';
import { adminDelete } from 'common/fetch';
import { titleCase, removeFromArray } from 'common/util';
import { isWorkflow } from 'common/util';

export default class Model extends BaseModel {
  @observable
  merchant = {
    details: {},
    balanceDetails: {},
    gatewayRules: {},
    terminals: { items: [], count: 0 },
    offers: [],
    pricingPlans: {},
    scheduleTasks: [],
    hasSettlementSchedule: undefined,
    features: {},
    bankDetails: {},
    creditsLogs: {},
    adminsMap: {},
  };

  //Following properties are depply nested into merchant details, hence create a diff observalble for it.
  @observable
  activationReview = {
    issue_fields: [],
    issue_fields_reason: '',
    internal_notes: '',
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
    return this.request(
      'fetchMerchantDetails',
      this.fetchFn({
        url: 'live/merchants/details',
        headers: {
          'X-Razorpay-Account': this.merchantId,
        },
      })
    ).then(data => {
      if (data) {
        this.setAutoRefundDelay(data);
        this.merchant.details = data;

        this.activationReview = {
          issue_fields: data.merchant_details.issue_fields
            ? data.merchant_details.issue_fields.split(',')
            : [],
          issue_fields_reason: data.merchant_details.issue_fields_reason || '',
          internal_notes: data.merchant_details.internal_notes || '',
        };
      }

      // TODO: Ensure rendering happpens on resolve of each below otherwise data will update but not merchant object, hence no re-rendering. Or take out each property instead of putting inside merchant object

      if (user.permissions.find(perm => perm === 'view_merchant_pricing')) {
        this.fetchPricingPlans();
      }

      if (user.permissions.find(perm => perm === 'view_merchant_balance')) {
        this.fetchBalance();
      }

      this.fetchScheduleTasks();
      this.fetchGatewayRules();

      if (user.permissions.find(perm => perm === 'view_all_admin')) {
        this.fetchAdmins();
      }

      this.fetchTerminals('live');
      // this.fetchTerminals('test');
      if (user.permissions.find(perm => perm === 'view_merchant_features')) {
        this.fetchFeatures('live');
        this.fetchFeatures('test');
      }
    });
  }

  @action
  fetchMerchantOffers() {
    const data = {
      url: 'live/admin/offer',
      params: {
        merchant_id: this.merchantId,
      },
    };

    return this.request(
      'fetchMerchantOffers',
      this.fetchFn({
        ...data,
      })
    ).then(data => {
      if (data) {
        this.merchant.offers = data.items;
      }
    });
  }

  @action
  fetchBalance() {
    const request = mode => {
      return this.request(
        'fetchMerchantBalance',
        this.fetchFn(`${mode}_${this.merchantId}/balance`)
      ).then(data => {
        if (data) {
          this.merchant.balanceDetails[mode] = data;
        }
      });
    };

    request('test');
    request('live');
  }

  @action
  fetchPricingPlans() {
    return this.request(
      'fetchMerchantPricingPlans',
      this.fetchFn(`live/merchants/${this.merchantId}/pricing`)
    ).then(data => {
      if (data) {
        this.merchant.pricingPlans = data;
      }
    });
  }

  @action
  fetchTerminals(mode) {
    return this.request(
      'fetchMerchantTerminals',
      this.fetchFn(`${mode}/merchants/${this.merchantId}/terminals`)
    ).then(data => {
      if (data) {
        this.merchant.terminals.items = data.items;
        this.merchant.terminals.count += data.count;
      }
    });
  }

  @action
  fetchFeatures(mode) {
    return this.request(
      'fetchMerchantFeatures',
      this.fetchFn(`${mode}/features/${this.merchantId}`)
    ).then(data => {
      if (data) {
        data.assigned_features = data.assigned_features.map(
          feature => feature.name
        );
        this.merchant.features = { ...this.merchant.features, [mode]: data }; // To allow re-render when 2nd api request modifies features.
      }
    });
  }

  // TODO: TEST if merchant id to be passed as params or in url
  @action
  fetchGatewayRules() {
    const data = {
      url: 'live/admin/gateway_rule',
      params: {
        merchant_id: this.merchantId,
      },
    };

    return this.request('fetchGatewayRules', this.fetchFn(data)).then(data => {
      if (data) {
        this.merchant.gatewayRules = data.items;
      }
    });
  }

  @action
  fetchAdmins() {
    return this.request('fetchAdmins', this.fetchFn('live/admins')).then(
      data => {
        const adminsMap = {};

        data.items.map(admin => {
          adminsMap[admin.id] = {
            role: admin.roles.length ? admin.roles[0].name : '',
            email: admin.email,
            name: admin.name,
          };
        });

        this.merchant.adminsMap = adminsMap;
      }
    );
  }

  @action
  fetchCreditsLogs = mode => {
    return this.request(
      'fetchGatewayRules',
      this.fetchFn(`${mode}_${this.merchantId}/credits`)
    ).then(data => {
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

    return this.request(
      'deleteCreditLogs',
      adminDelete(
        `${mode}/merchants/${this.merchantId}/credits/${creditId}`
      ).then(data => {
        if (data.success) {
          notifySuccess('Credit Log deleted successfully');
          this.fetchCreditsLogs(mode);
        }
      })
    );
  };

  @action
  deleteFeature = (featureName, featureMode) => {
    return this.request(
      'deleteFeature',
      adminDelete(
        `${featureMode}/features/${this.merchantId}/${featureName}`
      ).then(data => {
        if (isWorkflow(data)) {
          return;
        }

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

  // Updates only for live mode
  updateTerminal(data) {
    this.merchant.terminals.items.push(data);
    this.merchant.terminals.count += 1;
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

  /**
   * Sets the auto refund delay value and type.
   * `data` is passed by reference.
   * @param {Object} data
   */
  setAutoRefundDelay(data) {
    // Set Auto Refund Delay (value and type)
    let { auto_refund_delay: delay } = data;
    if (delay) {
      let val, type;
      if (delay % (24 * 60 * 60) === 0) {
        val = delay / (24 * 60 * 60);
        type = 'days';
      } else if (delay % (60 * 60) === 0) {
        val = delay / (60 * 60);
        type = 'hours';
      } else {
        val = delay / 60;
        type = 'mins';
      }
      data.auto_refund_delay_val = val;
      data.auto_refund_delay_type = type;
      delete data.auto_refund_delay;
    }
  }

  @action
  fetchScheduleTasks() {
    return this.request(
      'fetchMerchantScheduleTasks',
      this.fetchFn({
        url: 'live/admin/schedule_task',
        params: {
          merchant_id: this.merchantId,
          type: 'settlement',
        },
      })
    ).then(data => {
      // Check if merchant has Settlement Schedule
      if (data) {
        if (data.items.length) {
          this.merchant.hasSettlementSchedule = true;
        }

        this.merchant.scheduleTasks = data.items;

        if (this.merchant.hasSettlementSchedule === undefined) {
          this.merchant.hasSettlementSchedule = false;
        }
      }
    });
  }

  @action
  editIssuesList(selectedIssue) {
    const foundIndex = this.activationReview.issue_fields.findIndex(
      issue => issue === selectedIssue
    );

    if (foundIndex > -1) {
      this.activationReview.issue_fields.splice(foundIndex, 1);
    } else {
      this.activationReview.issue_fields.push(selectedIssue);
    }
  }
}
