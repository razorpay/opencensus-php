import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import RTracking from 'react-tracking';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

import { activationDuration } from 'merchant/helpers/data';
import { trackGoToActivationFromError } from '../../../containers/Home/ga';

const FREE_CREDITS_SIGNUP_SEGMENT = 1594751400;

@RTracking(() => window.rzpQ.component('InstantActivationAnnouncements'))
export default class InstantActivationAnnouncements extends Component {
  trackEvent = eventOrigin => {
    const { tracking } = this.props;
    tracking.trackEvent(
      window.rzpQ.onbr().initiated(`act.${eventOrigin}`, {
        clickSource: 'Instant_Announcement_Banner',
      })
    );
  };

  render() {
    const { user, mode, payments } = this.props;

    let theme = 'warning',
      title,
      content,
      isPaymentsOfTypeObject = payments instanceof Object;
    if (!user.isSubmitted) {
      if (
        user.instantActivation.isWhitelistFlow &&
        !user.isAccepted &&
        mode === 'live'
      ) {
        if (payments && payments.items.length === 0) {
          title = 'Accept Payments';
          if (user.created_at > FREE_CREDITS_SIGNUP_SEGMENT) {
            content = (
              <span>
                Start accepting payments right away to avail ₹1 lakh free
                credits & slashed pricing on apps. Meanwhile we will await your
                KYC details to enable settlements. &nbsp;
                <Link to="/activation">Fill KYC Form</Link>
              </span>
            );
          } else {
            content = (
              <span>
                You can start using our products to accept payments right away.
                Meanwhile we will await your KYC details to enable settlements
                for your account. &nbsp;
                <Link to="/activation">Fill KYC Form</Link>
              </span>
            );
          }
        } else if (payments && payments.items.length > 0) {
          title = 'Enable Settlements';
          content = (
            <span>
              You can continue accepting payments from your customers. However,
              you must complete KYC for the payments to be settled to your
              account. &nbsp;
              <Link to="/activation">Fill KYC Form</Link>
            </span>
          );
        }
      } else if (user.instantActivation.isGraylistFlow) {
        title = 'Submit your KYC';
        content = (
          <span>
            In order to enable payments for your business model we need your KYC
            Details. &nbsp;
            <Link to="/activation">Fill KYC Form</Link>
          </span>
        );
      } else if (
        payments &&
        payments.items.length > 0 &&
        !user.isAccepted &&
        mode === 'live'
      ) {
        title = 'Enable Settlements';
        content = (
          <span>
            You can continue accepting payments from your customers. However,
            you must complete KYC for the payments to be settled to your
            account. &nbsp;
            <Link to="/activation">Fill KYC Form</Link>
          </span>
        );
      } else if (
        user.isActivated &&
        user.bank_details_verification_status == 'failed'
      ) {
        theme = 'danger';
        title = 'Bank Verification Failed';
        content =
          'We were unable to verify your bank account. Please upload bank account proof.';
      } else if (user.business_type == 11) {
        if (user.isActivated && user.poi_verification_status == 'verified') {
          theme = 'success';
          title = 'Accept Payments';
          if (user.created_at > FREE_CREDITS_SIGNUP_SEGMENT) {
            content = (
              <span>
                Your PAN is verified and you can start accepting domestic
                payments to avail ₹1 lakh free credits. Meanwhile we will await
                your KYC details to enable settlements. &nbsp;
                <Link to="/activation">Fill KYC Form</Link>
              </span>
            );
          } else {
            content = (
              <span>
                Your PAN was successfully verified and you can start accepting
                domestic payments now. Meanwhile we will await your KYC details
                to enable settlements for your account. &nbsp;
                <Link to="/activation">Fill KYC Form</Link>
              </span>
            );
          }
        } else if (user.poi_verification_status == 'failed') {
          theme = 'danger';
          title = 'Unable To Verify PAN';
          content = (
            <React.Fragment>
              Government’s PAN database seems to be down, we couldn’t verify
              your PAN Details. Please try again in a couple of minutes. &nbsp;
              <Link
                to="/activation?auto-submit=l1-form"
                onClick={() => {
                  this.trackEvent('nav_try_again');
                }}
              >
                Try Again
              </Link>
            </React.Fragment>
          );
        } else if (
          user.poi_verification_status == 'incorrect_details' ||
          user.poi_verification_status == 'not_matched'
        ) {
          theme = 'danger';
          title = 'PAN Verification Failed';
          content = (
            <React.Fragment>
              Your PAN details did not match with the government PAN database.
              Please review and submit again. &nbsp;
              <Link
                to="/activation"
                onClick={() => {
                  this.trackEvent('nav_review_details');
                  trackGoToActivationFromError();
                }}
              >
                Review Details
              </Link>
            </React.Fragment>
          );
        } else return null;
      } else {
        return null;
      }
    } else {
      if (user.isAccepted) {
        if (!user.isNPSSurveyBannerEnabled && !user.isCovidFeatureEnabled) {
          theme = 'success';
          title = 'Settlements Enabled';
          content =
            'Your KYC verification was successful. Payments will be settled to your bank account as per settlement cycle.';
          if (user.instantActivation.isGraylistFlow) {
            content = content + ' ' + 'Go ahead and accept your first payment.';
          }
        } else return null;
      } else if (user.isRejected || user.needsClarification) {
        theme = 'danger';

        if (user.isRejected) {
          title = 'Account Suspended';
          content =
            'Due to irregularities in documents submitted by you, your account has been suspended. You will not be able to conduct live transactions';
        } else {
          title = 'KYC Clarification';
          content = (
            <React.Fragment>
              Your KYC details require further clarification. We have reached
              out to you seeking more information. Please check your email for
              details.
              <span class="big-dot-separator" />
              <Link to="/activation" onClick={trackGoToActivationFromError}>
                Review Details
              </Link>
            </React.Fragment>
          );
        }
      } else {
        title = 'KYC Under Review';
        if (user.instantActivation.isWhitelistFlow) {
          if (payments && payments.items.length > 0 && mode === 'live') {
            content = `We are reviewing your KYC details. This process usually takes 1-2 days from the date of the first transaction, we will reach out to you on your registered email ID if we need any clarifications.
                       Your settlements will be enabled after your KYC is reviewed and approved.`;
          } else {
            title = 'Accept Payments';
            content = (
              <React.Fragment>
                You can start using our products to accept payments right away,
                however your settlements will be enabled after your KYC is
                reviewed. KYC Review process usually takes 1-2 days from the
                date of the first transaction, we will reach out to you on your
                registered email ID if we need any clarifications. &nbsp;
                <a
                  href="https://razorpay.freshdesk.com/support/solutions/articles/11000092582"
                  target="_blank"
                >
                  Know more
                </a>
              </React.Fragment>
            );
          }
        } else if (user.isUnregisteredBusiness) {
          content = `We are reviewing your KYC Details. This process usually takes 1-2 working days post your first transaction. If we need any more information, we will reach out to you on your registered email address.`;
        } else {
          content = `We are reviewing your KYC Details. This process usually takes 1-2 working days post your KYC Submission. If we need any more information, we will reach out to you on your registered email address.`;
        }
      }
    }
    return (
      <AnnouncementBanner
        title={title}
        theme={theme}
        bannerKey={`announcement-banner-${user.activation_status}-${
          user.current
        }`}
        canBeClosed={user.isAccepted}
      >
        {content}
      </AnnouncementBanner>
    );
  }
}
