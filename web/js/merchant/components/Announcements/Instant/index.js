import React, { Component } from 'react';
import { Link } from 'react-router-dom';

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
          'KYC verification successful. Your payments will now be settled';
      } else if (user.isRejected || user.needsClarification) {
        theme = 'danger';

        if (user.isRejected) {
          title = 'Account Blocked';
          content =
            'Due to certain anomalies in your submitted KYC Form, your account has been blocked';
        } else {
          title = 'KYC Clarification';
          content =
            'Your KYC Form has an anomaly and needs clarification. Please check your email for details.';
        }
      } else {
        title = 'KYC under review';
        content = 'We are reviewing your form. Expect confirmation in 2-3 days';
      }
    }

    return (
      <div className="announcement-banner-container">
        <Announcement
          title={title}
          theme={theme}
          bannerKey={`announcement-banner-${user.activation_status}`}
          canBeClosed={user.isAccepted}
        >
          {content}
        </Announcement>
      </div>
    );
  }
}
