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

import PaymentMethods from 'merchant/views/Offers/New/paymentMethods';
import OfferDescription from 'merchant/views/Offers/New/offerDescription';
import OfferDiscount from 'merchant/views/Offers/New/offerDiscount';
import OfferDuration from 'merchant/views/Offers/New/offerDuration';
import OfferReview from 'merchant/views/Offers/New/offerReview';
import NoCostEmiMethods from 'merchant/views/Offers/New/NoCostEmiMethods';

const MAX_INT = 21474836;
const CURRENCY = 'INR';
const SUCCESS_NOTIFICATION = 'New offer created';
const NO_COST_EMI = 'no_cost_emi';

@withRouter
@connect(state => state.session, {
  showNotification,
  openModal,
  closeModal,
  luminateRow,
  appendOfferInReduxList,
})
@RTracking(() => window.rzpQ.component('CreateOfferWizard'))
export default class CreateOfferWizard extends React.Component {
  state = {
    currentTab: 0,
    validTabs: [false, false, false, false],
    allPaymentMethodsAllowed: false,
    creation_terms_accepted: 'false',
  };
  IS_MODAL_VIEW = (this.props.onClose && true) || false;

  tabsData = [
    {
      name: 'Description',
      renderFunction: () => (
        <OfferDescription
          name={this.state.name}
          getFormElementValidations={this.getFormElementValidations}
          displayText={this.state.display_text}
          terms={this.state.terms}
          getFormOnChangeHandler={this.getFormOnChangeHandler}
          type={this.state.type}
        />
      ),
      getFieldsToBeValidated: () => {
        return ['name', 'display_text', 'terms', 'type'];
      },
    },
    {
      name: 'Discount type',
      renderFunction: () => (
        <OfferDiscount
          getFormOnChangeHandler={this.getFormOnChangeHandler}
          percentRate={this.state.percent_rate}
          maxCashback={this.state.max_cashback}
          flatCashback={this.state.flat_cashback}
          discountType={this.state.discount_type}
          getFormElementValidations={this.getFormElementValidations}
          minAmount={this.state.min_amount}
          maxAmount={this.state.max_order_amount}
          currency={CURRENCY}
          type={this.state.type}
        />
      ),
      getFieldsToBeValidated: () => {
        const discountTypeSpecificFields = (() => {
          switch (this.state.discount_type) {
            case 'flat':
              return ['flat_cashback'];
            case 'percent':
              return ['max_cashback', 'percent_rate'];
            case 'no_cost_emi':
              return [];
            default:
              return [];
          }
        })();
        return ['discount_type', 'min_amount', ...discountTypeSpecificFields];
      },
    },
    {
      name: 'Applicable On',
      renderFunction: () => {
        if (this.state.discount_type === NO_COST_EMI) {
          return (
            <NoCostEmiMethods
              getFormOnChangeHandler={this.getFormOnChangeHandler}
              minAmount={this.state.min_amount || 0}
              emiDurations={this.state.emi_durations}
              issuer={this.state.issuer}
              getFormElementValidations={this.getFormElementValidations}
            />
          );
        }
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
            walletIssuer={this.state.wallet_issuer}
            paymentMethod={this.state.payment_method}
            getFormElementValidations={this.getFormElementValidations}
          />
        );
      },
      getFieldsToBeValidated: () => {
        if (this.state.discount_type === NO_COST_EMI) {
          return ['issuer', 'emi_durations'];
        }
        return ['payment_method'];
      },
    },
    {
      name: 'Offer Validity',
      renderFunction: () => (
        <OfferDuration
          startsAt={this.state.starts_at}
          getFormElementValidations={this.getFormElementValidations}
          getFormOnChangeHandler={this.getFormOnChangeHandler}
          endsAt={this.state.ends_at}
          block={this.state.block}
          minAmount={this.state.min_amount}
          maxOfferUsage={this.state.max_offer_usage}
          checkoutDisplay={this.state.default_offer}
        />
      ),
      getFieldsToBeValidated: () => {
        return ['starts_at', 'ends_at', 'block', 'max_offer_usage'];
      },
    },
    {
      name: 'Overview',
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
          return 'Offer terms should contain at least of 4 characters';
        }
        if (val.length > 250) {
          return 'Offer terms should not exceed 250 characters';
        }
      },
      discount_type: val => {
        if (!val || val == '') {
          return 'Please select a discount type';
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
        val = parseFloat(val);
        if (val > 99.99 || val < 0.01) {
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
        if (!val && this.state.discount_type === 'percent') return;
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
        if (this.state.max_order_amount && this.state.max_order_amount < val) {
          return `Minimum order amount should be less than max order amount`;
        }
      },
      max_order_amount: val => {
        if (!new RegExp('^[0-9]+(.[0-9][0-9]?)?$').test(val))
          return 'Please enter number upto 2 decimal points';
        val = parseFloat(val);
        if (val > MAX_INT) {
          return `Maximum value allowed is ${MAX_INT}`;
        }
        if (!this.state.min_amount || val < this.state.min_amount) {
          return `Maximum order amount should be more than minimum order amount`;
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
        if (!val) return 'Please select a date';
        if (this.state.starts_at >= val) {
          return 'End date cannot be less that start date.';
        }
        if (moment() > val) {
          return 'End date cannot be in past.';
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
          return 'Payment method cannot be empty';
        }
      },
      max_offer_usage: val => {
        if (!val) return;
        if (!new RegExp('^[0-9]+$').test(val)) return 'Please enter a number';
        val = parseFloat(val);
        if (val > MAX_INT) {
          return `Maximum value allowed is ${MAX_INT}`;
        }
      },
      max_payment_count: val => {
        if (!val) return;
        if (!new RegExp('^[0-9]+$').test(val)) return 'Please enter a number';
        val = parseFloat(val);
        if (val > MAX_INT) {
          return `Maximum value allowed is ${MAX_INT}`;
        }
      },
      issuer: val => {
        if (this.state.discount_type === NO_COST_EMI && (!val || val === '')) {
          return 'Issuer cannot be null for no cost emi';
        }
      },
      emi_durations: val => {
        if (
          !Array.isArray(this.state.emi_durations) ||
          this.state.emi_durations.length < 1
        ) {
          return 'Emi durations not selected';
        }
      },
      type: val => {
        if (!val) return 'Please select a value';
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
      default: syntheticEvent => {
        this.setState({
          [syntheticEvent.target.name]: syntheticEvent.target.value,
        });
      },
      // momentObj / null if not date is required
      datetime: momentObj => this.setState({ [options[0]]: momentObj }),
      stateResetter: resetFields => syntheticEvent => {
        let clonedState = { ...this.state };
        clonedState[syntheticEvent.target.name] = syntheticEvent.target.value;
        let statePropTobeDeleted = resetFields || [];
        statePropTobeDeleted.forEach(stateProp => {
          clonedState[stateProp] = undefined;
        });
        this.setState(clonedState);
      },
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
          <Form class="PaymentLinks--Create--Form" layout="tabular">
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
              Create Offer
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
      'max_order_amount',
    ];
    const dateFields = ['starts_at', 'ends_at'];
    const fieldsToBeDeletedIfFalsey = [
      'payment_method_type',
      'issuer',
      'payment_network',
      'max_payment_count',
      'iins',
      'max_offer_usage',
      'max_order_amount',
    ];
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
    const checkboxFields = ['default_offer'];

    const issuers = ['AMEX', 'BAJAJ'];

    checkboxFields.forEach(field => {
      transformed[field] = form[field] === '1' ? 1 : 0;
    });

    dateFields.forEach(field => {
      if (!form[field]) {
        fieldsTobeDeleted.push(field);
      } else transformed[field] = form[field].unix();
    });

    fieldsToBeDeletedIfFalsey.forEach(field => {
      if (!this.state[field]) fieldsTobeDeleted.push(field);
    });

    //Convert rupees to paisa
    amountFields.forEach(field => {
      transformed[field] *= 100;
    });
    // get additional fields to be deleted based on the discount_type
    if (transformed.discount_type === 'flat') {
      fieldsTobeDeleted.push('max_cashback');
      fieldsTobeDeleted.push('percent_rate');
    }
    if (transformed.discount_type === 'percent') {
      fieldsTobeDeleted.push('flat_cashback');
    }
    if (transformed.discount_type === 'no_cost_emi') {
      fieldsTobeDeleted.push('flat_cashback');
      fieldsTobeDeleted.push('max_cashback');
      fieldsTobeDeleted.push('percent_rate');
      fieldsTobeDeleted.push('payment_network');
      transformed['emi_subvention'] = 1;
      transformed['payment_method'] = 'emi';
    } else {
      fieldsTobeDeleted.push('max_order_amount');
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

    if (issuers.includes(transformed.issuer)) {
      transformed.payment_network = transformed.issuer;
      delete transformed.issuer;
    }

    return transformed;
  }

  onCreate = () => {
    let form = this.tranformFormFields(this.state);
    let offer = new Offer(form);
    return offer
      .save(form, {
        headers: {
          'Content-Type': 'application/json',
        },
      })
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
            window.rzpQ.merchantActions().success('Offer_create', {
              offer_id: savedOffer.id,
            })
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

  areGivenFormElementsValid = (...formFields) => {
    return !formFields.some(formField => !this.isFormElementValid(formField));
  };
}
