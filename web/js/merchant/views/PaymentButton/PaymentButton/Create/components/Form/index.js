import ButtonDetails from './ButtonDetails';
import AmountDetails from './AmountDetails';
import CustomerDetails from './CustomerDetails';
import ReviewAndCreate from './ReviewAndCreate';

import { templateTypes } from 'merchant/views/PaymentButton/PaymentButton/Create/components/Templates/meta';

const buttonDetailsTab = () => ({
  component: ButtonDetails,
  title: 'Button Details',
  description: 'Customers will see this button to initiate a transaction',
});

const amountDetailsTab = context => ({
  component: AmountDetails,
  title: context.isDonationsTemplate ? 'Donation Amount' : 'Amount Details',
  // TODO: Change description as per template selection
  description: 'Customers will fill this form before making the final payment',
});

const customerDetailsTab = () => ({
  component: CustomerDetails,
  title: 'Customer Details',
  description: 'Customers will fill this form before making the final payment',
});

const reviewAndCreateTab = () => ({
  component: ReviewAndCreate,
  title: 'Review and Create',
  description: 'Customers will see the button and forms as shown below ',
});

export default class Form extends React.Component {
  constructor(props) {
    super(props);

    let tabContents;

    if (this.isQuickPayTemplate) {
      tabContents = [buttonDetailsTab, customerDetailsTab, reviewAndCreateTab];
    } else {
      tabContents = [
        buttonDetailsTab,
        amountDetailsTab,
        customerDetailsTab,
        reviewAndCreateTab,
      ];
    }

    this.tabContents = tabContents;
  }

  get isQuickPayTemplate() {
    const { paymentButtonEntity } = this.props;
    const templateType =
      paymentButtonEntity.settings.payment_button_template_type;

    return templateType === templateTypes.quickPay.key;
  }

  get isDonationsTemplate() {
    const { paymentButtonEntity } = this.props;
    const templateType =
      paymentButtonEntity.settings.payment_button_template_type;

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

  verifyNewTabIndex = newIndex => {
    if (0 <= newIndex && newIndex < this.tabContents.length) {
      return true;
    }

    return false;
  };

  get activeTabContents() {
    return tabContent || {};
  }

  render() {
    const activeTabIndex = this.props.activeTabIndex,
      activeTabContent = this.tabContents[activeTabIndex](this);

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
