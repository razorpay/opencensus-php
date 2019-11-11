import { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';

import RadioButton from 'rzp/ui/Forms/RadioButton';
import InputField from 'rzp/ui/Forms/InputField';

import { saveGST } from 'merchant/reducers/profile';
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
            <div class="section-title">GST Details:</div>
            {merchantGstin ? (
              <Fragment>
                <div class="m-t">
                  Your Business GSTIN:{' '}
                  <span class="section-title">{merchantGstin}</span>
                </div>
                <p class="help-block">
                  To update your GST details, please{' '}
                  <Link to="#ticket">write to support</Link>
                </p>
              </Fragment>
            ) : (
              <Fragment>
                <div class="m-t">No GSTIN added</div>
                <p class="help-block">
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
                Previous Step
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
