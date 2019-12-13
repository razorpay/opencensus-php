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
import OfferDescription from './offerDescription';
import OfferDiscount from './offerDiscount';
import OfferDuration from './offerDuration';

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
    block: '',
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
      block: val => {
        if (!val || val == '') {
          return 'Please select an option';
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
      min_amount: val => {
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
      payment_method: val => {
        if (!val) {
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
        return this.areGivenFormElementsValid('payment_method');
      case 2:
        return this.areGivenFormElementsValid(
          'discount_type',
          'min_amount',
          ...{
            flat: ['flat_cashback'],
            percent: ['max_cashback', 'percent_rate'],
          }[this.state.discount_type]
        );
      case 3:
        return this.areGivenFormElementsValid('starts_at', 'ends_at', 'block');
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

  renderOfferDescriptionFormInputs() {
    return (
      <OfferDescription
        name={this.state.name}
        getFormElementValidations={this.getFormElementValidations}
        displayText={this.state.display_text}
        terms={this.state.terms}
      />
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
        getFormElementValidations={this.getFormElementValidations}
      />
    );
  }

  renderDiscountFormInputs = () => {
    return (
      <OfferDiscount
        percentRate={this.state.percent_rate}
        maxCashback={this.state.max_cashback}
        flatCashback={this.state.flat_cashback}
        discountType={this.state.discount_type}
        getFormElementValidations={this.getFormElementValidations}
        minAmount={this.state.min_amount}
      />
    );
  };

  renderOfferDurationFormInputs = () => {
    const {
      starts_at,
      block,
      ends_at,
      min_amount,
      max_offer_usage,
      checkout_visibility,
    } = this.state;
    return (
      <OfferDuration
        startsAt={starts_at}
        getFormElementValidations={this.getFormElementValidations}
        getFormOnChangeHandler={this.getFormOnChangeHandler}
        endsAt={ends_at}
        block={block}
        checkoutVisibility={checkout_visibility}
        minAmount={min_amount}
        maxOfferUsage={max_offer_usage}
      />
    );
  };

  renderForm() {
    const { currentTab } = this.state;

    switch (currentTab) {
      case 0:
        return this.renderOfferDescriptionFormInputs();
      case 1:
        return this.renderPaymentMethods();
      case 2:
        return this.renderDiscountFormInputs();
      case 3:
        return this.renderOfferDurationFormInputs();
    }
  }

  renderWizard() {
    const { currentTab, validTabs } = this.state;
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
            x => validTabs[x] && this.isTabDataValid(x)
          )}
          disableTabCondition={tabIndex =>
            tabIndex !== 0 && !validTabs[tabIndex - 1]
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
              disabled={!this.isTabDataValid(currentTab)}
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
