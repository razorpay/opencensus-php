import React from 'react';

import ButtonDetails from './ButtonDetails';
import AmountDetails from './AmountDetails';
import DonationAmountDetails from './DonationAmountDetails';
import CustomerDetails from './CustomerDetails';
import ReviewAndCreate from './ReviewAndCreate';

import { templateTypes } from 'merchant/views/PaymentButton/PaymentButton/Create/components/Templates/meta';
import track from '../../track';

const buttonDetailsTab = (context) => ({
  component: ButtonDetails,
  title: 'Button Details',
  description: context.isDonationsTemplate
    ? 'Supporters will see this button to initiate a transaction'
    : 'Customers will see this button to initiate a transaction',
});

const amountDetailsTab = (context) => ({
  component: context.isDonationsTemplate ? DonationAmountDetails : AmountDetails,
  title: context.isDonationsTemplate ? 'Donation Amount' : 'Amount Details',
  description: context.isDonationsTemplate
    ? 'Configure how supporters will see the donation options'
    : 'Customers can buy one or more items with support for quantity selection',
});

const customerDetailsTab = (context) => ({
  component: CustomerDetails,
  title: context.isDonationsTemplate ? 'Donor Details' : 'Customer Details',
  description: context.isDonationsTemplate
    ? 'Supporters will fill this form before making the final payment'
    : 'Customers will fill this form before making the final payment',
});

const reviewAndCreateTab = (context) => ({
  component: ReviewAndCreate,
  title: 'Review and Create',
  description: context.isDonationsTemplate
    ? 'Supporters will see the button and forms as shown below'
    : 'Customers will see the button and forms as shown below',
});

export default class Form extends React.Component {
  constructor(props) {
    super(props);

    let tabContents;

    if (this.isQuickPayTemplate) {
      tabContents = [buttonDetailsTab, customerDetailsTab, reviewAndCreateTab];
    } else {
      tabContents = [buttonDetailsTab, amountDetailsTab, customerDetailsTab, reviewAndCreateTab];
    }

    this.tabContents = tabContents;
  }

  componentDidMount() {
    track.setConfig({
      template: this.props.paymentButtonEntity.template_type,
    });
  }

  get isQuickPayTemplate() {
    const { paymentButtonEntity } = this.props;
    const templateType = paymentButtonEntity.settings.payment_button_template_type;

    return templateType === templateTypes.quickPay.key;
  }

  get isBuyNowTemplate() {
    const { paymentButtonEntity } = this.props;
    const templateType = paymentButtonEntity.settings.payment_button_template_type;

    return templateType === templateTypes.buyNow.key;
  }

  get isDonationsTemplate() {
    const { paymentButtonEntity } = this.props;
    const templateType = paymentButtonEntity.settings.payment_button_template_type;

    return templateType === templateTypes.donation.key;
  }

  goBack = () => {
    const newTabIndex = this.props.activeTabIndex - 1;

    if (this.verifyNewTabIndex(newTabIndex)) {
      this.props.onChangeActiveTabIndex(newTabIndex);
    }
  };

  goNext = () => {
    const newTabIndex = this.props.activeTabIndex + 1;

    if (this.verifyNewTabIndex(newTabIndex)) {
      this.props.onChangeActiveTabIndex(newTabIndex);
    }
  };

  verifyNewTabIndex = (newIndex) => {
    if (newIndex >= 0 && newIndex < this.tabContents.length) {
      return true;
    }

    return false;
  };

  render() {
    const activeTabIndex = this.props.activeTabIndex;
    const activeTabContent = this.tabContents[activeTabIndex](this);

    return (
      <div class="PaymentButton-Create-Form">
        <div class="Form-container">
          <div class="Form-title">
            {activeTabContent.title}
            <div class="Form-description">{activeTabContent.description}</div>
          </div>

          {this.tabContents.map((tab, index) => {
            const isActiveTab = activeTabIndex === index;
            const Component = tab(this).component;

            return (
              <Component
                key={index}
                isHidden={!isActiveTab}
                goBack={this.goBack}
                goNext={this.goNext}
                submitPaymentButtonForm={this.props.submitPaymentButtonForm}
                {...this.props}
              />
            );
          })}
        </div>
      </div>
    );
  }
}
