import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { Step, StepTitle, StepContent, possibleStatuses } from './Step';
import RTracking from 'react-tracking';
import { getActivationState } from 'merchant/components/Activation/ActivationUtils';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import SettlementSchedule from 'merchant/views/Settlements/Settlements/components/SettlementSchedule';
import { openModal } from 'merchant_common/reducers/modals';
import * as EventsActions from 'merchant/reducers/trackEvents';
import ProductsModal from 'merchant/components/Home/ProductsModal';
import { trackProductsModal } from './ga';
import { compose } from 'redux';
import {
  showProductsModal,
  hideProductsModal as hideProductsModalAction,
} from 'merchant/reducers/home';

const initialState = {
  status: null,
  content: null,
  title: 'Account Activation',
  showProductModal: false,
};

class ActivationCard extends Component {
  constructor(props) {
    super(props);
    this.state = initialState;
  }

  handleBlackListFlowClick = () => {
    const { track, tracking } = this.props;
    track.refillActivationForm();
    tracking.trackEvent(
      window.rzpQ.onbr().initiated('act.form_fill', {
        clickSource: 'Modify_Business_Category',
      }),
    );
    tracking.trackEvent(
      window.rzpQ.onbr().initiated('act.blacklist_change_category', {
        clickSource: 'Dashboard_Link',
      }),
    );
  };

