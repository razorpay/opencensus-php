import React, { Component } from 'react';
import { ModalContent } from 'component/Modal';
import { toJS, observable } from 'mobx';
import { observer } from 'mobx-react';
import { closeModal, notifyError, notifySuccess } from 'common/modal';
import fetch, { adminFetch, adminPost } from 'common/fetch';
import { validateMultipleEmails } from 'rzp/utils/validators';
import { getRiskRating } from '../entity-resources';
import ShowWhen from 'admin/components/ShowWhen';
import Form from 'ui/Form';
import Field, { SelectField, SwitchField } from 'ui/Field';
import MultiSelectField from 'ui/MultiSelectField';
import AsyncButton from 'ui/AsyncButton';
import Table from 'ui/Table';

@observer
export default class EditMerchant extends Component {
  allGroups = observable.map();
  selectedGroups = observable.map();

  constructor() {
    super();
    this.fetchGroups();
    this.state = {};
  }

  componentWillMount() {
    if (this.props.props) {
      let { groups } = this.props.props.merchant.details;
      groups.forEach(group => this.selectedGroups.set(group.id, true));
    }
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
    let auto_refund_delay = [];
    body.groups = this.selectedGroups.keys();
    if (body.admins) {
      body.admins = body.admins.split(',');
    }
    body.max_payment_amount *= 100;

    // validation all the transaction report emails and removing spaces if any present
    body.transaction_report_email = body.transaction_report_email
      ? body.transaction_report_email.replace(' ', '').split(',')
      : [];
    if (!validateMultipleEmails(body.transaction_report_email)) {
      notifyError('Please ensure all transaction report emails are valid');
      return;
    }

    if (body.auto_refund_delay_val || body.auto_refund_delay_type) {
      body.auto_refund_delay = `${body.auto_refund_delay_val} ${
        body.auto_refund_delay_type
      }`;
    }

    body.convert_currency = body.convert_currency || null;

    this.dropUnchangedFields(body);

    delete body.auto_refund_delay_type;
    delete body.auto_refund_delay_val;

    if (!Object.keys(body).length) {
      notifyError('Edit something before clicking Save');
      return;
    }

    return fetch({
      url: '/admin/api/live/merchants/' + this.props.merchantId,
      method: 'put',
      data: body,
      headers: {
        'Content-Type': 'application/json',
      },
    })
      .then(data => {
        if (data) {
          closeModal();
          notifySuccess('Merchant edited successfully');

          this.props.props.setAutoRefundDelay(data);
          this.props.props.updateDetails(data);
        }
      })
      .catch(err => {
        notifyError(err);
      });
  };

  fetchGroups() {
    adminFetch('live/groups')
      .then(data => {
        data.items.forEach(group => this.allGroups.set(group.id, group));
      })
      .catch(err => {
        notifyError(err);
      });
  }

  toggleGroupSelect = groupId => {
    let { selectedGroups, allGroups } = this;
    let isThere = selectedGroups.has(groupId);

    if (isThere) {
      selectedGroups.delete(groupId);
    } else {
      selectedGroups.set(groupId, allGroups.get(groupId));
    }
  };

  toggleGroupAllSelect = e => {
    let { selectedGroups, allGroups } = this;
    selectedGroups.clear();
    if (e.target.checked) {
      allGroups.entries().forEach(g => selectedGroups.set(g[0], g[1]));
    }
  };

  getGroupsFields = () => {
    const {
      selectedGroups,
      allGroups,
      toggleGroupAllSelect,
      toggleGroupSelect,
    } = this;
    let isAllChecked = selectedGroups.size === allGroups.size;

    return [
      [
        <input
          type="checkbox"
          checked={isAllChecked}
          onChange={toggleGroupAllSelect}
        />,
        item => (
          <input
            type="checkbox"
            checked={selectedGroups.has(item.id)}
            onChange={() => toggleGroupSelect(item.id)}
          />
        ),
      ],
      ['Group', item => item.name],
      ['Group Description', item => item.description],
    ];
  };

  render() {
    const { adminsMap, details } = this.props.props.merchant;
    const { allGroups } = this;
    const adminsList = Object.keys(adminsMap).map(key => {
      let obj = adminsMap[key];
      obj.id = key;

      return obj;
    });

    return (
      <ModalContent header="Edit Merchant">
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
            label="Website"
            name="website"
            defaultValue={
              details.website
                ? details.website
                : details.merchant_details.business_website
            }
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
            defaultValue={
              details.transaction_report_email instanceof Array
                ? details.transaction_report_email.join(',')
                : ''
            }
            required
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

          <div class="field multi">
            <label>Auto Refund Delay</label>
            <input
              name="auto_refund_delay_val"
              defaultValue={details.auto_refund_delay_val || 5}
            />
            <select
              name="auto_refund_delay_type"
              defaultValue={details.auto_refund_delay_type || 'days'}
            >
              <option value="days">Days</option>
              <option value="hours">Hours</option>
              <option value="mins">Minutes</option>
            </select>
          </div>

          <SwitchField
            label="Auto Capture Late Auth"
            name="auto_capture_late_auth"
            defaultValue={details.auto_capture_late_auth ? '1' : '0'}
          />

          <SelectField
            name="convert_currency"
            label="Convert Currency"
            defaultValue={details.convert_currency === false ? '0' : ''}
          >
            <option value="">Disabled</option>
            <option value="0">Enabled</option>
          </SelectField>

          <div class="field">
            <label>Groups</label>
            <Table
              items={allGroups.entries().map(g => g[1])}
              fields={this.getGroupsFields()}
            />
          </div>

          {user.permissions.find(perm => perm === 'view_all_admin') && (
            <MultiSelectField
              label="Admins"
              name="admins"
              options={adminsList}
              defaultValue={details.admins}
              trackBy="id"
              keys={['name', 'email']}
              placeholder="Select users"
            />
          )}

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
      </ModalContent>
    );
  }
}

/* Resources */
const category2Map = {
  commodities: 'Commodities',
  corporate: 'Corporate',
  cryptocurrency: 'Cryptocurrency',
  ecommerce: 'Ecommerce',
  government: 'Government',
  govt_education: 'Govt Education',
  grocery: 'Grocery',
  housing: 'Housing',
  forex: 'Forex',
  hospitality: 'Hospitality',
  insurance: 'Insurance',
  lending: 'Lending',
  logistics: 'Logistics',
  mutual_funds: 'Mutual funds',
  others: 'Others',
  pharma: 'Pharma',
  pvt_education: 'Pvt Education',
  securities: 'Securities',
  travel_agency: 'Travel Agency',
  utilities: 'Utilities',
};
