import React from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
// eslint-disable-next-line import/no-extraneous-dependencies
import { createPopup } from '@typeform/embed';
import { compose } from 'redux';

import User from 'merchant/models/User';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { updateSession } from 'merchant/reducers/session';
import { fetchAddWebsiteWorkflowStatus } from 'merchant/reducers/profile';
import { fetchSchedule } from 'merchant/reducers/settlements/details';
import { isPresent } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';

import { getItem, setItem } from 'common/utils/localStorage';

import RequestInitiateModal from './components/InternationalConfigComponents/RequestInitiateModal';
import RequestSubmittedModal from './components/InternationalConfigComponents/RequestSubmittedModal';
import Questionnaire from './Questionnaire';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { trackFormSubmitted, trackRequestClicked } from './Questionnaire/analytics';

const NO_ACTION_RECEIVED = 'no_action_received';
const IN_REVIEW = 'in_review';
const APPROVED = 'approved';
const REJECTED = 'rejected';

function withInternationalConfig(WrappedComponent) {
  class InternationalConfig extends React.Component {
    constructor(props) {
      super(props);
      const currentMID = props.user.current;
      this.internationalSection = React.createRef();
      const isAccessRequested = getItem(`international-access-requested-${currentMID}`);
      this.state = {
        isAccessRequested,
        pgProductStatus: '', // Payment Gateway
        otherProductsStatus: '', // otherProducts = Payment Pages, Payment Links, Invoices
        requestedAccessFrom: '',
        isWebsiteInWorkflow: null,
        showStatusLabel: props.user.international,
        questionnaireStatus: null,
      };
      this.initializeTypeForm();
    }

    initializeTypeForm = () => {
      const currentMID = this.props.user.current;
      const IntlEnableTypeForm = createPopup('jJCZoO', {
        hideHeaders: true,
        hideFooters: true,
        hidden: { mid: currentMID },
        onSubmit: this.handleTypeFormSubmitted,
      });
      this.IntlEnableTypeForm = IntlEnableTypeForm; // saving reference typeform
    };

    async getQuestionnaireCompletion() {
      try {
        const status = await merchantFetch({
          url: 'international_enablement/preview',
          mode: 'live',
          method: 'GET',
        });

        if (status && status.success) {
          this.setState({ questionnaireStatus: status.data });
        }
      } catch (err) {
        this.props.showNotification({
          type: 'error',
          message: 'Could not fetch international questionnaire status!', // TODO: Fix appropriate message for questionnaire vs typeform
        });
      }
    }

    async componentDidMount() {
      this.getQuestionnaireCompletion();

      try {
        const fetchStatusRes = await merchantFetch({
          url: 'merchants/product_international/workflow/status/all',
          mode: 'live',
          method: 'GET',
        });

        if (fetchStatusRes.success) {
          this.setInternationalFlowStatusForProducts(fetchStatusRes.data.data);
        }
      } catch (err) {
        this.props.showNotification({
          type: 'error',
          message:
            err.status_code === 500
              ? 'Could not fetch international payments feature status!'
              : err.errors[0],
        });
      }

      this.props
        .fetchAddWebsiteWorkflowStatus()
        .then(({ data }) => {
          this.setState({
            isWebsiteInWorkflow: data,
          });
        })
        .catch(() => {
          this.props.showNotification({
            type: 'error',
            message: 'Could not fetch website workflow status.',
          });
        });

      this.props.fetchSchedule();
    }

    setInternationalFlowStatusForProducts = (internationalWorkflowStatus) => {
      const currentMID = this.props.user.current;

      const isPGStatusSetInLocalStorage = !!getItem(`international-pg-${currentMID}`);
      const isOtherProductsStatusInLocalStorage = !!getItem(
        `international-otherProducts-${currentMID}`,
      );

      let pgProductStatus = '';
      let otherProductsStatus = '';

      pgProductStatus = internationalWorkflowStatus.payment_gateway;

      if (pgProductStatus === NO_ACTION_RECEIVED && isPGStatusSetInLocalStorage) {
        pgProductStatus = IN_REVIEW;
      }

      otherProductsStatus = internationalWorkflowStatus.payment_links;

      if (otherProductsStatus === NO_ACTION_RECEIVED && isOtherProductsStatusInLocalStorage) {
        otherProductsStatus = IN_REVIEW;
      }

      this.setState({ pgProductStatus, otherProductsStatus });
    };

    analytics = (action) => {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Settings',
        eventAction: `${action} - International card payments`,
      });
    };

    closeModal = () => {
      this.props.closeModal();
    };

    openRequestInitiateModal = ({ triggerSource = '' }) => {
      const { questionnaireStatus } = this.state;

      const objectName = questionnaireStatus?.new_flow ? 'intl enablement form' : 'intl typeform';
      const actionName =
        questionnaireStatus?.new_flow &&
        ['in_progress', 'submitted'].includes(questionnaireStatus?.enablement_progress)
          ? 'edit draft'
          : 'request access';
      trackRequestClicked(actionName, objectName);
      let modalOptions = {
        component: (
          <RequestInitiateModal
            closeModal={this.closeModal}
            openTypeForm={this.openTypeForm}
            triggerSource={triggerSource}
            showNotification={this.props.showNotification}
          />
        ),
        size: 'medium',
      };

      if (questionnaireStatus?.new_flow) {
        modalOptions = {
          component: <Questionnaire triggerSource={triggerSource} isRevampFlow />,
          overlayStyles: { display: 'flex', justifyContent: 'center', alignItems: 'center' },
        };
      }
      this.props.openModal(modalOptions);
    };

    openTypeForm = (triggerSource) => {
      this.setState(
        {
          requestedAccessFrom: triggerSource ? triggerSource : 'requestedFromHeader',
        },
        () => {
          this.IntlEnableTypeForm.open();
        },
      );
    };

    handleTypeFormSubmitted = () => {
      const { requestedAccessFrom } = this.state;
      const currentMID = this.props.user.current;

      if (requestedAccessFrom === 'requestedFromHeader') {
        setItem(`international-access-requested-${currentMID}`, true);
      } else {
        setItem(`international-${requestedAccessFrom}-${currentMID}`, true);
      }

      let stateKeyToUpdate = '';

      if (requestedAccessFrom === 'pg') {
        stateKeyToUpdate = 'pgProductStatus';
      } else if (requestedAccessFrom === 'otherProducts') {
        stateKeyToUpdate = 'otherProductsStatus';
      } else stateKeyToUpdate = 'isAccessRequested';

      this.setState({ [stateKeyToUpdate]: IN_REVIEW }, () => {
        trackFormSubmitted('intl typeform');
        this.IntlEnableTypeForm.close();
        this.openRequestSubmittedModal();
      });
    };

    openRequestSubmittedModal = () => {
      this.props.openModal({
        component: <RequestSubmittedModal closeModal={this.closeModal} />,
        size: 'medium',
      });
    };

    toggleInternationalization = (enableInternational, postActionCB) => {
      window.rzpQ.onbr().initiated('dash.settings_action', {
        action: 'Toggle_International_Payments',
      });
      this.analytics(enableInternational ? 'Enable' : 'Disable');
      selfServeTrackInitiate({
        selfServeAction: 'International Payments Applied',
        page: 'Config',
        screen: 'Settings',
      });
      this.setState({ showStatusLabel: enableInternational });

      return merchantFetch({
        url: 'merchant/international',
        method: 'PATCH',
        data: {
          international: enableInternational ? 1 : 0,
        },
      })
        .then((resp) => {
          // Check if the response sets international as intended in this request
          if (resp.data.international === !!enableInternational) {
            selfServeTrackSuccess({
              selfServeAction: 'International Payments Applied',
              page: 'Config',
              screen: 'Settings',
            });
            postActionCB(true);
            // Update user in store
            const user = new User({
              ...this.props.user,
              international: resp.data.international,
            });

            this.props.updateSession({ user });
          } else {
            throw new Error(
              'We are unable to process this request. Please reach out to support@razorpay.com',
            ); // This code is ideally unreachable as per business logic. However, since Api silently fails here, hence handling explicitly.
          }
        })
        .catch((err) => {
          postActionCB(false);
          let error = 'Something went wrong!';

          if (typeof err === 'object' && err.hasOwnProperty('message')) {
            error = err.message;
          } else if (err.errors) {
            error = err.errors[1];
          } else {
            error = err;
          }

          this.props.showNotification({
            type: 'error',
            message: error,
          });
        });
    };

    hasRequestedAccessForProduct = (product) => {
      const currentMID = this.props.user.current;

      const isProductAccessRequested = getItem(`international-${product}-${currentMID}`);

      return this.isStatusUpdatedForProduct(product) || isPresent(isProductAccessRequested);
    };

    get currentStatusOnHeader() {
      const { pgProductStatus, otherProductsStatus } = this.state;

      if (!(pgProductStatus || otherProductsStatus) || this.isAnyProductIntlApproved) {
        return null;
      }

      if (!this.isStatusUpdatedForAnyProduct && this.state.isAccessRequested) {
        return IN_REVIEW;
      }

      const isAnyProductInReview =
        pgProductStatus === IN_REVIEW || otherProductsStatus === IN_REVIEW;

      if (isAnyProductInReview) {
        return IN_REVIEW;
      }

      const allProductsRejected = pgProductStatus === REJECTED && otherProductsStatus === REJECTED;

      if (allProductsRejected) {
        return REJECTED;
      }

      return null;
    }

    isStatusUpdatedForProduct = (product) => {
      const { pgProductStatus, otherProductsStatus } = this.state;

      if (!pgProductStatus && !otherProductsStatus) return false;

      if (product === 'pg' && pgProductStatus !== NO_ACTION_RECEIVED) {
        return true;
      }

      if (product === 'otherProducts' && otherProductsStatus !== NO_ACTION_RECEIVED) {
        return true;
      }

      return false;
    };

    get isStatusUpdatedForAnyProduct() {
      const { pgProductStatus, otherProductsStatus } = this.state;

      return pgProductStatus !== NO_ACTION_RECEIVED || otherProductsStatus !== NO_ACTION_RECEIVED;
    }

    get isRequestAccessAllowed() {
      const { isAccessRequested } = this.state;

      return (
        this.isInternationalGreyList && !isAccessRequested && !this.isStatusUpdatedForAnyProduct
      );
    }

    get isInternationalPaymentsAllowed() {
      const { user } = this.props;

      if (user.isUnregisteredBusiness && !this.isAnyProductIntlApproved) {
        return false;
      }

      if (this.isInternationalBlackList) {
        return false;
      }

      return true;
    }

    get isInternationalGreyList() {
      return !!(
        this.props.user.international_activation_flow &&
        this.props.user.international_activation_flow === 'greylist'
      );
    }

    get isAnyProductIntlApproved() {
      return this.state.pgProductStatus === APPROVED || this.state.otherProductsStatus === APPROVED;
    }

    get isInternationalWhiteList() {
      return !!(
        this.props.user.international_activation_flow &&
        this.props.user.international_activation_flow === 'whitelist'
      );
    }

    get isInternationalBlackList() {
      return !!(
        this.props.user.international_activation_flow &&
        this.props.user.international_activation_flow === 'blacklist'
      );
    }

    get isKycComplete() {
      return this.props.user.isAccepted;
    }

    get description() {
      const { user } = this.props;
      const _isInternationalPaymentsAllowed = this.isInternationalPaymentsAllowed;

      let line = '';

      if (this.isInternationalGreyList && !this.isKycComplete) {
        if (
          user.activation_status === 'under_review' ||
          user.activation_status === 'kyc_qualified_unactivated' ||
          user.activation_status === 'needs_clarification'
        ) {
          line =
            'Your KYC form is under review. You will be able to request access for international payments once your KYC is approved.';
        } else {
          line = 'Please submit your KYC form to request access.';
        }
      } else if (!this.isWebsiteAdded) {
        if (this.isInternationalWhiteList) {
          line = 'Add a website to enable international payments.';
        } else if (this.isInternationalGreyList) {
          line = 'You need to add your website to request access for international payments.';
        }
      } else if (this.isInternationalWhiteList && user.isAccepted) {
        // Intl. whitelist but added website after L1 completion
        line =
          'Your website is currently in review. International payments will be enabled once website is approved.';
      } else if (this.isInternationalWhiteList && user.instantActivation.isL1Submitted) {
        line = 'Please complete your KYC to enable international payments.';
      }

      if (_isInternationalPaymentsAllowed) {
        return <>{!this.isAnyProductIntlApproved && line}</>;
      }

      return <>International card payments is not supported for your business model.</>;
    }

    get isWebsiteAdded() {
      return this.props.user.business_website || this.state.isWebsiteInWorkflow;
    }

    render() {
      const internationalEnabled = this.props.user.international;
      const isTogglerVisible = this.isAnyProductIntlApproved;
      const { settlement, user } = this.props;
      const { pgProductStatus, otherProductsStatus, showStatusLabel } = this.state;

      const settlementCycle = settlement.schedule.data.filter(
        (item) => item.method === null && item.international === 1,
      );

      const productStatus = {
        pg: { status: pgProductStatus, isRequested: !this.hasRequestedAccessForProduct('pg') },
        otherProducts: {
          status: otherProductsStatus,
          isRequested: !this.hasRequestedAccessForProduct('otherProducts'),
        },
      };

      const config = {
        showStatusLabel,
        isKycComplete: this.isKycComplete,
        isTogglerVisible,
        isWebsiteAdded: this.isWebsiteAdded,
        internationalEnabled,
        currentStatusOnHeader: this.currentStatusOnHeader,
        maxPaymentAmount: user.merchant.max_payment_amount,
        questionnaireStatus: this.state.questionnaireStatus,
        isRequestAccessAllowed: this.isRequestAccessAllowed,
        isAnyProductIntlApproved: this.isAnyProductIntlApproved,
        isInternationalBlackList: this.isInternationalBlackList,
        isInternationalPaymentsAllowed: this.isInternationalPaymentsAllowed,
      };

      return (
        <WrappedComponent
          {...this.props}
          config={config}
          productStatus={productStatus}
          description={this.description}
          onRequestAccessClick={this.openRequestInitiateModal}
          toggleInternationalization={this.toggleInternationalization}
          settlementDelay={settlementCycle.length && settlementCycle[0].delay}
        />
      );
    }
  }

  return compose(
    connect(
      (state) => ({
        user: state.session.user,
        settlement: state.settlement,
      }),
      {
        openModal,
        closeModal,
        fetchSchedule,
        showNotification,
        updateSession,
        fetchAddWebsiteWorkflowStatus,
      },
    ),
    // eslint-disable-next-line
    RTracking(() => window.rzpQ.component('InternationalConfig')),
  )(InternationalConfig);
}

export default withInternationalConfig;
