import { Component } from 'react';
import { observer } from 'mobx-react';

import Collection from 'model/collection';

import Form from 'ui/Form';
import { ModalContent } from 'component/Modal';

import { isPresent, pickProps } from 'rzp/utils/rzp-utils';

import { adminFetch, adminPost, adminPut } from 'common/fetch';
import { openModal } from 'common/modal';

import Write from '../Write';
import Filter from './SubmerchantsFilter';
import PartnerDetails from './PartnerDetails';
import SubmerchantsList from './SubmerchantsList';
import { SelectApplication, WriteConfigButton } from './ConfigFormFields';

@observer
export default class PartnerConfigList extends Component {
  submerchants = new Collection({ items: [] });

  constructor(props) {
    super();
    this.merchantId = props.match.params.id;
    this.state = {
      applications: {
        loading: false,
        data: [],
      },
      merchant: {
        loading: true,
        data: {},
      },
    };
  }

  componentWillMount() {
    this.fetchMerchantDetails();
  }

  onAppChange = ({ target }) => {
    this.applicationId = target.value;
    this.submerchants.applyFilters({
      application_id: this.applicationId,
    });
  };

  onWriteConfig = ({ submerchant, config }) => () => {
    if (!(config || {}).id) {
      values = {
        partner_id: !this.applicationId ? this.merchantId : undefined,
        application_id: this.applicationId,
        submerchant_id: (submerchant || {}).id,
      };
    }

    openModal(
      <div style={{ width: '1050px' }}>
        <ModalContent
          header={`${
            isPresent(submerchant) ? 'Override Submerchant' : 'Default'
          } Partner Settings`}
        >
          <Write
            values={{
              ...values,
              ...(isPresent(config)
                ? sanitizeConfig(config)
                : getDefaultValues()),
            }}
            submit={!!(config || {}).id ? adminPut : adminPost}
            buttonText={!!(config || {}).id ? 'Update' : 'Create'}
            submerchant={submerchant}
            config_id={(config || {}).id}
          />
        </ModalContent>
      </div>
    );

    var values;
  };

  onFilterSubmit = filters => {
    this.submerchants.applyFilters({
      application_id: this.applicationId,
      partner_id: !this.applicationId ? this.merchantId : undefined,
      ...filters,
    });
  };

  fetchApps = () => {
    this.setState({
      applications: {
        loading: true,
        data: [],
      },
    });
    return adminFetch({
      url: `live_${this.merchantId}/oauth/applications`,
    }).then(response => {
      if (response)
        this.setState({
          applications: {
            loading: false,
            data: response.items,
          },
        });
    });
  };

  fetchMerchantDetails = () => {
    return adminFetch({
      url: 'live/merchants/details',
      headers: {
        'X-Razorpay-Account': this.props.match.params.id,
      },
    }).then(data => {
      const purePlatform = isPurePlatform(data);
      const partnerDetails = pickProps(data, ['name', 'id']);
      const fetchFn = this.fetchConfigAndSubs;

      if (purePlatform) {
        this.fetchApps();
        items = [];
      } else {
        filters = { partner_id: this.merchantId };
      }

      this.submerchants = new Collection({ fetchFn, items, filters });
      this.setState({
        merchant: { loading: false, data: { purePlatform, ...partnerDetails } },
      });

      var filters, items;
    });
  };

  fetchConfigAndSubs = ({
    params: { partner_id, application_id, ...submerchantFilters },
  }) => {
    return Promise.all([
      this.fetchConfigs({ partner_id, application_id }),
      this.fetchSubmerhants({ application_id, ...submerchantFilters }),
    ]).then(([configs, submerchants]) => {
      const overridenConfigs = getOverridenConfigs(configs);

      this.setState({
        defaultConfigs: getDefaultConfigs(configs),
      });

      const items = submerchants.items.map(
        combineWithSubmerchants(overridenConfigs)
      );

      return { items };
    });
  };

  fetchConfigs = params => {
    return adminFetch({
      url: 'live/partner_configs',
      params,
    });
  };

  fetchSubmerhants = params => {
    return adminFetch({
      url: `live_${this.merchantId}/submerchants`,
      params,
    });
  };

  render() {
    const { applications, merchant } = this.state;
    return (
      <div class="list-container">
        <div class="box">
          {!merchant.loading ? (
            <>
              <header>Partner Commission Settings</header>
              <PartnerDetails partner={merchant.data} />
              <Form>
                {merchant.data.purePlatform && (
                  <SelectApplication
                    applications={applications}
                    onAppChange={this.onAppChange}
                  />
                )}

                {!!this.state.defaultConfigs && (
                  <WriteConfigButton
                    defaultConfigs={this.state.defaultConfigs}
                    onWriteConfig={this.onWriteConfig}
                  />
                )}
              </Form>
              <Filter onFilterSubmit={this.onFilterSubmit} />

              <SubmerchantsList
                model={this.submerchants}
                onWriteConfig={this.onWriteConfig}
                defaultConfigs={this.state.defaultConfigs}
              />
            </>
          ) : (
            <div class="box">
              <div class="spinner center" />
            </div>
          )}
        </div>
      </div>
    );
  }
}

function isPurePlatform(merchant = {}) {
  return merchant.partner_type === 'pure_platform';
}

function sanitizeConfig(data = {}) {
  return pickProps(data, [
    'commissions_enabled',
    'implicit_expiry_at',
    'revisit_at',
    'explicit_refund_fees',
    'explicit_should_charge',
    'default_plan_id',
    'implicit_plan_id',
    'explicit_plan_id',
    'id',
  ]);
}

function getDefaultValues() {
  return {
    commissions_enabled: 0,
    explicit_should_charge: 0,
    explicit_refund_fees: 0,
    default_plan_id: null,
    revisit_at: moment()
      .add(1, 'year')
      .format('X'),
  };
}

function combineWithSubmerchants(configs) {
  return function(submerchant) {
    const config = configs.find(
      ({ entity_id }) => entity_id === submerchant.id.replace('acc_', '')
    );
    return { submerchant, config };
  };
}

function getOverridenConfigs(configs = []) {
  return configs.filter(config => config.entity_type !== 'application');
}

function getDefaultConfigs(configs = []) {
  return configs.filter(config => config.entity_type === 'application');
}
