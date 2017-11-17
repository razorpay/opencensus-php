import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';
import { toJS } from 'mobx';

import { closeModal, notifyError, notifySuccess } from 'common/modal';
import { adminFetch, adminPost } from 'util/fetch';
import { getRiskRating } from '../entity-resources';

import Form from 'ui/Form';
import Field, { SelectField, SwitchField } from 'ui/Field';
import MultiSelectField from 'ui//MultiSelectField';
import AsyncButton from 'ui/AsyncButton';
import Table from 'ui/Table';

export default class EditMerchant extends Component {
  constructor() {
    super();
    this.fetchGroups();
    this.state = {};
  }

  dropUnchangedFields(requestData) {
    const baseData = toJS(this.props.props.merchant.details);

    for (let key in requestData) {
      const valInBase = baseData[key];
      // Matching boolean values
      if (typeof valInBase === 'boolean') {
        if (
          (valInBase === true && requestData[key] == '1') ||
          (valInBase === false && requestData[key] == '0')
        ) {
          delete requestData[key];
        }
      }

      // Assuming it's admins/groups/transaction_email Array
      if (valInBase && typeof valInBase === 'object') {
        let isSame = false;

        let tempBaseVal = valInBase;
        if (typeof tempBaseVal[0] === 'object') {
          // If array of objects (eg- admins/groups)
          tempBaseVal = valInBase.map(item => item.id);
        }

        isSame =
          tempBaseVal.sort().join(',') === requestData[key].sort().join(',');

        if (isSame) {
          delete requestData[key];
        }
      } else if (valInBase == requestData[key]) {
        delete requestData[key];
      }
    }
  }

  handleConfirm = body => {
    body.groups = Object.keys(body.groups).filter(
      key => body.groups[key] === '1'
    );
    body.admins = body.admins.split(',');
    body.max_payment_amount *= 100;
    body.transaction_report_email = body.transaction_report_email.split(',');

    this.dropUnchangedFields(body);

    if (!Object.keys(body).length) {
      notifyError('Edit something before clicking Save');
      return;
    }

    return adminPost(
      body,
      '/admin/merchant/' + this.props.props.merchant.details.id + '/edit'
    )
      .then(data => {
        if (data) {
          closeModal();
          notifySuccess('Merchant edited successfully');
          this.props.props.updateDetails(data);
        }
      })
      .catch(err => {
        notifyError(err);
      });
  };

  fetchGroups() {
    adminFetch({
      route_name: 'group_get_multiple',
    })
      .then(data => {
        this.setState({ groups: data.items });
      })
      .catch(err => {
        notifyError(err);
      });
  }

  getGroupsFields(defaultSelectedGroups) {
    const selectedGroups = defaultSelectedGroups.map(group => group.id); // Shouldn't override new selection
    return [
      [
        'Select',
        item => (
          <input
            name={`groups[${item.id}]`}
            type="checkbox"
            defaultChecked={selectedGroups.indexOf(item.id) > -1}
          />
        ),
      ],
      ['Group', item => item.name],
      ['Group Description', item => item.description],
    ];
  }

  render() {
    const { adminsMap, details } = this.props.props.merchant;
    const adminsList = Object.keys(adminsMap).map(key => {
      let obj = adminsMap[key];
      obj.id = key;

      return obj;
    });

    return (
      <BaseModal header="Edit Merchant">
        <Form class="full-span full-elements" style={{ width: '650px' }}>
          <Field label="Name" name="name" defaultValue={details.name} />
          <Field label="MCC" name="category" defaultValue={details.category} />
          <Field
            label="Max Payment Amount (INR)"
            name="max_payment_amount"
            defaultValue={details.max_payment_amount / 100}
          />
          <SwitchField
            label="Marketplace Linked Account KYC"
            name="linked_account_kyc"
            defaultValue={details.linked_account_kyc ? '1' : '0'}
          />

          <SelectField
            name="category2"
            label="Category2"
            defaultValue={details.category2}
            required
          >
            <option value="">Select one of the below options</option>

            {Object.keys(category2Map).map(key => (
              <option key={key} value={key}>
                {category2Map[key]}
              </option>
            ))}
          </SelectField>

          <SelectField
            name="risk_rating"
            label="Risk"
            defaultValue={details.risk_rating}
            required
          >
            <option value="">Select one of the below options</option>

            {Object.keys(getRiskRating()).map(key => (
              <option key={key} value={key}>
                {getRiskRating(key)[0]}
              </option>
            ))}
          </SelectField>

          <Field
            label="Risk Threshold"
            name="risk_threshold"
            defaultValue={details.risk_threshold}
            type="number"
            min="5"
            max="20"
            placeholder="Valid range: 5 - 20"
          />

          <Field
            label="Website"
            name="website"
            defaultValue={details.website}
          />
          <Field
            label="Billing Label"
            name="billing_label"
            defaultValue={details.billing_label}
          />
          <Field
            label="Transaction Report Email"
            name="transaction_report_email"
            placeholder="Enter comma(,) separated emails"
            defaultValue={details.transaction_report_email[0]}
          />

          <SelectField
            name="fee_bearer"
            label="Fee Bearer"
            defaultValue={details.fee_bearer}
            required
          >
            <option value="platform">Platform</option>
            <option value="customer">Customer</option>
          </SelectField>

          <SelectField
            name="fee_model"
            label="Fee Model"
            defaultValue={details.fee_model}
            required
          >
            <option value="prepaid">Prepaid</option>
            <option value="postpaid">Postpaid</option>
          </SelectField>

          <div class="field">
            <label>Groups</label>
            <Table
              items={this.state.groups}
              fields={this.getGroupsFields(details.groups)}
            />
          </div>

          <MultiSelectField
            label="Admins"
            name="admins"
            options={adminsList}
            defaultValue={details.admins}
            trackBy="id"
            keys={['name', 'email']}
            placeholder="Select users"
          />

          <AsyncButton
            text="Cancel"
            class="btn btn-default"
            pendingClass="small spinner"
            onSubmit={closeModal}
          />
          <AsyncButton
            text="Save"
            class="btn"
            pendingClass="small spinner"
            onSubmit={this.handleConfirm}
          />
        </Form>
      </BaseModal>
    );
  }
}

/* Resources */
const category2Map = {
  lending: 'Lending',
  securities: 'Securities',
  commodities: 'Commodities',
  grocery: 'Grocery',
  ecommerce: 'Ecommerce',
  govt_education: 'Govt Education',
  pvt_education: 'Pvt Education',
  utilities: 'Utilities',
  corporate: 'Corporate',
  insurance: 'Insurance',
  housing: 'Housing',
  mutual_funds: 'Mutual funds',
  travel_agency: 'Travel Agency',
  pharma: 'Pharma',
  cryptocurrency: 'Cryptocurrency',
  forex: 'Forex',
  hospitality: 'Hospitality',
  logistics: 'Logistics',
  others: 'Others',
};