  componentWillReceiveProps(nextProps) {
    let { status, content, title } = initialState;
    const { onActive, track, user } = nextProps;
    const { limitBreach } = this.props;

    const limitBreachHappened =
      !!limitBreach && limitBreach.type === 'payment_breach'
        ? (limitBreach.amount * 100) / limitBreach.limit >= 100
        : false;

    const activationState = getActivationState(user, user.isUnregisteredBusiness);
    const L2_dedupe_blocked = activationState === 'L2_dedupe_blocked';
    const isReferredMerchant = this.props.referee?.status === 'signup';
    const activationFormUrl = user.isActivationFormFullView ? '/kyc' : '/activation';

    switch (activationState) {
      case 'L1_Start': {
        title = 'Live payments and Settlements';
        status = possibleStatuses.active;
        content = (
          <>
            {isReferredMerchant ? (
              <div>
                Complete this step to receive{' '}
                <strong>{getFormattedAmountNew(this.props.referee.referral_amount, true)}</strong>{' '}
                in collections with zero charges
              </div>
            ) : (
              <div>
                Submit a few KYC details to start accepting payments and receive{' '}
                <a
                  className="btn-link"
                  target="_blank"
                  rel="noopener noreferrer"
                  href="http://razorpay.com/settlement"
                >
                  settlement
                </a>{' '}
                in your account
              </div>
            )}
            <div>
              <Link
                to={activationFormUrl}
                className="btn btn-primary"
                onClick={() => {
                  track.activateAccount();
                  this.props.tracking.trackEvent(
                    window.rzpQ.onbr().initiated('act.form_fill', {
                      clickSource: 'onboarding banner',
                    }),
                  );
                  this.props.trackEvents({
                    objectName: 'act form fill initiated',
                    actionName: 'clicked',
                    screen: 'home page',
                    properties: {
                      clickSource: 'onboarding card',
                      milestone: 'L1 start',
                    },
                  });

                  this.props.trackEvents({
                    objectName: `L1 Form`,
                    actionName: 'initiated',
                    screen: 'home page',
                    properties: {
                      ctaLabel: 'Submit KYC',
                      ctaLocation: 'onboarding card',
                    },
                    toCleverTap: true,
                  });
                }}
              >
                Submit KYC
              </Link>
            </div>
          </>
        );
        break;
      }
      case 'L2_dedupe_blocked':
      case 'L1_dedupe_blocked': {
        title = 'Live payments and Settlements';
        status = possibleStatuses.blocked;
        content = `We can’t support your business to accept payments. Please reach out to support for any queriest.
        ${
          L2_dedupe_blocked
            ? 'In case you have pending settlements, you can raise a ticket and get your funds settled to your account.'
            : ''
        }`;
        break;
      }
      case 'payment_disabled':
      case 'poi_failed': {
        title = 'Live payments and Settlements';
        status = possibleStatuses.active;
        content = (
          <div>
            Submit a few KYC details to start accepting payments and receive{' '}
            <a
              className="btn-link"
              target="_blank"
              rel="noopener noreferrer"
              href="http://razorpay.com/settlement"
            >
              settlement
            </a>{' '}
            in your account{' '}
            <div>
              <Link
                to={activationFormUrl}
                className="btn btn-primary"
                onClick={() => {
                  track.activateAccount();
                  this.props.tracking.trackEvent(
                    window.rzpQ.onbr().initiated('kyc.form_fill', {
                      clickSource: 'onboarding banner',
                    }),
                  );
                  this.props.trackEvents({
                    objectName: 'L2 Start',
                    actionName: 'form fill initiated',
                    screen: 'home page',
                    properties: {
                      clickSource: 'form submission popup',
                      milestone: 'L2 Start',
                    },
                  });

                  this.props.trackEvents({
                    objectName: `L2 Form`,
                    actionName: 'initiated',
                    screen: 'home page',
                    properties: {
                      ctaLabel: 'Submit KYC',
                      ctaLocation: 'onboarding card',
                    },
                    toCleverTap: true,
                  });
                }}
              >
                Complete KYC
              </Link>
            </div>
          </div>
        );
        break;
      }
      case 'poi_initiated': {
        title = 'Live payments and Settlements';
        status = possibleStatuses.active;
        content =
          'Your submitted KYC details are under review. You can start accepting payments once the KYC is approved';
        break;
      }
      case 'poi_verified':
      case 'L1_instantly_activated': {
        if (limitBreachHappened) {
          title = 'Live payments and Settlements';
          status = possibleStatuses.blocked;
          content = (
            <div>
              <a
                className="btn-link"
                target="_blank"
                rel="noopener noreferrer"
                href="http://razorpay.com/settlement"
              >
                Settlements
              </a>{' '}
              will be enabled and payments limit will be extended after successful KYC review
            </div>
          );
        } else {
          title = 'Live payments and Settlements';
          status = possibleStatuses.active;
          content = (
            <div>
              <a className="btn-link" onClick={() => this.props.showProductsModal()}>
                Start Accepting payments.
              </a>{' '}
              Settlements will be enabled once your KYC has been reviewed successfully
            </div>
          );
        }
        break;
      }
      case 'under_review_with_tnc_partial': {
        title = 'Live payments and Settlements';
        status = possibleStatuses.active;
        content = (
          <div>
            Payments have been temporarily paused Payments and{' '}
            <a
              className="btn-link"
              target="_blank"
              rel="noopener noreferrer"
              href="http://razorpay.com/settlement"
            >
              Settlements
            </a>{' '}
            will be enabled after successful KYC review
          </div>
        );

        break;
      }
      case 'under_review_without_tnc_partial': {
        title = 'Live payments and Settlements';
        status = possibleStatuses.active;
        content = (
          <div>
            You can accept unlimited payments now. Start receiving{' '}
            <a
              className="btn-link"
              target="_blank"
              rel="noopener noreferrer"
              href="http://razorpay.com/settlement"
            >
              Settlements
            </a>{' '}
            in your account after successful KYC review
          </div>
        );

        break;
      }
      case 'under_review_with_tnc_passed': {
        title = 'Live payments and Settlements';
        status = possibleStatuses.active;
        content = (
          <div>
            You can accept unlimited payments now. Start receiving{' '}
            <a
              className="btn-link"
              target="_blank"
              rel="noopener noreferrer"
              href="http://razorpay.com/settlement"
            >
              Settlements
            </a>{' '}
            in your account after successful KYC review
          </div>
        );

        break;
      }
      case 'under_review_without_tnc_passed': {
        title = 'Live payments and Settlements';
        status = limitBreachHappened ? possibleStatuses.blocked : possibleStatuses.active;
        content = (
          <div>
            You can accept unlimited payments now. Start receiving{' '}
            <a
              className="btn-link"
              target="_blank"
              rel="noopener noreferrer"
              href="http://razorpay.com/settlement"
            >
              Settlements
            </a>{' '}
            in your account after successful KYC review
          </div>
        );

        break;
      }
      case 'under_review_with_tnc': {
        title = 'Live payments and Settlements';
        status = possibleStatuses.active;
        content = (
          <div>
            You can start accepting payments and recieve{' '}
            <a
              className="btn-link"
              target="_blank"
              rel="noopener noreferrer"
              href="http://razorpay.com/settlement"
            >
              Settlements
            </a>{' '}
            in your account after successful KYC review
          </div>
        );

        break;
      }
      case 'under_review_without_tnc': {
        title = 'Live payments and Settlements';
        status = limitBreachHappened ? possibleStatuses.blocked : possibleStatuses.active;
        content = (
          <div>
            You can start accepting payments and recieve{' '}
            <a
              className="btn-link"
              target="_blank"
              rel="noopener noreferrer"
              href="http://razorpay.com/settlement"
            >
              Settlements
            </a>{' '}
            in your account after successful KYC review
          </div>
        );

        break;
      }
      case 'needs_clarificarion': {
        if (user.activated) {
          // if user.activated is 1 it means payment enabled
          title = 'Live payments and Settlements';
          status = limitBreachHappened ? possibleStatuses.blocked : possibleStatuses.active;
          content = (
            <div>
              Payments have been enabled. Start receiving{' '}
              <a
                className="btn-link"
                target="_blank"
                rel="noopener noreferrer"
                href="http://razorpay.com/settlement"
              >
                settlements
              </a>{' '}
              in your account post successful KYC review
            </div>
          );
        } else {
          title = 'Live payments and Settlements';
          status = limitBreachHappened ? possibleStatuses.blocked : possibleStatuses.active;
          content = (
            <div>
              You can start accepting payments and receive settlements{' '}
              <a
                className="btn-link"
                target="_blank"
                rel="noopener noreferrer"
                href="http://razorpay.com/settlement"
              >
                settlements
              </a>{' '}
              in your account after your successful review of your KYC
            </div>
          );
        }
        break;
      }
      case 'needs_clarification_mcc_pending': {
        title = 'Live payments and Settlements';
        status = possibleStatuses.done;
        content = (
          <div>
            Now you can accept unlimited payments and it will be settled into your account according
            to your{' '}
            <a
              className="btn-link"
              target="_blank"
              rel="noopener noreferrer"
              href="http://razorpay.com/settlement"
            >
              settlements schedule
            </a>{' '}
          </div>
        );
        break;
      }
      case 'needs_clarification_funds_on_hold': {
        title = 'Live payments and Settlements';
        status = possibleStatuses.blocked;
        content = (
          <div>
            Your settlements have been paused please update your KYC details to enable{' '}
            <a
              className="btn-link"
              target="_blank"
              rel="noopener noreferrer"
              href="http://razorpay.com/settlement"
            >
              settlements
            </a>
          </div>
        );
        break;
      }
      case 'needs_clarification': {
        title = 'Live payments and Settlements';
        status = possibleStatuses.active;
        content = (
          <div>
            You can start accepting payments and recieve{' '}
            <a
              className="btn-link"
              target="_blank"
              rel="noopener noreferrer"
              href="http://razorpay.com/settlement"
            >
              settlements
            </a>{' '}
            in your account after your successful review of your KYC
          </div>
        );
        break;
      }
      case 'rejected': {
        title = 'Live payments and Settlements';
        status = possibleStatuses.blocked;
        content =
          'We can’t support your business to accept payments. Please reach out to support for any queries';
        break;
      }
      case 'funds_on_hold': {
        title = 'Live payments and Settlements';
        status = possibleStatuses.blocked;
        content = (
          <div>
            You can keep accepting payments{' '}
            <a
              className="btn-link"
              target="_blank"
              rel="noopener noreferrer"
              href="http://razorpay.com/settlement"
            >
              settlements
            </a>{' '}
            will be enabled after successful KYC review
          </div>
        );
        break;
      }
      case 'account_activated': {
        title = 'Live payments and Settlements';
        status = possibleStatuses.done;
        content = (
          <div>
            Now you can accept unlimited payments and it will be settled into your account according
            to your{' '}
            <a
              className="btn-link"
              onClick={() => {
                this.props.openModal({
                  size: 'medium',
                  component: <SettlementSchedule />,
                });
              }}
            >
              settlement schedule
            </a>
          </div>
        );
        break;
      }
      case 'activated_mcc_pending_with_tnc': {
        title = 'Live payments and Settlements';
        status = possibleStatuses.done;
        content = (
          <div>
            You can accept unlimited payments. Settlements are enabled for your account according to
            your{' '}
            <a
              className="btn-link"
              onClick={() => {
                this.props.openModal({
                  size: 'medium',
                  component: <SettlementSchedule />,
                });
              }}
            >
              settlement schedule
            </a>
          </div>
        );
        break;
      }
      case 'activated_mcc_pending_without_tnc': {
        title = 'Live payments and Settlements';
        status = possibleStatuses.done;
        content = (
          <div>
            Now you can accept unlimited payments and it will be settled into your account according
            to your{' '}
            <a
              className="btn-link"
              onClick={() => {
                this.props.openModal({
                  size: 'medium',
                  component: <SettlementSchedule />,
                });
              }}
            >
              settlement schedule
            </a>
          </div>
        );
        break;
      }

      default:
        break;
    }

    if (
      onActive &&
      status !== this.state.status &&
      (status === possibleStatuses.active ||
        status === possibleStatuses.progress ||
        status === possibleStatuses.blocked)
    ) {
      onActive();
    }

    this.setState({
      content,
      title,
      status,
    });
  }

