import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';

import ModalHeader from 'rzp/ui/ModalHeader';
import InputField from 'rzp/ui/Forms/InputField';
import FileUploadInputButton from 'rzp/ui/FileUpload/InputButton';
import Fieldset from 'rzp/ui/Forms/Fieldset';

import { required } from 'rzp/utils/validators';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';

import { isWebkit } from 'rzp/utils/rzp-utils';

const verifyAccountNumber = (value, allValues, props) => {
  return value !== allValues.account_number
    ? "Bank Number doesn't match"
    : undefined;
};

@connect(null, {
  ...ModalActions,
  ...NotificationsActions,
})
@reduxForm({
  form: 'changeBankAccountDetails',
})
export default class BandAccountDetailsChange extends Component {
  state = {
    addressProof: null,
  };
  handleFileChange = event => {
    if (event) {
      this.setState({
        file: event.target.files[0],
      });
    }
  };

  handleSubmission = body => {
    const { currentBankAccount } = this.props;

    if (!this.state.file) {
      this.props.showNotification({
        type: 'error',
        message: 'Please upload a valid Bank Account proof.',
      });
      return;
    }

    body.address_proof_url = this.state.file;

    return this.props.onSave(body);
  };

  render() {
    const { isBankAccountChangeAllowed, handleSubmit } = this.props;
    return (
      <div class="bank-details-change">
        <ModalHeader
          title="Change Bank Account Details"
          onCloseClick={this.props.closeModal}
        />
        <div class="modal-body bank-details-change-content">
          <form
            class="form-horizontal"
            onSubmit={handleSubmit(this.handleSubmission)}
          >
            <Fieldset>
              <div class="form-group">
                <label class="col-md-3 control-label label-required">
                  Branch IFSC Code
                </label>
                <div class="col-md-9">
                  <Field
                    name="ifsc_code"
                    component={InputField}
                    class="form-control"
                    placeholder="IFSC Code of the Bank Branch"
                    autoFocus={true}
                    validate={[required()]}
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="col-md-3 control-label label-required">
                  Bank Account Number
                </label>
                <div class="col-md-9">
                  <Field
                    name="account_number"
                    component={InputField}
                    class={`form-control ${isWebkit ? 'webkit-sec' : ''}`}
                    placeholder="Bank Account Number"
                    type={isWebkit ? 'text' : 'password'}
                    autoComplete="off"
                    validate={[required()]}
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="col-md-3 control-label label-required">
                  Re-enter Bank Account Number
                </label>
                <div class="col-md-9">
                  <Field
                    name="account_number_confirmation"
                    component={InputField}
                    class="form-control"
                    placeholder="Re-enter your Bank Account Number"
                    validate={[required(), verifyAccountNumber]}
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="col-md-3 control-label label-required">
                  Beneficiary Name
                </label>
                <div class="col-md-9">
                  <Field
                    name="beneficiary_name"
                    component={InputField}
                    class="form-control"
                    placeholder="Account Holder Name"
                    validate={[required()]}
                  />
                  <small class="help-block">
                    <i class="i i-info-circle" />
                    <span>Should be same as business/individual name</span>
                  </small>
                </div>
              </div>

              <div class="form-group">
                <label class="col-md-3 control-label label-required">
                  Company's Bank Account Statement with Address
                </label>
                <div class="col-md-9">
                  <span class="help-block">
                    Upload following:
                    <ul>
                      <li>
                        Bank Account Statement (last three months or since
                        opening of account) OR cancelled cheque issued in the
                        name of the registered business
                      </li>
                    </ul>
                  </span>
                  <FileUploadInputButton
                    accept="image/jpeg,image/png,application/pdf,application/x-pdf"
                    maxSize="8000000"
                    uploadedFileName={this.state.file}
                    onChange={event => {
                      this.handleFileChange(event);
                    }}
                  />
                </div>
              </div>
              <div class="form-group">
                <div class="col-md-offset-3 col-md-9">
                  <div class="btn-toolbar">
                    <AsyncButton
                      type="button"
                      class="btn btn-primary pull-right"
                      text="Save"
                      onClick={handleSubmit(this.handleSubmission)}
                    />
                  </div>
                </div>
              </div>
            </Fieldset>
          </form>
        </div>
      </div>
    );
  }
}
