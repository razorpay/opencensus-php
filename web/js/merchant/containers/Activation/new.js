/* eslint-disable */
import React from 'react';
import { connect } from 'react-redux';
import { merchantFetch } from 'merchant/utils/ajax';
import { showNotification } from 'merchant_common/reducers/notifications';
import { closeModal, openModal } from 'merchant_common/reducers/modals';

import { LinkCard } from 'common/new-ui/Cards';
import ActivationWizard from 'merchant/components/Activation';
import Button from 'common/new-ui/Button';

import { updateSession } from 'merchant/reducers/session';
import User from 'merchant/models/User';
import { showKYCStatusModal, showTnC } from 'merchant/reducers/home';
import { rxCaSelectedFlag, caReqEventType } from 'merchant/containers/Home/OnboardingCard/data';

import RTracking from 'react-tracking';
import { withRouter } from 'common/deprecated/withRouter';
import { trackLinkClick, trackGoToConfig } from './ga_new';

import { LLPIN_BusinessTypes } from 'merchant/components/Activation/ActivationFormMap';
import { isSourceRX, isDedupeOldFunc } from 'merchant/components/Activation/ActivationUtils';
import { fetchModalConfigDetails } from 'merchant/reducers/ModalConfigApi';
import * as EventsActions from 'merchant/reducers/trackEvents';

const welcomeImg = '/img/activation/welcome.svg';
const successImg = '/img/activation/submit-success.svg';
const POLLING_COUNTER_LIMIT = 5;
/*
 * ActivationContainer is used in:
 * 1. '/activation' route for Activation form for merchant, and
 * 2. Marketplace > Accounts for linked account (AccoundDetails)
 * 3. Partner Dashboard -> Submerchant KYC
 * @props {onClose, Function, optional}. Without this modal would not be opened. Also, this would be used to close the modal
 * @props {accountId, String, optional}. Needed if the ActivationWizard is opened for Linked Account
 * @props {submerchantId} for Submerchant KYC from Partner dashboard - submerchantContainer.js
 * */
@RTracking(() => window.rzpQ.component('ActivationContainer'))
@connect(
  (state) => ({
    session: state.session,
    user: state.session.user,
  }),
  {
    showNotification,
    updateSession,
    showKYCStatusModal,
    showTnC,
    openModal,
    closeModal,
    ...EventsActions,
  },
)
class ActivationContainer extends React.Component {
  constructor(props) {
    super(props);

    const { data, user } = props;

    if (this.props.setOnCloseCb) {
      this.props.setOnCloseCb(this.saveDirtyState);
    }

    const someDetailsFilled = isFormTouched(data);

    if (!someDetailsFilled) {
      this.preloadWelcomeAsset();
    }

    if (data && data.can_submit) {
      this.preloadSuccessAsset();
    }

    this.state = {
      isFormTouched: someDetailsFilled,
      rxCaCheckboxSelect: false, // local checkbox state
      showL2Form: false,
      bvsApiCount: null,
    };

    this.activationFormName = user.showInstantActivation ? 'KYC Form' : 'Activation Form';

    if (props.rpc && props.rpc.notifyOnKYCSuccess) {
      props.rpc.notifyOnKYCSuccess((reply) => {
        this.onKYCSuccess = reply;
      });

      props.rpc.notifySupportPopupOpen((reply) => {
        this.onSupportOpen = reply;
      });

      props.rpc.notifySupportPopupClose((reply) => {
        this.onSupportClose = reply;
      });
    }

    const settings = props.user.user.settings;
    if (!!settings[rxCaSelectedFlag] && settings[rxCaSelectedFlag] === '1') {
      this.state.rxCaCheckboxSelect = true; // make it checked if the rxCaSelectedFlag exist in Settings (persists post refresh)
    }
    const { isGstinSyncFlowEnabled, isLlpinSyncFlowEnabled, isCinSyncFlowEnabled } = user;

    this.hasGstinLLpinCinSyncFlow =
      isGstinSyncFlowEnabled || isLlpinSyncFlowEnabled || isCinSyncFlowEnabled;

    window.addEventListener('modal-open', this.handleSupportModalOpen);
  }

