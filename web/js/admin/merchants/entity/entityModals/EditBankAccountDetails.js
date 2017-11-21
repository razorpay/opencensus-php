import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';
import { withRouter } from 'react-router-dom';
import { closeModal, notifyError, notifySuccess } from 'common/modal';
import { beneficiaryStateMap } from '../entity-resources';
import { isWorkflow } from 'util/index';
import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';
import { adminFetch, adminPost } from 'util/fetch';
import AsyncButton from 'ui/AsyncButton';

@withRouter
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
        if (isWorkflow(response, this.props.history)) {
          return;
        }

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
        if (data) {
          this.setState({ bankAccount: data });
        } else {
          closeModal();
        }
      })
      .catch(err => {
        notifyError(err);
      });
  }

  render() {
    const { bankAccount } = this.state;

    return (
      <BaseModal header="Edit Bank Account details">
        {!bankAccount ? (
          <div class="spinner center m-t" />
        ) : (
          <Form class="full-span full-elements" style={{ width: '600px' }}>
            <div class="m-b">
              <strong>
                Note: This is the current Bank Account where the settlements are
                being made.
              </strong>
            </div>

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

            <div class="m-t m-b" />
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
