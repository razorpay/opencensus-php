import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import { ModalAsideNav } from 'common/new-ui/Wizard';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import Input from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';
import Button, { AsyncBtn } from 'common/new-ui/Button';

@withRouter
@connect(state => ({
  user: state.session.user,
}))
export default class CreateOfferWizard extends Component {
  getFormElementValidations = elementName => {
    return (
      {
        name: val => {
          if (!val || val.length < 4) {
            return 'Short name should be at least of 4 characters';
          }
          if (val.length > 50) {
            return 'Short name should not exceed 50 characters';
          }
        },
        display_text: val => {
          if (!val || val.length < 4) {
            return 'Short name should be at least of 4 characters';
          }
          if (val.length > 250) {
            return 'Short name should not exceed 50 characters';
          }
        },
        discount_type: val => {
          if (!val || val == '') {
            return 'Please select a field type';
          }
        },
        percent_rate: val => {
          if (!val) {
            return 'Should be valid number between 0 and 100';
          }
          if (val > 100 || val < 0) {
            return 'Percentage should be between 0 and 100';
          }
          if (!new RegExp('^[0-9]+(.[0-9][0-9]?)?$').test(val))
            return 'Please enter number upto 2 decimal points';
        },
        flat_cashback: val => {
          if (!new RegExp('^[0-9]+(.[0-9][0-9]?)?$').test(val))
            return 'Please enter number upto 2 decimal points';
        },
        max_cashback: val => {
          if (!new RegExp('^[0-9]+(.[0-9][0-9]?)?$').test(val))
            return 'Please enter number upto 2 decimal points';
        },
        ends_at: val => {
          if (this.state.starts_at >= val) {
            return 'End date cannot be less that start date.';
          }
        },
        starts_at: val => {
          if (moment() > val) {
            return 'Start date cannot be in past.';
          }
        },
      }[elementName] ||
      (val => {
        if (!val) return 'must exist';
      })
    );
  };

  isFormElementValid = elementName => {
    return !this.getFormElementValidations(elementName)(
      this.state[elementName]
    );
  };

  isTabDataValid = tabNumber => {
    let isValid = true;
    switch (tabNumber) {
      case 0:
        isValid =
          isValid &&
          this.isFormElementValid('name') &&
          this.isFormElementValid('display_text');
        break;
      case 1:
        isValid = isValid;
        break;
      case 2:
        isValid =
          this.isFormElementValid('discount_type') &&
          (this.state.discount_type == 'flat'
            ? this.isFormElementValid('flat_cashback')
            : this.isFormElementValid('max_cashback') &&
              this.isFormElementValid('percent_rate'));
        break;
      case 3:
        isValid =
          this.isFormElementValid('starts_at') &&
          this.isFormElementValid('ends_at');
      default:
        break;
    }
    return isValid;
  };

  state = {
    currentTab: 0,
    starts_at: moment(),
    ends_at: moment().add(1, 'days'),
    validTabs: [false, false, false, false],
    fields: {
      quantity: 1,
      addons: [],
    },
    internals: {},
    allPaymentMethodsAllowed: false,
  };

