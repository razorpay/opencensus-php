import React, { Component, ComponentType } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';

import { create } from 'merchant/reducers/submerchant';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  createPartnerSubmerchantBatch as createBatch,
  validatePartnerSubmerchantBatch as validateBatch,
  validatePartnerSubmerchantCapitalBatch as validateCapitalBatch,
  createPartnerSubmerchantCapitalBatch as createCapitalBatch,
  createPartnerSubmerchantReferralInvitesBatch as createReferralInvitesBatch,
  validatePartnerSubmerchantReferralInvitesBatch as validateReferralInvitesBatch,
} from 'merchant/reducers/batches';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import rTracking from 'react-tracking';

import ModalHeader from 'common/ui/ModalHeader';
import InputField from './components/InputField';

import { required, email, isEmail, isMobile, maxLength, name } from 'common/utils/validators';
import ShowWhen, { showWhenUtil } from 'merchant/components/ShowWhen';
import BatchValidate from 'merchant/containers/BatchNew/Validate';
import { withRouter } from 'common/deprecated/withRouter';

import { trackAddNewMerchantEvents } from 'merchant/views/PartnerDashboard/ga';
import SelectBox from 'merchant/views/PartnerDashboard/SubMerchant/components/SelectBox';
import Button from 'common/new-ui/Button';
import SocialShareGroup from 'merchant/views/PartnerDashboard/SubMerchant/components/SocialShareGroup';
import { merchantFetch } from 'merchant/utils/ajax';
import { PRODUCT_TYPE, ADD_MODE, ORG_CUSTOM_CODE } from 'merchant/views/PartnerDashboard/constants';
import { minLength, getInitialState } from 'merchant/views/PartnerDashboard/SubMerchant/utils';
import type {
  AddMerchantPropsT,
  AddMerchantStateT,
  ReduxFormEvent,
  NewMerchant,
} from 'merchant/views/PartnerDashboard/SubMerchant/AddMerchant.types';
import { classList } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { createSubmerchantInvite } from './api';
import {
  trackSubmerchantReferViaBulkUpload,
  trackSubmerchantReferViaEmail,
} from './utils/analytics';

const gaEvents = setGaTrack('Dashboard - Partner Submerchant - BU');
const ORG_CONTACT_PLACEHOLDER_TEXT = {
  rzp: "Affiliate's 10 digit mobile number",
  curlec: "Affiliate's phone number starting with 0 or country code",
};
const PAYMENTS_MAINTENANCE_STATUS = {
  rzp: false,
  curlec: false,
};
const PAYMENTS_DISABLED_STATUS = {
  rzp: false,
  curlec: false,
};

const getPaymentNote = (isShowResumeOnboarding = false) => {
  return {
    rzp: !isShowResumeOnboarding,
    curlec: false,
  };
};

const MOBILE_NUMBER_MAX_LENGTH = {
  IN: 10,
  MY: 12,
};

// eslint-disable-next-line prettier/prettier
const RzpSuccessContainer = lazy(
  () => import('merchant/views/PartnerDashboard/SubMerchant/components/RzpSuccessContainer'),
);

// eslint-disable-next-line prettier/prettier
const CurlecSuccessContainer = lazy(
  () => import('merchant/views/PartnerDashboard/SubMerchant/components/CurlecSuccessContainer'),
);

/*
  Note: a revamped version of this component(InviteMerchantModal) is in use under a ramp experiment. 
  Should aim at removing this code after the ramp up finishes (will need to ramp up in capital and x flows separately)
*/

// TODO replace window.rzpQ with analyticsTrack for entire file. currently handled only for capital
class AddMerchant extends Component<AddMerchantPropsT, AddMerchantStateT> {
  onAddSuccess: () => void;
  isPartnershipForCapitalEnabled: boolean;
  isPartnershipFUX: boolean;
  isShowResumeOnboarding: boolean;
  orgCode: string;
  orgName: string;
  countryCode: string;
  constructor(props: AddMerchantPropsT) {
    super(props);
    const { user, addType, referralData, onAddSuccess = () => {}, org } = props;
    const state = getInitialState({ user, addType, referralData });
    const { isPartnershipFUX, isPartnershipForCapitalEnabled, isShowResumeOnboarding } = user;
    this.state = state;
    this.onAddSuccess = onAddSuccess;
    this.isPartnershipFUX = isPartnershipFUX;
    this.isPartnershipForCapitalEnabled = isPartnershipForCapitalEnabled;
    this.isShowResumeOnboarding = isShowResumeOnboarding;
    this.orgCode = org?.custom_code || 'rzp';
    this.orgName = org?.business_name || 'Razorpay';
    this.countryCode = user?.merchant?.country_code || 'IN';
  }

