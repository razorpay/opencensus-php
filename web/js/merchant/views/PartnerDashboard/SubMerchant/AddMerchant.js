import { Component } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
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
import rTracking from 'react-tracking';

import ModalHeader from 'common/ui/ModalHeader';
import InputField from './components/InputField';

import { required, email, isEmail, isMobile, maxLength } from 'common/utils/validators';
import ShowWhen, { showWhenUtil } from 'merchant/components/ShowWhen';
import BatchValidate from 'merchant/containers/BatchNew/Validate';
import { withRouter } from 'react-router-dom';

import { trackAddNewMerchantEvents } from '../ga';
import SelectBox from 'merchant/views/PartnerDashboard/SubMerchant/components/SelectBox';
import Button from 'common/new-ui/Button';
import SocialShareGroup from 'merchant/views/PartnerDashboard/SubMerchant/components/SocialShareGroup';
import { merchantFetch } from 'merchant/utils/ajax';
import { PRODUCT_TYPE, ADD_MODE } from 'merchant/views/PartnerDashboard/constants';

const gaEvents = setGaTrack('Dashboard - Partner Submerchant - BU');

class AddMerchant extends Component {
  constructor(props) {
    super(props);
    const state = {
      file_id: '',
      addMode: ADD_MODE.single,
      bulkContactsCount: 0,
      step: 1,
      merchantType: PRODUCT_TYPE.PG,
      merchantEmail: '',
      merchantName: '',
      merchantContact: '',
      referralData: props.referralData || '',
      isFormValid: false,
    };
    const { isPartnershipForXEnabled, isPartnershipFUX } = props.user;
    switch (props.addType) {
      case PRODUCT_TYPE.PG: {
        state.step = 2;
        state.merchantType = PRODUCT_TYPE.PG;
        break;
      }
      case PRODUCT_TYPE.X: {
        state.step = 2;
        state.merchantType = PRODUCT_TYPE.X;
        break;
      }
      default: {
        if (!isPartnershipForXEnabled) {
          state.step = 2;
          state.merchantType = PRODUCT_TYPE.PG;
        }
      }
    }
    this.state = state;
    this.isPartnershipForXEnabled = isPartnershipForXEnabled;
    this.isPartnershipFUX = isPartnershipFUX;
  }

  sampleUrl = () => {
    if (this.isPartnershipForXEnabled) {
      return '/files/sample_submerchant_batch.xlsx';
    }
    return '/files/sample_submerchant_link.xlsx';
  };

  getModalHeaderText = () => {
    const { step, merchantType } = this.state;
    switch (step) {
      case 1:
        return 'Add New Merchants';
      case 2:
        return merchantType === PRODUCT_TYPE.X
          ? 'Add New Merchants - RazorpayX'
          : 'Add New Merchants - Razorpay Payments';
      case 3:
        return 'Merchant Added Successfully';
      default:
        return 'Add New Merchants';
    }
  };

  getTabHeaderText = (mode) => {
    const { isMobileResolution } = this.props;
    switch (mode) {
      case ADD_MODE.single: {
        if (isMobileResolution) {
          return 'Email Invites';
        }
        return 'Invite using Email';
      }
      case ADD_MODE.bulk: {
        if (isMobileResolution) {
          return 'Bulk Invite';
        }
        return 'Invite Multiple Clients';
      }
      case ADD_MODE.social: {
        if (isMobileResolution) {
          return ' Link Invite';
        }
        return 'Invite using Links';
      }
      default: {
        return 'Invite using Email';
      }
    }
  };

