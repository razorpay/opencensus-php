import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { deepClone } from 'common/utils/rzp-utils';

import { ModalAsideNav } from 'common/new-ui/Wizard';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import Form from 'common/new-ui/Form';
import Button, { AsyncBtn } from 'common/new-ui/Button';

import { showNotification } from 'merchant_common/reducers/notifications';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { luminateRow } from 'merchant/reducers/app';
import Offer from 'merchant/models/Offer';
import { appendOfferInReduxList } from 'merchant/reducers/offers/offersList';

import PaymentMethods from './paymentMethods';
import OfferDescription from './offerDescription';
import OfferDiscount from './offerDiscount';
import OfferDuration from './offerDuration';
import OfferReview from './offerReview';

const MAX_INT = 21474836;
const CURRENCY = 'INR';
const SUCCESS_NOTIFICATION = 'New offer created';

@withRouter
@connect(state => state.session, {
  showNotification,
  openModal,
  closeModal,
  luminateRow,
  appendOfferInReduxList,
})
@RTracking(() => window.rzpQ.component('NewOfferForm'))
export default class CreateOfferWizard extends React.Component {
  state = {
    currentTab: 0,
    starts_at: moment(),
    ends_at: moment(),
    validTabs: [false, false, false, false],
    allPaymentMethodsAllowed: false,
    creation_terms_accepted: 'false',
  };
  IS_MODAL_VIEW = (this.props.onClose && true) || false;

  tabsData = [
    {
      name: 'Offer Description',
      renderFunction: () => (
        <OfferDescription
          name={this.state.name}
          getFormElementValidations={this.getFormElementValidations}
          displayText={this.state.display_text}
          terms={this.state.terms}
        />
      ),
      getFieldsToBeValidated: () => {
        return ['name', 'display_text', 'terms'];
      },
    },
    {
      name: 'Applicable On',
      renderFunction: () => (
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
      ),
      getFieldsToBeValidated: () => {
        return ['payment_method'];
      },
    },
    {
      name: 'Offer Amount',
      renderFunction: () => (
        <OfferDiscount
          percentRate={this.state.percent_rate}
          maxCashback={this.state.max_cashback}
          flatCashback={this.state.flat_cashback}
          discountType={this.state.discount_type}
          getFormElementValidations={this.getFormElementValidations}
          minAmount={this.state.min_amount}
          currency={CURRENCY}
        />
      ),
      getFieldsToBeValidated: () => {
        return [
          'discount_type',
          'min_amount',
          ...{
            flat: ['flat_cashback'],
            percent: ['max_cashback', 'percent_rate'],
          }[this.state.discount_type],
        ];
      },
    },
    {
      name: 'Valid Until',
      renderFunction: () => (
        <OfferDuration
          startsAt={this.state.starts_at}
          getFormElementValidations={this.getFormElementValidations}
          getFormOnChangeHandler={this.getFormOnChangeHandler}
          endsAt={this.state.ends_at}
          block={this.state.block}
          minAmount={this.state.min_amount}
          maxOfferUsage={this.state.max_offer_usage}
        />
      ),
      getFieldsToBeValidated: () => {
        return ['starts_at', 'ends_at', 'block', 'max_offer_usage'];
      },
    },
    {
      name: 'Review',
      renderFunction: () => (
        <OfferReview
          data={this.state}
          currencySymbol={window.currencyList[CURRENCY].symbol}
          getFormOnChangeHandler={this.getFormOnChangeHandler}
        />
      ),
      getFieldsToBeValidated: () => [],
    },
  ];

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
        if (val > 99.99 || val < 0.1) {
          return 'Percentage should be between 0 and 100';
        }
        if (!new RegExp('^[0-9]+(.[0-9][0-9]?)?$').test(val))
          return 'Please enter number upto 2 decimal points';
      },
      flat_cashback: val => {
        if (!new RegExp('^[0-9]+(.[0-9][0-9]?)?$').test(val))
          return 'Please enter number upto 2 decimal points';
        val = parseFloat(val);
        if (val > MAX_INT) {
          return `Maximum value allowed is ${MAX_INT}`;
        }
        if (val > this.state.min_amount) {
          return 'Discount value cannot be greater than minimum amount';
        }
      },
      min_amount: val => {
        if (!new RegExp('^[0-9]+(.[0-9][0-9]?)?$').test(val))
          return 'Please enter number upto 2 decimal points';
        val = parseFloat(val);
        if (val > MAX_INT) {
          return `Maximum value allowed is ${MAX_INT}`;
        }
        if (this.state.discount_type === 'flat') {
          if (val < this.state.flat_cashback) {
            return 'Minimum payment is less than discount value';
          }
        }
      },
      max_cashback: val => {
        if (!new RegExp('^[0-9]+(.[0-9][0-9]?)?$').test(val))
          return 'Please enter number upto 2 decimal points';
        val = parseFloat(val);
        if (val > MAX_INT) {
          return `Maximum value allowed is ${MAX_INT}`;
        }
      },
      ends_at: val => {
        if (this.state.ends_at === null) return;
        if (this.state.starts_at >= val) {
          return 'End date cannot be less that start date.';
        }
      },
      starts_at: val => {
        if (this.state.starts_at === null) return;
        if (moment() > val) {
          return 'Start date cannot be in past.';
        }
      },
      payment_method: val => {
        if (!val) {
          return 'Start date cannot be in past.';
        }
      },
      max_offer_usage: val => {
        if (!new RegExp('[0-9]').test(val)) return 'Please enter a number';
        val = parseFloat(val);
        if (val > MAX_INT) {
          return `Maximum value allowed is ${MAX_INT}`;
        }
      },
    }[elementName];
  };

  isTabDataValid = tabNumber => {
    return this.areGivenFormElementsValid(
      ...this.tabsData[tabNumber].getFieldsToBeValidated()
    );
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
      // momentObj / null if not date is required
      datetime: momentObj => this.setState({ [options[0]]: momentObj }),

      iins: syntheticEvent => {
        const binRegex = /^\d{6}$/;
        let iins = syntheticEvent.target.value
          .split(',')
          .map(iin => iin.trim())
          .filter(iin => binRegex.test(iin));
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

  renderForm() {
    const { currentTab } = this.state;
    return this.tabsData[currentTab].renderFunction();
  }

  renderWizard() {
    const { currentTab, validTabs } = this.state;
    const isLastTab = currentTab === this.tabsData.length - 1;

    return (
      <div class="PaymentLinks--Create SubscriptionLinks--new Wizard">
        <ModalAsideNav
          title="Create an Offer"
          description={
            <p>
              Provide details regarding how you would like the offer to function
            </p>
          }
          tabs={this.tabsData.map(x => x.name)}
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
          <main-title>{this.tabsData[currentTab].name}</main-title>
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
              disabled={
                [0, 1, 2, 3].some(tab => !this.isTabDataValid(tab)) ||
                this.state.creation_terms_accepted === 'false'
              }
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
    const fieldsToBeDeletedIfFalsey = ['payment_method_type'];
    const fieldsTobeDeleted = [
      'discount_type',
      'errors',
      'parentFormLock',
      'disableSubmit',
      'currentTab',
      'validTabs',
      'fields',
      'allPaymentMethodsAllowed',
      'creation_terms_accepted',
    ];

    dateFields.forEach(field => {
      if (!form[field]) {
        fieldsTobeDeleted.push(field);
      } else transformed[field] = form[field].unix();
    });

    fieldsToBeDeletedIfFalsey.forEach(field => {
      if (!field) fieldsTobeDeleted.push(field);
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
          debugger;
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
        debugger;
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
