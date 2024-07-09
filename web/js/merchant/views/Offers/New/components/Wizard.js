import React from 'react';
import { Button, Box, Modal, ModalBody, ModalFooter, Heading } from '@razorpay/blade/components';

import Form from 'common/new-ui/Form';
import { ModalAsideNav } from 'common/new-ui/Wizard';
import Alert from 'common/ui/Forms/Alert';
import Spinner from 'common/ui/Spinner';
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
  zIndex = this.props.zIndex;
  // TODO: Refactor this
  TABS_DATA = this.props.tabsData;

  TABS_NAMES = this.TABS_DATA.map((tab) => tab.name);

  componentDidMount() {
    this.toggleDisableState();
  }

  componentDidUpdate() {
    this.toggleDisableState();
  }

  toggleDisableState = () => {
    // HINT: Render taking sometime so we need to delay the validations by 100 milli sec
    setTimeout(() => {
      const { state } = this;
      const { errors } = this.props;
      const invalidFields = Object.entries(errors)
        // eslint-disable-next-line no-unused-vars
        .filter(([key, value]) => value !== false)
        // eslint-disable-next-line no-unused-vars
        .map(([key, value]) => key);
      const currentTabStatus = invalidFields.length === 0;

      let isValidTabsUpdate = false;
      const newValidTabs = state.validTabs.map((tabStatus, idx) => {
        if (state.currentTab === idx && tabStatus !== currentTabStatus) {
          isValidTabsUpdate = true;
          return currentTabStatus;
        }

        return tabStatus;
      });

      if (isValidTabsUpdate) {
        this.setState({
          validTabs: newValidTabs,
        });
      }
    }, 100);
  };

  handleTabChange = ({ currentTarget }) => {
    this.setState({ currentTab: Number(currentTarget.dataset.index) });
    this.props.setErrors({});
  };

  changeTab = (step) => () => {
    const validTabs = [...this.state.validTabs];
    validTabs[this.state.currentTab] = true;
    this.setState((prevState) => ({
      currentTab: prevState.currentTab + step,
      validTabs,
    }));
    this.props.setErrors({});
  };

  renderForm() {
    const { currentTab } = this.state;
    if (this.props.isLoading) {
      return (
        <div class="page-spinner-container">
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
    const { offersData, isLowCostExperimentEnabled } = this.props;

    const isApplicableOnStepValid = () => {
      const { currentTab } = this.state;
      return (
        currentTab === 2 &&
        isLowCostExperimentEnabled &&
        offersData &&
        (!Object.keys(offersData).length ||
          isOfferTypeAbsent(offersData) ||
          isLowCostAmountMissing(offersData))
      );
    };

    const isDisabled = () => {
      const { currentTab } = this.state;
      // Validate Low cost offer form if the experiment is enabled
      if (isApplicableOnStepValid()) {
        return true;
      }
      return !validTabs[currentTab];
    };
    return (
      <Modal isOpen={true} onDismiss={this.props.onClose} zIndex={this.props.zIndex} size="large">
        <ModalBody padding="spacing.0">
          <div class="PaymentLinks--Create SubscriptionLinks--new Wizard">
            <ModalAsideNav
              title={props.title || 'Create an Offer'}
              description={
                <p>Provide details regarding how you would like the offer to function</p>
              }
              tabs={this.TABS_NAMES}
              activeTab={currentTab}
              tabsValidity={validTabs}
              tabClickHandler={this.handleTabChange}
              disableTabCondition={(tabIndex) => {
                let isDisabled = tabIndex !== 0 && !validTabs[tabIndex - 1];
                // If low cost tenure selected but form is invalid disable next step in sidebar
                if (tabIndex >= 3 && isApplicableOnStepValid()) {
                  return true;
                }
                if (tabIndex === 4) {
                  validTabs.forEach((isValidTab, idx) => {
                    if (!isValidTab && idx !== 4 && !isDisabled) {
                      isDisabled = true;
                    }
                  });
                }
                return isDisabled;
              }}
            />

            <main class="form-container">
              <Heading size="medium">{this.TABS_DATA[currentTab].name}</Heading>

              <Form class={CLASS_NAME} layout={layout} onChange={props.onChange}>
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
                marginRight={'spacing.4'}
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
        <div class="NewSubscriptionLink Offers--Create ">
          <div>{this.renderWizard()}</div>
        </div>
      );
    }

    return <div class="StandAloneContainer">{this.renderWizard()}</div>;
  }
}
