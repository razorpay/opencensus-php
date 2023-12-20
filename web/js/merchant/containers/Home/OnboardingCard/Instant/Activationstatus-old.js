/* eslint-disable react/no-unsafe */
import React, { Component } from 'react';
import { Link } from 'react-router-dom';

import { analyticsTrack } from 'common/utils/analytics';
import { Step, StepTitle, StepContent, possibleStatuses } from './Step';
import { trackGoToActivationFromError } from 'merchant/containers/Home/ga';
import RTracking from 'react-tracking';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';
import SupportButton from 'merchant/components/Home/SupportButton';
import {
  checkEligibilityForFeeBasedGating,
  handleFeeBasedGatingNavigation,
} from 'merchant/utils/feeBasedGatingUtils';

const initialState = {
  status: null,
  content: null,
  title: 'Account Activation',
};

@RTracking(() => {
  return window.rzpQ.component('ActivationCard');
})
export default class ActivationCard extends Component {
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

  UNSAFE_componentWillReceiveProps(nextProps) {
    const {
      instantActivation,
      isSubmitted,
      needsClarification,
      isActivated,
      isRejected,
      onActive,
      track,
      activated,
      poi_verification_status,
      isUnregisteredBusiness,
      locked,
      isHardLimitReached,
      merchant,
      canSkipPoiValidation,
      activationStatus,
      user,
    } = nextProps;
    const { isBlacklistFlow } = instantActivation;

    let { status, content, title } = initialState;

    const isEligibleForFeeBasedGating = checkEligibilityForFeeBasedGating(user);

    const kycPending = () => {
      return (
        <div>
          Submit your KYC details to activate your account once new businesses onboarding resumes
          <div>
            <Link
              to="/activation"
              className="btn btn-primary"
              onClick={() => {
                track.activateAccount();
                this.props.tracking.trackEvent(
                  window.rzpQ.onbr().initiated('act.form_fill', {
                    clickSource: 'Dashboard_CTA',
                  }),
                );
                analyticsTrack({
                  objectName: 'SignUp Activate Account Progress Bar CTA',
                  actionName: 'clicked',
                  screen: 'home page',
                  properties: {
                    ...getCommonSegmentProperties(),
                  },
                });
              }}
            >
              Submit KYC details
            </Link>
          </div>
        </div>
      );
    };

    if (activationStatus === 'kyc_qualified_unactivated') {
      title = 'KYC Verification';
      status = possibleStatuses.done;
      content =
        'There is no action due from your end. You can continue exploring Razorpay products in Test Mode till we activate your account.';
    } else if (isActivated) {
      title = 'Account Activated';
      status = possibleStatuses.done;
      content = this.activatedAccountContent;
    } else if (isSubmitted) {
      // if (!isSubmitted) {
      //   status = possibleStatuses.active;
      //   content = (
      //     <div>
      //       <div>For your business model, we need a few more details for activation</div>
      //       <Link
      //         to="/activation"
      //         className="btn btn-primary"
      //         onClick={() => {
      //           track.fillKyc();
      //           tracking.trackEvent(
      //             window.rzpQ.onbr().initiated('kyc.form_fill', {
      //               clickSource: 'Dashboard_CTA',
      //             }),
      //           );
      //         }}
      //       >
      //         Fill KYC Form
      //       </Link>
      //     </div>
      //   );
      // } else {
      if (needsClarification) {
        status = possibleStatuses.blocked;
        content = (
          <span>
            Your KYC details require further clarification. Please update required details{' '}
            <Link to="/activation" className="btn-link">
              here
            </Link>
          </span>
        );
      } else if (isRejected) {
        status = possibleStatuses.blocked;
        content = 'Your KYC form has been rejected.';
      } else if (isHardLimitReached) {
        title = 'Account Under Review';
        status = possibleStatuses.blocked;
        content =
          'Your submitted KYC documents are being reviewed. This will be done in less than 48 hours.';
      } else if (!!locked && !activated && merchant.hold_funds) {
        status = possibleStatuses.blocked;
        content = (
          <span>
            Please{' '}
            <SupportButton
              type="anchor"
              buttonLabel="contact support"
              category="merchant"
              openSection="account-activation"
            />{' '}
            to activate your account
          </span>
        );
      } else if (isEligibleForFeeBasedGating) {
        title = 'KYC Verification Pending';
        status = possibleStatuses.active;
        content = (
          <span>
            Get your business KYC verified to start collecting payments
            <button
              type="button"
              className="btn btn-primary"
              onClick={() => handleFeeBasedGatingNavigation({ ctaLocation: 'Activation Card' })}
            >
              Get KYC Verified
            </button>
          </span>
        );
      } else {
        status = possibleStatuses.progress;
        content = this.accountUnderReviewContent;
      }
      // }
    } else if (isBlacklistFlow) {
      status = possibleStatuses.blocked;
      content = (
        <span>
          We do not support your selected business model. In case you entered it wrong, change it{' '}
          <Link to="/activation" className="btn-link" onClick={this.handleBlackListFlowClick}>
            here
          </Link>
        </span>
      );
    } else {
      status = possibleStatuses.active;
      if (poi_verification_status && isUnregisteredBusiness) {
        status = possibleStatuses.blocked;
        if (poi_verification_status == 'failed' && !canSkipPoiValidation) {
          content = (
            <div>
              Unable to verify PAN with the central database at the moment.
              <div>
                <Link
                  to="/activation"
                  className="btn btn-primary"
                  onClick={() => {
                    track.activateAccount();
                    this.props.tracking.trackEvent(
                      window.rzpQ.onbr().initiated('act.form_fill', {
                        clickSource: 'Dashboard_CTA',
                      }),
                    );
                  }}
                >
                  Try Again
                </Link>
              </div>
            </div>
          );
        } else if (
          (poi_verification_status == 'incorrect_details' ||
            poi_verification_status == 'not_matched') &&
          !canSkipPoiValidation
        ) {
          content = (
            <div>
              Your PAN details did not match with the government database. Please review your
              details
              <div>
                <Link
                  to="/activation"
                  className="btn btn-primary"
                  onClick={() => {
                    track.activateAccount();
                    this.props.tracking.trackEvent(
                      window.rzpQ.onbr().initiated('act.form_fill', {
                        clickSource: 'Dashboard_CTA',
                      }),
                    );
                    trackGoToActivationFromError();
                  }}
                >
                  Review Details
                </Link>
              </div>
            </div>
          );
        } else {
          status = possibleStatuses.active;
          content = kycPending();
        }
      } else {
        content = kycPending();
      }
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

    if (
      (activationStatus === 'under_review' || activationStatus === 'kyc_qualified_unactivated') &&
      this.internationalPGStatus === 'approved'
    ) {
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

    return 'We are reviewing your KYC details';
  }

  render() {
    const { status, content, title } = this.state;

    return (
      <Step status={status} isInstantActivationEnabled={this.props.user.isInstantActivationEnabled}>
        <StepTitle>{title}</StepTitle>
        <StepContent>{content}</StepContent>
      </Step>
    );
  }
}
