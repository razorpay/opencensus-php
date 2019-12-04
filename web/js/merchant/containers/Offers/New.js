import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import { classList, deepClone } from 'common/utils/rzp-utils';

import { Modal, ModalContent } from 'common/new-ui/Modal';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { luminateRow } from 'merchant/reducers/app';
import Alert from 'common/ui/Forms/Alert';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Offer from 'merchant/models/Offer';
import { appendOfferInReduxList } from 'merchant/reducers/offers/offersList';
import RTracking from 'react-tracking';

const SUCCESS_NOTIFICATION = 'New offer created';
const MAX_INT = 21474836;

@RTracking(() => window.rzpQ.component('NewOfferForm'))
class NewOfferForm extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      errors: null,
      disableSubmit: true,
    };
    this.IS_MODAL_VIEW = (this.props.onClose && true) || false;
  }

  //util method
  stringToInt(subject) {
    if (subject === null) {
      return null;
    }
    return subject.toLowerCase() === 'true' ? 1 : 0;
  }

  toggleDisableState() {
    let disableSubmit = true;

    const invalidFields = document.querySelectorAll(
      '.PaymentLinks--Create .Input.is-invalid'
    );
    if (invalidFields.length < 1 && this.getMissingRequiredFields() < 1) {
      disableSubmit = false;
    }
    this.setState({ disableSubmit });
  }

  getMissingRequiredFields() {
    const requiredFields = ['starts_at', 'ends_at'];
    const missingFields = [];
    requiredFields.forEach(field => {
      if (!this.state[field]) {
        missingFields.push(field);
      }
    });

    return missingFields;
  }

  tranformFormFields(form) {
    const transformed = deepClone(form);
    const amountFields = [
      'max_cashback',
      'flat_cashback',
      'min_amount',
      'percent_rate',
    ];
    const fieldsTobeDeleted = [
      'discount_type',
      'errors',
      'parentFormLock',
      'disableSubmit',
    ];

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
          () => this.toggleDisableState()
        );
      },
      floatFromEvent: syntheticEvent => {
        this.setState(
          makeState(
            syntheticEvent.target.name,
            parseFloat(syntheticEvent.target.value)
          ),
          () => this.toggleDisableState()
        );
      },
      datetime: momentObj => {
        this.setState(makeState(options[0], momentObj.unix()), () =>
          this.toggleDisableState()
        );
      },
      iins: syntheticEvent => {
        const binRegex = /^\d{6}$/;
        let iins = syntheticEvent.target.value
          .split(',')
          .map(iin => iin.trim())
          .filter(iin => binRegex.test(iin));
        this.setState({ iins }, () => this.toggleDisableState());
      },
    };
    return handlers[type] || handlers.default;
  }

  isSelectedPaymentMethod = (...methods) => {
    return (
      this.state.payment_method &&
      methods.indexOf(this.state.payment_method) > -1
    );
  };

  renderPaymentMethods() {
    let paymentMethods = [
      { label: 'Select Payment method', name: '' },
      { label: 'Card', name: 'card' },
      { label: 'Net Banking', name: 'netbanking' },
      { label: 'Wallet', name: 'wallet' },
      { label: 'UPI', name: 'upi' },
      { label: 'EMI', name: 'emi' },
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

    let walletIssuers = [
      { label: 'Select Issuers', name: '' },
      { label: 'Paytm', name: 'paytm' },
      { label: 'PAYZAPP', name: 'payzapp' },
      { label: 'MOBIKWIK', name: 'mobikwik' },
      { label: 'PayU Money', name: 'payumoney' },
      { label: 'OLA Money', name: 'olamoney' },
      { label: 'Airtel Money', name: 'airtelmoney' },
      { label: 'Amazon Pay', name: 'amazonpay' },
      { label: 'Freecharge', name: 'freecharge' },
      { label: 'JIO Money', name: 'jiomoney' },
      { label: 'SBI buddy', name: 'sbibuddy' },
      { label: 'OPEN WALLET', name: 'openwallet' },
      { label: 'M PESA', name: 'mpesa' },
      { label: 'Phone Pe', name: 'phonepe' },
      { label: 'Paypal', name: 'paypal' },
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
        <Input.Select
          label="Payment Method"
          name="payment_method"
          options={paymentMethods}
          placeholder="Payment Method"
          onChange={this.getFormOnChangeHandler()}
        />

        {(this.isSelectedPaymentMethod(
          'netbanking',
          'card',
          'emi',
          'wallet'
        ) && (
          <Input.Select
            label="Bank"
            name="issuer"
            placeholder="Payment Instrument Issuer/Bank Name"
            options={
              (this.isSelectedPaymentMethod('wallet') && walletIssuers) ||
              paymentIssuers
            }
            onChange={this.getFormOnChangeHandler()}
          />
        )) ||
          null}

        {(this.isSelectedPaymentMethod('card', 'emi') && (
          <React.Fragment>
            <Input
              label="Maximum Usage Per Card"
              name="max_payment_count"
              type="number"
              onChange={this.getFormOnChangeHandler()}
              description="Maximum number of times a particular card can avail this offer"
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
              label="Network"
              name="payment_network"
              placeholder="Payment Method Type"
              onChange={this.getFormOnChangeHandler()}
              options={paymentNetworks}
            />
            <Input
              label="IINs"
              onChange={this.getFormOnChangeHandler('iins')}
              placeholder="6 digit IINs Separated by comma"
              description={this.state.iins && this.state.iins.join(', ')}
            />
          </React.Fragment>
        )) ||
          null}
      </React.Fragment>
    );
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
          this.props.tracking.trackEvent(window.rzpQ.success('Offer_create'));

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
            window.rzpQ.failed('Offer_create', {
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
          pattern="[0-9]+(\.[0-9][0-9]?)?"
          patternError="Please enter number upto 2 decimal points"
          validator={val => {
            if (val > MAX_INT) {
              return `Maximum value allowed is ${MAX_INT}`;
            }
          }}
          onChange={this.getFormOnChangeHandler('floatFromEvent')}
          required
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
            onChange={this.getFormOnChangeHandler('floatFromEvent')}
            required
            pattern="[0-9]+(\.[0-9][0-9]?)?"
            patternError="Please enter number upto 2 decimal points"
            validator={val => {
              if (!val) {
                return 'Should be valid percentage';
              }
              if (val > 99.99 || val < 0.1) {
                return 'Percentage should be greater than 0 and less than 100';
              }
            }}
          />
          <Input
            label="Maximum Discount"
            name="max_cashback"
            class="Input--half"
            onChange={this.getFormOnChangeHandler('floatFromEvent')}
            description="Maximum discount for this offer"
            addonBefore={<span>{window.currencyList['INR'].symbol}</span>}
            pattern="[0-9]+(\.[0-9][0-9]?)?"
            patternError="Please enter number upto 2 decimal points"
            validator={val => {
              if (val > MAX_INT) {
                return `Maximum value allowed is ${MAX_INT}`;
              }
            }}
            required
          />
        </React.Fragment>
      );
    }
  }

  render() {
    return (
      <div class="PaymentLinks--Create Wizard">
        <main class="form-container">
          <main-title class="main-title">Create New Offer</main-title>
          <Form autoComplete="off" layout="tabular">
            {/* ALERTS */}
            {this.props.mode === 'test' && (
              <Alert type="warning">
                You are creating the offer in <b>Test Mode</b>. So, only test
                payments can be made for it.
              </Alert>
            )}
            <Alert type="error" message={this.state.errors} />
            <h4>Offer Description</h4>
            <Input
              label="Offer Name"
              name="name"
              placeholder="Enter your offer name here"
              autoFocus={true}
              required
              onChange={this.getFormOnChangeHandler()}
              validator={val => {
                if (!val || val.length < 4) {
                  return 'Too short offer name';
                }
                if (val.length > 50) {
                  return 'Offer name should not exceed 50 characters';
                }
              }}
            />
            <Input
              label="Display Text"
              name="display_text"
              placeholder="Details about your offer"
              onChange={this.getFormOnChangeHandler()}
              required
              validator={val => {
                if (!val || val.length < 4) {
                  return 'Too short offer text';
                }
                if (val.length > 250) {
                  return 'Offer text should not exceed 250 characters';
                }
              }}
            />
            <Input
              label="Terms"
              name="terms"
              placeholder="Terms and conditions for offer"
              onChange={this.getFormOnChangeHandler()}
              required
            />
            <hr />
            <h4>Discount</h4>
            <Input.Select
              name="discount_type"
              label="Discount Type"
              placeholder="Discount Type"
              onChange={this.getFormOnChangeHandler()}
              required
              options={[
                { label: 'Select Type', name: '' },
                { label: 'Percentage', name: 'percent' },
                { label: 'Flat', name: 'flat' },
              ]}
            />
            {this.renderDiscountDetailsSection()}
            <hr />
            <h4>Checks</h4>
            <Input
              label="Minimum Payment"
              name="min_amount"
              class="Input--half"
              addonBefore={<span>{window.currencyList['INR'].symbol}</span>}
              onChange={this.getFormOnChangeHandler('floatFromEvent')}
              description="Minimum bill amount on for this offer"
              pattern="[0-9]+(\.[0-9][0-9]?)?"
              patternError="Please enter number upto 2 decimal points"
              validator={val => {
                val = parseFloat(val);
                if (this.state.discount_type === 'flat') {
                  if (val < this.state.flat_cashback) {
                    return 'Minimum payment is less than discount value';
                  }
                }
                if (val > MAX_INT) {
                  return `Maximum value allowed is ${MAX_INT}`;
                }
              }}
            />
            <Input.Select
              label="On Offer Failure"
              name="block"
              description="Block/Allow payment on failure of offer validation"
              onChange={this.getFormOnChangeHandler()}
              required
              options={[
                { label: 'Select Type', name: '' }, // empty string is treated as null and throws required error
                { label: 'Block Payment', name: true },
                { label: 'Allow Payment', name: false },
              ]}
            />
            <Input
              type="Number"
              label="Maximum Usage"
              name="max_offer_usage"
              placeholder="Maximum usage for this offer"
              onChange={this.getFormOnChangeHandler()}
              validator={val => {
                if (val > MAX_INT) {
                  return `Maximum value allowed is ${MAX_INT}`;
                }
              }}
            />

            <hr />
            <h4>Payment Method</h4>
            {this.renderPaymentMethods()}
            <hr />
            <h4>Offer Duration</h4>
            <Input.DateTime
              label="Offer begins on"
              name="starts_at"
              onChange={this.getFormOnChangeHandler('datetime', 'starts_at')}
              isInline
              required
              defaultValue={''}
              validator={val => {
                if (moment() > val) {
                  return 'Start date cannot be in past.';
                }
              }}
            />
            <Input.DateTime
              label="Offer ends on"
              name="ends_at"
              onChange={this.getFormOnChangeHandler('datetime', 'ends_at')}
              isInline
              required
              defaultValue={''}
              validator={val => {
                if (this.state.starts_at > val.unix()) {
                  return 'End date cannot be less that start date.';
                }
              }}
            />
            <hr />
          </Form>
        </main>
        <footer>
          {/* Action Button 1 */}
          {this.props.onClose && (
            <Button onClick={e => this.props.onClose()}>Cancel</Button>
          )}
          {/* Action Button 2 */}
          <AsyncBtn.Primary
            onClick={this.onCreate}
            pendingState={'Creating...'}
            disabled={this.state.disableSubmit}
          >
            Create Offer
          </AsyncBtn.Primary>
        </footer>
      </div>
    );
  }
}

@withRouter
@connect(state => state.session, {
  showNotification,
  openModal,
  closeModal,
  luminateRow,
  appendOfferInReduxList,
})
export default class New extends React.Component {
  render() {
    // `onClose` is passed only when Modal is to be opened. In case of Account Details, onClose is passed.
    const IS_MODAL_VIEW = (this.props.onClose && true) || false;
    const content = <NewOfferForm {...this.props} />;
    return IS_MODAL_VIEW ? (
      <Modal
        class={classList('PaymentLinks', content && 'animate-down')}
        onClose={this.props.onClose}
      >
        <ModalContent>{content}</ModalContent>
      </Modal>
    ) : (
      <div class="StandAloneContainer">{content}</div>
    );
  }
}
