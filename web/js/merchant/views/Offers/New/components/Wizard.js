import React from 'react';
import { Button, Box, Modal, ModalBody, ModalFooter, Heading } from '@razorpay/blade/components';

import Form from 'common/new-ui/Form';
import { ModalAsideNav } from 'common/new-ui/Wizard';
import Alert from 'common/ui/Forms/Alert';
import Spinner from 'common/ui/Spinner';
import { noop } from 'common/utils/rzp-utils';
import {
  isLowCostAmountMissing,
  isOfferTypeAbsent,
} from 'merchant/views/Offers/New/Screens/NoCostEMI/helpers/helper';
const CLASS_NAME = 'Offers--Create-form';

export default class CreateOfferWizard extends React.Component {
  state = {
    currentTab: 0,
    validTabs: this.props.validTabs,
  };

  IS_MODAL_VIEW = !!this.props.onClose;
  // TODO: Refactor this
  TABS_DATA = this.props.tabsData;

  TABS_NAMES = this.TABS_DATA.map((tab) => tab.name);

  componentDidMount() {
    this.toggleDisableState();
  }

  componentDidUpdate() {
    this.toggleDisableState(true);
  }

  toggleDisableState = (isUpdate = false) => {
    setTimeout(() => {
      const { state } = this;
      const { errors, values = {} } = this.props;
      const currentTabName = this.TABS_DATA[state.currentTab].name;

      // Get required fields for current tab
      const requiredFields = this.getRequiredFieldsForTab(currentTabName);

      // Check for both errors and missing required values
      const invalidFields = Object.entries(errors)
        .filter(([_key, value]) => value !== false)
        .map(([key, _value]) => key);

      // Check if any required field is empty
      const missingRequiredFields = requiredFields.some((field) => {
        // Special handling for min_amount in Applicable On tab
        if (field === 'min_amount' && currentTabName === 'Applicable On') {
          return !values[field];
        }
        // For other required fields
        return requiredFields.includes(field) && !values[field];
      });

      const currentTabStatus = invalidFields.length === 0 && !missingRequiredFields;
      let isValidTabsUpdate = false;

      // Create a new validTabs array with the correct length based on actual tabs
      const newValidTabs = Array(this.TABS_DATA.length)
        .fill(false)
        .map((_, idx) => {
          // If isUpdate is true, preserve validation state of previous tabs
          if (isUpdate && idx < state.currentTab) {
            return state.validTabs[idx];
          }

          if (state.currentTab === idx && state.validTabs[idx] !== currentTabStatus) {
            isValidTabsUpdate = true;
            return currentTabStatus;
          }
          return state.validTabs[idx] || false;
        });

      if (isValidTabsUpdate) {
        this.setState({
          validTabs: newValidTabs,
        });
      }
    }, 100);
  };

  getRequiredFieldsForTab = (tabName) => {
    const commonFields = {
      Description: ['name', 'display_text', 'terms'],
      'Offer Validity': ['ends_at', 'block'],
      Overview: ['creation_terms_accepted'],
      'Discount type': [],
    };

    const formSpecificFields = {
      'Applicable On': this.isNoCostEMIForm()
        ? ['min_amount', 'issuer', 'emi_durations'] // NoCostEMI form
        : [], // Regular Offers form - let it use default validation
      'Additional Offer type':
        this.isNoCostEMIForm() && this.props.values?.additional_offer
          ? this.props.values?.additional_offer_discount_type === 1
            ? ['tenures_applicable', 'additional_offer_discount_type', 'flat_cashback']
            : [
                'tenures_applicable',
                'additional_offer_discount_type',
                'percent_rate',
                'max_cashback',
              ]
          : [],
    };

    return [...(commonFields[tabName] || []), ...(formSpecificFields[tabName] || [])];
  };

  isNoCostEMIForm = () => {
    const { values } = this.props;
    return values?.discount_type === 'no_cost_emi';
  };

  handleTabChange = ({ currentTarget }) => {
    const newTab = Number(currentTarget.dataset.index);
    const { currentTab } = this.state;

    // Only clear errors if we're moving to a different tab
    if (newTab !== currentTab) {
      this.setState({ currentTab: newTab });
    }
  };

