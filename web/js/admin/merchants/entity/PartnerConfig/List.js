import { Component } from 'react';

import Collection from 'model/collection';

import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';
import { PageTable } from 'ui/Table';
import { ModalContent } from 'component/Modal';

import { isPresent, pickProps } from 'rzp/utils/rzp-utils';

import { adminFetch, adminPost, adminPut } from 'common/fetch';
import { openModal } from 'common/modal';

import Write from './Write';
export default class PartnerConfigList extends Component {
  constructor(props) {
    super();
    this.merchantId = props.match.params.id;
    this.state = {
      applications: {
        loading: false,
        data: [],
      },
      merchant: {},
    };
  }

  componentWillMount() {
    this.fetchMerchantDetails();
  }

  onAppChange = ({ target }) => {
    this.applicationId = target.value;
    this.fetchConfigAndSubs({
      application_id: this.applicationId,
    });
  };

  onWriteConfig = ({ submerchant, config }) => () => {
    const values = {
      partner_id: !this.applicationId ? this.merchantId : undefined,
      application_id: this.applicationId,
      submerchant_id: (submerchant || {}).id,
    };

    openModal(
      <div style={{ width: '1050px' }}>
        <ModalContent header="Partner Config">
          <Write
            values={{
              ...values,
              ...sanitizeConfig(config),
            }}
            submit={!!config ? adminPut : adminPost}
            buttonText={!!config ? 'Update' : 'Create'}
          />
        </ModalContent>
      </div>
    );
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
      if (purePlatform) {
        this.fetchApps();
      } else {
        this.fetchConfigAndSubs({ partner_id: this.merchantId });
      }
      this.setState({
        merchant: { purePlatform },
      });
    });
  };

  fetchConfigAndSubs = ({ partner_id, application_id }) => {
    this.setState({ configsAndSubsFetched: false });
    Promise.all([
      this.fetchConfigs({ partner_id, application_id }),
      this.fetchSubmerhants({ application_id }),
    ]).then(([configs, submerchants]) => {
      const overridenConfigs = (configs || []).filter(
        config => config.entity_type !== 'application'
      );
      this.defaultConfigs = (configs || []).filter(
        config => config.entity_type === 'application'
      );

      submerchants = submerchants.items.map(submerchant => {
        const overriddenConfig = overridenConfigs.find(
          config => config.entity_id === submerchant.id
        );
        return {
          submerchant,
          config: overriddenConfig,
        };
      });
      this.submerchants = new Collection({ items: submerchants });

      this.setState({
        configsAndSubsFetched: true,
      });
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
    });
  };

  render() {
    const { applications, merchant, configsAndSubsFetched } = this.state;
    return (
      <div className="list-container">
        <div className="box">
          <header>Partner Configs</header>
          {merchant.purePlatform && (
            <Form class="full-span full-elements" style={{ width: '85%' }}>
              {applications.loading ? (
                <Field
                  value="Loading Applications..."
                  label="Application"
                  disabled
                />
              ) : (
                <SelectField label="Application" onChange={this.onAppChange}>
                  <option value="">Select Application</option>
                  {applications.data.map(app => (
                    <option key={app.id} value={app.id}>
                      {app.name} - {app.id}
                    </option>
                  ))}
                </SelectField>
              )}
            </Form>
          )}
        </div>
        {isPresent(configsAndSubsFetched) &&
          (configsAndSubsFetched === true ? (
            <>
              <div className="box">
                <header>
                  Default Config
                  <button
                    class="btn pull-right"
                    onClick={this.onWriteConfig({
                      config: this.defaultConfigs[0],
                    })}
                  >
                    {isPresent(this.defaultConfigs) ? 'Update' : 'Create'}
                  </button>
                </header>
              </div>
              <PageTable
                model={this.submerchants}
                animateRow={false}
                fields={getSubmerchantFields({
                  write: this.onWriteConfig,
                })}
              />
            </>
          ) : (
            <div className="box">
              <div className="spinner center" />
            </div>
          ))}
      </div>
    );
  }
}

function isPurePlatform(merchant = {}) {
  return merchant.partner_type === 'pure_platform';
}

var getSubmerchantFields = ({ write }) => [
  [
    'Submerchant ID',
    item => <span className="link">{item.submerchant.id}</span>,
  ],
  ['Submerchant Name', item => item.submerchant.name],
  [
    'Config',
    item => (
      <button class="button" onClick={write(item)}>
        {item.config ? 'Update' : 'Create'}
      </button>
    ),
  ],
];

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
