import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';

import { create } from 'merchant/reducers/submerchant';
import { showNotification } from 'merchant_common/reducers/notifications';
import { closeModal } from 'merchant_common/reducers/modals';
import {
  createPartnerSubmerchantBatch as createBatch,
  validatePartnerSubmerchantBatch as validateBatch,
} from 'merchant/reducers/batches';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import RTracking from 'react-tracking';

import ModalHeader from 'common/ui/ModalHeader';
import InputField from 'common/ui/Forms/InputField';

import { required } from 'common/utils/validators';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import BatchValidate from 'merchant/containers/BatchNew/Validate';

import { trackAddNewMerchantEvents } from '../ga';

const gaEvents = setGaTrack('Dashboard - Partner Submerchant - BU');

@connect((state) => ({ ...state.session }), {
  create,
  showNotification,
  closeModal,
  createBatch,
  validateBatch,
})
@reduxForm({
  form: 'addMerchant',
})
@RTracking(() => window.rzpQ.component('AddMerchant'))
export default class AddMerchant extends Component {
  state = {
    file_id: '',
    bulkMode: false,
    bulkContactsCount: 0,
  };

  get sampleUrl() {
    return '/files/sample_submerchant_link.xlsx';
  }

  addNewMerchant = (params) => {
    const { user } = this.props;
    return this.props
      .create(params)
      .then((response) => {
        const { id } = response;
        this.props.showNotification({
          type: 'success',
          message: 'Submerchant created successfully',
        });
        this.props.closeModal();
        this.props.tracking.trackEvent(
          window.rzpQ.onbr().interaction('partnerships.add.submerchant', {
            partnerID: user.id,
            mid: id,
          }),
        );
        trackAddNewMerchantEvents('Submit Form');
      })
      .catch(({ errors }) => {
        this.props.tracking.trackEvent(
          window.rzpQ.onbr().interaction('partnerships.add.error', {
            partnerID: user.id,
            error: errors && errors[0],
          }),
        );
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  handleBatchCreate = () => {
    const { user } = this.props;
    gaEvents.trackUploadBatch('Partner submerchant');
    this.props.tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.add.multiple.upload.invite', {
        partnerID: user.id,
        contactsCount: this.state.bulkContactsCount,
      }),
    );
    return this.props
      .createBatch({
        file_id: this.state.file_id,
      })
      .then((response) => {
        this.props.showNotification({
          type: 'success',
          message:
            'Your file has been successfully processed. Status of account creation will be sent to you within 2 hours.',
        });
        this.props.closeModal();
      })
      .catch((error) => {
        this.props.showNotification({
          type: 'error',
          message: 'Failed to invite.',
        });
      });
  };

  onValidation = (response, name) => {
    const { user } = this.props;
    if (response && response.file_id) {
      this.setState({
        file_id: response.file_id,
        bulkContactsCount: response.processable_count || 0,
      });
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().interaction('partnerships.add.multiple.upload.success', {
          partnerID: user.id,
          contactsCount: response.processable_count || 0,
        }),
      );
    } else {
      this.setState({ file_id: '' });
    }
  };

  onValidationFail = (error) => {
    const { user } = this.props;
    this.props.tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.add.multiple.upload.error', {
        partnerID: user.id,
        error,
      }),
    );
  };

  handleModeChange = (mode) => {
    const { user } = this.props;
    if (mode === 'bulk') {
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().interaction('partnerships.add.multiple', {
          partnerID: user.id,
        }),
      );
      this.setState({ bulkMode: true });
    } else {
      this.setState({ bulkMode: false });
    }
  };

  componentDidMount() {
    trackAddNewMerchantEvents('Open Form');
  }

  render() {
    const { handleSubmit, user } = this.props;
    const emailMandatory = isEmailMandatory(user);
    const emailValidators = emailMandatory ? [required()] : [];

    return (
      <div class="partner-submerchant-modal">
        <ModalHeader title="Add New Accounts" onCloseClick={this.props.closeModal} />
        <div class="modal-body">
          <ul class="tab-headers">
            <li class={this.state.bulkMode ? '' : 'active'} onClick={this.handleModeChange}>
              Add an Account
            </li>
            <li
              class={this.state.bulkMode ? 'active' : ''}
              onClick={() => this.handleModeChange('bulk')}
            >
              Add Multiple Accounts
            </li>
          </ul>
          {/* Bulk start */}
          <div style={{ display: this.state.bulkMode ? 'block' : 'none' }}>
            <BatchValidate
              onValidation={this.onValidation}
              batchType={'partner_submerchant_invite'}
              batchTypeText={'text'}
              sampleUrl={this.sampleUrl}
              gaEvents={gaEvents}
              validateBatch={this.props.validateBatch}
              maxRows={500}
              maxFileSize={52428800}
              onFileRemove={this.onValidation}
              batchClass={'batch-upload-modal'}
              onValidationFail={this.onValidationFail}
            />
            {this.state.file_id ? (
              <div class="success-message">
                <p>
                  <img src="/dist/css/assets/check-round.svg" /> &nbsp;{' '}
                  {this.state.bulkContactsCount} contacts have been identified.
                </p>
                <span>
                  Email will be sent to {this.state.bulkContactsCount} identified contacts. Status
                  of account creation will be sent to your email address within 2 hours.
                </span>
                <div style={{ textAlign: 'right' }}>
                  <AsyncButton
                    type="button"
                    class={`btn btn-primary`}
                    text={`Invite ${this.state.bulkContactsCount} contacts`}
                    pendingText={`Inviting ${this.state.bulkContactsCount} contacts...`}
                    onClick={this.handleBatchCreate}
                  />
                </div>
              </div>
            ) : null}
          </div>
          <div class="add-single-block" style={{ display: this.state.bulkMode ? 'none' : 'block' }}>
            {/* Merchant Name */}
            <div class="form-group">
              <label class="label-required">Account Name</label>
              <Field
                name="name"
                component={InputField}
                class="form-control"
                autoFocus
                validate={required()}
              />
            </div>

            {/* Merchant Email */}
            <div class="form-group">
              <label class={emailMandatory ? 'label-required' : ''}>Email Address</label>
              <Field
                name="email"
                component={InputField}
                validate={emailValidators}
                placeholder={emailMandatory ? '' : 'Optional'}
                class="form-control"
              />
              <span class="help-block">The Razorpay sign-up link will be sent to this email.</span>

              {!emailMandatory && (
                <span class="help-block">
                  If no email is provided, your email will be mapped as the registered email ID of
                  this merchant.
                </span>
              )}
            </div>

            <div class="Modal__Actions clearfix">
              <AsyncButton
                class="btn btn-primary btn-block"
                text="Send Invite"
                pendingText="Inviting..."
                onClick={handleSubmit(this.addNewMerchant)}
              />
            </div>
          </div>
        </div>
      </div>
    );
  }

  componentWillUnmount() {
    trackAddNewMerchantEvents('Close Form');
  }
}

function isEmailMandatory(user) {
  if (user.isPartner('aggregator')) {
    return !showWhenUtil({ featureEnabled: 'allow_sub_without_email' });
  }
  return !user.isPartner('fully_managed');
}