  get internationalPGStatus() {
    return (
      this.props.internationalProductsStatus &&
      this.props.internationalProductsStatus.data.payment_gateway
    );
  }

  get internationalOtherProductsStatus() {
    return (
      this.props.internationalProductsStatus &&
      this.props.internationalProductsStatus.data.payment_links
    );
  }

  get isAllInternationalProductsApproved() {
    return (
      this.internationalPGStatus === 'approved' &&
      this.internationalOtherProductsStatus === 'approved'
    );
  }

  get isAnyInternationalProductsApproved() {
    return (
      this.internationalPGStatus === 'approved' ||
      this.internationalOtherProductsStatus === 'approved'
    );
  }

  get intlWhitelistedContent() {
    const {
      businessWebsite,
      activationStatus,
      isWebsiteInWorkflow,
      instantActivation,
      isAccepted,
    } = this.props;

    if (this.isAllInternationalProductsApproved) {
      return <>You can now start accepting domestic and international payments.</>;
    }

    if (!businessWebsite) {
      if (isWebsiteInWorkflow) {
        return (
          <>
            You can now start accepting domestic payments. Please raise a support ticket post
            website review to start accepting international payments.{' '}
            <Link to="/config#request-international">request here</Link>.
          </>
        );
      }
      return (
        <>
          You can now start accepting domestic payments. Update your{' '}
          <Link to="/profile">website details</Link> to activate international payments.
        </>
      );
    }

    if (activationStatus === 'under_review' && this.internationalPGStatus === 'approved') {
      return (
        <>
          You can now start accepting domestic and international payments via the Payment Gateway.
          You can request for international payments acceptance using other products post KYC
          Verification.
        </>
      );
    }

    if (isAccepted) {
      if (this.internationalPGStatus !== 'approved') {
        return (
          <>
            You can now start accepting domestic payments. Please raise a support ticket to start
            accepting international payments.
          </>
        );
      }

      if (this.internationalOtherProductsStatus === 'in_review') {
        return (
          <>
            You can now start accepting domestic and international payments via the Payment Gateway.
            Your request to accept international payments using our other products is under review.
          </>
        );
      }

      return (
        <>
          You can now start accepting domestic and international payments through our Payment
          Gateway. To accept international payments through other methods,{' '}
          <Link to="/config#request-international">request here</Link>.
        </>
      );
    }

    if (instantActivation.isL1Submitted && this.internationalPGStatus !== 'approved') {
      return (
        <>
          You can now start accepting domestic payments. You can start accepting international
          payments via the Payment Gateway post KYC Verification.
        </>
      );
    }

    return (
      <>
        You can now start accepting domestic and international payments, through our payment
        gateway. <Link to="/config#request-international">View Details</Link>
      </>
    );
  }