  fetchReferralURL = () => {
    const { referralData } = this.state;
    if (referralData === '') {
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

  getIsInsertTable = () => {
    const { merchantType } = this.state;
    const { location } = this.props;
    const addXIntent = merchantType === PRODUCT_TYPE.X;
    const addPGIntent = merchantType === PRODUCT_TYPE.PG;
    const currentPageX = location.pathname === '/partners/submerchants/x';
    const currentPagePG = location.pathname === '/partners/submerchants';
    if ((addXIntent && currentPageX) || (addPGIntent && currentPagePG)) {
      return true;
    }
    return false;
  };

  addNewMerchant = (params) => {
    this.trackUserEvent('partnerships.submerchant.add.product_group.single.action', {
      action: 'Send Invite',
    });
    const { user, showNotification, closeModal, tracking } = this.props;
    const { merchantType } = this.state;
    this.fetchReferralURL();
    const isInsertTable = this.getIsInsertTable();
    return this.props
      .create({
        ...params,
        product: merchantType,
        isInsertTable,
      })
      .then((response) => {
        const { id } = response;
        // go to referral link screen only partner is reseller
        if (user && user.isPartner('reseller')) {
          this.setState((prevState) => ({
            step: prevState.step + 1,
            merchantEmail: params.email,
          }));
        } else {
          showNotification({
            type: 'success',
            message: 'Submerchant created successfully',
          });
          closeModal();
        }
        tracking?.trackEvent(
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
        tracking?.trackEvent(
          window.rzpQ.onbr().interaction('partnerships.submerchant.add.error', {
            partnerID: user.id,
            error: errors && errors[0],
          }),
        );
        showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  handleBatchCreate = () => {
    const { user, tracking, showNotification, closeModal } = this.props;
    const { bulkContactsCount, file_id, merchantType } = this.state;
    gaEvents.trackUploadBatch('Partner submerchant');
    tracking?.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.submerchant.add.multiple.upload.invite', {
        partnerID: user.id,
        contactsCount: bulkContactsCount,
      }),
    );
    trackAddNewMerchantEvents('Add Multiple - Invite Contacts');
    return this.props
      .createBatch({
        file_id,
        config: {
          product: merchantType,
        },
      })
      .then((_response) => {
        this.trackUserEvent('partnerships.submerchant.add.product_group.multiple.upload', {
          Action: 'Invite',
          success: bulkContactsCount,
        });
        this.trackUserEvent('partnerships.submerchant.add.product_group.multiple.invite', {
          success: bulkContactsCount,
        });
        showNotification({
          type: 'success',
          message:
            'Your file has been successfully processed. Status of account creation will be sent to you within 2 hours.',
        });
        closeModal();
      })
      .catch((error) => {
        this.trackUserEvent('partnerships.submerchant.add.product_group.multiple.upload', {
          Action: 'Invite',
          error: error && error[0],
        });
        this.trackUserEvent('partnerships.submerchant.add.product_group.multiple.invite', {
          error: error && error[0],
        });
        showNotification({
          type: 'error',
          message: 'Failed to invite.',
        });
      });
  };

  onValidation = (response, _name) => {
    const { user, tracking } = this.props;
    if (response && response.file_id) {
      this.setState({
        file_id: response.file_id,
        bulkContactsCount: response.processable_count || 0,
      });
      tracking?.trackEvent(
        window.rzpQ.onbr().interaction('partnerships.submerchant.add.multiple.upload.success', {
          partnerID: user.id,
          contactsCount: response.processable_count || 0,
        }),
      );
      trackAddNewMerchantEvents('Add Multiple - Success');
    } else {
      this.setState({ file_id: '' });
      this.trackUserEvent('partnerships.submerchant.add.product_group.multiple.upload', {
        Action: 'Cancel the uploaded file',
      });
    }
  };

  onValidationFail = (error) => {
    const { user, tracking } = this.props;
    tracking?.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.submerchant.add.multiple.upload.error', {
        partnerID: user.id,
        error,
      }),
    );
  };