  get isSupportUrl() {
    return window.location.href.indexOf('#ticket') > 0;
  }

  handleSupportModalOpen = () => {
    window.addEventListener('modal-close', this.handleSupportModalClose);
    return this.isSupportUrl && this.onSupportOpen && this.onSupportOpen();
  };

  handleSupportModalClose = () => {
    window.removeEventListener('modal-close', this.handleSupportModalClose);
    return this.onSupportClose && this.onSupportClose();
  };

  preloadWelcomeAsset() {
    const welcome = new Image();
    welcome.src = welcomeImg;
  }

  preloadSuccessAsset() {
    const success = new Image();
    success.src = successImg;
  }

  sendRXcaDetailsToSF = () => {
    if (!!this.state.rxCaCheckboxSelect) {
      const { current } = this.props.user.user;
      let payload = {
        event_type: caReqEventType,
        event_properties: {
          interested_in_current_account: 1,
          pin_code: null,
          average_monthly_balance: null,
          current_ca: null,
          use_case: null,
          product_name: 'Current_Account',
          source: 'PG-KYC',
        },
      };

      merchantFetch({
        url: `merchant/${current}/salesforce_event`,
        mode: 'test',
        method: 'post',
        data: payload,
        headers: {
          'Content-Type': 'application/json',
        },
      }).then(() => {
        this.props.tracking.trackEvent(window.rzpQ.onbr().initiated('rx_KYC_ca_requested'));
      });
    }
  };

  updateSession(data) {
    // submerchantId received from submerchantContainer.js
    const { session, accountId } = this.props;

    // Update data
    if (this.props.onNewData) {
      this.props.onNewData(data);
    }

    // Session need not be updated if it's linked account form
    if (accountId) {
      return;
    }

    if (data.can_submit) {
      this.preloadSuccessAsset();
    }

    const {
      activation_progress,
      activated,
      activation_status,
      submitted,
      business_website,
      contact_email,
      activation_form_milestone,
      dedupe,
      poi_verification_status,
      business_type,
      merchant,
      isHardLimitReached,
      company_pan_verification_status,
      business_name,
      company_pan,
      promoter_pan,
      promoter_pan_name,
      bank_details_verification_status,
      bank_branch_ifsc,
      bank_account_name,
      bank_account_number,
      contact_name,
      playstore_url,
      merchant_business_detail,
      gstin_verification_status,
      cin_verification_status,
      company_cin,
      gstin,
      verification_error_codes,
    } = data;

    // Updating % activation_progress (side bar) and other important activation fields

    const user = new User({
      ...session.user,
      activation_progress,
      activated,
      activation_status,
      submitted: +submitted,
      business_website,
      contact_email,
      activation_form_milestone,
      dedupe,
      poi_verification_status,
      business_type,
      merchant,
      isHardLimitReached,
      company_pan_verification_status,
      business_name,
      company_pan,
      promoter_pan,
      promoter_pan_name,
      bank_details_verification_status,
      bank_branch_ifsc,
      bank_account_name,
      bank_account_number,
      contact_name,
      playstore_url,
      merchant_business_detail,
      gstin_verification_status,
      cin_verification_status,
      company_cin,
      gstin,
      verification_error_codes,
    });

    this.props.updateSession({
      user,
      mode: session.mode,
    });
  }

  fetchBusinessCategory = () => {
    return merchantFetch('merchant/activation/business_categories').then((response) => {
      return response;
    });
  };

  fetchMerchantDetails = async () => {
    const response = await merchantFetch({
      url: 'merchant/activation',
      mode: 'live',
      accountId: this.props.accountId || this.props.submerchantId,
    });
    this.updateSession(response.data);
    return response;
  };

