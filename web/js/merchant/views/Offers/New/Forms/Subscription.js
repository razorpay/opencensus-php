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
  // eslint-disable-next-line no-useless-constructor
  constructor(props) {
    super(props);
  }
  componentDidMount() {
    const { setFieldValue } = this.props;
    //setting initial values
    setFieldValue('type', 'instant');
    setFieldValue('redemption_type', 'single');
    setFieldValue('applicable_on', 'both');
  }
  get tabsData() {
    return [
      {
        name: 'Description',
        render: () => (
          <Description
            hideType
            isFormLocked={this.props.isFormLocked}
            values={this.props.values}
            handleChange={this.props.handleChange}
            handleBlur={this.props.handleBlur}
            setFieldTouched={this.props.setFieldTouched}
            setFieldValue={this.props.setFieldValue}
            errors={this.props.errors}
            setErrors={this.props.setErrors}
            touched={this.props.touched}
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
            offerType={this.props.values.type}
            values={this.props.values}
            handleChange={this.props.handleChange}
            handleBlur={this.props.handleBlur}
            setFieldTouched={this.props.setFieldTouched}
            setFieldValue={this.props.setFieldValue}
            errors={this.props.errors}
            setErrors={this.props.setErrors}
            touched={this.props.touched}
          />
        ),
      },
      {
        name: 'Applicable On',
        render: () => {
          return (
            <ApplicableOn
              isFormLocked={this.props.isFormLocked}
              values={this.props.values}
              handleChange={this.props.handleChange}
              handleBlur={this.props.handleBlur}
              setFieldTouched={this.props.setFieldTouched}
              setFieldValue={this.props.setFieldValue}
              errors={this.props.errors}
              setErrors={this.props.setErrors}
              touched={this.props.touched}
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
            isFormLocked={this.props.isFormLocked}
            values={this.props.values}
            handleChange={this.props.handleChange}
            handleBlur={this.props.handleBlur}
            setFieldTouched={this.props.setFieldTouched}
            setFieldValue={this.props.setFieldValue}
            errors={this.props.errors}
            setErrors={this.props.setErrors}
            touched={this.props.touched}
          />
        ),
      },
      {
        name: 'Overview',
        render: () => (
          <Overview
            currencySymbol={this.currencySymbol}
            isFormLocked={this.props.isFormLocked}
            values={this.props.values}
            handleChange={this.props.handleChange}
            handleBlur={this.props.handleBlur}
            setFieldTouched={this.props.setFieldTouched}
            setFieldValue={this.props.setFieldValue}
            errors={this.props.errors}
            setErrors={this.props.setErrors}
            touched={this.props.touched}
          />
        ),
      },
    ];
  }

  onSubmit = () => {
    const { values } = this.props;
    const preparedFormData = prepareDataForSubmit({
      product_type: 'subscription',
      ...values,
    });

    return this.props.onSubmit(preparedFormData);
  };

  render() {
    const { isFormLocked, values } = this.props;
    const isFormDisabled = isFormLocked || !values.creation_terms_accepted;
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
        values={this.props.values}
        errors={this.props.errors}
        setErrors={this.props.setErrors}
      />
    );
  }
}
