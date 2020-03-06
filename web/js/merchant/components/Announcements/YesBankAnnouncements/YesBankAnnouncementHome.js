import React, { Component } from 'react';
import Announcement from 'merchant/components/Announcement';

export default class YesBankAnnouncementHome extends Component {
  getContent = () => {
    const { user, virtualAccounts } = this.props;
    let title = '',
      description = '',
      theme = '';

    if (user) {
      if (user.isYesBankMerchant) {
        title = 'Yes Bank Moratorium';
        description = (
          <React.Fragment>
            Your settlements are blocked as you are using a Yesbank account for
            settlements. If you have an alternate bank account, you can use to
            change the bank account details on your dashboard for smooth
            settlements.
            <span class="big-dot-separator" />
            <a
              href="https://razorpay.com/docs/payment-gateway/dashboard-guide/my-account/?utm_campaign=Yes%20Bank%20Moratarium&utm_source=hs_email&utm_medium=email&_hsenc=p2ANqtz-9wvLkNiY0CP_1F-2nnr94JTdXuL9v8NGHG0PWC-LcjrD7dPwyRZ8cC0r8Wf7WstxswPR0l#change-bank-account-details"
              target="_blank"
            >
              Update account
            </a>
          </React.Fragment>
        );
        theme = 'danger';
      } else if (
        virtualAccounts &&
        !virtualAccounts.error &&
        Array.isArray(virtualAccounts.items) &&
        virtualAccounts.items.length > 0
      ) {
        //check smart collect
        title = 'Yes Bank Moratorium';
        description = (
          <React.Fragment>
            Razorpay’s e-mandate services are up and operational. Our team is
            working on resuming service for Smart Collect. We will get back to
            you with updates shortly. Be assured, our payment gateway services
            are functioning normally.
            <span class="big-dot-separator" />
            <a
              href="https://lp.razorpay.com/unregistered-businesses-faqs-0"
              target="_blank"
            >
              Know more
            </a>
          </React.Fragment>
        );
        theme = 'danger';
      } else {
        title = 'Yes Bank Moratorium';
        description = (
          <React.Fragment>
            Our payment gateway services are completely unaffected. We are
            working to ensure there is no disruption in any services. In case of
            any concerns please reach out to support.
            <span class="big-dot-separator" />
            <a
              href="https://lp.razorpay.com/unregistered-businesses-faqs-0"
              target="_blank"
            >
              Know more
            </a>
          </React.Fragment>
        );
        theme = 'warning';
      }
      return {
        title,
        description,
        theme,
      };
    }
    return null;
  };

  render() {
    const content = this.getContent();

    if (content) {
      return (
        <Announcement
          class="settlement-anc"
          theme={content.theme}
          title={content.title}
          canBeClosed={false}
        >
          <div>{content.description}</div>
        </Announcement>
      );
    }

    return null;
  }
}
