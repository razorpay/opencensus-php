/* eslint-disable */

import { Component } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import QueryString from 'query-string';
import { withRouter } from 'react-router-dom';

import { Modal, ModalContent } from 'common/new-ui/Modal';
import { classList } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';
import * as EventsActions from 'merchant/reducers/trackEvents';

import KycForm from './new';
import { setInstantActivationsTracking } from './ga_new';
import KYCStatusModal from 'merchant/views/PartnerDashboard/Activation/Components/KYCStatus/KYCStatusModal';

const SOURCE_RAZORPAY_X = 'x';

@withRouter
@RTracking(() => window.rzpQ.component('ActivationContainer'))
@connect(
  (state) => ({
    user: state.session.user,
    session: state.session,
    current_tab_name: state.activationWizard.current_tab_name,
  }),
  { ...EventsActions },
)
export default class ActivationContainer extends Component {
  constructor(props) {
    super(props);

    const IS_SUB_MERCHANT_VIEW = props.location.pathname.includes('route');

    this.state = {
      data: null,
      categories: null,
      additionalModalClass: null,
      aovRange: null,
      clarificationReasons: null,
      gstinDetails: null,
      isActivationFormLoading: false,
      showWelcomeBanner: false,
      submerchantId: props.submerchantId && props.submerchantId.replace('acc_', ''),
      shouldBlockMerchantKYC: false,
      partnerActivationData: null,
    };

    this.fetchActivationDetails = this.fetchActivationDetails.bind(this);
    this.setAdditionalModalClass = this.setAdditionalModalClass.bind(this);
    this.handleNewData = this.handleNewData.bind(this);

    if (props.user.showInstantActivation) {
      setInstantActivationsTracking();
    }

    if (window.RZP && window.RZP.appName === 'businessbanking') {
      const commonActivationMethods = [
        {
          name: 'notifyWindowResize',
          hasReply: true,
          callback: (reply) => {
            this.handleUIUpdate = () => {
              const body = document.body;
              reply(body.clientWidth, body.clientHeight);
            };
          },
        },
        {
          name: 'notifyUnmount',
          hasReply: true,
          callback: (reply) => {
            this.handleUnmount = reply;
          },
        },
      ];
      const iaActivationMethods = [
        {
          name: 'submitForm',
          hasReply: true,
        },
        {
          name: 'notifyFormValidity',
          hasReply: true,
        },
      ];
      const kycActivationMethods = [
        {
          name: 'notifyOnKYCSuccess',
          hasReply: true,
        },
        {
          name: 'notifySupportPopupOpen',
          hasReply: true,
        },
        {
          name: 'notifySupportPopupClose',
          hasReply: true,
        },
      ];

      const { isL1Submitted } = props.user.instantActivation;

      this.rpc = window.RZP.rpcServer(
        window.RZP.appHost,
        (isL1Submitted ? kycActivationMethods : iaActivationMethods).concat(
          commonActivationMethods,
        ),
        'activation',
      );
    }

    const query = QueryString.parse(props.location.search);
    this.isSourceRX = !!(query && query.merchant && query.merchant === SOURCE_RAZORPAY_X);
  }

  setAdditionalModalClass(additionalModalClass) {
    return (
      additionalModalClass !== this.state.additionalModalClass &&
      this.setState({
        additionalModalClass,
      })
    );
  }

  fetchActivationDetails(accountId) {
    const isLiteOnboarding = this.props?.user?.isLiteOnboarding;
    // Check Partner activation status for eligible users
    const shouldCheckForPartnerActivationStatus =
      !this.isSourceRX &&
      this.props.user.isPartner() &&
      this.props.user.isIndependentPartnerKYCEnabled;
    if (shouldCheckForPartnerActivationStatus) {
      this.fetchPartnerActivationDetails();
    }
    return Promise.all([
      merchantFetch({
        url: 'merchant/activation',
        // For accountId, mode must be respected, otherwise accountId in Headers would be ignored in api.
        mode: !!accountId ? this.props.session.mode : 'live',
        accountId: accountId || this.state.submerchantId,
      }),
      !accountId && merchantFetch('merchant/activation/business_categories'),
      !isLiteOnboarding && !this.isSourceRX && merchantFetch('merchant/aov-config'),
    ]).then(async ([data, categories, aov_list]) => {
      data = data.data;
      categories = categories && categories.data;
      aov_list = aov_list && aov_list.data;

      let gst_details;
      let gstinDetails = null;

      try {
        // call only if L1 form fill up is completed
        if (this.props.user.isGstinAutoPopulate && data?.activation_form_milestone) {
          gst_details = await merchantFetch('merchant/activation/gst_details');
          gst_details = gst_details?.data;
        }
      } catch {}

      if (gst_details?.results && gst_details.results.length) {
        gstinDetails = {
          gstinList: gst_details.results,
          defaultGstin: gst_details.results[0],
        };
      }

      if (data.activation_status === 'needs_clarification' && data.kyc_clarification_reasons) {
        merchantFetch('merchant/activation/clarification_reasons').then((clarification_reasons) => {
          const clarificationReasons = clarification_reasons && clarification_reasons.data;
          this.setState({
            data,
            categories,
            aovRange: aov_list,
            clarificationReasons,
            gstinDetails,
          });

          return [data, categories];
        });
      } else {
        this.setState({
          data,
          categories,
          aovRange: aov_list,
          clarificationReasons: {},
          gstinDetails,
        });

        return [data, categories];
      }
    });
  }

