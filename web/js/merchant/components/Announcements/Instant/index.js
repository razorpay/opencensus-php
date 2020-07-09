import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import RTracking from 'react-tracking';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

import { activationDuration } from 'merchant/helpers/data';
import { trackGoToActivationFromError } from '../../../containers/Home/ga';

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
          content = (
            <span>
              You can start using our products to accept payments right away.
              Meanwhile, we will await your KYC Details, that can be filled{' '}
              <Link to="/activation">here</Link>.
            </span>
          );
        } else if (payments && payments.items.length > 0) {
          title = 'Enable Settlements';
          content = (
            <span>
              Your settlements are on hold, kindly fill your KYC Form to enabled
              settlements.
              <span class="big-dot-separator" />
              <Link to="/activation">Fill KYC Form</Link>
            </span>
          );
        }
      } else if (payments && payments.items.length > 0 && !user.isAccepted) {
        title = 'Enable Settlements';
        content = (
          <span>
            Your settlements are on hold. You will need to fill the KYC Form to
            receive your payments in your bank account.
            <span class="big-dot-separator" />
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
      } else if (
        user.isActivated &&
        user.poi_verification_status == 'verified' &&
        user.company_pan_verification_status === 'verified'
      ) {
        theme = 'success';
        title = 'Account Activated';
        content =
          'PAN verification successful. You can start accepting domestic payments.';
      } else if (user.business_type == 11) {
        if (user.isActivated && user.poi_verification_status == 'verified') {
          theme = 'success';
          title = 'Account Activated';
          content =
            'PAN verification successful. You can start accepting domestic payments.';
        } else if (user.poi_verification_status == 'failed') {
          theme = 'danger';
          title = 'Unable To Verify PAN';
          content = (
            <React.Fragment>
              The central databse seems to be down, we couldn't verify you PAN
              details. Please try again in a couple of minutes.{' '}
              <span class="big-dot-separator" />{' '}
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
              Your PAN details did not match with the government database.
              Please review your details.
              <span class="big-dot-separator" />
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
            'KYC verification successful. Payments collected by you will now be settled in your bank account in the next immediate settlement cycle.';
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
            content = `We are reviewing your KYC details. This process usually takes 1-2 days from the date of the first transaction, we will reach out to you if we need any clarifications.`;
          } else {
            title = 'Accept Payments';
            content = (
              <React.Fragment>
                You can start using our products to accept payments right away.
                KYC Review process usually takes 1-2 days from the date of the
                first transaction, we will reach out to you if we need any
                clarifications.
                <span class="big-dot-separator" />
                <a
                  href="https://razorpay.freshdesk.com/support/solutions/articles/11000092582"
                  target="_blank"
                >
                  Know more
                </a>
              </React.Fragment>
            );
          }
        } else {
          content = `We are reviewing your KYC details. This process usually takes ${activationDuration}.`;
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