  get intlGreylistedContent() {
    const { isAccepted, businessWebsite, isWebsiteInWorkflow } = this.props;

    if (this.isAnyInternationalProductsApproved) {
      return (
        <>
          You can now start accepting domestic payments. Please raise a support ticket post website
          review to start accepting international payments.{' '}
          <Link to="/config#request-international">request here</Link>.
        </>
      );
    }

    if (
      this.internationalPGStatus === 'in_review' ||
      this.internationalOtherProductsStatus === 'in_review'
    ) {
      return (
        <>
          You can now start accepting domestic payments. Your request to enable international
          payments is under review.
        </>
      );
    }

    if (!businessWebsite && !isWebsiteInWorkflow) {
      return (
        <>
          You can now start accepting domestic payments. Update your{' '}
          <Link to="/profile">website details</Link> to activate international payments.
        </>
      );
    }

    if (
      isAccepted &&
      !(
        this.internationalPGStatus === 'rejected' ||
        this.internationalOtherProductsStatus === 'rejected'
      )
    ) {
      return (
        <>
          You can now start accepting domestic payments. To enable international payments,{' '}
          <Link to="/config#request-international">request here</Link>.
        </>
      );
    }

    return (
      <>
        You can now start accepting domestic payments. Please raise a support ticket post website
        review to start accepting international payments.{' '}
        <Link to="/config#request-international">request here</Link>.
      </>
    );
  }

