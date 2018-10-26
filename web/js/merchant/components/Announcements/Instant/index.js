import React, { Component } from 'react';
import { Link } from 'react-router-dom';

import { activationDuration } from 'common/data';
import Announcement from 'merchant/components/Announcement';

export default class InstantActivationAnnouncements extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const { user, mode, payments } = this.props;

    let theme = 'warning',
      title,
      content;

    if (!user.isSubmitted) {
      if (mode !== 'live' || payments.loading || payments.items.length === 0) {
        return null;
      }

      title = 'Enable Settlements';
      content = (
        <span>
          Your settlements are on hold. You will need to fill the KYC Form to
          receive your payments in your bank account.
          <span className="big-dot-separator" />
          <Link to="/activation">Fill KYC Form</Link>
        </span>
      );
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
            'Due to irregularities with your account or the documents submitted by you, your account has been suspended.';
        } else {
          title = 'KYC Clarification';
          content =
            'Your KYC Form has an anomaly and needs clarification. Please check your email for details.';
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
