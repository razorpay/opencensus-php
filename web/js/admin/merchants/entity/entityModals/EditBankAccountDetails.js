import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';
import { closeModal, notifyError, notifySuccess } from 'common/modal';
import { beneficiaryStateMap } from '../entity-resources';
import { isWorkflow } from 'common/util';
import Form from 'ui/Form';
import Field, { SelectField } from 'ui/Field';
import { adminFetch, adminPost } from 'common/fetch';
import AsyncButton from 'ui/AsyncButton';

export default class EditBankAccountDetails extends Component {
  state = { bankAccount: null };

  handleConfirm = body => {
    // Required fields
    body.beneficiary_email = this.state.bankAccount.beneficiary_email;
    body.beneficiary_mobile = this.state.bankAccount.beneficiary_mobile;

    let isAllSame = true;
    for (let key in body) {
      if (body[key] !== this.state.bankAccount[key]) {
        isAllSame = false;
      }
    }
    if (isAllSame) {
      notifyError('Make some changes before save');
      return;
    }

    return adminPost({
      url: `live_${this.props.merchantId}/merchants/${
        this.props.merchantId
      }/bank_account`,
      data: body,
    })
      .then(response => {
        if (response) {
          closeModal();
          if (isWorkflow(response)) {
            notifySuccess('Workflow is created successfully.');
            return;
          }
          notifySuccess('Merchant bank details changed successfully');
        }
      })
      .catch(err => {
        notifyError(err);
      });
  };

  componentWillMount() {
    adminFetch(`live/merchants/${this.props.merchantId}/bank_account`)
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
        <Form class="full-span full-elements" style={{ width: '600px' }}>
          {!bankAccount ? (
            <div class="spinner center m-t" />
          ) : (
            <div>
              <div class="m-b">
                <strong>
                  Note: This is the current Bank Account where the settlements
                  are being made.
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
            </div>
          )}
        </Form>
      </BaseModal>
    );
  }
}