  get intlUnregisteredBusinessContent() {
    const { isAccepted } = this.props;
    if (isAccepted) {
      return (
        <>
          You can now start accepting domestic payments. Please raise a support ticket post website
          review to start accepting international payments.{' '}
          <Link to="/config#request-international">request here</Link>.
        </>
      );
    }

    return <>You can start accepting domestic payments.</>;
  }

  get activatedAccountContent() {
    const { isUnregisteredBusiness, mode, internationalActivationFlow, international } = this.props;

    if (mode !== 'live') {
      if (international) {
        return <>You can now start accepting domestic and international payments.</>;
      }
      return <>You can now start accepting domestic payments.</>;
    }

    if (internationalActivationFlow.isWhitelistFlow) {
      return this.intlWhitelistedContent;
    }

    if (internationalActivationFlow.isGraylistFlow) {
      return (
        <>
          Start accepting domestic payments . To accept international payments.{' '}
          <Link to="/config#request-international">request here</Link>.
        </>
      );
    }

    if (isUnregisteredBusiness) {
      return this.intlUnregisteredBusinessContent;
    }

    return (
      <>
        You can now start accepting domestic payments. Please raise a support ticket post website
        review to start accepting international payments.{' '}
        <Link to="/config#request-international">request here</Link>.
      </>
    );
  }

  get accountUnderReviewContent() {
    const { internationalActivationFlow, kyc_clarification_reasons } = this.props;
    if (internationalActivationFlow.isGraylistFlow) {
      return `We are reviewing your form. Expect confirmation in ${
        kyc_clarification_reasons?.nc_count ? ' 3 ' : ' 3 - 4 '
      } business days. You can request for international payments acceptance post KYC Verification.`;
    }

    return 'We are reviewing your KYC details for activation';
  }

  render() {
    const { status, content, title } = this.state;
    const { showProducts, hideProductsModal: _hideProductsModal } = this.props;

    return (
      <>
        <Step
          status={status}
          isInstantActivationEnabled={this.props.user.isInstantActivationEnabled}
        >
          <StepTitle>{title}</StepTitle>
          <StepContent>{content}</StepContent>
        </Step>
        {showProducts ? (
          <ProductsModal onClose={_hideProductsModal} track={trackProductsModal} />
        ) : null}
      </>
    );
  }
}

const mapStateToProps = (state) => ({
  showProducts: state.home.instantActivations.showProductsModal,
  limitBreach: state.home.limitBreach,
  referee: state.merchantReferral.data.referee,
});

export default compose(
  connect(mapStateToProps, {
    openModal,
    showProductsModal,
    hideProductsModal: hideProductsModalAction,
    ...EventsActions,
  }),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => {
    return window.rzpQ.component('ActivationCard');
  }),
)(ActivationCard);