  submitForm = ({ data }) => {
    const { user, accountId, submerchantId } = this.props;
    if (user.isInstantActivationEnabled) {
      const isL1Done = user.activation_form_milestone;

      const objectName = isL1Done ? 'L2 form' : 'L1 form';

      this.props.trackEvents({
        objectName,
        actionName: 'Submitted',
        screen: 'home page',
        toCleverTap: true,
        toFacebook: true,
        properties: {
          submerchant_id: submerchantId,
        },
      });

      let payload = {};

      if (isL1Done === 'L1') {
        payload = {
          consent: true,
          documents_detail: [
            {
              type: 'Privacy Policy',
              url: 'https://razorpay.com/privacy/',
            },
            {
              type: 'Terms & Conditions',
              url: 'https://razorpay.com/terms/',
            },
          ],
        };
      }

      return merchantFetch({
        url: 'merchant/activation',
        method: 'post',
        mode: !!accountId ? this.props.session.mode : 'live',
        data: {
          activation_form_milestone:
            user.instantActivation.isL1Submitted ||
            accountId ||
            user.activation_form_milestone === 'L2'
              ? 'L2'
              : 'L1',
          ...payload,
          ...data,
        },
        accountId: accountId || submerchantId,
        // accountId for linked_accounts. Axios auto-ignore undefined keys in options
      })
        .then((response) => {
          this.props.trackEvents({
            objectName,
            actionName: 'Result',
            screen: 'home page',
            properties: {
              Status: 'success',
              submerchant_id: submerchantId,
            },
          });

          if (isL1Done && !response?.data?.can_submit) {
            throw { errors: ['Some mandatory fields are required'] };
          }

          if (this.onKYCSuccess) {
            this.onKYCSuccess(response);
            return response;
          }

          if (response?.data?.activation_form_milestone === 'L1') {
            this.props.tracking.trackEvent(
              window.rzpQ.onbr().success('act.submit_form', {
                clickSource: 'Dashboard_CTA',
              }),
            );
            this.props.trackEvents({
              objectName: 'act submit form success',
              actionName: 'clicked',
              screen: 'home page',
              properties: {
                submerchant_id: submerchantId,
              },
            });
          }

          const isTestMode =
            localStorage.getItem(`rzp_mode--${this.props.user.current}`) === 'test';

          //if recommand product payment gateway and business_website avilable
          if (response?.data?.business_website) {
            localStorage.setItem('merchant_landing_page', 'payment_gateway');
          }

          //render loader if poi status is initiated and post 20sec fetch data to check poi status.
          //if poi status verified open instant activation modal otherwise reload the page.
          if (
            !accountId &&
            response?.data?.activation_form_milestone === 'L1' &&
            response?.data?.poi_verification_status === 'initiated' &&
            ['11', '2'].includes(response?.data?.business_type) &&
            response?.data?.activation_flow !== 'greylist' &&
            user.isAutoPLEnabled
          ) {
            if (!this.props.isModalView) {
              this.props.openModal({
                size: 'small',
                component: (
                  <InstantActivationLoadingState contactName={response.data.contact_name} />
                ),
                className: 'instantActivation-loading--Modal',
              });
            } else {
              this.props.setActivationFormLoadingState(); //true loading state
            }
            setTimeout(() => {
              this.fetchMerchantDetails().then((res) => {
                if (res?.data && res?.data?.poi_verification_status === 'verified') {
                  if (res?.data?.activated && isTestMode) {
                    localStorage.setItem(`rzp_mode--${this.props.user.current}`, 'live');
                    this.props.updateSession({ mode: 'live' });
                  }
                  this.updateSession(res.data);
                  if (!this.props.isModalView) this.props.closeModal();
                  else this.props.setActivationFormLoadingState(); //false loading state
                  this.setState({ showSuccessScreen: true });
                } else if (
                  res?.data &&
                  ['incorrect_details', 'not_matched', 'failed'].includes(
                    res.data?.poi_verification_status,
                  )
                ) {
                  this.updateSession(res.data);
                  if (!this.props.isModalView) this.props.closeModal();
                  else this.props.setActivationFormLoadingState(); //false loading state
                  if (user.autoOpenL2Form && res?.data && !res.data.activated) {
                    this.props.trackEvents({
                      objectName: 'Auto Open L2 form on not instantly activated',
                      actionName: 'displayed',
                      screen: 'home page',
                      properties: {
                        loginL1Experiment: 'auto open L2 form on not instantly activated',
                        submerchant_id: submerchantId,
                      },
                    });
                    this.setState({
                      showL2Form: true,
                    });
                    return res;
                  } else {
                    this.setState({ showSuccessScreen: true });
                  }
                } else {
                  this.updateSession(res.data);
                  if (!this.props.isModalView) this.props.closeModal();
                  else this.props.setActivationFormLoadingState(); //false loading state
                  // if poi status not changed reload the page
                  this.goToDashboard();
                }
                this.props.trackEvents({
                  objectName: 'poi verification status',
                  actionName: 'load',
                  screen: 'home page',
                  properties: {
                    poi_status: res?.data?.poi_verification_status,
                    submerchant_id: submerchantId,
                  },
                });
              });
            }, 9000);
          } else {
            if (
              user.autoOpenL2Form &&
              !this.props.accountId &&
              response?.data &&
              !response.data.activated
            ) {
              this.props.trackEvents({
                objectName: 'Auto Open L2 form on not instantly activated',
                actionName: 'displayed',
                screen: 'home page',
                properties: {
                  loginL1Experiment: 'auto open L2 form on not instantly activated',
                  submerchant_id: submerchantId,
                },
              });
              this.updateSession(response.data);
              this.setState({
                showL2Form: true,
              });
              return response;
            }

            if (response?.data?.activated && isTestMode) {
              localStorage.setItem(`rzp_mode--${this.props.user.current}`, 'live');
              this.props.updateSession({ mode: 'live' });
              if (!user.isAutoPLEnabled) {
                this.props.showNotification({
                  type: 'success',
                  message: 'You have switched to live mode, transact now!',
                  hidePrevious: true,
                });
              }
            }
            this.updateSession(response.data);
            this.postSubmitStep(response);
          }

          return response;
        })
        .catch((err) => {
          this.props.showNotification({
            type: 'error',
            message: err.errors,
          });

          this.props.trackEvents({
            objectName,
            actionName: 'Result',
            screen: 'home page',
            properties: {
              status: 'failure',
              errorMessage: err.errors,
              submerchant_id: submerchantId,
            },
          });
        });
    } else {
      return merchantFetch({
        url: 'merchant/activation',
        method: 'post',
        // For accountId, mode must be respected, otherwise accountId in Headers would be ignored in api.
        mode: !!this.props.accountId ? this.props.session.mode : 'live',
        data: { submit: '1' },
        accountId: this.props.accountId, // accountId for linked_accounts. Axios auto-ignore undefined keys in options
      })
        .then((response) => {
          if (!response.data.can_submit) {
            throw { errors: ['Some mandatory fields are required'] };
          }

          if (this.onKYCSuccess) {
            this.onKYCSuccess(response);
            return response;
          }

          if (isDedupeOldFunc(response.data)) {
            location.href = '/app/dashboard';
          } else if (
            response.data &&
            !response.data.business_website &&
            !isSourceRX() &&
            this.props.user.canGenerateTnCPage
          ) {
            this.props.showTnC();
            this.updateSession(response.data);
            this.goToDashboard();
          } else {
            this.postSubmitStep(response);
          }

          return response;
        })
        .catch((err) => {
          this.props.showNotification({
            type: 'error',
            message: err.errors,
          });

          // throw err;
        });
    }
  };

