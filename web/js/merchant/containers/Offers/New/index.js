import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { deepClone } from 'common/utils/rzp-utils';

import { ModalAsideNav } from 'common/new-ui/Wizard';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import Input from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';
import Button, { AsyncBtn } from 'common/new-ui/Button';

import { showNotification } from 'merchant_common/reducers/notifications';
import Offer from 'merchant/models/Offer';

import PaymentMethods from './paymentMethods';

@withRouter
@connect(state => ({
  user: state.session.user,
  showNotification,
}))
@RTracking(() => window.rzpQ.component('NewOfferForm'))
export default class CreateOfferWizard extends React.Component {
  state = {
    currentTab: 0,
    starts_at: moment(),
    ends_at: moment().add(1, 'days'),
    block: 'Block Payment',
    validTabs: [false, false, false, false],
    fields: {
      quantity: 1,
      addons: [],
    },
    internals: {},
    allPaymentMethodsAllowed: false,
  };

  stringToInt(subject) {
    if (subject === null) {
      return null;
    }
    return subject.toLowerCase() === 'true' ? 1 : 0;
  }

  getFormElementValidations = elementName => {
    return {
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
          return 'Short description should be at least of 4 characters';
        }
        if (val.length > 250) {
          return 'Short description should not exceed 250 characters';
        }
      },
      terms: val => {
        if (!val || val.length < 4) {
          return 'Short description should be at least of 4 characters';
        }
        if (val.length > 250) {
          return 'Short description should not exceed 250 characters';
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
    }[elementName];
  };

  isTabDataValid = tabNumber => {
    switch (tabNumber) {
      case 0:
        return this.areGivenFormElementsValid('name', 'display_text', 'terms');
      case 1:
        return true;
      case 2:
        return this.areGivenFormElementsValid(
          'discount_type',
          ...{
            flat: ['flat_cashback'],
            percent: ['max_cashback', 'percent_rate'],
          }[this.state.discount_type]
        );
      case 3:
        return this.areGivenFormElementsValid('starts_at', 'ends_at');
      default:
        return false;
    }
  };

  handleTabChange = ({ target }) => {
    const currentTab = Number(target.dataset.index);
    this.setState({ currentTab });
  };

  getFormOnChangeHandler = (type = 'default', ...options) => {
    const handlers = {
      default: syntheticEvent =>
        this.setState({
          [syntheticEvent.target.name]: syntheticEvent.target.value,
        }),

      datetime: momentObj => this.setState({ [options[0]]: momentObj }),

      iins: syntheticEvent => {
        let iins = syntheticEvent.target.value
          .split(',')
          .map(iin => iin.trim())
          .filter(iin => iin.length > 5 && iin.length < 7);
        this.setState({ iins });
      },
    };

    return handlers[type];
  };

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

  renderDiscountFormInputs() {
    return (
      <React.Fragment>
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
      </React.Fragment>
    );
  }

