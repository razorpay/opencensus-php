import { Component } from 'react';

import Collection from 'model/collection';

import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';
import { PageTable } from 'ui/Table';
import { isPresent } from 'rzp/utils/rzp-utils';

import { adminFetch } from 'common/fetch';

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
          ...submerchant,
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
                  <button class="btn pull-right">
                    {isPresent(this.defaultConfigs) ? 'Update' : 'Create'}
                  </button>
                </header>
              </div>
              <PageTable
                model={this.submerchants}
                animateRow={false}
                fields={submerchantFields}
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

var submerchantFields = [
  ['Submerchant ID', item => <span className="link">{item.id}</span>],
  ['Submerchant Name', item => item.name],
  [
    'Config',
    item =>
      item.config ? (
        <button class="button">Update</button>
      ) : (
        <button class="button">Create</button>
      ),
  ],
];