  isCapitalProduct = (): boolean => this.state.merchantType === PRODUCT_TYPE.CAPITAL;
  isPGInviteFlow = (): boolean =>
    this.state.merchantType === PRODUCT_TYPE.PG && this.props.user.isPartnershipsInviteFlowEnabled;

  sampleUrl = () => {
    const { merchantType } = this.state;
    if (this.isPGInviteFlow()) return '/files/sample_invite_submerchant_batch.xlsx';

    if (merchantType !== PRODUCT_TYPE.CAPITAL) {
      return '/files/sample_submerchant_batch.xlsx';
    }
    if (this.isCapitalProduct()) {
      return '/files/sample_capital_submerchant_batch.xlsx';
    }
    return '/files/sample_submerchant_link.xlsx';
  };

  batchType = () => {
    if (this.isCapitalProduct()) {
      return 'partner_submerchant_invite_capital';
    }
    if (this.isPGInviteFlow()) return 'partner_submerchant_referral_invite';
    return 'partner_submerchant_invite';
  };

  validateBatchType = () => {
    const { validateBatch, validateCapitalBatch, validateReferralInvitesBatch } = this.props;
    if (this.isCapitalProduct()) {
      return validateCapitalBatch;
    }
    if (this.isPGInviteFlow()) return validateReferralInvitesBatch;

    return validateBatch;
  };

  getModalHeaderText = () => {
    const { step, merchantType } = this.state;
    switch (step) {
      case 1:
        return this.isPGInviteFlow() ? 'Invite New Merchant' : 'Add New Merchants';
      case 2:
        if (this.isPGInviteFlow()) return 'Invite New Merchant';
        return merchantType === PRODUCT_TYPE.X
          ? 'Add New Merchants - RazorpayX'
          : this.isCapitalProduct()
          ? 'Add New Merchants - Line Of Credit'
          : `Add New Merchants - ${this.orgName} Payments`;
      case 3:
        return 'Merchant Added Successfully';
      default:
        return 'Add New Merchants';
    }
  };