  handleModeChange = (mode) => {
    const { user, tracking } = this.props;
    if (mode === ADD_MODE.bulk) {
      this.trackUserEvent('partnerships.submerchant.add.product_group.multiple');
      trackAddNewMerchantEvents('Click - Add Multiple');
      tracking?.trackEvent(
        window.rzpQ.onbr().interaction('partnerships.submerchant.add.multiple', {
          partnerID: user.id,
        }),
      );
      this.setState({ addMode: ADD_MODE.bulk });
    } else if (mode === ADD_MODE.single) {
      this.setState({ addMode: ADD_MODE.single, file_id: '' });
    } else {
      this.setState({
        addMode: ADD_MODE.social,
        file_id: '',
      });
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
    tracking?.trackEvent(
      window.rzpQ.onbr().interaction(eventName, {
        partnerID: user.id,
        productGroup,
        isPartnershipFUX: this.isPartnershipFUX,
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

  isNumber = (str) => {
    const pattern = /^\d+$/;
    return pattern.test(str);
  };

  optionalMobileValidator = (value) => {
    if (value) {
      if (isMobile(value)) return undefined;
      else return 'Invalid Contact';
    } else return undefined;
  };

  handleFormChange = (e) => {
    let { merchantName, merchantEmail, merchantContact } = this.state;
    const { user } = this.props;
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
      case 'contact_mobile':
        if (this.isNumber(value) || !value) {
          this.setState({
            merchantContact: value,
          });
          merchantContact = value;
        } else e.preventDefault();
        break;
      default:
        console.warn('incorrect field name');
    }
    let isEmailValid;
    if (isEmailMandatory(user)) {
      isEmailValid = merchantEmail && isEmail(merchantEmail);
    } else {
      isEmailValid = true;
    }
    let isPhoneNumberValid = true;
    if (merchantContact) {
      isPhoneNumberValid = merchantContact && isMobile(merchantContact);
    }
    const isFormValid = merchantName && isEmailValid && isPhoneNumberValid;
    this.setState({
      isFormValid,
    });
  };

  handleFormFocus = (e) => {
    const { name } = e.target;
    const { step } = this.state;
    if (step === 2) {
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
    if (!this.isPartnershipForXEnabled) {
      this.eventAddNewMerchant();
    }
    this.fetchReferralURL();
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
    const { step, addMode } = this.state;
    const { closeModal } = this.props;
    closeModal();
    if (step === 1) {
      this.trackUserEvent('partnerships.submerchant.add.product_group', {
        action: 'cancel',
      });
    }
    if (step === 2) {
      if (addMode === ADD_MODE.single) {
        // Add a Account Tab
        this.trackUserEvent('partnerships.submerchant.add.product_group.single.action', {
          action: 'cancel',
        });
      } else if (addMode === ADD_MODE.bulk) {
        // Add Multiple Account Tab
        this.trackUserEvent('partnerships.submerchant.add.product_group.multiple.action', {
          action: 'cancel',
        });
      } else if (addMode === ADD_MODE.social) {
        // Invite using links
        this.trackUserEvent('partnerships.submerchant.add.product_group.socialLink.action', {
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
    const { handleSubmit, user, validateBatch, tracking } = this.props;
    const {
      merchantType,
      addMode,
      referralData,
      step,
      file_id,
      bulkContactsCount,
      merchantEmail,
      isFormValid,
      merchantContact,
    } = this.state;
    const partnerID = user?.id;
    const emailMandatory = isEmailMandatory(user);
    const emailValidators = emailMandatory ? [required(), email()] : [];
    const referralUrl = referralData ? referralData[merchantType]?.url : '';
    return (
      <div className="partner-submerchant-modal fixed-height-modal">
        <ModalHeader title={this.getModalHeaderText()} onCloseClick={this.modalCloseClick} />
        <div className="modal-body">
          <ShowWhen additionalCondition={() => step === 1}>
            <div className="step">
              <div className="type-selection flex-col-between">
                <div className="type-selection__content">
                  <SelectBox
                    label="Razorpay Payments"
                    description="Invite affiliates to use Razorpay Payment products to collect payments"
                    onClick={() => {
                      this.setState({ merchantType: PRODUCT_TYPE.PG });
                      this.trackUserEvent('partnerships.submerchant.add.product_group', {
                        productGroup: 'Payments',
                      });
                    }}
                    checked={merchantType === PRODUCT_TYPE.PG}
                  />
                  <SelectBox
                    label="RazorpayX"
                    description="Invite affiliates to open RazorpayX powered Current Account to process payouts"
                    onClick={() => {
                      this.setState({ merchantType: PRODUCT_TYPE.X });
                      this.trackUserEvent('partnerships.submerchant.add.product_group', {
                        productGroup: 'X',
                      });
                    }}
                    checked={merchantType === PRODUCT_TYPE.X}
                  />
                </div>
                <div className="type-selection__actions">
                  <Button.Primary
                    onClick={this.handleNextClick}
                    disabled={merchantType === ''}
                    iconAfter="arrow-forward"
                  >
                    Next
                  </Button.Primary>
                </div>
              </div>
            </div>
          </ShowWhen>
          <ShowWhen additionalCondition={() => step === 2}>
            <div className="step">
              <ul className="tab-headers">
                <li
                  className={addMode === ADD_MODE.single ? 'active' : ''}
                  onClick={() => this.handleModeChange(ADD_MODE.single)}
                >
                  {this.getTabHeaderText(ADD_MODE.single)}
                </li>
                <li
                  className={addMode === ADD_MODE.bulk ? 'active' : ''}
                  onClick={() => this.handleModeChange(ADD_MODE.bulk)}
                >
                  {this.getTabHeaderText(ADD_MODE.bulk)}
                </li>
                {this.isPartnershipFUX && (
                  <li
                    className={addMode === ADD_MODE.social ? 'active' : ''}
                    onClick={() => this.handleModeChange(ADD_MODE.social)}
                  >
                    {this.getTabHeaderText(ADD_MODE.social)}
                  </li>
                )}
              </ul>
              {/* Bulk start */}
              <ShowWhen additionalCondition={() => addMode === ADD_MODE.bulk}>
                <div className="add-batch-block">
                  <BatchValidate
                    sampleFileDownloadAnalytics={this.sampleFileDownloadAnalytics}
                    clickToUploadAnalytics={this.clickToUploadAnalytics}
                    onValidation={this.onValidation}
                    batchType="partner_submerchant_invite"
                    batchTypeText="text"
                    sampleUrl={this.sampleUrl()}
                    gaEvents={gaEvents}
                    validateBatch={validateBatch}
                    maxRows={500}
                    maxFileSize={52428800}
                    onFileRemove={this.onValidation}
                    batchClass="batch-upload-modal"
                    onValidationFail={this.onValidationFail}
                  />
                  {file_id ? (
                    <div className="success-message flex-col-between">
                      <div>
                        <p>
                          <img src="/dist/css/assets/check-round.svg" alt="Tick icon" /> &nbsp;
                          {bulkContactsCount} contacts have been identified.
                        </p>
                        <span>
                          Email will be sent to {bulkContactsCount} identified contacts. Status of
                          account creation will be sent to your email address within 2 hours.
                        </span>
                      </div>
                      <div className="bulk-actions">
                        <AsyncButton
                          type="button"
                          className="btn btn-primary"
                          text={`Invite ${bulkContactsCount} contacts`}
                          pendingText={`Inviting ${bulkContactsCount} contacts...`}
                          onClick={this.handleBatchCreate}
                        />
                      </div>
                    </div>
                  ) : null}
                </div>
              </ShowWhen>

              <ShowWhen additionalCondition={() => addMode === ADD_MODE.single}>
                <div className="add-single-block flex-col-between">
                  <div>
                    {/* Merchant Name */}
                    <div className="form-group">
                      <label className="label-required">Account Name</label>
                      <Field
                        name="name"
                        component={InputField}
                        className="form-control"
                        autoFocus
                        placeholder="Affiliate's name"
                        validate={required()}
                        onChange={this.handleFormChange}
                        onFocus={this.handleFormFocus}
                      />
                    </div>

                    {/* Merchant Email */}
                    <div className="form-group">
                      <label className={emailMandatory ? 'label-required' : ''}>
                        Email Address
                      </label>
                      <Field
                        name="email"
                        component={InputField}
                        validate={emailValidators}
                        placeholder={emailMandatory ? "Affiliate's email id" : 'Optional'}
                        className="form-control"
                        onChange={this.handleFormChange}
                        onFocus={this.handleFormFocus}
                      />

                      {!emailMandatory && (
                        <span className="help-block">
                          If no email is provided, your email will be mapped as the registered email
                          ID of this merchant.
                        </span>
                      )}
                    </div>

                    {this.isPartnershipForXEnabled ? (
                      <div className="form-group">
                        <label>Contact Number</label>
                        <Field
                          maxLength={10}
                          name="contact_mobile"
                          component={InputField}
                          value={merchantContact}
                          className="form-control"
                          placeholder="Affiliate's 10 digit mobile number"
                          validate={[
                            this.optionalMobileValidator,
                            maxLength(10, 'Mobile number should have 10 digits'),
                          ]}
                          onChange={this.handleFormChange}
                          onFocus={this.handleFormFocus}
                        />
                      </div>
                    ) : null}

                    <span className="help-block">
                      Razorpay account access link will be sent to your affiliate's email{' '}
                      {/* MobileNumber SMS Text will be added later */}
                      {/* {merchantContact ? 'and phone number' : ''} */}
                    </span>
                  </div>

                  <div className="modal-actions clearfix">
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
                      className="btn btn-primary"
                      text="Send Invite"
                      pendingText="Inviting..."
                      onClick={handleSubmit(this.addNewMerchant)}
                      disabled={!isFormValid}
                    />
                  </div>
                </div>
              </ShowWhen>
              <ShowWhen
                additionalCondition={(currentUser) =>
                  currentUser.isPartnershipFUX && addMode === ADD_MODE.social
                }
              >
                <div className="step merchant-added-container">
                  <div className="social-share-container">
                    <div className="social-share-text">
                      <span>You can also copy and share the link via other mediums</span>
                    </div>
                    <SocialShareGroup
                      referralUrl={referralUrl}
                      tracking={tracking}
                      product={merchantType}
                      partnerID={partnerID}
                    />
                  </div>
                </div>
              </ShowWhen>
            </div>
          </ShowWhen>
          <ShowWhen additionalCondition={() => step === 3}>
            <div className="step merchant-added-container">
              <div className="success-container">
                <div className="left-icon-container">
                  <i className="i i-done ModeIndicator--live-icon" />
                </div>
                <div className="text-container">
                  <div>
                    <span className="success-text">
                      Razorpay account access link will be sent to your affiliate's email at
                    </span>
                  </div>
                  <div className="merchant-email-wrapper">
                    <span className="merchant-email">
                      {merchantEmail}
                      {/* MobileNumber SMS Text will be added later */}
                      {/* {merchantContact ? `and +91-${merchantContact}` : ''} */}
                    </span>
                  </div>
                </div>
              </div>
              <div className="social-share-container">
                <div className="social-share-text">
                  <span>You can also copy and share the link via other mediums</span>
                </div>
                <SocialShareGroup
                  referralUrl={referralUrl}
                  tracking={tracking}
                  product={merchantType}
                  partnerID={partnerID}
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

export default compose(
  rTracking(() => window.rzpQ.component('AddMerchant')),
  withRouter,
  connect((state) => ({ ...state.session, isMobileResolution: state.app.isMobileResolution }), {
    create,
    showNotification,
    closeModal,
    createBatch,
    validateBatch,
  }),
  reduxForm({
    form: 'addMerchant',
  }),
)(AddMerchant);
