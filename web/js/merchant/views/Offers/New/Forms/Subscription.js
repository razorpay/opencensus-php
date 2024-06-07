/* eslint-disable react/display-name */
import Wizard from '../components/Wizard';

import Description from 'merchant/views/Offers/New/Screens/Description';
import DiscountType from 'merchant/views/Offers/New/Screens/DiscountTypes';
import ApplicableOn from 'merchant/views/Offers/New/Screens/Subscription/ApplicableOn';
import OfferValidity from 'merchant/views/Offers/New/Screens/OfferValidity';
import Overview from 'merchant/views/Offers/New/Screens/Overview';
import BaseForm from './BaseForm';

import { prepareDataForSubmit } from 'merchant/views/Offers/New/helpers';

const VALID_TABS = [false, false, false, false, false];

export default class SubscriptionOffersForm extends BaseForm {
  constructor(props) {
    super(props);

    this.state = {
      formData: {
        description: {
          type: 'instant',
        },
        discountType: {
          redemption_type: 'single',
        },
        applicableOn: {
          applicable_on: 'both',
        },
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
            hideType
            formData={this.state.formData.description}
            isFormLocked={this.props.isFormLocked}
          />
        ),
      },
      {
        name: 'Discount type',
        render: () => (
          <DiscountType
            showSubscriptionOfferFields
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
            showSubscriptionOfferFields
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

  onSubmit = () => {
    const { formData } = this.state;
    const preparedFormData = prepareDataForSubmit({
      product_type: 'subscription',
      ...formData.description,
      ...formData.discountType,
      ...formData.applicableOn,
      ...formData.offerValidity,
    });

    return this.props.onSubmit(preparedFormData);
  };

  render() {
    const isFormDisabled =
      this.props.isFormLocked || this.state.formData.creation_terms_accepted !== '1';
    return (
      <Wizard
        title="Offer for Subscription"
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
