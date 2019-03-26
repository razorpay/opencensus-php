import { Component } from 'react';
import { Link } from 'react-router-dom';

import Collection from 'model/collection';

import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';
import { PageTable } from 'ui/Table';
import EntityRow from 'ui/EntityRow';
import { ModalContent } from 'component/Modal';

import { isPresent, pickProps, without } from 'rzp/utils/rzp-utils';

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
              ...(isPresent(config)
                ? sanitizeConfig(config)
                : getDefaultValues()),
            }}
            submit={!!(config || {}).id ? adminPut : adminPost}
            buttonText={!!(config || {}).id ? 'Update' : 'Create'}
            submerchant={submerchant}
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
      const partnerDetails = pickProps(data, ['name', 'id']);
      if (purePlatform) {
        this.fetchApps();
      } else {
        this.fetchConfigAndSubs({ partner_id: this.merchantId });
      }
      this.setState({
        merchant: { loading: false, data: { purePlatform, ...partnerDetails } },
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
      params,
    });
  };

  render() {
    const { applications, merchant, configsAndSubsFetched } = this.state;
    return (
      <div class="list-container">
        <div class="box">
          {!merchant.loading ? (
            <>
              <header>Partner Commission Settings</header>
              <div class="heading">Partner Details</div>
              <EntityRow
                label="Partner Id"
                value={() => (
                  <Link to={`/merchants/${merchant.data.id}`} class="link">
                    {merchant.data.id}
                  </Link>
                )}
              />
              <EntityRow label="Partner Name" value={merchant.data.name} />
              <Form>
                {merchant.data.purePlatform &&
                  (applications.loading ? (
                    <EntityRow
                      label={<em>Fetching Applications...</em>}
                      value={' '}
                    />
                  ) : (
                    <SelectField
                      label="Select Application"
                      onChange={this.onAppChange}
                    >
                      <option value="">Not Selected</option>
                      {applications.data.map(app => (
                        <option key={app.id} value={app.id}>
                          {app.name} - {app.id}
                        </option>
                      ))}
                    </SelectField>
                  ))}
                {isPresent(configsAndSubsFetched) &&
                  configsAndSubsFetched === true && (
                    <div class="field">
                      <button
                        class="btn"
                        type="button"
                        style={{ marginBottom: '0' }}
                        onClick={this.onWriteConfig({
                          config: this.defaultConfigs[0],
                        })}
                      >
                        {`${
                          isPresent(this.defaultConfigs) ? 'Update' : 'Create'
                        } Default Config`}
                      </button>
                    </div>
                  )}
              </Form>
            </>
          ) : (
            <div class="box">
              <div class="spinner center" />
            </div>
          )}
        </div>
        {isPresent(configsAndSubsFetched) &&
          (configsAndSubsFetched === true ? (
            <PageTable
              model={this.submerchants}
              animateRow={false}
              fields={getSubmerchantFields({
                write: this.onWriteConfig,
                defaultConfig: without(this.defaultConfigs[0] || {}, ['id']),
              })}
              info={false}
            />
          ) : (
            <div class="box">
              <div class="spinner center" />
            </div>
          ))}
      </div>
    );
  }
}

function isPurePlatform(merchant = {}) {
  return merchant.partner_type === 'pure_platform';
}

var getSubmerchantFields = ({ write, defaultConfig }) => [
  [
    'Submerchant ID',
    item => (
      <Link
        to={`/merchants/${item.submerchant.id.replace('acc_', '')}`}
        class="link"
      >
        {item.submerchant.id}
      </Link>
    ),
  ],
  ['Submerchant Name', item => item.submerchant.name],
  [
    'Config',
    item => (
      <button
        class="button"
        onClick={write({ ...item, config: item.config || defaultConfig })}
      >
        {item.config ? 'Update' : 'Override'}
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

function getDefaultValues() {
  return {
    revisit_at: moment()
      .add(1, 'year')
      .format('X'),
  };
}