  startPollingCounter = () => {
    let pollingCounter = 1;
    if (typeof setInterval === 'function') {
      this.intervalTimer = setInterval(async () => {
        const { user, data } = this.props;
        const cinStatus = user.cin_verification_status || data.cin_verification_status;
        const gstinStatus = user.gstin_verification_status || data.gstin_verification_status;

        const canStartPolling = [gstinStatus, cinStatus].indexOf('initiated') !== -1;

        if (canStartPolling && pollingCounter < POLLING_COUNTER_LIMIT) {
          ++pollingCounter;
          await this.fetchMerchantDetails();
        }
        if (pollingCounter === POLLING_COUNTER_LIMIT || !canStartPolling) {
          clearInterval(this.intervalTimer);
        }
      }, 2000);
    }
  };

  saveStep = (data) => {
    return merchantFetch({
      url: 'merchant/activation',
      // For accountId, mode must be respected, otherwise accountId in Headers would be ignored in api.
      mode: !!this.props.accountId ? this.props.session.mode : 'live',
      method: 'post',
      headers: {
        'content-type': 'application/json',
      },
      accountId: this.props.accountId || this.props.submerchantId, // accountId for linked_accounts. Axios auto-ignore undefined keys in options
      data,
    })
      .then((response) => {
        if (!response.data) {
          this.props.showNotification({
            type: 'error',
            message: response.errors,
          });
        } else {
          this.updateSession(response.data);

          if (this.hasGstinLLpinCinSyncFlow) {
            const { gstin_verification_status, cin_verification_status, submitted } =
              response.data ?? {};

            //if anyone of these status got updated and status is initiated then start polling.
            const canStartPolling =
              [gstin_verification_status, cin_verification_status].indexOf('initiated') !== -1;

            if (canStartPolling && !submitted) {
              this.startPollingCounter();
            }
          }
        }

        return response;
      })
      .catch((err) => {
        let errors = [];

        if (err.errors) {
          err.errors.forEach((er) => {
            if (er.toLowerCase().indexOf('status code') === -1) {
              // TODO: BE treats LLPin as cin currently. So, gives error for cin, not LLPin. To revert when BE handles.
              if (er.indexOf('cin') !== -1) {
                const businessType = this.props.data.business_type;

                if (businessType && LLPIN_BusinessTypes.indexOf(Number(businessType)) !== -1) {
                  er = er.replace('cin', 'llpin');
                }
              }

              errors.push(er);
            }
          });
        }

        // In Internal server error, only 1 error is sent and that is also removed above.
        if (!errors.length || (errors.length === 1 && !errors[0])) {
          errors[0] = 'Network error occurred';
        }

        this.props.showNotification({
          type: 'error',
          message: errors,
        });

        return {
          errors,
        };
      });
  };

