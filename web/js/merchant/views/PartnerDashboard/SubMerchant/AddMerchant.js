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

import { required, email, isEmail } from 'common/utils/validators';
import ShowWhen, { showWhenUtil } from 'merchant/components/ShowWhen';
import BatchValidate from 'merchant/containers/BatchNew/Validate';

import { trackAddNewMerchantEvents } from '../ga';
import SelectBox from 'merchant/views/PartnerDashboard/SubMerchant/components/SelectBox';
import Button from 'common/new-ui/Button';
import SocialShareGroup from 'merchant/views/PartnerDashboard/SubMerchant/components/SocialShareGroup';
import { merchantFetch } from 'merchant/utils/ajax';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

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
  constructor(props) {
    super(props);
    const state = {
      file_id: '',
      bulkMode: false,
      bulkContactsCount: 0,
      step: 1,
      merchantType: '',
      merchantEmail: '',
      merchantName: '',
      referralData: '',
      isFormValid: false,
    };
    if (!props.user.isPartnershipForXEnabled) {
      state.step = 2;
      state.merchantType = PRODUCT_TYPE.PG;
    }
    this.state = state;
  }

  get sampleUrl() {
    return '/files/sample_submerchant_link.xlsx';
  }

  getModalHeaderText = () => {
    switch (this.state.step) {
      case 1:
        return 'Add New Merchants';
      case 2:
        return this.state.merchantType === PRODUCT_TYPE.X
          ? 'Add New Merchants - RazorpayX'
          : 'Add New Merchants - Razorpay Payments';
      case 3:
        return 'Merchant Added Successfully';
      default:
        return 'Add New Merchants';
    }
  };

  fetchReferralURL = () => {
    if (this.state.referralData === '') {
      merchantFetch({
        url: 'merchant/referral',
        mode: 'live',
        method: 'post',
        data: {},
      })
        .then(({ data }) => {
          this.setState({
            referralData: data.referrals,
          });
        })
        .catch(() => {});
    }
  };

  addNewMerchant = (params) => {
    this.trackUserEvent('partnerships.submerchant.add.product_group.single.action', {
      action: 'Send Invite',
    });
    const { user } = this.props;
    this.fetchReferralURL();
    return this.props
      .create({
        ...params,
        product: this.state.merchantType,
      })
      .then((response) => {
        const { id } = response;
        this.setState((prevState) => ({
          step: prevState.step + 1,
          merchantEmail: params.email,
        }));
        this.props.tracking.trackEvent(
          window.rzpQ.onbr().interaction('partnerships.submerchant.add.submerchant', {
            partnerID: user.id,
            mid: id,
          }),
        );
        trackAddNewMerchantEvents('Submit Form');
      })
      .catch(({ errors }) => {
        this.trackUserEvent('partnerships.submerchant.add.product_group.single.action', {
          action: 'Send Invite',
          error: errors && errors[0],
        });
        this.props.tracking.trackEvent(
          window.rzpQ.onbr().interaction('partnerships.submerchant.add.error', {
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
      window.rzpQ.onbr().interaction('partnerships.submerchant.add.multiple.upload.invite', {
        partnerID: user.id,
        contactsCount: this.state.bulkContactsCount,
      }),
    );
    return this.props
      .createBatch({
        file_id: this.state.file_id,
        config: {
          product: this.state.merchantType,
        },
      })
      .then((_response) => {
        this.trackUserEvent('partnerships.submerchant.add.product_group.multiple.upload', {
          Action: 'Invite',
          success: this.state.bulkContactsCount,
        });
        this.trackUserEvent('partnerships.submerchant.add.product_group.multiple.invite', {
          success: this.state.bulkContactsCount,
        });
        this.props.showNotification({
          type: 'success',
          message:
            'Your file has been successfully processed. Status of account creation will be sent to you within 2 hours.',
        });
        this.props.closeModal();
      })
      .catch((error) => {
        this.trackUserEvent('partnerships.submerchant.add.product_group.multiple.upload', {
          Action: 'Invite',
          error: error && error[0],
        });
        this.trackUserEvent('partnerships.submerchant.add.product_group.multiple.invite', {
          error: error && error[0],
        });
        this.props.showNotification({
          type: 'error',
          message: 'Failed to invite.',
        });
      });
  };

  onValidation = (response, _name) => {
    const { user } = this.props;
    if (response && response.file_id) {
      this.setState({
        file_id: response.file_id,
        bulkContactsCount: response.processable_count || 0,
      });
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().interaction('partnerships.submerchant.add.multiple.upload.success', {
          partnerID: user.id,
          contactsCount: response.processable_count || 0,
        }),
      );
    } else {
      this.setState({ file_id: '' });
      this.trackUserEvent('partnerships.submerchant.add.product_group.multiple.upload', {
        Action: 'Cancel the uploaded file',
      });
    }
  };

  onValidationFail = (error) => {
    const { user } = this.props;
    this.props.tracking.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.submerchant.add.multiple.upload.error', {
        partnerID: user.id,
        error,
      }),
    );
  };

  handleModeChange = (mode) => {
    const { user } = this.props;
    if (mode === 'bulk') {
      this.trackUserEvent('partnerships.submerchant.add.product_group.multiple');
      this.props.tracking.trackEvent(
        window.rzpQ.onbr().interaction('partnerships.submerchant.add.multiple', {
          partnerID: user.id,
        }),
      );
      this.setState({ bulkMode: true });
    } else {
      this.setState({ bulkMode: false });
    }
  };

  getCurrentProduct = () => {
    const { merchantType } = this.state;
    if (merchantType === PRODUCT_TYPE.PG) {
      return 'Payments';
    }
    if (merchantType === PRODUCT_TYPE.X) {
      return 'X';
    }
    return '';
  };

  trackUserEvent = (eventName, properties = {}) => {
    const { user, tracking } = this.props;
    const productGroup = this.getCurrentProduct();
    tracking.trackEvent(
      window.rzpQ.onbr().interaction(eventName, {
        partnerID: user.id,
        productGroup,
        ...properties,
      }),
    );
  };

  eventAddNewMerchant = () => {
    this.trackUserEvent('partnerships.submerchant.add.product_group.single');
  };

  handleNextClick = () => {
    this.setState((prevState) => ({ step: prevState.step + 1 }));

    const { step } = this.state;
    if (step === 1) {
      this.trackUserEvent('partnerships.submerchant.add.product_group.next');
      this.eventAddNewMerchant();
    }
  };

  handleBackClick = () => {
    this.setState((prevState) => ({ step: prevState.step - 1 }));
  };

  handleFormChange = (e) => {
    let { merchantName, merchantEmail } = this.state;
    const { name: FieldName, value } = e.target;
    switch (FieldName) {
      case 'name':
        this.setState({
          merchantName: value,
        });
        merchantName = value;
        break;
      case 'email':
        this.setState({
          merchantEmail: value,
        });
        merchantEmail = value;
        break;
      default:
        console.warn('incorrect field name');
    }
    let isEmailValid;
    if (isEmailMandatory(this.props.user)) {
      isEmailValid = merchantEmail && isEmail(merchantEmail);
    } else {
      isEmailValid = true;
    }
    const isFormValid = merchantName && isEmailValid;
    this.setState({
      isFormValid,
    });
  };

  handleFormFocus = (e) => {
    const { name } = e.target;
    if (this.state.step === 2) {
      this.trackUserEvent('partnerships.submerchant.add.product_group.single.action', {
        action: name,
      });
    }
  };

  handleFormFocus = (e) => {
    const { name } = e.target;
    this.trackUserEvent('partnerships.submerchant.add.product_group.single.action', {
      action: name,
    });
  };

  componentDidMount() {
    trackAddNewMerchantEvents('Open Form');
    if (!this.props.user.isPartnershipForXEnabled) {
      this.eventAddNewMerchant();
    }
  }

  sampleFileDownloadAnalytics = () => {
    this.trackUserEvent('partnerships.submerchant.add.product_group.multiple.action', {
      action: 'Download Sample file',
    });
  };

  clickToUploadAnalytics = () => {
    this.trackUserEvent('partnerships.submerchant.add.product_group.multiple.action', {
      action: 'Click to upload',
    });
  };

  modalCloseClick = () => {
    this.props.closeModal();
    const { step, bulkMode } = this.state;
    if (step === 1) {
      this.trackUserEvent('partnerships.submerchant.add.product_group', {
        action: 'cancel',
      });
    }
    if (step === 2) {
      if (!bulkMode) {
        // Add a Account Tab
        this.trackUserEvent('partnerships.submerchant.add.product_group.single.action', {
          action: 'cancel',
        });
      } else {
        // Add Multiple Account Tab
        this.trackUserEvent('partnerships.submerchant.add.product_group.multiple.action', {
          action: 'cancel',
        });
      }
    }
    if (step === 3) {
      // merchant added successfully
      this.trackUserEvent('partnerships.submerchant.add.product_group.social.cancel');
    }
  };

  render() {
    const { handleSubmit, user } = this.props;
    const emailMandatory = isEmailMandatory(user);
    const emailValidators = emailMandatory ? [required(), email()] : [];
    const referralUrl = this.state.referralData
      ? this.state.referralData[this.state.merchantType]?.url
      : '';

    return (
      <div class="partner-submerchant-modal">
        <ModalHeader title={this.getModalHeaderText()} onCloseClick={this.modalCloseClick} />
        <div class="modal-body">
          <ShowWhen additionalCondition={() => this.state.step === 1}>
            <div className="step">
              <SelectBox
                label="Razorpay Payments"
                description="Refer merchants to Razorpay Payment gateway and other products to receive payments"
                onClick={() => {
                  this.setState({ merchantType: PRODUCT_TYPE.PG });
                  this.trackUserEvent('partnerships.submerchant.add.product_group', {
                    productGroup: 'Payments',
                  });
                }}
                checked={this.state.merchantType === PRODUCT_TYPE.PG}
              />
              <SelectBox
                label="RazorpayX"
                description="Refer merchants to RazorpayX products like Current account to process payouts"
                onClick={() => {
                  this.setState({ merchantType: PRODUCT_TYPE.X });
                  this.trackUserEvent('partnerships.submerchant.add.product_group', {
                    productGroup: 'X',
                  });
                }}
                checked={this.state.merchantType === PRODUCT_TYPE.X}
              />
              <div style={{ textAlign: 'right', marginTop: '25px' }}>
                <Button.Primary
                  onClick={this.handleNextClick}
                  disabled={this.state.merchantType === ''}
                  iconAfter="arrow-forward"
                >
                  Next
                </Button.Primary>
              </div>
            </div>
          </ShowWhen>
          <ShowWhen additionalCondition={() => this.state.step === 2}>
            <div className="step">
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
              <ShowWhen additionalCondition={() => this.state.bulkMode}>
                <div>
                  <BatchValidate
                    sampleFileDownloadAnalytics={this.sampleFileDownloadAnalytics}
                    clickToUploadAnalytics={this.clickToUploadAnalytics}
                    onValidation={this.onValidation}
                    batchType="partner_submerchant_invite"
                    batchTypeText="text"
                    sampleUrl={this.sampleUrl}
                    gaEvents={gaEvents}
                    validateBatch={this.props.validateBatch}
                    maxRows={500}
                    maxFileSize={52428800}
                    onFileRemove={this.onValidation}
                    batchClass="batch-upload-modal"
                    onValidationFail={this.onValidationFail}
                  />
                  {this.state.file_id ? (
                    <div class="success-message">
                      <p>
                        <img src="/dist/css/assets/check-round.svg" /> &nbsp;{' '}
                        {this.state.bulkContactsCount} contacts have been identified.
                      </p>
                      <span>
                        Email will be sent to {this.state.bulkContactsCount} identified contacts.
                        Status of account creation will be sent to your email address within 2
                        hours.
                      </span>
                      <div style={{ textAlign: 'right' }}>
                        <AsyncButton
                          type="button"
                          class="btn btn-primary"
                          text={`Invite ${this.state.bulkContactsCount} contacts`}
                          pendingText={`Inviting ${this.state.bulkContactsCount} contacts...`}
                          onClick={this.handleBatchCreate}
                        />
                      </div>
                    </div>
                  ) : null}
                </div>
              </ShowWhen>

              <ShowWhen additionalCondition={() => !this.state.bulkMode}>
                <div class="add-single-block">
                  {/* Merchant Name */}
                  <div class="form-group">
                    <label class="label-required">Account Name</label>
                    <Field
                      name="name"
                      component={InputField}
                      class="form-control"
                      autoFocus
                      validate={required()}
                      onChange={this.handleFormChange}
                      onFocus={this.handleFormFocus}
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
                      onChange={this.handleFormChange}
                      onFocus={this.handleFormFocus}
                    />
                    <span class="help-block">
                      The Razorpay sign-up link will be sent to this email.
                    </span>

                    {!emailMandatory && (
                      <span class="help-block">
                        If no email is provided, your email will be mapped as the registered email
                        ID of this merchant.
                      </span>
                    )}
                  </div>

                  <div class="Modal__Actions clearfix" style={{ textAlign: 'right' }}>
                    <ShowWhen
                      additionalCondition={(currentUser) => currentUser.isPartnershipForXEnabled}
                    >
                      <Button.Transparent
                        onClick={this.handleBackClick}
                        style={{ marginRight: '14px' }}
                      >
                        Back
                      </Button.Transparent>
                    </ShowWhen>
                    <AsyncButton
                      class="btn btn-primary"
                      text="Send Invite"
                      pendingText="Inviting..."
                      onClick={handleSubmit(this.addNewMerchant)}
                      disabled={!this.state.isFormValid}
                    />
                  </div>
                </div>
              </ShowWhen>
            </div>
          </ShowWhen>
          <ShowWhen additionalCondition={() => this.state.step === 3}>
            <div className="step merchant-added-container">
              <div className="success-container">
                <div className="left-icon-container">
                  <i className="i i-done ModeIndicator--live-icon" />
                </div>
                <div className="text-container">
                  <div>
                    <span className="success-text">
                      A signup link has been sent to the following email
                    </span>
                  </div>
                  <div>
                    <span className="merchant-email">{this.state.merchantEmail}</span>
                  </div>
                </div>
              </div>
              <div className="social-share-container">
                <div className="social-share-text">
                  <span>You can also copy and share the link via other mediums</span>
                </div>
                <SocialShareGroup
                  referralUrl={referralUrl}
                  tracking={this.props.tracking}
                  product={this.state.merchantType}
                  partnerID={this.props.user.id}
                />
              </div>
            </div>
          </ShowWhen>
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