  handleTabChange = ({ target }) => {
    const currentTab = Number(target.dataset.index);
    this.setState({ currentTab });
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
          makeState(syntheticEvent.target.name, syntheticEvent.target.value)
        );
      },
      datetime: momentObj => this.setState(makeState(options[0], momentObj)),
      iins: syntheticEvent => {
        let iins = syntheticEvent.target.value
          .split(',')
          .map(iin => iin.trim())
          .filter(iin => iin.length > 5 && iin.length < 7);
        this.setState({ iins });
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

  isSelectedPaymentMethod = (...methods) => {
    return (
      this.state.payment_method &&
      methods.indexOf(this.state.payment_method) > -1
    );
  };

  renderPaymentMethods() {
    const { allPaymentMethodsAllowed } = this.state;

    let paymentMethods = [
      { label: 'Select Payment method', name: '' },
      { label: 'Card', name: 'card' },
      { label: 'Net Banking', name: 'netbanking' },
      { label: 'Wallet', name: 'wallet' },
      { label: 'UPI', name: 'upi' },
      { label: 'EMI', name: 'emi' },
      { label: 'Cardless EMI', name: 'cardless_emi' },
      { label: 'Pay Later', name: 'paylater' },
    ];
    let paymentIssuers = [
      { label: 'Select Issuers', name: '' },
      { label: 'HDFC Bank', name: 'HDFC' },
      { label: 'HSBC Bank', name: 'HSBC' },
      { label: 'ICICI Bank', name: 'ICIC' },
      { label: 'INDUSIND Bank', name: 'INDB' },
      { label: 'Kotak Mahindra Bank', name: 'KKBK' },
      { label: 'Ratnakar Bank Bank', name: 'RATN' },
      { label: 'Standard Chartered Bank', name: 'SCBL' },
      { label: 'Axis Bank', name: 'UTIB' },
      { label: 'Yes Bank', name: 'YESB' },
      { label: 'Citi Bank', name: 'CITI' },
      { label: 'State Bank of India', name: 'SBIN' },
      { label: 'Bank of Baroda Bank', name: 'BARB' },
    ];

    let paymentNetworks = [
      { label: 'Select Network', name: '' },
      { label: 'Visa', name: 'VISA' },
      { label: 'RuPay', name: 'RUPAY' },
      { label: 'MasterCard', name: 'MC' },
      { label: 'Diners Club', name: 'DICL' },
      { label: 'Maestro', name: 'MAES' },
      { label: 'American Express', name: 'AMEX' },
    ];
    return (
      <React.Fragment>
        {!allPaymentMethodsAllowed && (
          <Input.Select
            label="Payment Method"
            name="payment_method"
            options={paymentMethods}
            placeholder="Payment Method"
            onChange={this.getFormOnChangeHandler()}
          />
        )}

        {(this.isSelectedPaymentMethod('netbanking', 'card', 'emi') && (
          <Input.Select
            label="Issuer"
            name="issuer"
            placeholder="Payment Instrument Issuer/Bank Name"
            options={paymentIssuers}
          />
        )) ||
          null}
        {(this.isSelectedPaymentMethod('card', 'emi') && (
          <React.Fragment>
            <Input
              label="Maximum Usage Per Card"
              name="max_payment_count"
              type="number"
              placeholder="Maximum usage of a card to avail this offer"
            />
            <Input.Select
              label="Card Type"
              name="payment_method_type"
              description="Card Type"
              onChange={this.getFormOnChangeHandler()}
              options={(() => {
                return this.isSelectedPaymentMethod('emi')
                  ? [{ label: 'Credit Card', name: 'credit' }]
                  : [
                      { label: 'Credit Card', name: 'credit' },
                      { label: 'Debit Card', name: 'debit' },
                    ];
              })()}
              required
            />
            <Input.Select
              label="Payment Method Network"
              name="payment_network"
              placeholder="Payment Method Type"
              options={paymentNetworks}
            />
            <Input
              label="IINs"
              onChange={this.getFormOnChangeHandler('iins')}
              placeholder="6 digit IINs for cards. Separated by comma if more than one"
              description={this.state.iins && this.state.iins.join(', ')}
            />
          </React.Fragment>
        )) ||
          null}
      </React.Fragment>
    );
  }

  renderDiscountDetailsSection() {
    let type = this.state.discount_type;
    if (type && type === 'flat') {
      return (
        <Input
          label="Discount Worth"
          name="flat_cashback"
          class="Input--half"
          addonBefore={<span>{window.currencyList['INR'].symbol}</span>}
          description="Discount worth in cash"
          required
          defaultValue={this.state.flat_cashback}
          validator={this.getFormElementValidations('flat_cashback')}
        />
      );
    }
    if (type && type === 'percent') {
      return (
        <React.Fragment>
          <Input
            label="Discount Worth"
            name="percent_rate"
            class="Input--half"
            description="Discount worth in Percent"
            addonBefore={<span>%</span>}
            required
            validator={this.getFormElementValidations('percent_rate')}
          />
          <Input
            label="Maximum Cashback"
            name="max_cashback"
            class="Input--half"
            onChange={this.getFormOnChangeHandler()}
            description="Maximum cashback for this offer"
            addonBefore={<span>{window.currencyList['INR'].symbol}</span>}
            validator={this.getFormElementValidations('max_cashback')}
            required
          />
        </React.Fragment>
      );
    }
  }

  renderForm() {
    switch (this.state.currentTab) {
      case 0:
        return this.getOfferDescriptionFormInputs();
      case 1:
        return this.renderPaymentMethods();
      case 2:
        return (
          <div>
            <strong>Instant Discount</strong>
            <p>The customer will pay the discounted price for the product</p>
            <div>
              <Input.Select
                name="discount_type"
                label="Discount Type"
                placeholder="Discount Type"
                onChange={this.getFormOnChangeHandler()}
                required
                defaultValue={this.state.discount_type}
                options={[
                  { label: 'Select Type', name: '' },
                  { label: 'Flat', name: 'flat' },
                  { label: 'Percentage', name: 'percent' },
                ]}
                validator={this.getFormElementValidations('discount_type')}
              />
              {this.renderDiscountDetailsSection()}
            </div>
          </div>
        );
      case 3:
        return (
          <React.Fragment>
            <Input.DateTime
              label="Starting On"
              name="starts_at"
              description="Start date for offer"
              onChange={this.getFormOnChangeHandler('datetime', 'starts_at')}
              isInline
              required
              validator={this.getFormElementValidations('starts_at')}
              defaultValue={this.state.starts_at}
            />
            <Input.DateTime
              label="Expires On"
              name="ends_at"
              onChange={this.getFormOnChangeHandler('datetime', 'ends_at')}
              description="Expiry date for offer"
              isInline
              required
              validator={this.getFormElementValidations('ends_at')}
              defaultValue={this.state.ends_at}
            />
          </React.Fragment>
        );
    }
  }

  renderWizard() {
    const { currentTab } = this.state;
    const isLastTab = currentTab === tabs.length - 1;

    return (
      <div class="PaymentLinks--Create SubscriptionLinks--new Wizard">
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
              disabled={!this.isTabDataValid(this.state.currentTab)}
            >
              Next
            </Button.Primary>
          ) : (
            <AsyncBtn.Primary
              pendingState="Creating..."
              type="submit"
              onClick={() => {}}
              disabled={[0, 1, 2, 3].some(tab => !this.isTabDataValid(tab))}
            >
              Create Subscription Link
            </AsyncBtn.Primary>
          )}
        </footer>
      </div>
    );
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

  getOfferDescriptionFormInputs() {
    return (
      <React.Fragment>
        <Input
          label="Offer Name"
          name="name"
          placeholder="Offer Short name"
          autoFocus={true}
          defaultValue={this.state.name}
          required
          validator={this.getFormElementValidations('name')}
        />
        <Input
          label="Display Text"
          name="display_text"
          placeholder="Display text for offer"
          required
          defaultValue={this.state.display_text}
          validator={this.getFormElementValidations('display_text')}
        />
      </React.Fragment>
    );
  }
}

const tabs = [
  'Offer Description',
  'Applicable On',
  'Offer Amount',
  'Valid Until',
];