  postSubmitStep(response) {
    if (this.props.accountId) {
      this.props.callback && this.props.callback(); // Support for callback for linked_account activation
    } else {
      this.setState({ showSuccessScreen: true });
    }
    this.updateSession(response.data);
    this.sendRXcaDetailsToSF();
  }

  saveFile = (fieldName, file, progressTracker, uploadAs) => {
    let formData = new FormData();
    if (typeof uploadAs === 'string') {
      fieldName = uploadAs;
    }
    formData.append('document_type', fieldName);
    formData.append('file', file);
    return merchantFetch({
      url: 'merchant/documents/upload',
      method: 'post',
      mode: 'live',
      data: formData,
      accountId: this.props.accountId || this.props.submerchantId,
      onUploadProgress: progressTracker,
    })
      .then((response) => {
        if (response.data) {
          // make aadhar_linked 0 as we are removing aadhar esign verification
          if (response.data.stakeholder?.aadhaar_linked === 1) {
            this.saveStep({
              stakeholder: {
                aadhaar_linked: 0,
              },
            });
          }

          this.props.showNotification({
            type: 'success',
            message: 'File uploaded successfully',
          });

          this.updateSession(response.data);

          return response;
        }
      })
      .catch((err) => {
        if (err.errors.length && err.errors[0]) {
          this.props.showNotification({
            type: 'error',
            message: err.errors,
          });
        }

        // Track session for any error on file upload (non-LA account)
        if (!this.props.accountId && typeof window.hj === 'function') {
          window.hj('tagRecording', ['activation_form_save_error']);
        }

        return err;
      });
  };

  deleteFile = (name, cb) => {
    const { showNotification, data } = this.props;
    const documents = data.documents;
    if (documents && documents[name] && documents[name].length) {
      const curDoc = documents[name][0];
      return merchantFetch({
        url: `merchant/documents/doc_${curDoc.id}`,
        method: 'delete',
        mode: 'live',
        accountId: this.props.submerchantId,
      })
        .then((res) => {
          if (res.success && res.data) {
            this.props.updateActivationData(res.data);
            cb && cb();
            showNotification({
              type: 'success',
              message: 'File deleted successfully',
            });
          }
        })
        .catch((err) => {
          showNotification({
            type: 'error',
            message: 'File Not Found!',
          });
        });
    }
  };

  // Fetch state_code and city to auto populate business_*_state and business_*_city fields in form
  getPincodeDetails(pincode) {
    return merchantFetch(`pincodes/${pincode}`)
      .then((response) => {
        if (response.data) {
          return {
            city: response.data.city,
            state_code: response.data.state_code,
          };
        }

        return null;
      })
      .catch((err) => {
        return null;
      });
  }

