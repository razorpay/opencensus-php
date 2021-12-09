/* eslint-disable react/display-name */
import Wizard from '../components/Wizard';

import BaseForm from './BaseForm';

import Description from 'merchant/views/Offers/New/Screens/Description';
import DiscountType from 'merchant/views/Offers/New/Screens/DiscountTypes';
import ApplicableOn from 'merchant/views/Offers/New/Screens/NoCostEMI/ApplicableOn';
import OfferValidity from 'merchant/views/Offers/New/Screens/OfferValidity';
import Overview from 'merchant/views/Offers/New/Screens/Overview';

import { merchantFetch } from 'merchant/utils/ajax';

const VALID_TABS = [false, false, false, false, false];

export default class NoCostEMIForm extends BaseForm {
  constructor(props) {
    super(props);

    this.state = {
      formData: {
        description: {
          type: 'instant',
        },
        discountType: {
          discount_type: 'no_cost_emi',
        },
        applicableOn: {},
        offerValidity: {},
        // HINT: Input.Check don't have validation support
        creation_terms_accepted: undefined,
      },
      isLoading: true,
      emiData: {},
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
            hideDiscountType
            isFormLocked={this.props.isFormLocked}
            currencySymbol={this.currencySymbol}
            offerType={this.state.formData.description.type}
            formData={this.state.formData.discountType}
            emiData={this.state.emiData}
          />
        ),
      },
      {
        name: 'Applicable On',
        render: () => {
          return (
            <ApplicableOn
              emiData={this.state.emiData}
              onChange={this.onFieldChange}
              minAmount={this.state.formData.discountType.min_amount}
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

  componentDidMount() {
    this.prepareDataForForm();
  }

  prepareDataForForm = () => {
    merchantFetch('merchant/methods')
      .then((resp) => {
        if (resp.data) {
          const { emi_plans = {}, emi_options = {} } = resp.data;

          this.setState({
            emiData: {
              emi_options,
              emi_plans,
            },
            isLoading: false,
          });
        }
      })
      .catch(({ errors }) => {
        let error = (errors || [])[0];

        if (!error) {
          error = `Some network error has occured`;
        }

        this.setState({
          error,
          isLoading: false,
        });
      });
  };

  render() {
    const isFormDisabled =
      this.props.isFormLocked || this.state.formData.creation_terms_accepted !== '1';
    return (
      <Wizard
        error={this.state.error}
        isLoading={this.state.isLoading}
        disabled={isFormDisabled}
        ref={(form) => (this.FormWizard = form)}
        tabsData={this.tabsData}
        validTabs={VALID_TABS}
        submitBtnText="Create No Cost EMI"
        onChange={this.onFieldChange}
        onClose={this.props.onClose}
        onSubmit={this.onSubmit}
      />
    );
  }
}
