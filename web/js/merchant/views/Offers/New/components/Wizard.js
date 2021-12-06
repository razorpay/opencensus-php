import React from 'react';
import { ModalAsideNav } from 'common/new-ui/Wizard';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import Form from 'common/new-ui/Form';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';

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
    this.toggleDisableState();
  }

  toggleDisableState = () => {
    // HINT: Render taking sometime so we need to delay the validations by 100 milli sec
    setTimeout(() => {
      const { state } = this;
      const invalidFields = document.querySelectorAll(`.${CLASS_NAME} .Input.is-invalid`);
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

  handleTabChange = ({ target }) => {
    this.setState({ currentTab: Number(target.dataset.index) });
  };

  changeTab = (step) => () => {
    const validTabs = [...this.state.validTabs];
    validTabs[this.state.currentTab] = true;
    this.setState((prevState) => ({
      currentTab: prevState.currentTab + step,
      validTabs,
    }));
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
    const isNextBtnDisabled = !validTabs[currentTab];
    const layout = !isLastTab && 'tabular';

    return (
      <div class="PaymentLinks--Create SubscriptionLinks--new Wizard">
        <ModalAsideNav
          title={props.title || 'Create an Offer'}
          description={<p>Provide details regarding how you would like the offer to function</p>}
          tabs={this.TABS_NAMES}
          activeTab={currentTab}
          tabsValidity={validTabs}
          tabClickHandler={this.handleTabChange}
          disableTabCondition={(tabIndex) => {
            let isDisabled = tabIndex !== 0 && !validTabs[tabIndex - 1];
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
          <main-title>{this.TABS_DATA[currentTab].name}</main-title>

          <Form class={CLASS_NAME} layout={layout} onChange={props.onChange}>
            {this.renderForm()}
          </Form>
        </main>

        <footer>
          {currentTab > 0 && (
            <Button class="btn-outline" type="button" onClick={this.changeTab(-1)}>
              Previous
            </Button>
          )}

          {!isLastTab ? (
            <Button.Primary type="button" disabled={isNextBtnDisabled} onClick={this.changeTab(1)}>
              Next
            </Button.Primary>
          ) : (
            <AsyncBtn.Primary
              pendingState="Creating..."
              type="submit"
              onClick={props.onSubmit}
              disabled={disabled}
            >
              {props.submitBtnText}
            </AsyncBtn.Primary>
          )}
        </footer>
      </div>
    );
  }

  render() {
    if (this.IS_MODAL_VIEW) {
      return (
        <Modal class="NewSubscriptionLink Offers--Create" onClose={this.props.onClose}>
          <ModalContent>{this.renderWizard()}</ModalContent>
        </Modal>
      );
    }

    return <div class="StandAloneContainer">{this.renderWizard()}</div>;
  }
}