  openWizard = () => {
    trackLinkClick('Activate Now');
    this.setState({ openWizard: true });
  };

  saveDirtyState = (e) => {
    this.wizard && this.wizard.goto(null); // To save existing tab in Activation Wizard
  };

  goToDashboard = () => {
    if (this.props.submerchantId) {
      this.props.history.replace(`/partners/submerchants`);
    } else {
      this.props.history.replace(`/`);
    }
  };

  handleUIUpdate = () => {
    return this.props.handleUIUpdate && this.props.handleUIUpdate();
  };

  getBankVerificationAttemptCount = () => {
    try {
      fetchModalConfigDetails('onboarding').then((res) => {
        if (res?.data) {
          this.setState({ bvsApiCount: res.data?.bank_account_verification_attempt_count });
        }
      });
    } catch (err) {}
  };

  componentDidMount() {
    const { submitted } = this.props.user;
    this.handleUIUpdate();
    this.getBankVerificationAttemptCount();

    if (this.hasGstinLLpinCinSyncFlow && !submitted) {
      this.startPollingCounter();
    }
    this.props?.sendEventsForSubMerchantView?.(
      window.rzpQ
        .routeActions()
        .initiated('route.linked_account.activate_account.bank_account_details'),
    );
    this.props.trackEvents({
      objectName: 'Modal',
      actionName: 'Displayed',
      screen: 'home page',
      properties: {
        'Modal Label': 'KYC Form',
        submerchant_id: this.props.submerchantId,
      },
    });
  }

  handleRxCaCheckboxChange = () => {
    this.setState(
      (state) => ({
        rxCaCheckboxSelect: !state.rxCaCheckboxSelect, // set local state
      }),
      () => {
        const _settings = { ...this.props.user.user.settings };
        _settings[rxCaSelectedFlag] = '0';

        if (!!this.state.rxCaCheckboxSelect) {
          _settings[rxCaSelectedFlag] = '1';
        }

        // update the status to settings table so that it persists post refresh
        merchantFetch({
          url: 'users',
          mode: 'live',
          method: 'patch',
          data: { settings: _settings },
        }).then((res) => {
          this.props.updateUser({ settings: _settings });
        });
      },
    );
  };

  componentWillUnmount() {
    this.handleSupportModalClose();
    clearInterval(this.intervalTimer);
  }

  getWizardUser() {
    if (this.props.submerchantId) {
      // submerchantUser received from submerchantContainer.js
      return this.props.submerchantUser;
    } else {
      return this.props.user;
    }
  }

  /*
   * 1. For linked account form, only spinner or Activation wizard.
   * 2. For main account form, spinner, Welcome Screen, Activation wizard and Success screens are shown.
   * */
  render() {
    const accountId = this.props.accountId; // If accountId present, then Welcome screen and Success screen are not required.

    let {
      data,
      categories,
      aovRange,
      clarificationReasons,
      gstinDetails,
      isModalView,
      isActivationFormLoading,
      user,
      businessTypeOptions,
    } = this.props;

    let content, modalClass;

    if (!accountId && this.state.showSuccessScreen) {
      if (!user.showInstantActivation) {
        modalClass = 'Activation--success';
        content = <SuccessScreen />;
      } else {
        this.props.showKYCStatusModal({
          modalType: 'KYC_ACTIVATION_SUBMIT_MODAL',
        });
        this.goToDashboard();
        content = null;
      }
    } else if (!accountId && !this.state.isFormTouched && !this.state.openWizard) {
      modalClass = 'Activation--welcome';
      content = <WelcomeScreen onClose={this.props.onClose} openWizard={this.openWizard} />;
    } else {
      modalClass = 'Activation--wizard';

      content = (
        <ActivationWizard
          accountId={this.props.accountId}
          submerchantId={this.props.submerchantId}
          data={data}
          clarificationReasons={clarificationReasons}
          ref={(refId) => (this.wizard = refId)}
          categories={categories}
          isFormTouched={this.state.isFormTouched}
          save={this.saveStep}
          saveFile={this.saveFile}
          submitForm={this.submitForm}
          getPincodeDetails={this.getPincodeDetails}
          defaultMsg={this.props.defaultMsg}
          handleUIUpdate={this.handleUIUpdate}
          deleteFile={this.deleteFile}
          rxCaCheckboxSelect={this.state.rxCaCheckboxSelect}
          handleRxCaCheckboxChange={this.handleRxCaCheckboxChange}
          fetchBusinessCategory={this.fetchBusinessCategory}
          aovRange={aovRange}
          trackEvent={this.props.tracking.trackEvent}
          fetchMerchantDetails={this.fetchMerchantDetails}
          gstinDetails={gstinDetails}
          isModalView={isModalView && isActivationFormLoading}
          showL2Form={this.state.showL2Form}
          bvsApiCount={this.state.bvsApiCount}
          fetchBankVerificationAttemptCount={this.getBankVerificationAttemptCount}
          partnerActivationData={this.props.partnerActivationData}
          user={this.getWizardUser()}
          businessTypes={businessTypeOptions}
        />
      );
    }

    if (modalClass) {
      this.props.setAdditionalModalClass(modalClass);
    }

    return content;
  }
}