  getTabHeaderText = (mode: string) => {
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
    const { user } = this.props;
    const { referralData } = this.state;

    if (referralData === '' && !user.isPartner('pure_platform')) {
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
    const isAddXIntent = merchantType === PRODUCT_TYPE.X;
    const isAddPGIntent = merchantType === PRODUCT_TYPE.PG;
    let isCurrentPageX: boolean | undefined;
    let isCurrentPagePG: boolean | undefined;
    if (location) {
      isCurrentPageX = location.pathname === '/partners/submerchants/x';
      isCurrentPagePG = location.pathname === '/partners/submerchants';
    }
    if ((isAddXIntent && isCurrentPageX) || (isAddPGIntent && isCurrentPagePG)) {
      return true;
    }
    return false;
  };

  addNewMerchant = (params: NewMerchant): void => {
    this.trackUserEvent('partnerships.submerchant.add.product_group.single.action', {
      action: 'Send Invite',
    });
    const { user, showNotification, closeModal, create, tracking } = this.props;
    const { merchantType } = this.state;
    this.fetchReferralURL();
    const isInsertTable = this.getIsInsertTable();

    if (this.isPGInviteFlow()) {
      const { contact_mobile, ...rest } = params;
      trackSubmerchantReferViaEmail(params);
      return createSubmerchantInvite({
        ...rest,
        contact_no: contact_mobile,
        product: merchantType,
        partner_id: user.id,
      })
        .then((response) => {
          if (!response.success) return;

          if (user && user.isPartner('reseller')) {
            this.setState((prevState) => ({
              step: prevState.step + 1,
              merchantEmail: params.email,
              merchantContact: params.contact_mobile,
            }));
          } else {
            showNotification?.({
              type: 'success',
              message: `Invite is sent successfully to the merchant's email provided`,
            });
            closeModal();
          }

          this.onAddSuccess();
        })
        .catch(({ errors }) => {
          showNotification?.({
            type: 'error',
            message: errors?.[0],
          });
        });
    }
    return create?.({
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
          showNotification?.({
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
        this.onAddSuccess();
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
        showNotification?.({
          type: 'error',
          message: errors[0],
        });
      });
  };

  handleBatchCreate = (): void => {
    // eslint-disable-next-line prettier/prettier
    const {
      user,
      tracking,
      showNotification,
      closeModal,
      createBatch,
      createCapitalBatch,
      createReferralInvitesBatch,
      // eslint-disable-next-line prettier/prettier
    } = this.props;
    const { bulkContactsCount, file_id, merchantType } = this.state;
    gaEvents.trackUploadBatch('Partner submerchant');
    if (this.isCapitalProduct()) {
      analyticsTrack({
        screen: 'Add Merchant modal',
        objectName: 'partnerships.capital.bulk.upload.invite',
        actionName: 'contacts button clicked',
        properties: {
          partner_id: user.id,
          contactsCount: bulkContactsCount,
        },
        toLumberjack: true,
      });
    } else {
      tracking?.trackEvent(
        window.rzpQ.onbr().interaction('partnerships.submerchant.add.multiple.upload.invite', {
          partnerID: user.id,
          contactsCount: bulkContactsCount,
        }),
      );
    }
    trackAddNewMerchantEvents('Add Multiple - Invite Contacts');
    if (this.isCapitalProduct()) {
      return createCapitalBatch?.({
        file_id,
        config: {
          product: merchantType,
        },
      })
        .then(() => {
          showNotification?.({
            type: 'success',
            message:
              'Your file has been successfully processed. Status of account creation will be sent to you within 2 hours.',
          });
          this.onAddSuccess();
          closeModal();
        })
        .catch((error) => {
          analyticsTrack({
            screen: 'Add Merchant modal',
            objectName: 'partnerships.submerchant.add.product.group.multiple.upload',
            actionName: 'bulk upload error',
            properties: {
              error: error && error[0],
            },
            toLumberjack: true,
          });
          analyticsTrack({
            screen: 'Add Merchant modal',
            objectName: 'partnerships.submerchant.add.product.group.multiple.invite',
            actionName: 'bulk upload error',
            properties: {
              error: error && error[0],
            },
            toLumberjack: true,
          });
          showNotification?.({
            type: 'error',
            message: 'Failed to invite.',
          });
        });
    }
    if (this.isPGInviteFlow()) {
      trackSubmerchantReferViaBulkUpload({ bulkContactsCount });

      return createReferralInvitesBatch?.({
        file_id,
        config: {
          product: merchantType,
        },
      })
        .then(() => {
          showNotification?.({
            type: 'success',
            message:
              'Your file has been successfully processed. Status of invites creation will be sent to you within 2 hours.',
          });
          this.onAddSuccess();
          closeModal();
        })
        .catch(() => {
          showNotification?.({
            type: 'error',
            message: 'Failed to invite.',
          });
        });
    }
    return (
      createBatch &&
      createBatch({
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
          showNotification?.({
            type: 'success',
            message:
              'Your file has been successfully processed. Status of account creation will be sent to you within 2 hours.',
          });
          this.onAddSuccess();
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
          showNotification?.({
            type: 'error',
            message: 'Failed to invite.',
          });
        })
    );
  };

  onValidation = (response, _name) => {
    const { user, tracking } = this.props;
    if (response && response.file_id) {
      const bulkContactsCount = response.processable_count || 0;
      this.setState({
        file_id: response.file_id,
        bulkContactsCount,
      });
      if (this.isCapitalProduct()) {
        analyticsTrack({
          screen: 'Add Merchant modal',
          objectName: 'partnerships.capital.bulk upload',
          actionName: 'bulk file uploaded',
          properties: {
            partner_id: user.id,
            no_of_leads_added: bulkContactsCount,
          },
          toLumberjack: true,
        });
      } else {
        tracking?.trackEvent(
          window.rzpQ.onbr().interaction('partnerships.submerchant.add.multiple.upload.success', {
            partnerID: user.id,
            contactsCount: bulkContactsCount,
          }),
        );
      }
      trackAddNewMerchantEvents('Add Multiple - Success');
    } else {
      this.setState({ file_id: '' });
      this.trackUserEvent('partnerships.submerchant.add.product_group.multiple.upload', {
        Action: 'Cancel the uploaded file',
      });
    }
  };

  onValidationFail = (error: Error) => {
    const { user, tracking } = this.props;
    tracking?.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.submerchant.add.multiple.upload.error', {
        partnerID: user.id,
        error,
      }),
    );
  };

