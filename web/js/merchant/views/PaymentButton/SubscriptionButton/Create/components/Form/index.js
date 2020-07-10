import ButtonDetails from './ButtonDetails';
import PlansDetails from './PlansDetails';
import CustomerDetails from './CustomerDetails';
import ReviewAndCreate from './ReviewAndCreate';

import track from '../../track';

const buttonDetailsTab = {
  component: ButtonDetails,
  title: 'Button Details',
  description: 'Customers will see this button to initiate a transaction',
};

const plansDetailsTab = {
  component: PlansDetails,
  title: 'Plans Details',
  description: 'Customers can select one of the plans from the list',
};

const customerDetailsTab = {
  component: CustomerDetails,
  title: 'Customer Details',
  description: 'Customers will fill this form before making the final payment',
};

const reviewAndCreateTab = {
  component: ReviewAndCreate,
  title: 'Review and Create',
  description: 'Customers will see the button and forms as shown below',
};

export default class Form extends React.Component {
  constructor(props) {
    super(props);

    let tabContents = [
      buttonDetailsTab,
      plansDetailsTab,
      customerDetailsTab,
      reviewAndCreateTab,
    ];

    this.tabContents = tabContents;
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
    const { subscriptionButtonEntity, activeTabIndex } = this.props;
    const activeTabContent = this.tabContents[activeTabIndex];

    return (
      <div class="PaymentButton-Create-Form">
        {subscriptionButtonEntity && (
          <div class="Form-container">
            <div class="Form-title">
              {activeTabContent.title}
              <div class="Form-description">{activeTabContent.description}</div>
            </div>

            {this.tabContents.map((tab, index) => {
              const isActiveTab = activeTabIndex === index;
              const Component = tab.component;

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
        )}
      </div>
    );
  }
}
