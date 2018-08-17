import { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';

import RadioButton from 'rzp/ui/Forms/RadioButton';
import InputField from 'rzp/ui/Forms/InputField';

import { saveGST } from 'merchant/modules/profile';
import { closeModal } from 'rzp/modules/modals';
import LocalStorageService from 'rzp/utils/localStorage';
import * as NotificationsActions from 'rzp/modules/notifications';

const selector = formValueSelector('gstStepOnboarding');

@connect(null, {
  closeModal,
  ...NotificationsActions,
})
export default class GSTStepOnboarding extends Component {
  successNotification = () => {
    this.props.closeModal();
    this.props.showNotification({
      type: 'success',
      message:
        'Invoices configured successfully. Start creating your first Invoice.',
    });
    this.props.onStart();
  };

  render() {
    const { merchantGstin, onSwitchStep, handleSubmit } = this.props;

    return (
      <form autoComplete="off">
        <div class="row">
          <div class="col-md-12">
            <small class="help-block">STEP 2/2</small>
            <span class="section-title" style={{ display: 'inline-block' }}>
              GST Details:
            </span>
            {merchantGstin ? (
              <Fragment>
                <div class="m-t">
                  Your Business GSTIN:{' '}
                  <span class="section-title">{merchantGstin}</span>
                </div>
                <p class="help-block p-t">
                  To update your GST details, reach out to us at{' '}
                  <a href="mailto:support@razorpay.com">support@razorpay.com</a>
                </p>
              </Fragment>
            ) : (
              <Fragment>
                <div class="m-t">No GSTIN added</div>
                <p class="help-block p-t">
                  You can still create Non-GST invoices. For GST Invoices, you
                  can add your GSTIN later from Invoice Settings.
                </p>
              </Fragment>
            )}
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <div class="Modal__actions">
              <button
                class="btn btn-default m-r"
                onClick={e => onSwitchStep(e, 0)}
              >
                Previos Step
              </button>
              <AsyncButton
                type="submit"
                class="btn btn-primary"
                text={`Start Creating ${
                  merchantGstin ? '' : 'Non-'
                }GST Invoices`}
                pendingText="Saving..."
                onClick={this.successNotification}
              />
            </div>
          </div>
        </div>{' '}
      </form>
    );
  }
}