  handleModeChange = (mode: string) => {
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
      if (this.isCapitalProduct()) {
        analyticsTrack({
          screen: 'Add Merchant modal',
          objectName: 'partnerships.capital.link invites.tab clicked',
          actionName: 'invites.tab clicked',
          properties: {
            partner_id: user.id,
          },
          toLumberjack: true,
        });
      } else {
        this.trackUserEvent('partnerships.submerchant.add.product_group.socialLink');
      }
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
    if (this.isCapitalProduct()) {
      return 'Capital';
    }
    return '';
  };

  trackUserEvent = (eventName: string, properties = {}) => {
    const { user, tracking, source } = this.props;
    const productGroup = this.getCurrentProduct();
    tracking?.trackEvent(
      window.rzpQ.onbr().interaction(eventName, {
        partnerID: user.id,
        productGroup,
        isPartnershipFUX: this.isPartnershipFUX,
        source,
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
    if (this.isCapitalProduct()) {
      this.setState({ addMode: ADD_MODE.bulk });
    }
    if (step === 1) {
      this.trackUserEvent('partnerships.submerchant.add.product_group.next');
      this.eventAddNewMerchant();
    }
  };

  handleBackClick = () => {
    this.setState((prevState) => ({ step: prevState.step - 1, file_id: '' }));
  };

  isNumber = (str: string) => {
    const pattern = /^\d+$/;
    return pattern.test(str);
  };

  optionalMobileValidator = (value: string | number) => {
    if (value) {
      if (isMobile(value, this.countryCode)) return undefined;
      else return 'Invalid Contact';
    } else return undefined;
  };

  handleFormChange = (e: ReduxFormEvent) => {
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
      isPhoneNumberValid = merchantContact && isMobile(merchantContact, this.countryCode);
    }
    const isFormValid = merchantName && isEmailValid && isPhoneNumberValid;
    this.setState({
      isFormValid,
    });
  };

  handleFormFocus = (e: ReduxFormEvent) => {
    const { name } = e.target;
    this.trackUserEvent('partnerships.submerchant.add.product_group.single.action', {
      action: name,
    });
  };

  componentDidMount() {
    trackAddNewMerchantEvents('Open Form');
    this.fetchReferralURL();
  }

  sampleFileDownloadAnalytics = () => {
    const { user } = this.props;
    if (this.isCapitalProduct()) {
      analyticsTrack({
        screen: 'Add Merchant modal',
        objectName: 'partnerships.capital.bulk upload',
        actionName: 'download sample file clicked',
        properties: {
          partner_id: user.id,
        },
        toLumberjack: true,
      });
    } else {
      this.trackUserEvent('partnerships.submerchant.add.product_group.multiple.action', {
        action: 'Download Sample file',
      });
    }
  };

  clickToUploadAnalytics = () => {
    const { user } = this.props;
    if (this.isCapitalProduct()) {
      analyticsTrack({
        screen: 'Add Merchant modal',
        objectName: 'partnerships.capital.bulk upload.file',
        actionName: 'upload option clicked',
        properties: {
          partner_id: user.id,
        },
        toLumberjack: true,
      });
    } else {
      this.trackUserEvent('partnerships.submerchant.add.product_group.multiple.action', {
        action: 'Click to upload',
      });
    }
  };

  contactNumberValidation = () => {
    const ORG_VALIDATION_LIST = {
      rzp: [this.optionalMobileValidator, maxLength(10, 'Mobile number should have 10 digits')],
      curlec: [this.optionalMobileValidator],
    };
    return ORG_VALIDATION_LIST[this.orgCode];
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
      this.trackUserEvent('partnerships.submerchant.add.product_group.social.cancel', {
        action: 'Merchant Added',
      });
    }
  };

  render() {
    const { handleSubmit, user, tracking, source, isConfigTagEnabled } = this.props;
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
    const emailValidators = isEmailMandatory(user) ? [required(), email()] : [];
    const accountNameValidators = [required(), name(), minLength(4), maxLength(255)];
    const referralUrl = referralData ? referralData[merchantType]?.url : '';

    return (
      <div className="partner-submerchant-modal fixed-height-modal">
        <ModalHeader title={this.getModalHeaderText()} onCloseClick={this.modalCloseClick} />
        <div className="modal-body">
          <ShowWhen additionalCondition={() => step === 1}>
            <div className="step">
              <div className="type-selection flex-col-between">
                <div
                  className={classList(
                    this.isPartnershipForCapitalEnabled
                      ? 'type-selection__content-capital'
                      : 'type-selection__content',
                  )}
                >
                  <SelectBox
                    label={`${this.orgName} Payments`}
                    description={`Invite affiliates to use ${this.orgName} Payment products to collect payments`}
                    onClick={() => {
                      this.setState({ merchantType: PRODUCT_TYPE.PG });
                      this.trackUserEvent('partnerships.submerchant.add.product_group', {
                        productGroup: 'Payments',
                      });
                    }}
                    checked={merchantType === PRODUCT_TYPE.PG}
                    disabled={PAYMENTS_DISABLED_STATUS[this.orgCode]}
                    isMaintenance={PAYMENTS_MAINTENANCE_STATUS[this.orgCode]}
                    showNote={getPaymentNote(this.isShowResumeOnboarding)[this.orgCode]}
                    orgName={this.orgName}
                  />
                  <ShowWhen
                    additionalCondition={() =>
                      !isConfigTagEnabled('partnership.add_new_razorpay_x_merchant')
                    }
                  >
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
                  </ShowWhen>
                  <ShowWhen additionalCondition={() => this.isPartnershipForCapitalEnabled}>
                    <SelectBox
                      label="Line Of Credit"
                      description="Refer merchants to Capital products like Line Of Credit"
                      onClick={() => {
                        this.setState({ merchantType: PRODUCT_TYPE.CAPITAL });
                      }}
                      checked={this.isCapitalProduct()}
                    />
                  </ShowWhen>
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
                <ShowWhen additionalCondition={() => merchantType !== PRODUCT_TYPE.CAPITAL}>
                  <li
                    className={addMode === ADD_MODE.single ? 'active' : ''}
                    onClick={() => this.handleModeChange(ADD_MODE.single)}
                  >
                    {this.getTabHeaderText(ADD_MODE.single)}
                  </li>
                </ShowWhen>
                <li
                  className={addMode === ADD_MODE.bulk ? 'active' : ''}
                  onClick={() => this.handleModeChange(ADD_MODE.bulk)}
                >
                  {this.getTabHeaderText(ADD_MODE.bulk)}
                </li>
                <ShowWhen
                  additionalCondition={() => !isConfigTagEnabled('partnership.referral_links')}
                >
                  {this.isPartnershipFUX && (
                    <li
                      className={addMode === ADD_MODE.social ? 'active' : ''}
                      onClick={() => this.handleModeChange(ADD_MODE.social)}
                    >
                      {this.getTabHeaderText(ADD_MODE.social)}
                    </li>
                  )}
                </ShowWhen>
              </ul>
              {/* Bulk start */}
              <ShowWhen additionalCondition={() => addMode === ADD_MODE.bulk}>
                <div className="add-batch-block">
                  <BatchValidate
                    sampleFileDownloadAnalytics={this.sampleFileDownloadAnalytics}
                    clickToUploadAnalytics={this.clickToUploadAnalytics}
                    onValidation={this.onValidation}
                    batchType={this.batchType()}
                    batchTypeText="text"
                    sampleUrl={this.sampleUrl()}
                    gaEvents={gaEvents}
                    validateBatch={this.validateBatchType()}
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
                        <ShowWhen
                          additionalCondition={() =>
                            this.isPartnershipForCapitalEnabled && this.isCapitalProduct()
                          }
                        >
                          <div>
                            <Button.Transparent onClick={this.handleBackClick}>
                              Back
                            </Button.Transparent>
                          </div>
                        </ShowWhen>
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
                  <ShowWhen
                    additionalCondition={() =>
                      this.isPartnershipForCapitalEnabled && this.isCapitalProduct() && !file_id
                    }
                  >
                    <div className="bulk_back_button_container">
                      <Button.Transparent onClick={this.handleBackClick}>Back</Button.Transparent>
                    </div>
                  </ShowWhen>
                </div>
              </ShowWhen>

              <ShowWhen additionalCondition={() => addMode === ADD_MODE.single}>
                <div className="add-single-block flex-col-between">
                  <div>
                    {/* Merchant Name */}
                    <div className="form-group">
                      {this.isPGInviteFlow() ? (
                        <label className="label-required">Invitee Name</label>
                      ) : (
                        <label className="label-required">Account Name</label>
                      )}
                      <Field
                        name="name"
                        data-testid="input-name"
                        component={InputField}
                        className="form-control"
                        autoFocus
                        placeholder="Affiliate's name"
                        validate={accountNameValidators}
                        onChange={this.handleFormChange}
                        onFocus={this.handleFormFocus}
                      />
                    </div>

                    {/* Merchant Email */}
                    <div className="form-group">
                      <label className={isEmailMandatory(user) ? 'label-required' : ''}>
                        Email Address
                      </label>
                      <Field
                        name="email"
                        data-testid="input-email"
                        component={InputField}
                        validate={emailValidators}
                        placeholder={isEmailMandatory(user) ? "Affiliate's email id" : 'Optional'}
                        className="form-control"
                        onChange={this.handleFormChange}
                        onFocus={this.handleFormFocus}
                      />

                      {!isEmailMandatory(user) && (
                        <span className="help-block">
                          If no email is provided, your email will be mapped as the registered email
                          ID of this merchant.
                        </span>
                      )}
                    </div>

                    <div className="form-group">
                      <label>Contact Number</label>
                      <Field
                        maxLength={MOBILE_NUMBER_MAX_LENGTH[this.countryCode]}
                        name="contact_mobile"
                        data-testid="input-contact"
                        component={InputField}
                        value={merchantContact}
                        className="form-control"
                        placeholder={ORG_CONTACT_PLACEHOLDER_TEXT[this.orgCode]}
                        validate={this.contactNumberValidation()}
                        onChange={this.handleFormChange}
                        onFocus={this.handleFormFocus}
                      />
                    </div>

                    <span className="help-block">
                      {this.isPGInviteFlow()
                        ? `Razorpay account creation invite link will be sent via email and SMS(if contact number provided) to your affiliate`
                        : `${this.orgName} account access link will be sent to your affiliate's email `}
                    </span>
                  </div>

                  <div className="modal-actions clearfix">
                    <Button.Transparent
                      onClick={this.handleBackClick}
                      style={{ marginRight: '14px' }}
                    >
                      Back
                    </Button.Transparent>
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
                      source={source}
                      product={merchantType}
                      partnerID={partnerID}
                    />
                  </div>
                </div>
              </ShowWhen>
            </div>
          </ShowWhen>
          <ShowWhen additionalCondition={() => step === 3}>
            {this.orgCode === ORG_CUSTOM_CODE.CURLEC ? (
              <SuspenseWithLoader>
                <CurlecSuccessContainer />
              </SuspenseWithLoader>
            ) : (
              <SuspenseWithLoader>
                <RzpSuccessContainer
                  merchantContact={this.isPGInviteFlow() ? merchantContact : null}
                  merchantEmail={merchantEmail}
                  referralUrl={referralUrl}
                  tracking={tracking}
                  source={source}
                  merchantType={merchantType}
                  partnerID={partnerID}
                />
              </SuspenseWithLoader>
            )}
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

export default compose<ComponentType<AddMerchantPropsT>>(
  rTracking(() => window.rzpQ.component('AddMerchant')),
  withRouter,
  connect((state) => ({ ...state.session, isMobileResolution: state.app.isMobileResolution }), {
    create,
    showNotification,
    createBatch,
    validateBatch,
    validateCapitalBatch,
    createCapitalBatch,
    createReferralInvitesBatch,
    validateReferralInvitesBatch,
  }),
  reduxForm({
    form: 'addMerchant',
  }),
)(AddMerchant);
