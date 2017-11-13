import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';

import { closeModal, notifyError, notifySuccess } from 'common/modal';

import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';
import { adminFetch, adminPost } from 'util/fetch';
import AsyncButton from 'ui/AsyncButton';

export default class EditBankAccountDetails extends Component {
  state = { bankAccount: null };

  handleConfirm = body => {
    // Required fields
    body.beneficiary_email = this.state.bankAccount.beneficiary_email;
    body.beneficiary_mobile = this.state.bankAccount.beneficiary_mobile;

    return adminPost({
      route_name: 'merchant_add_bank_account',
      url_params: {
        id: this.props.props.merchant.details.id,
      },
      body,
    })
      .then(response => {
        closeModal();
        notifySuccess('Merchant bank details changed successfully');
      })
      .catch(err => {
        notifyError(err);
      });
  };

  componentWillMount() {
    adminFetch({
      route_name: 'merchant_fetch_bank_account',
      url_params: {
        id: this.props.props.merchant.details.id,
      },
    })
      .then(data => {
        this.setState({ bankAccount: data });
      })
      .catch(err => {
        notifyError(err);
      });
  }

  render() {
    const { bankAccount } = this.state;

    return (
      <BaseModal header="Edit Bank Account details">
        <span>
          <strong>
            Note: This is the current Bank Account where the settlements are
            being made.
          </strong>
        </span>

        {!bankAccount ? (
          <div class="spinner" />
        ) : (
          <Form>
            <Field
              label="Bank Account Number"
              name="account_number"
              defaultValue={bankAccount.account_number}
            />
            <Field
              label="Beneficiary Name"
              name="beneficiary_name"
              defaultValue={bankAccount.beneficiary_name}
              infoMsg="Should be same as business/individual name"
            />
            <Field
              label="Beneficiary Address Line 1"
              name="beneficiary_address1"
              defaultValue={bankAccount.beneficiary_address1}
              type="textarea"
              placeholder="Beneficiary Address Line 1"
              required
            />
            <Field
              label="Beneficiary Address Line 2"
              name="beneficiary_address2"
              defaultValue={bankAccount.beneficiary_address2}
              type="textarea"
              placeholder="Beneficiary Address Line 2"
            />
            <Field
              label="Beneficiary Address Line 3"
              name="beneficiary_address3"
              defaultValue={bankAccount.beneficiary_address3}
              type="textarea"
              placeholder="Beneficiary Address Line 3"
            />
            <Field
              label="Beneficiary Address City"
              name="beneficiary_city"
              defaultValue={bankAccount.beneficiary_city}
              type="textarea"
              placeholder="Beneficiary Address City"
              required
            />

            <SelectField
              name="beneficiary_state"
              label="Beneficiary Address State"
              defaultValue={bankAccount.beneficiary_state}
              required
            >
              <option value="? undefined:undefined ?" />
              {Object.keys(beneficiaryStateMap).map(key => (
                <option key={key} value={key}>
                  {beneficiaryStateMap[key]}
                </option>
              ))}
            </SelectField>

            <Field
              label="Beneficiary Address Pincode"
              name="beneficiary_pin"
              defaultValue={bankAccount.beneficiary_pin}
              type="textarea"
              placeholder="Beneficiary Address Pincode"
              required
            />
            <Field
              label="Branch IFSC Code"
              name="ifsc_code"
              defaultValue={bankAccount.ifsc_code}
              type="textarea"
              placeholder="IFSC Code of the Bank Branch"
              required
            />

            <AsyncButton
              text="Cancel"
              class="btn btn-default"
              pendingClass="small spinner"
              onSubmit={closeModal}
            />
            <AsyncButton
              text="Ok"
              class="btn"
              pendingClass="small spinner"
              onSubmit={this.handleConfirm}
            />
          </Form>
        )}
      </BaseModal>
    );
  }
}

/* Resources */
const beneficiaryStateMap = {
  AN: 'Andaman And Nicobar',
  AP: 'Andhra Pradesh',
  AR: 'Arunachal Pradesh',
  AS: 'Assam',
  BI: 'Bihar',
  CH: 'Chandigarh (UT)',
  CT: 'Chattisgarh',
  DN: 'Dadra And Nagar Haveli',
  DD: 'Daman And Diu (UT)',
  DL: 'Delhi',
  GO: 'Goa',
  GJ: 'Gujarat',
  HA: 'Haryana',
  HP: 'Himachal Pradesh',
  JK: 'Jammu And Kashmir',
  JH: 'Jharkhand',
  KA: 'Karnataka',
  KE: 'Kerala',
  MP: 'Madhya Pradesh',
  MH: 'Maharashtra',
  MA: 'Manipur',
  ME: 'Meghalaya',
  MI: 'Mizoram',
  NA: 'Nagaland',
  OR: 'Orissa',
  PO: 'Pondicherry(UT)',
  PB: 'Punjab',
  RJ: 'Rajasthan',
  SK: 'Sikkim',
  TG: 'Telangana',
  TN: 'Tamilnadu',
  TR: 'Tripura',
  UP: 'Uttar Pradesh',
  UT: 'Uttranchal',
  WB: 'West Bengal',
};