  renderOfferDescriptionFormInputs() {
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
        <Input.Textarea
          label="Terms"
          name="terms"
          placeholder="Terms and conditions for offer"
          defaultValue={this.state.terms}
          onChange={this.getFormOnChangeHandler()}
          validator={this.getFormElementValidations('terms')}
          required
        />
      </React.Fragment>
    );
  }

  renderPaymentMethods() {
    return (
      <PaymentMethods
        allPaymentMethodsAllowed={this.state.allPaymentMethodsAllowed}
        getFormOnChangeHandler={this.getFormOnChangeHandler}
        isSelectedPaymentMethod={this.isSelectedPaymentMethod}
        iins={this.state.iins}
        paymentNetwork={this.state.payment_network}
        maxPaymentCount={this.state.max_payment_count}
        paymentMethodType={this.state.payment_method_type}
        issuer={this.state.issuer}
        paymentMethod={this.state.payment_method}
      />
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
            defaultValue={this.state.percent_rate}
            validator={this.getFormElementValidations('percent_rate')}
          />
          <Input
            label="Maximum Cashback"
            name="max_cashback"
            defaultValue={this.state.max_cashback}
            class="Input--half"
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
    const { currentTab, discount_type, starts_at, ends_at } = this.state;

    switch (currentTab) {
      case 0:
        return this.renderOfferDescriptionFormInputs();
      case 1:
        return this.renderPaymentMethods();
      case 2:
        return this.renderDiscountFormInputs();
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
              defaultValue={starts_at}
            />
            <Input.DateTime
              label="Expires On"
              name="ends_at"
              onChange={this.getFormOnChangeHandler('datetime', 'ends_at')}
              description="Expiry date for offer"
              isInline
              required
              validator={this.getFormElementValidations('ends_at')}
              defaultValue={ends_at}
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
          tabsValidity={[0, 1, 2, 3].map(
            x => this.state.validTabs[x] && this.isTabDataValid(x)
          )}
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
              onClick={this.onCreate}
              disabled={[0, 1, 2, 3].some(tab => !this.isTabDataValid(tab))}
            >
              Create Subscription Link
            </AsyncBtn.Primary>
          )}
        </footer>
      </div>
    );
  }

  tranformFormFields(form) {
    const transformed = deepClone(form);
    const amountFields = [
      'max_cashback',
      'flat_cashback',
      'min_amount',
      'percent_rate',
    ];
    const dateFields = ['starts_at', 'ends_at'];
    const fieldsTobeDeleted = [
      'discount_type',
      'errors',
      'parentFormLock',
      'disableSubmit',
      'currentTab',
      'validTabs',
      'fields',
      'allPaymentMethodsAllowed',
      // 'block',
    ];

    dateFields.forEach(field => {
      transformed[field] = form[field].unix();
    });

    //Convert rupees to paisa
    amountFields.forEach(field => {
      transformed[field] *= 100;
    });
    // get additional fields to be deleted based on the discount_type
    if (transformed.discount_type === 'flat') {
      fieldsTobeDeleted.push('max_cashback');
      fieldsTobeDeleted.push('percent_rate');
    } else {
      fieldsTobeDeleted.push('flat_cashback');
    }
    if (!this.isSelectedPaymentMethod('card', 'emi')) {
      fieldsTobeDeleted.push('max_payment_count');
    }
    if (transformed.min_amount === null || isNaN(transformed.min_amount)) {
      fieldsTobeDeleted.push('min_amount');
    }
    //fields to be deleted
    fieldsTobeDeleted.forEach(field => {
      if (field in transformed) {
        delete transformed[field];
      }
    });
    transformed.block = this.stringToInt(transformed.block);
    return transformed;
  }

  onCreate = () => {
    let form = this.tranformFormFields(this.state);
    let offer = new Offer(form);
    return offer
      .save(form)
      .then(savedOffer => {
        this.setState({
          parentFormLock: false,
        });

        if (savedOffer && savedOffer.id) {
          this.props.showNotification({
            type: 'success',
            message: SUCCESS_NOTIFICATION,
          });

          //analytics event tracking
          this.props.tracking.trackEvent(
            window.rzpQ.merchantActions().success('Offer_create')
          );

          const entityId = savedOffer.id;

          if (this.IS_MODAL_VIEW) {
            this.props.appendOfferInReduxList(savedOffer);
            this.props.luminateRow(entityId); // Make it promise based
            setTimeout(this.props.onClose, 50);
          } else {
            const redirectUrl = '/offers/' + entityId;

            this.props.history.push(redirectUrl);
          }
        } else {
          //analytics event tracking
          this.props.tracking.trackEvent(
            window.rzpQ.merchantActions().failed('Offer_create', {
              error: resp.errors,
            })
          );
          throw new Error(resp.errors);
        }
      })
      .catch(({ errors }) => {
        let err = errors;
        if (Array.isArray(err)) {
          err = [];

          errors.length &&
            errors.forEach(e => {
              if (e && e.toLowerCase().indexOf('status code') === -1) {
                err.push(e);
              }
            });

          err = err.length ? err : null;
        }

        if (!err) {
          err = `Some network error has occured`;
        }
        this.props.showNotification({
          type: 'error',
          message: err,
        });
      });
  };

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

  isFormElementValid = elementName => {
    return !this.getFormElementValidations(elementName)(
      this.state[elementName]
    );
  };

  areGivenFormElementsValid = (...args) => {
    return !args.some(x => !this.isFormElementValid(x));
  };
}

const tabs = [
  'Offer Description',
  'Applicable On',
  'Offer Amount',
  'Valid Until',
];
