import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import { fetchPlans } from 'merchant/reducers/plans';
import {
  fetchSubscriptionItems,
  fetchSubscription,
  saveSubscription,
} from 'merchant/reducers/subscriptions';
import { fetchAddOns } from 'merchant/reducers/addons';
import { fetchCustomer } from 'merchant/reducers/customers';
import { showNotification } from 'merchant_common/reducers/notifications';

import { ModalAsideNav } from 'common/new-ui/Wizard';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import Input from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';
import Button, { AsyncBtn } from 'common/new-ui/Button';

import {
  isPresent,
  findBy,
  getURLQueryParams,
  stringToObj,
  deepClone,
} from 'common/utils/rzp-utils';

import AddOnDetails from './AddOnDetails';
import LinkDetails from './LinkDetails';
// import PlanDetails from '../common/PlanDetails';
import Review from './Review';
import Spinner from 'common/ui/Spinner';
import moment from 'moment';

// import {
//   trackSaveDuplicateSubscription,
//   trackAddAddon,
//   trackAddPlans,
// } from '../../ga';

@withRouter
@connect(
  state => ({
    plans: state.plans,
    items: state.items,
    user: state.session.user,
  }),
  {
    fetchSubscription,
    fetchCustomer,
    fetchPlans,
    fetchSubscriptionItems,
    saveSubscription,
    showNotification,
  }
)
export default class NewSubscriptionLink extends Component {
  state = {
    currentTab: 0,
    validTabs: [false, false, false, false],
    fields: {
      quantity: 1,
      addons: [],
    },
    internals: {},
  };

  handleTabChange = ({ target }) => {
    const currentTab = Number(target.dataset.index);
    this.setState({ currentTab });
  };

  handleChangeIn = ({ target }) => {
    let value = target.value;
    const name = target.name || target.dataset.name;
    const stateKey = target.name ? 'fields' : 'internals';
    let values = { ...this.state[stateKey] };

    if (name.match(/_time/)) {
      return;
    } else if (target.type === 'number') {
      value = Number(value);
    } else if (target.type === 'checkbox') {
      value = target.checked;
    }

    values = stringToObj(name, value, values);

    this.setState({ [stateKey]: values }, () => {
      if (name === '_addOnPresent') {
        this.setState({
          fields: {
            ...this.state.fields,
            addons: target.checked ? [{}] : [],
          },
        });
      }
      this.toggleDisableState();
    });
  };

  getFormOnChangeHandler(type, ...options) {
    const makeState = (name, value) => {
      let state = {};
      state[name] = value;
      return state;
    };
    const handlers = {
      default: syntheticEvent => {
        this.setState(
          makeState(syntheticEvent.target.name, syntheticEvent.target.value),
          this.toggleDisableState
        );
      },
      datetime: momentObj =>
        this.setState(
          makeState(options[0], momentObj.unix()),
          this.toggleDisableState
        ),
      iins: syntheticEvent => {
        let iins = syntheticEvent.target.value
          .split(',')
          .map(iin => iin.trim())
          .filter(iin => iin.length > 5 && iin.length < 7);
        this.setState({ iins }, this.toggleDisableState);
      },
    };

    return handlers[type] || handlers.default;
  }

  changeTab = step => () => {
    const currentTab = this.state.currentTab + step;

    const validTabs = [...this.state.validTabs];
    validTabs[this.state.currentTab] = true;

    this.setState({ currentTab, validTabs });
  };

  renderForm() {
    switch (this.state.currentTab) {
      case 0:
        return (
          <React.Fragment>
            <Input
              label="Offer Name"
              name="name"
              placeholder="Offer Short name"
              autoFocus={true}
              defaultValue={this.state.name}
              required
              validator={val => {
                if (!val || val.length < 4) {
                  return 'Short name should be at least of 4 characters';
                }
                if (val.length > 50) {
                  return 'Short name should not exceed 50 characters';
                }
              }}
            />
            <Input
              label="Display Text"
              name="display_text"
              placeholder="Display text for offer"
              required
              defaultValue={this.state.display_text}
              validator={val => {
                if (!val || val.length < 4) {
                  return 'Short name should be at least of 4 characters';
                }
                if (val.length > 250) {
                  return 'Short name should not exceed 250 characters';
                }
              }}
            />
          </React.Fragment>
        );
      case 1:
        return <div>{/* <Input.Radio>
          </Input.Radio> */}</div>;
      case 2:
        return <div />;
      case 3:
        return <div />;
    }
  }

  renderWizard() {
    const { isFetchingSubscription, currentTab } = this.state;
    const isLastTab = currentTab === tabs.length - 1;

    return (
      // need to improve this css styling
      <div class="PaymentLinks--Create SubscriptionLinks--new Wizard">
        {/* create subscription link tabs */}
        <ModalAsideNav
          title="Create an Offer"
          description={
            <p>
              Provide details regarding how you would like the offer to function
            </p>
          }
          tabs={tabs}
          tabClickHandler={this.handleTabChange}
          activeTab={currentTab}
          tabsValidity={this.state.validTabs}
          disableTabCondition={tabIndex =>
            tabIndex !== 0 && !this.state.validTabs[tabIndex - 1]
          }
        />
        {isFetchingSubscription ? (
          <div className="page-center">
            <Spinner />
          </div>
        ) : (
          <>
            <main class="form-container">
              <main-title>{tabs[currentTab]}</main-title>
              <Form
                class="PaymentLinks--Create--Form"
                layout="tabular"
                onChange={this.getFormOnChangeHandler()}
              >
                {this.renderForm()}
              </Form>
            </main>
            <footer>
              {currentTab > 0 && (
                <Button onClick={this.changeTab(-1)} type="button">
                  Previous
                </Button>
              )}
              {!isLastTab ? (
                <Button.Primary
                  onClick={this.changeTab(1)}
                  type="button"
                  disabled={!this.state.enableSubmit}
                >
                  Next
                </Button.Primary>
              ) : (
                <AsyncBtn.Primary
                  pendingState="Creating..."
                  type="submit"
                  onClick={() => {}}
                >
                  Create Subscription Link
                </AsyncBtn.Primary>
              )}
            </footer>
          </>
        )}
      </div>
    );
  }

  toggleDisableState() {
    let enableSubmit = false;
    const invalidFields = document.querySelectorAll(
      '.PaymentLinks--Create .Input.is-invalid'
    );
    if (!invalidFields.length) {
      enableSubmit = true;
    }
    this.setState({ enableSubmit });
  }

  render() {
    const isModalView = this.props.onClose;

    return isModalView ? (
      <Modal
        class="NewSubscriptionLink animate-down"
        onClose={this.props.onClose}
      >
        <ModalContent>{this.renderWizard({ isModalView })}</ModalContent>
      </Modal>
    ) : (
      <div class="StandAloneContainer">
        {this.renderWizard({ isModalView })}
      </div>
    );
  }
}

const tabs = [
  'Offer Description',
  'Applicable On',
  'Offer Amount',
  'Valid Until',
];
