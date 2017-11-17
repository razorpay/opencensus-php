import React, { Component } from 'react';
import { observer } from 'mobx-react';
import { toJS } from 'mobx';
import { adminFetch } from 'util/fetch';
import { openModal, confirm } from 'common/modal';

import Model from './model';
import EntityRow from 'ui/EntityRow';
import TabsContainer from 'ui/Tabs';

import ContactDetails from './merchantActivationForms/ContactDetails';
import WebsiteDetails from './merchantActivationForms/WebsiteDetails';
import BankAccountDetails from './merchantActivationForms/BankAccountDetails';
import DocumentDetails from './merchantActivationForms/DocumentDetails';
import ProductOnboarding from './merchantActivationForms/ProductOnboarding';
import BusinessDetails from './merchantActivationForms/BusinessDetails';

@observer
export default class MerchantActivationForm extends Component {
  state = {};

  constructor(props) {
    super();
    this.merchantId = props.match.params.id;

    this.model = new Model({
      fetchFn: adminFetch,
      merchantId: this.merchantId,
    });
  }

  componentWillMount() {
    adminFetch({
      route_name: 'invitation_fetch',
      merchant_id: this.merchantId,
    }).then(data => {
      this.setState({
        pendingInvites: data,
      });
    });

    adminFetch({
      route_name: 'merchant_fetch_users',
      url_params: {
        id: this.merchantId,
      },
    }).then(data => {
      this.setState({
        users: data,
      });
    });
  }

  getOverview() {
    const { details } = this.model.merchant;

    return (
      <div class="box">
        <div class="heading">
          Merchant: <b>{this.merchantId}</b>
        </div>
        {!Object.keys(details).length ? (
          <div class="spinner center" />
        ) : (
          _getOverviewFields(details).map(row => (
            <EntityRow
              key={row.label}
              label={row.label}
              value={row.value}
              className="separate"
            />
          ))
        )}
      </div>
    );
  }

  render() {
    const { details } = this.model.merchant;
    return (
      <div class="entity-container">
        <header class="heading">Activation Form</header>
        {this.getOverview()}

        <div class="box">
          <div class="heading">Merchant Activation Form</div>

          {
            <TabsContainer
              tabNames={tabNames}
              className="activation-form"
              iconClass="i i-yes text-success"
              iconBoolList={
                details.merchant_details &&
                toJS(details.merchant_details.steps_finished)
              }
            >
              <ContactDetails {...details} title={tabNames[0]} />
              <BusinessDetails {...details} title={tabNames[1]} />
              <WebsiteDetails {...details} title={tabNames[2]} />
              <BankAccountDetails {...details} title={tabNames[3]} />
              <DocumentDetails
                merchantId={this.merchantId}
                {...details}
                title={tabNames[4]}
              />
              <ProductOnboarding
                merchantId={this.merchantId}
                title={tabNames[5]}
              />
            </TabsContainer>
          }
        </div>
      </div>
    );
  }
}

/* Resources */

function _getOverviewFields(details) {
  return [
    {
      label: 'Name',
      value: details.name,
    },
    {
      label: 'Email',
      value: details.email,
    },
    {
      label: 'Activation Form Submitted',
      value: () => (
        <i
          class={`i ${details.merchant_details.submitted == 1
            ? 'i-yes text-success'
            : 'i-no text-danger'}`}
        />
      ),
    },
    {
      label: 'Activation Form Status',
      value: () => (
        <i
          class={`i i-${details.merchant_details.locked == 1
            ? 'lock'
            : 'unlock'}`}
        />
      ),
    },
    {
      label: 'Activated',
      value: () => (
        <i
          class={`i ${details.activated == 1
            ? 'i-yes text-success'
            : 'i-no text-danger'}`}
        />
      ),
    },
  ];
}

const tabNames = [
  'Contact Details',
  'Business Details',
  'Website details',
  'Bank Account Details',
  'Document Uploads',
  'Product Onboading',
];