ActivationContainer.MODAL_MASK_CLASS = 'Activation';

/*
 * Success screen is shown only when the user has submitted the form. It's not shown in linked account activation but only main form.
 * */
export const SuccessScreen = ({ formName = 'Activation Form' }) => {
  function clickConfig(e) {
    trackGoToConfig();
  }

  return (
    <div class="Activation--success">
      <div class="Activation-title">
        <side-title>{formName} submitted Successfully!</side-title>
        <img src={successImg} class="submit-illustration" />
      </div>
      <div class="Activation-info">
        <i class="i i-check" /> {formName} Submitted
        <p class="desc">
          Our team will review the form and submitted documents. We will reach out on your contact
          email for all updates.
        </p>
      </div>

      <div class="Activation-actions">
        <side-title>What's Next?</side-title>
        <LinkCard
          title={'Personalise Your Account'}
          description={'Personalise your checkout form, emails and pages with your logo and brand.'}
          icon={'icon-done'}
          onClick={clickConfig}
          to="/config"
        />
      </div>
    </div>
  );
};

/*
 * Welcome screen is shown only when the user has not started filling the form. It's not shown in linked account activation but only main form.
 * */
export const WelcomeScreen = ({ openWizard }) => {
  return (
    <div class="Activation--welcome">
      <h3> Get Started with Activation</h3>
      <div class="underline" />
      <img src={welcomeImg} class="welcome-illustration" />
      <div class="short-content">
        <p>
          Simply submit your business details and upload relevant proofs to start accepting
          payments.
        </p>
        <p>Once you submit the form, our team will review it to activate your account.</p>

        <Button.Primary onClick={openWizard}>Go to Activation Form</Button.Primary>
      </div>
    </div>
  );
};

// ActivationContainer.MODAL_MASK_CLASS = 'Activation';

/*
 * Check if user filled any of the fields to be filled on fresh form
 * * */
export function isFormTouched(data) {
  if (!data) {
    return false;
  }

  let isDirty = false;

  Object.keys(data).find((key) => {
    if (defaultKeysInForm.indexOf(key) > -1 || excludedFieldsInForm.indexOf(key) > -1) {
      return false;
    }
    if (data[key] != null) {
      isDirty = true;
      return true;
    }
  });

  return isDirty;
}

export const InstantActivationLoadingState = ({ contactName }) => {
  return (
    <div className="activation-loader">
      <div className="spin-btn extra-large extra-width visible activation-spinner"></div>
      <div className="text">
        Hey {contactName}, we are verifying your entered details, this may take a minute.
      </div>
    </div>
  );
};

const defaultKeysInForm = ['contact_name', 'contact_email', 'contact_mobile'];
const excludedFieldsInForm = [
  'created_at',
  'business_international',
  'business_name',
  'business_type',
  'transaction_volume',
  'role',
  'department',
  'locked',
  'updated_at',
  'submitted',
  'can_submit',
  'steps_finished',
  'verification',
  'archived',
  'activated',
  'activation_progress',
  'allowed_next_activation_statuses',
];

export default withRouter(ActivationContainer);
