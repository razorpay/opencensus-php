import React from 'react';

import { getCurrency } from 'common/ui/Amount';
import { isLowCostExperimentEnabled as isLowCostEnabled } from 'merchant/views/Offers/New/Screens/NoCostEMI/helpers/helper';
import Wizard from 'merchant/views/Offers/New/components/Wizard';
import { prepareDataForSubmit } from 'merchant/views/Offers/New/helpers';
import {
  getIsMultiPaymentMethodExperimentEnabled,
  getIs10DigitBinExperimentEnabled,
  isGranularOfferExperimentEnabled,
} from 'merchant/views/Offers/utils';

const SCREEN_MAP = {
  0: 'description',
  1: 'applicableOn',
  2: 'discountType',
  3: 'offerValidity',
};

export default class BaseForm extends React.Component {
  FormWizard = React.createRef();

  get currencySymbol() {
    return getCurrency('INR').symbol;
  }

  onFieldChange = (event) => {
    const { currentTab, validTabs } = this.FormWizard.state;
    const currentFormScreen = SCREEN_MAP[currentTab];
    const { values } = this.props;
    const newValidTabs = [...validTabs];
    let invalidateTabs = false;

    let { value: fieldValue } = event.target;

    const { name: fieldName } = event.target;

    if (fieldName.length === 0) return;

    if (fieldName === 'iins') {
      fieldValue = fieldValue
        .split(',')
        .map((iin) => iin.trim())
        .filter((iin) => /^\d{6}$/.test(iin));
    }

    const newState = {
      values: {
        ...values,
      },
    };

    if (currentFormScreen) {
      newState.values[currentFormScreen] = {
        ...values[currentFormScreen],
        [fieldName]: fieldValue,
      };
    } else {
      newState.values[fieldName] = fieldValue;
    }
    // Description
    if (fieldName === 'type') {
      if (values.discount_type) {
        newState.values.discount_type = null;

        newValidTabs[1] = false;
        newValidTabs[4] = false;
        invalidateTabs = true;
      }

      if (values.min_amount) {
        newState.values.min_amount = null;

        newValidTabs[1] = false;
        newValidTabs[4] = false;
        invalidateTabs = true;
      }
    }

    // Discount Type
    if (fieldName === 'min_amount') {
      if (values.issuer) {
        newState.values.issuer = null;

        newValidTabs[1] = false;
        invalidateTabs = true;
        newValidTabs[4] = false;
      }

      if (values.emi_durations) {
        newState.values.emi_durations = null;

        newValidTabs[1] = false;
        invalidateTabs = true;
        newValidTabs[4] = false;
      }
    }

    if (fieldName === 'discount_type') {
      if (values.flat_cashback) {
        newState.values.flat_cashback = null;
      }

      if (values.percent_rate) {
        newState.values.percent_rate = null;
      }

      if (values.max_cashback) {
        newState.values.max_cashback = null;
      }
    }

    if (fieldName === 'redemption_type') {
      newState.values.no_of_cycles = null;
    }

    // Applicable On
    if (fieldName === 'payment_method' || fieldName === 'selectedInstruments') {
      if (values.payment_method_type) {
        newState.values.payment_method_type = null;
      }

      if (values.issuer) {
        newState.values.issuer = null;
      }

      if (values.payment_network) {
        newState.values.payment_network = null;
      }

      if (values.max_payment_count) {
        newState.values.max_payment_count = null;
      }

      if (values.iins) {
        newState.values.iins = null;
      }
    }

    if (fieldName === 'issuer' && newState.values) {
      newState.values.low_cost_emi = [];
      newState.values.emi_durations = [];
    }
    this.setState(newState);

    if (invalidateTabs) {
      this.FormWizard.setState({
        validTabs: newValidTabs,
      });
    }
  };

  onSubmit = () => {
    const { values, splitz } = this.props;

    let isLowCostExperimentEnabled = false;
    if (splitz) {
      const {
        abExperiments: { Low_cost_offer },
      } = splitz;

      isLowCostExperimentEnabled = isLowCostEnabled(Low_cost_offer);
    }
    const isGranularOfferExpEnabled = isGranularOfferExperimentEnabled(splitz);
    const isMultiPaymentOfferExperimentEnabled = getIsMultiPaymentMethodExperimentEnabled(splitz);

    const is10DigitBinExperimentEnabled = getIs10DigitBinExperimentEnabled(splitz);

    const preparedFormData = prepareDataForSubmit(
      values,
      isLowCostExperimentEnabled,
      isGranularOfferExpEnabled,
      isMultiPaymentOfferExperimentEnabled,
      is10DigitBinExperimentEnabled,
    );

    return this.props.onSubmit(preparedFormData);
  };

  render() {
    if (!this.props.onClose) return null;
    return (
      <Wizard
        {...this.props}
        validTabs={[false, false, false, false, false]}
        tabsData={[
          {
            name: 'Description',
            render: () => null,
          },
          {
            name: 'Discount type',
            render: () => null,
          },
          {
            name: 'Applicable On',
            render: () => null,
          },
          {
            name: 'Offer Validity',
            render: () => null,
          },
          {
            name: 'Overview',
            render: () => null,
          },
        ]}
        submitBtnText="Create Offer"
      />
    );
  }
}
