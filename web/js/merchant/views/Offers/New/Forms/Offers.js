import ApplicableOn from 'merchant/views/Offers/New/Screens/ApplicableOn';
import Description from 'merchant/views/Offers/New/Screens/Description';
import DiscountType from 'merchant/views/Offers/New/Screens/DiscountTypes';
import OfferValidity from 'merchant/views/Offers/New/Screens/OfferValidity';
import Overview from 'merchant/views/Offers/New/Screens/Overview';
import Wizard from 'merchant/views/Offers/New/components/Wizard';

import BaseForm from './BaseForm';

const VALID_TABS = [false, false, false, false, false];

export default class OffersForm extends BaseForm {
  // eslint-disable-next-line no-useless-constructor
  constructor(props) {
    super(props);
  }

  get tabsData() {
    return [
      {
        name: 'Description',
        render: () => (
          <Description
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

  render() {
    const { isFormLocked, values } = this.props;
    const isFormDisabled = isFormLocked || !values.creation_terms_accepted;
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
        values={this.props.values}
        errors={this.props.errors}
        setErrors={this.props.setErrors}
      />
    );
  }
}
