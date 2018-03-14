import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';

import ModalHeader from 'rzp/ui/ModalHeader';
import InputField from 'rzp/ui/Forms/InputField';
import FileUploadInputButton from 'rzp/ui/FileUpload/InputButton';

import { required } from 'rzp/utils/validators';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';

const verifyAccountNumber = (value, allValues, props) => {
  return value !== allValues.bank_account_number
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
  render() {
    const { isBankAccountChangeAllowed, handleSubmit, onSave } = this.props;
    return (
      <div class="bank-details-change">
        <ModalHeader
          title="Change Bank Account Details"
          onCloseClick={this.props.closeModal}
        />
        <div class="modal-body bank-details-change-content">
          <form class="form-horizontal" onSubmit={handleSubmit(onSave)}>
            <div class="form-group">
              <label class="col-md-3 control-label label-required">
                Branch IFSC Code
              </label>
              <div class="col-md-9">
                <Field
                  name="bank_branch_ifsc"
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
                  name="bank_account_number"
                  component={InputField}
                  class={`form-control ${this.isWebkit ? 'webkit-sec' : ''}`}
                  placeholder="Bank Account Number"
                  type={this.isWebkit ? 'text' : 'password'}
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
                  name="bank_account_number_confirmation"
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
                  name="bank_account_name"
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

            {/* TODO: change texts here */}
            <div class="form-group">
              <label class="col-md-3 control-label label-required">
                Bank Account Change Proof
              </label>
              <div class="col-md-9">
                <FileUploadInputButton
                  accept="image/jpeg,image/png,application/pdf,application/x-pdf"
                  uploadedFileName={'SomeProff'}
                  maxSize="8000000"
                  onChange={() => {}}
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
                    onClick={handleSubmit(onSave)}
                  />
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    );
  }
}
