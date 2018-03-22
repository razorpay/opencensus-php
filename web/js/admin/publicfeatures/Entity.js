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
import Table from 'ui/Table';
import { statusPill } from 'common/data';

import {
  adminFetch,
  adminPost,
  adminPut,
  adminFormUpload,
  adminPatch,
} from 'common/fetch';
import { snakeToTitleCase, formatDate } from 'common/util';
@observer
export default class EditPublicFeatures extends Component {
  state = {
    pending: true,
    agreement: null,
    statusLogs: [],
  };

  akaFeature = this.props.model.name;

  selectedStatus = this.props.model.status;

  // TODO: TEST Sending mode as live, not sent earlier
  featureRequestUrls = [
    `live/merchant/requests/${this.props.model.id}`,
    `live/merchant/requests/${this.props.model.id}/status_log`,
    'live/merchant/requests/rejection_reasons',
  ];

  componentWillMount() {
    let requests = [];

    requests = this.featureRequestUrls.map(url =>
      adminFetch({
        url,
      })
    );

    Promise.all(requests).then(([feature, statusLogs, allRejectionReasons]) => {
      if (feature) {
        this.submissions = feature.submissions;
        this.setState({ pending: false });
        if (feature.name === 'marketplace') {
          this.setState({
            agreement: feature.submissions.vendor_agreement,
          });
        }
      }

      if (statusLogs.items) {
        this.setState({
          statusLogs: statusLogs.items,
        });
      }
    });
  }

  changeAgreement = () => {
    this.setState({ agreement: null });
  };

  save = body => {
    let { akaFeature, selectedStatus } = this;

    return adminPatch({
      url: `live/merchant/requests/${this.props.model.id}`,
      data: body,
    }).then(response => {
      if (response) {
        //TODO: update collection for the list view
        notifySuccess('Submission edited successfully.');
        closeModal();
      }
    });
  };

  render() {
    let { merchant_id, name } = this.props.model;
    let {
      selectedStatus,
      akaFeature,
      submissions,
      changeAgreement,
      save,
    } = this;

    return (
      <BaseModal header="Edit Submission">
        <Form class="full-span full-elements" onSubmit={save}>
          {this.state.pending ? (
            <div class="spinner center" />
          ) : (
            <div>
              <div class="field">
                <label>Merchant ID</label>
                <code>{merchant_id}</code>
              </div>
              <div class="field">
                <label>Feature</label>
                <code>{name}</code>
              </div>
              <SelectField
                label="Status"
                name="status"
                defaultValue={selectedStatus}
              >
                {publicFeatureStatuses.map(status => (
                  <option value={status} key={status}>
                    {snakeToTitleCase(status)}
                  </option>
                ))}
              </SelectField>
              {featuresAkaMap[akaFeature] === featuresAkaMap.marketplace ||
              featuresAkaMap[akaFeature] === featuresAkaMap.virtual_accounts ? (
                <TextAreaField
                  label="Use Case"
                  name="submissions[use_case]"
                  defaultValue={submissions.use_case}
                />
              ) : null}

              {featuresAkaMap[akaFeature] ===
                featuresAkaMap.virtual_accounts && (
                <Field
                  label="Expected Monthly Revenue"
                  type="number"
                  name="submissions[expected_monthly_revenue]"
                  defaultValue={submissions.expected_monthly_revenue}
                />
              )}

              {featuresAkaMap[akaFeature] === featuresAkaMap.subscriptions && [
                <TextAreaField
                  label="Business Model"
                  name="submissions[business_model]"
                  key="business_model"
                  defaultValue={submissions.business_model}
                />,
                <TextAreaField
                  label="Subscription Plans"
                  name="submissions[sample_plans]"
                  key="sample_plans"
                  defaultValue={submissions.sample_plans}
                />,
                <TextAreaField
                  label="Website Details"
                  name="submissions[website_details]"
                  key="website_details"
                  defaultValue={submissions.website_details}
                />,
              ]}

              {featuresAkaMap[akaFeature] === featuresAkaMap.marketplace && [
                <SelectField
                  label="Transferring to"
                  name="submissions[settling_to]"
                  key="settling_to"
                  defaultValue={submissions.settling_to}
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
                        <a
                          class="link"
                          href={this.state.agreement}
                          target="_blank"
                        >
                          <i class="i-download" />&nbsp;Vendor Agreement
                        </a>
                        &nbsp;&nbsp;&nbsp;
                        <div class="link danger" onClick={changeAgreement}>
                          Change
                        </div>
                      </div>
                    </div>
                  ) : (
                    <FileField
                      label="Signed Vendor Agreement:"
                      name="file_name"
                    />
                  )}
                </div>,
              ]}
              <Table items={this.state.statusLogs} fields={statusLogsFields} />

              <button class="btn">Save</button>
            </div>
          )}
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
export const featuresAkaMap = {
  marketplace: 'Marketplace',
  subscriptions: 'Subscriptions',
  virtual_accounts: 'Virtual Accounts',
};

const publicFeatureStatuses = [
  'under_review',
  'needs_clarification',
  'activated',
  'rejected',
];

const statusLogsFields = [
  ['Created At', item => formatDate(item.created_at)],
  ['Activation Status', item => statusPill(item.name)],
];
