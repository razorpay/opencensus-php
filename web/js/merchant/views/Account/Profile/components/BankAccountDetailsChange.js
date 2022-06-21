import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import { bindActionCreators, compose } from 'redux';
import AsyncButton from 'react-async-button';

import ModalHeader from 'common/ui/ModalHeader';
import InputField from 'common/ui/Forms/InputField';
import FileUploadInputButton from 'common/ui/FileUpload/InputButton';
import Fieldset from 'common/ui/Forms/Fieldset';

import { required } from 'common/utils/validators';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import { isWebkit, validateBankDetails } from 'common/utils/rzp-utils';

export const BankVerificationErrors = {
  'KC03: Invalid Beneficiary Account Number or IFSC':
    "The bank account details you've provided are incorrect. Try again with another account.",
  'KC05: Account Blocked/Frozen':
    'The given bank account is either blocked or frozen. Try again with another account.',
  'KC06: NRE Account':
    'NRE accounts are currently not supported for payments.Try again with another account.',
  'KC07: Account Closed': 'The given bank account is closed. Try again with another account.',
  'KC27: Invalid Account':
    "The bank account details you've provided are incorrect. Try again with another account.",
  'KC40: Invalid Beneficiary IFSC Code or NBIN':
    "The bank account details you've provided are incorrect. Try again with another account.",
};

const reVerifyAccountNumber = (value, allValues) => {
  return value !== allValues.account_number ? "Bank Number doesn't match" : undefined;
};

/* Method to validate weather the entered IFSC code is a valid one or not */
const verifyIFSCCode = (value) => {
  return validateBankDetails(value, 'ifsc') ? undefined : 'Enter a Valid IFSC code';
};

/* Method to Validate the account Number Entry by Pattern */
const verifyAccountNumber = (value) => {
  return validateBankDetails(value, 'accNo') ? undefined : 'Enter a Valid Account Number';
};

/* Method to validate the benificiary name */
const validateBenificiaryName = (value) => {
  return validateBankDetails(value, 'name') ? undefined : "Enter a Valid Account Holder's Name";
};

const regText = 'Beneficiary name should be the same as a business name';
const unregText = 'Beneficiary name should be the same as your name in KYC documents';

class BankAccountDetailsChange extends Component {
  state = {
    file: null,
  };
  handleFileChange = (event) => {
    if (event) {
      this.setState({
        file: event.target.files[0],
      });
    }
  };

  handleSubmission = (body) => {
    if (!this.state.file) {
      this.props.showNotification({
        type: 'error',
        message: 'Please upload a valid Bank Account proof.',
      });
      return null;
    }

    body.address_proof_url = this.state.file;

    return this.props.onSave(body);
  };

  render() {
    const { user, handleSubmit, settlementConfig } = this.props;

    const isOnTemporaryHold = settlementConfig.data?.config?.features?.hold?.status;
    const temporaryHoldReason = settlementConfig.data?.config?.features?.hold?.reason;

    return (
      <div className="bank-details-change">
        <ModalHeader title="Change Bank Account Details" onCloseClick={this.props.closeModal} />
        <div className="modal-body bank-details-change-content">
          {isOnTemporaryHold && (
            <div className="temporary-hold-banner">
              <div className="pr-12">
                <i className="i i-triangle-alert alert-red" />
              </div>
              <div>
                <span className="pr-5">Your settlements have been put on hold due to</span>
                <strong className="pr-5">{temporaryHoldReason}</strong>
                <span>Please update alternate details to unblock settlements.</span>
              </div>
            </div>
          )}
          <form class="form-horizontal" onSubmit={handleSubmit(this.handleSubmission)}>
            <Fieldset>
              <div class="form-group">
                <label class="col-md-3 control-label label-required">Branch IFSC Code Here</label>
                <div class="col-md-9">
                  <Field
                    name="ifsc_code"
                    component={InputField}
                    class="form-control"
                    placeholder="IFSC Code of the Bank Branch"
                    autoFocus={true}
                    validate={[required(), verifyIFSCCode]}
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="col-md-3 control-label label-required">Bank Account Number</label>
                <div class="col-md-9">
                  <Field
                    name="account_number"
                    component={InputField}
                    class={`form-control ${isWebkit ? 'webkit-sec' : ''}`}
                    placeholder="Bank Account Number"
                    type={isWebkit ? 'text' : 'password'}
                    autoComplete="off"
                    validate={[required(), verifyAccountNumber]}
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
                    validate={[required(), reVerifyAccountNumber]}
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="col-md-3 control-label label-required">Beneficiary Name</label>
                <div class="col-md-9">
                  <Field
                    name="beneficiary_name"
                    component={InputField}
                    class="form-control"
                    placeholder="Account Holder Name"
                    validate={[required(), validateBenificiaryName]}
                  />
                  <small class="help-block">
                    <i class="i i-info-circle" />
                    <span>
                      {user.business_type == 2 || user.business_type == 11 ? unregText : regText}
                    </span>
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
                        Bank Account Statement (last three months or since opening of account) OR
                        cancelled cheque issued in the name of the registered business
                      </li>
                    </ul>
                  </span>
                  <FileUploadInputButton
                    accept="image/jpeg,image/png,application/pdf,application/x-pdf"
                    maxSize="8000000"
                    uploadedFileName={this.state.file}
                    onChange={(event) => {
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
                      pendingText="Saving..."
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

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    settlementConfig: state.settlement.config,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ ...ModalActions, ...NotificationsActions }, dispatch);
};

export default compose(
  reduxForm({
    form: 'changeBankAccountDetails',
  }),
  connect(mapStateToProps, mapDispatchToProps),
)(BankAccountDetailsChange);
