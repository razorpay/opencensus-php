import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { observer } from 'mobx-react';
import {
  openModal,
  notifyError,
  notifySuccess,
  closeModal,
} from 'common/modal';
import Form from 'ui/Form';
import BaseModal from 'ui/BaseModal';
import Field, { SelectField, TextAreaField, FileField } from 'ui/Field';
import { adminFetch, adminPost, adminPut, adminFormUpload } from 'util/fetch';

@observer
export default class EditPublicFeatures extends Component {
  state = {
    pending: true,
    agreement: null,
  };

  akaFeature = this.props.model.feature.toLowerCase().replace(' ', '_');

  selectedStatus = this.props.model.collection.filters.status;

  featureParams = {
    merchant_id: this.props.model.merchant_id,
    query_params: {
      features: [this.akaFeature],
    },
    route_name: 'onboarding_features_fetch_details',
  };

  componentWillMount() {
    adminFetch(this.featureParams).then(response => {
      if (response) {
        this.submissions = response.submissions;
        this.setState({ pending: false });
        if (response.submissions['marketplace']) {
          this.setState({
            agreement: response.submissions['marketplace'].vendor_agreement,
          });
        }
      }
    });
  }

  changeAgreement = () => {
    this.setState({ agreement: null });
  };

  save = body => {
    let { merchant_id } = this.props.model;
    let { akaFeature, selectedStatus } = this;

    //send request if status changed
    if (selectedStatus !== body.status) {
      let data = {
        route_name: 'onboarding_features_update_status',
        body: {
          status: body.status,
          merchant_id: body.merchant_id,
        },
        url_params: {
          feature: akaFeature,
        },
      };
      adminPut(data).then(response => {
        if (response) {
          notifySuccess('Status updated!');
        }
      });
    } else {
      delete body.status;
    }

    if (featuresAkaMap[akaFeature] === featuresAkaMap.marketplace) {
      let form = {};

      Object.keys(body).forEach(key => (form[`body[${key}]`] = body[key]));

      form['url_params[{feature}]'] = akaFeature;
      form['route_name'] = 'onboarding_features_update';

      return adminFormUpload(form).then(response => {
        if (response.data.success) {
          notifySuccess('Submission edited successfully.');
          closeModal();
        } else {
          notifyError(response.data.errors.join(', '));
        }
      });
    } else {
      let data = { body };

      data.route_name = 'onboarding_features_update';
      data.url_params = {
        feature: akaFeature,
      };

      return adminPost(data).then(response => {
        if (response) {
          notifySuccess('Submission edited successfully.');
          closeModal();
        }
      });
    }
  };

  render() {
    let { feature, merchant_id } = this.props.model;
    let {
      selectedStatus,
      akaFeature,
      submissions,
      changeAgreement,
      save,
    } = this;

    if (this.state.pending) {
      return <div class="spinner" />;
    }

    return (
      <BaseModal header="Edit Submission">
        <Form class="full-span full-elements" onSubmit={save}>
          <div class="field">
            <label>Merchant ID</label>
            <code>{merchant_id}</code>
          </div>
          <div class="field">
            <label>Feature</label>
            <code>{feature}</code>
          </div>
          <SelectField
            label="Status"
            name="status"
            defaultValue={selectedStatus}
          >
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </SelectField>
          {featuresAkaMap[akaFeature] === featuresAkaMap.marketplace ||
          featuresAkaMap[akaFeature] === featuresAkaMap.virtual_accounts ? (
            <TextAreaField
              label="Use Case"
              name="use_case"
              defaultValue={submissions[akaFeature].use_case}
            />
          ) : null}

          {featuresAkaMap[akaFeature] === featuresAkaMap.virtual_accounts && (
            <Field
              label="Expected Monthly Revenue"
              type="number"
              name="expected_monthly_revenue"
              defaultValue={submissions[akaFeature].expected_monthly_revenue}
            />
          )}

          {featuresAkaMap[akaFeature] === featuresAkaMap.subscriptions && [
            <TextAreaField
              label="Business Model"
              name="business_model"
              key="business_model"
              defaultValue={submissions[akaFeature].business_model}
            />,
            <TextAreaField
              label="Subscription Plans"
              name="sample_plans"
              key="sample_plans"
              defaultValue={submissions[akaFeature].sample_plans}
            />,
            <TextAreaField
              label="Website Details"
              name="website_details"
              key="website_details"
              defaultValue={submissions[akaFeature].website_details}
            />,
          ]}

          {featuresAkaMap[akaFeature] === featuresAkaMap.marketplace && [
            <SelectField
              label="Transferring to"
              name="settling_to"
              key="settling_to"
              defaultValue={submissions[akaFeature].settling_to}
            >
              {tranferToOptions.map(t => (
                <option key={t[0]} value={t[0]}>
                  {t[1]}
                </option>
              ))}
            </SelectField>,
            <div key="vendor_agreement">
              {this.state.agreement ? (
                <div>
                  <div class="field">
                    <label>Signed Vendor Agreement:</label>
                    <a class="link" href={this.state.agreement} target="_blank">
                      <i class="i-download" />&nbsp;Vendor Agreement
                    </a>
                    &nbsp;&nbsp;&nbsp;
                    <div class="link danger" onClick={changeAgreement}>
                      Change
                    </div>
                  </div>
                </div>
              ) : (
                <FileField label="Signed Vendor Agreement:" name="file_name" />
              )}
            </div>,
          ]}

          <button class="btn">Save</button>
        </Form>
      </BaseModal>
    );
  }
}

export function showEntity(collection) {
  openModal(<EditPublicFeatures collection={collection} model={this} />);
}

//Resources
const tranferToOptions = [
  ['Businesses', 'Third-party businesses'],
  ['Own Accounts', 'Own bank accounts'],
  ['Individuals', 'Individuals'],
];

//values might change in future
const featuresAkaMap = {
  marketplace: 'Marketplace',
  subscriptions: 'Subscriptions',
  virtual_accounts: 'Virtual Accounts',
};