  changeTab = (step) => () => {
    const validTabs = [...this.state.validTabs];
    validTabs[this.state.currentTab] = true;
    const newTab = this.state.currentTab + step;

    this.setState({
      currentTab: newTab,
      validTabs,
    });
  };

  renderForm() {
    const { currentTab } = this.state;
    if (this.props.isLoading) {
      return (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      );
    }

    if (this.props.error) {
      return <Alert type="error" message={this.props.error} />;
    }
    return this.TABS_DATA[currentTab].render();
  }

  renderWizard() {
    const { props, state } = this;
    const { currentTab, validTabs } = state;
    const isLastTab = currentTab === this.TABS_DATA.length - 1;

    const disabled = validTabs.some((tab) => tab === false) || props.disabled || props.isLoading;
    const layout = !isLastTab && 'tabular';
    const { offersData, values, errors = {} } = this.props;

    const isApplicableOnTabInvalid = () => {
      const { currentTab } = this.state;
      const currentTabName = this.TABS_DATA[currentTab].name;

      if (currentTabName !== 'Applicable On' || !this.isNoCostEMIForm()) {
        return false;
      }

      // Check if min_amount is missing
      if (!values?.min_amount) {
        return true;
      }

      // Check if issuer is missing
      if (!values?.issuer) {
        return true;
      }

      // Check if emi_durations is empty
      if (values?.emi_durations?.length === 0) {
        return true;
      }

      // Only check low-cost offer validation if the experiment is enabled
      if (this.props.isLowCostExperimentEnabled) {
        // Check if offersData is missing
        if (!offersData) {
          return true;
        }

        // If we have offersData, check if:
        // 1. Offer type is present (isOfferTypeAbsent should be false)
        // 2. Amount is properly set (isLowCostAmountMissing should be false)
        if (Object.keys(offersData).length > 0) {
          const hasOfferType = !isOfferTypeAbsent(offersData);
          const hasValidAmount = !isLowCostAmountMissing(offersData);

          // Tab is invalid if either offer type is missing OR amount is missing
          return !hasOfferType || !hasValidAmount;
        }

        // If no offers data yet, tab is invalid
        return true;
      }

      // If low-cost experiment is disabled, only check basic fields
      return false;
    };

    const isAdditionalOffersTabInvalid = () => {
      const { currentTab } = this.state;
      const currentTabName = this.TABS_DATA[currentTab].name;

      if (currentTabName !== 'Additional Offer type') {
        return false;
      }

      // If additional offer is not selected, tab is valid
      if (!values?.additional_offer) {
        return false;
      }

      if (!values?.tenures_applicable || !values?.tenures_applicable.length) {
        return true;
      }

      // If additional offer is selected, check required fields based on discount type
      if (values.additional_offer_discount_type === 1) {
        return !values.flat_cashback || errors?.flat_cashback;
      } else if (values.additional_offer_discount_type === 2) {
        return (
          !values.percent_rate ||
          !values.max_cashback ||
          errors?.percent_rate ||
          errors?.max_cashback
        );
      }

      // If no discount type selected yet, tab is invalid
      return values.additional_offer && !values.additional_offer_discount_type;
    };

    const isDisabled = () => {
      const { currentTab } = this.state;
      const currentTabName = this.TABS_DATA[currentTab].name;

      // Special handling for Additional Offers tab
      if (currentTabName === 'Additional Offer type') {
        return isAdditionalOffersTabInvalid();
      }

      // Special handling for Applicable On tab - only for NoCostEMI
      if (currentTabName === 'Applicable On' && this.isNoCostEMIForm()) {
        return isApplicableOnTabInvalid();
      }

      // Check if current tab has any errors
      const hasErrors = Object.entries(errors).some(([key, value]) => {
        const requiredFields = this.getRequiredFieldsForTab(currentTabName);
        return requiredFields.includes(key) && value !== false;
      });

      return hasErrors || !validTabs[currentTab];
    };

    const isPrevTabInvalid = (tabIndex) => {
      for (let i = 0; i < tabIndex; i++) {
        const prevTabName = this.TABS_DATA[i].name;
        let isPrevTabInvalid = false;

        // Use the same validation logic as isDisabled
        if (prevTabName === 'Additional Offer type' && this.isNoCostEMIForm()) {
          isPrevTabInvalid = isAdditionalOffersTabInvalid();
        } else if (prevTabName === 'Applicable On' && this.isNoCostEMIForm()) {
          isPrevTabInvalid = isApplicableOnTabInvalid();
        } else {
          const hasErrors = Object.entries(errors).some(([key, value]) => {
            const requiredFields = this.getRequiredFieldsForTab(prevTabName);
            return requiredFields.includes(key) && value !== false;
          });
          isPrevTabInvalid = hasErrors || !validTabs[i];
        }

        if (isPrevTabInvalid) {
          return true;
        }
      }
      return false;
    };

    const disableTabCondition = (tabIndex) => {
      // First check if any previous tab is invalid
      if (this.isNoCostEMIForm() && isPrevTabInvalid(tabIndex)) {
        return true;
      }

      let isTabDisabled = tabIndex !== 0 && !validTabs[tabIndex - 1];
      const tabName = this.TABS_DATA[tabIndex].name;

      // Special handling for NoCostEMI form
      if (this.isNoCostEMIForm()) {
        if (tabIndex >= 3 && isApplicableOnTabInvalid()) {
          return true;
        }

        // For Additional Offers tab, only check validation if an offer is selected
        if (tabName === 'Additional Offer type' && values?.additional_offer) {
          isTabDisabled = isTabDisabled || isAdditionalOffersTabInvalid();
        }
      }

      if (tabIndex === this.TABS_DATA.length - 1) {
        validTabs.forEach((isValidTab, idx) => {
          if (!isValidTab && idx !== this.TABS_DATA.length - 1 && !isTabDisabled) {
            isTabDisabled = true;
          }
        });
      }
      return isTabDisabled;
    };

    return (
      <Modal isOpen={true} onDismiss={this.props.onClose ?? noop} size="large">
        <ModalBody padding="spacing.0">
          <div className="PaymentLinks--Create SubscriptionLinks--new Wizard">
            <ModalAsideNav
              title={props.title || 'Create an Offer'}
              description={
                <p>Provide details regarding how you would like the offer to function</p>
              }
              tabs={this.TABS_NAMES}
              activeTab={currentTab}
              tabsValidity={validTabs}
              tabClickHandler={this.handleTabChange}
              disableTabCondition={disableTabCondition}
            />

            <main className="form-container">
              <Heading size="medium">{this.TABS_DATA[currentTab].name}</Heading>

              <Form className={CLASS_NAME} layout={layout} onChange={props.onChange}>
                {this.renderForm()}
              </Form>
            </main>
          </div>
        </ModalBody>
        <ModalFooter>
          <Box display="flex" justifyContent="flex-end">
            {currentTab > 0 && (
              <Button
                color="primary"
                onClick={this.changeTab(-1)}
                size="medium"
                type="button"
                variant="tertiary"
                marginRight="spacing.4"
              >
                Previous
              </Button>
            )}

            {!isLastTab ? (
              <Button
                color="primary"
                onClick={this.changeTab(1)}
                size="medium"
                type="button"
                variant="primary"
                isDisabled={isDisabled()}
              >
                Next
              </Button>
            ) : (
              <Button
                color="primary"
                onClick={props.onSubmit}
                size="medium"
                type="submit"
                variant="primary"
                isDisabled={disabled}
                isLoading={!!this.props.isPending}
              >
                {props.submitBtnText}
              </Button>
            )}
          </Box>
        </ModalFooter>
      </Modal>
    );
  }

  render() {
    if (this.IS_MODAL_VIEW) {
      return (
        <div className="NewSubscriptionLink Offers--Create ">
          <div>{this.renderWizard()}</div>
        </div>
      );
    }

    return <div className="StandAloneContainer">{this.renderWizard()}</div>;
  }
}
