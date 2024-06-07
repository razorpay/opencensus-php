import ApplicableOn from 'merchant/views/Offers/New/Screens/ApplicableOn';
import Description from 'merchant/views/Offers/New/Screens/Description';
import DiscountType from 'merchant/views/Offers/New/Screens/DiscountTypes';
import OfferValidity from 'merchant/views/Offers/New/Screens/OfferValidity';
import Overview from 'merchant/views/Offers/New/Screens/Overview';
import Wizard from 'merchant/views/Offers/New/components/Wizard';

import BaseForm from './BaseForm';

const VALID_TABS = [false, false, false, false, false];

export default class OffersForm extends BaseForm {
  constructor(props) {
    super(props);

    this.state = {
      formData: {
        description: {},
        discountType: {},
        applicableOn: {},
        offerValidity: {},
        // HINT: Input.Check don't have validation support
        creation_terms_accepted: undefined,
      },
    };
  }

  get tabsData() {
    return [
      {
        name: 'Description',
        render: () => (
          <Description
            formData={this.state.formData.description}
            isFormLocked={this.props.isFormLocked}
          />
        ),
      },
      {
        name: 'Discount type',
        render: () => (
          <DiscountType
            isFormLocked={this.props.isFormLocked}
            currencySymbol={this.currencySymbol}
            offerType={this.state.formData.description.type}
            formData={this.state.formData.discountType}
          />
        ),
      },
      {
        name: 'Applicable On',
        render: () => {
          return (
            <ApplicableOn
              formData={this.state.formData.applicableOn}
              isFormLocked={this.props.isFormLocked}
            />
          );
        },
      },
      {
        name: 'Offer Validity',
        render: () => (
          <OfferValidity
            onChange={this.onFieldChange}
            formData={this.state.formData.offerValidity}
            isFormLocked={this.props.isFormLocked}
          />
        ),
      },
      {
        name: 'Overview',
        render: () => (
          <Overview
            formData={this.state.formData}
            currencySymbol={this.currencySymbol}
            isFormLocked={this.props.isFormLocked}
          />
        ),
      },
    ];
  }

  render() {
    const isFormDisabled =
      this.props.isFormLocked || this.state.formData.creation_terms_accepted !== '1';
    return (
      <Wizard
        disabled={isFormDisabled}
        ref={(form) => (this.FormWizard = form)}
        tabsData={this.tabsData}
        validTabs={VALID_TABS}
        submitBtnText="Create Offer"
        onChange={this.onFieldChange}
        onClose={this.props.onClose}
        onSubmit={this.onSubmit}
        zIndex={9999}
      />
    );
  }
}