  fetchPartnerActivationDetails = () => {
    merchantFetch({
      url: 'partner/activation',
    }).then((res) => {
      const data = res?.data || {};
      let shouldBlockMerchantKYC = false;
      // block the merchant KYC if Partner KYC is in NC and Merchant KYC form is not submitted
      if (
        data?.partner_activation?.activation_status === 'needs_clarification' &&
        data.submitted === false
      ) {
        shouldBlockMerchantKYC = true;
      }
      this.setState({
        partnerActivationData: data,
        shouldBlockMerchantKYC,
      });
    });
  };

  updateActivationData = (activationData) => {
    this.setState({
      data: activationData,
    });
  };

  handleNewData(data) {
    this.setState({ data });
  }

  handleCloseActivationForm = (e) => {
    const isL1Submitted = this.props.user.instantActivation.isL1Submitted;
    let eventName = 'act.form_fill';
    if (isL1Submitted) {
      eventName = 'kyc.form_fill';
    }
    this.props.tracking.trackEvent(
      window.rzpQ.onbr().dropped(eventName, {
        clickSource: this.props.current_tab_name,
      }),
    );

    this.sendEventsForSubMerchantView(
      window.rzpQ.routeActions().dropped('route.linked_account.activate_account'),
    );
  };

  sendSegmentEvents = (isFormCloseAction) => {
    const { user } = this.props;
    const isL1Submitted = user.instantActivation.isL1Submitted;
    const objectName = isL1Submitted ? 'L2 form' : 'L1 form';
    const actionName = isFormCloseAction ? 'Closed' : 'Loaded';

    this.props.trackEvents({
      objectName,
      actionName,
      screen: 'home page',
      toCleverTap: true,
    });

    if (isFormCloseAction) {
      this.props.trackEvents({
        objectName: 'Modal',
        actionName: 'Closed',
        screen: 'home page',
        properties: {
          'Modal Label': 'KYC Form',
        },
      });
    }
  };

  componentWillMount() {
    this.fetchActivationDetails(this.props.accountId);
  }

  componentDidMount() {
    const isFormCloseAction = false;
    this.sendSegmentEvents(isFormCloseAction);
    this.sendEventsForSubMerchantView(
      window.rzpQ.routeActions().interaction('route.linked_account.activate_account.started'),
    );
  }

  setOnCloseCb(cb) {
    this.onCloseCB = cb;
  }

  componentWillUnmount() {
    const isFormCloseAction = true;
    this.sendSegmentEvents(isFormCloseAction);
    this.handleUnmount && this.handleUnmount();
  }

  sendEventsForSubMerchantView = (event) => {
    if (!this.IS_SUB_MERCHANT_VIEW || !event) return;

    this.props.tracking.trackEvent(event);
  };

  setActivationFormLoadingState = () => {
    this.setState({ isActivationFormLoading: !this.state.isActivationFormLoading });
  };

  render() {
    const {
      data,
      categories,
      additionalModalClass,
      aovRange,
      clarificationReasons,
      gstinDetails,
      isActivationFormLoading,
      showWelcomeBanner,
      submerchantId,
      partnerActivationData,
      shouldBlockMerchantKYC,
    } = this.state;
    const { user } = this.props;
    const commonProps = {
      accountId: this.props.accountId,
      submerchantId,
      fetchActivationDetails: this.fetchActivationDetails,
      updateActivationData: this.updateActivationData,
      data,
      clarificationReasons,
      categories,
      handleUIUpdate: this.handleUIUpdate,
      rpc: this.rpc,
      aovRange,
      gstinDetails,
      setActivationFormLoadingState: this.setActivationFormLoadingState,
      isActivationFormLoading,
    };
    const isLoading = !data;
    // `onClose` is passed only when Modal is to be opened. In case of Account Details, onClose is passed.
    const isModal = !!this.props.onClose && this.props.location.pathname !== '/kyc';

    let content = null;
    let modalClasses = ['animate-down'];
    let trackerIntent = null;

    if (isLoading) {
      modalClasses = ['spinner', 'transparent'];

      content = (
        <div className="spinner-container">
          <div className={classList('spin-btn large page-center visible', isModal && 'gray')} />
        </div>
      );
    } else if (shouldBlockMerchantKYC) {
      // show modal to complete the Partner KYC first before proceeding to Merchant KYC
      content = (
        <KYCStatusModal
          onGoToDashboard={() => {
            this.props.history.push('/partners');
          }}
          activationStatus={'needs_clarification'}
          modalType={'MERCHANT_KYC_BLOCKED_MODAL'}
        />
      );
    } else {
      content = (
        <KycForm
          {...commonProps}
          onNewData={this.handleNewData}
          setAdditionalModalClass={this.setAdditionalModalClass}
          sendEventsForSubMerchantView={this.sendEventsForSubMerchantView}
          isModalView={isModal}
          partnerActivationData={partnerActivationData}
        />
      );
      trackerIntent = 'kyc.form_fill';
    }

    if (additionalModalClass) {
      modalClasses.push(additionalModalClass);
    }

    return isModal ? (
      <div>
        <Modal
          class={classList(...modalClasses)}
          onClose={this.props.onClose}
          onCloseCB={this.handleCloseActivationForm}
          canDisableCloseBtn={isActivationFormLoading}
        >
          <ModalContent>{content || spinner}</ModalContent>
        </Modal>
      </div>
    ) : (
      <div>
        <div
          className={
            this.props.location.pathname === '/kyc'
              ? 'ActivationContainer kyc fullViewFormContainer'
              : 'ActivationContainer kyc'
          }
        >
          {showWelcomeBanner && (
            <div className="welcome-header">
              <div>Welcome {user.contact_name}</div>
              <div className="description">
                Activate your account to start accepting payments 🎉
              </div>
            </div>
          )}
          {content || spinner}
        </div>
      </div>
    );
  }
}
