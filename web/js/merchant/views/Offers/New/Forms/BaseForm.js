import React from 'react';

import { getCurrency } from 'common/ui/Amount';
import { isLowCostExperimentEnabled as isLowCostEnabled } from 'merchant/views/Offers/New/Screens/NoCostEMI/helpers/helper';
import Wizard from 'merchant/views/Offers/New/components/Wizard';
import { prepareDataForSubmit } from 'merchant/views/Offers/New/helpers';

const SCREEN_MAP = {
  0: 'description',
  1: 'discountType',
  2: 'applicableOn',
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
    const { formData } = this.state;
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
      formData: {
        ...formData,
      },
    };

    if (currentFormScreen) {
      newState.formData[currentFormScreen] = {
        ...formData[currentFormScreen],
        [fieldName]: fieldValue,
      };
    } else {
      newState.formData[fieldName] = fieldValue;
    }
    // Description
    if (fieldName === 'type') {
      if (formData.discountType.discount_type) {
        newState.formData.discountType.discount_type = null;

        newValidTabs[1] = false;
        newValidTabs[4] = false;
        invalidateTabs = true;
      }

      if (formData.discountType.min_amount) {
        newState.formData.discountType.min_amount = null;

        newValidTabs[1] = false;
        newValidTabs[4] = false;
        invalidateTabs = true;
      }
    }

    // Discount Type
    if (fieldName === 'min_amount') {
      if (formData.applicableOn.issuer) {
        newState.formData.applicableOn.issuer = null;

        newValidTabs[2] = false;
        invalidateTabs = true;
        newValidTabs[4] = false;
      }

      if (formData.applicableOn.emi_durations) {
        newState.formData.applicableOn.emi_durations = null;

        newValidTabs[2] = false;
        invalidateTabs = true;
        newValidTabs[4] = false;
      }
    }

    if (fieldName === 'discount_type') {
      if (formData.discountType.flat_cashback) {
        newState.formData.discountType.flat_cashback = null;
      }

      if (formData.discountType.percent_rate) {
        newState.formData.discountType.percent_rate = null;
      }

      if (formData.discountType.max_cashback) {
        newState.formData.discountType.max_cashback = null;
      }
    }

    if (fieldName === 'redemption_type') {
      newState.formData.discountType.no_of_cycles = null;
    }

    // Applicable On
    if (fieldName === 'payment_method') {
      if (formData.applicableOn.payment_method_type) {
        newState.formData.applicableOn.payment_method_type = null;
      }

      if (formData.applicableOn.issuer) {
        newState.formData.applicableOn.issuer = null;
      }

      if (formData.applicableOn.payment_network) {
        newState.formData.applicableOn.payment_network = null;
      }

      if (formData.applicableOn.max_payment_count) {
        newState.formData.applicableOn.max_payment_count = null;
      }

      if (formData.applicableOn.iins) {
        newState.formData.applicableOn.iins = null;
      }
    }

    if (fieldName === 'issuer' && newState.formData.applicableOn) {
      newState.formData.applicableOn.low_cost_emi = [];
      newState.formData.applicableOn.emi_durations = [];
    }

    this.setState(newState);

    if (invalidateTabs) {
      this.FormWizard.setState({
        validTabs: newValidTabs,
      });
    }
  };

  onSubmit = () => {
    const { formData } = this.state;

    let isLowCostExperimentEnabled = false;
    if (this.props.splitz) {
      const {
        abExperiments: { Low_cost_offer },
      } = this.props.splitz;

      isLowCostExperimentEnabled = isLowCostEnabled(Low_cost_offer);
    }

    const preparedFormData = prepareDataForSubmit(
      {
        ...formData.description,
        ...formData.discountType,
        ...formData.applicableOn,
        ...formData.offerValidity,
      },
      isLowCostExperimentEnabled,
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
