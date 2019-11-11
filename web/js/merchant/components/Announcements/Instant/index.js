import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import RTracking from 'react-tracking';

import Announcement from 'merchant/components/Announcement';

import { activationDuration } from 'merchant_common/helpers/data';

@RTracking(() => window.rzpQ.component('InstantActivationAnnouncements'))
export default class InstantActivationAnnouncements extends Component {
  render() {
    const { user, mode, payments } = this.props;

    let theme = 'warning',
      title,
      content,
      isPaymentsOfTypeObject = payments instanceof Object;
    if (!user.isSubmitted) {
      if (payments && payments.items.length > 0 && !user.isAccepted) {
        title = 'Enable Settlements';
        content = (
          <span>
            Your settlements are on hold. You will need to fill the KYC Form to
            receive your payments in your bank account.
            <span className="big-dot-separator" />
            <Link to="/activation">Fill KYC Form</Link>
          </span>
        );
      } else if (user.business_type == 11) {
        if (
          user.isActivated &&
          user.bank_details_verification_status == 'failed'
        ) {
          theme = 'danger';
          title = 'Bank Verification Failed';
          content =
            'We were unable to verify your bank account. Please upload bank account proof.';
        } else if (
          user.isActivated &&
          user.poi_verification_status == 'verified'
        ) {
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
              details. <span class="big-dot-separator" />{' '}
              <Link to="/activation?auto-submit=l1-form">Try Again</Link>
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
              <Link to="/activation">Review details</Link>
            </React.Fragment>
          );
        } else return null;
      } else {
        return null;
      }
    } else {
      if (user.isAccepted) {
        theme = 'success';
        title = 'Settlements Enabled';
        content =
          'KYC verification successful. Payments collected by you will now be settled in your bank account in the next immediate settlement cycle.';
      } else if (user.isRejected || user.needsClarification) {
        theme = 'danger';

        if (user.isRejected) {
          title = 'Account Suspended';
          content =
            'Due to irregularities in documents submitted by you, your account has been suspended. You will not be able to conduct live transactions';
        } else {
          title = 'KYC Clarification';
          content =
            'Your KYC details require further clarification. We have reached out to you seeking more information. Please check your email for details.';
        }
      } else {
        title = 'KYC under review';
        content = `We are reviewing your KYC details. This process usually takes ${activationDuration}.`;
      }
    }
    return (
      <Announcement
        title={title}
        theme={theme}
        bannerKey={`announcement-banner-${user.activation_status}-${
          user.current
        }`}
        canBeClosed={user.isAccepted}
      >
        {content}
      </Announcement>
    );
  }
}
