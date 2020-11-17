import React, { Component } from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal } from 'merchant_common/reducers/modals';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { isInteger } from 'common/utils/validators';
import ajax from 'merchant/utils/ajax';
import { trackOndemand } from '../../ga';
import { fetchCurrentBalance } from 'merchant/reducers/home';
import Input from 'common/new-ui/Input';
import Alert from 'common/ui/Forms/Alert';
import { AmountTooltip } from 'common/ui/Amount';
import Amount from 'common/ui/Amount';
import debounce from 'common/utils/debounce';
import PropTypes from 'prop-types';
import Popover, { PopoverBody } from 'common/ui/Popover';
import ModalCloseReasons from 'merchant/views/Settlements/Settlements/components/Modals/ModalCloseReasons';
import ScheduledBanner from 'merchant/views/Settlements/Settlements/components/ScheduledBanner';

@connect((state) => ({ user: state.session.user }), {
  closeModal,
  fetchCurrentBalance,
})
export default class OndemandModal extends Component {
  constructor(props) {
    super(props);

    this.state = {
      isSaving: false,
      isSaved: false,
      amount: props.currentBalance ? parseInt(props.currentBalance / 100) : 0,
      validAmount: true,
      closeClicked: false,
      errors: [],
      breakupShow: false,
      checkedBreakup: false,
      isLoadingBreakup: false,
      hasChangedAmount: false,
      clickedConfirm: false,
      closeReason: '',
      needFetch: true,
      taxPercent: 0,
      instantFeePercent: 0,
      tax: 0,
      instantFee: 0,
    };

    this.updateFeeDebounced = debounce(this.updateFee, 300);
  }

  static contextTypes = {
    confirm: PropTypes.func,
  };

  renderConfirmation = () => {
    const { amount } = this.state;
    return (
      <div class="m-b">
        The settlement amount is:{` `}
        <span class="bold-amount">
          <Amount value={amount * 100} currency={'INR'} parentQuerySelector={'.modal-body'} />
        </span>
      </div>
    );
  };

  gaEventDispatcher = (eventObject) => {
    eventObject['eventCategory'] = 'Dashboard - Early Settlement';
    window.rzpAnalytics(eventObject);
  };

  openConfirmSettlement = () => {
    const { hasChangedAmount, checkedBreakup, amount } = this.state;
    this.setState({
      clickedConfirm: true,
    });

    this.gaEventDispatcher({
      eventAction: `Confirm`,
      eventLabel: `${hasChangedAmount ? 'Changed amount' : 'preFilled amount'} - ${
        checkedBreakup ? 'after' : 'before'
      } show breakup| close`,
    });

    this.gaEventDispatcher({
      eventAction: `Amount`,
      eventLabel: `${this.amountCategory(amount)} - ${
        hasChangedAmount ? 'Changed amount' : 'preFilled amount'
      } -confirm`,
    });

    this.context.confirm({
      header: 'Are you sure you want to do this settlement?',
      message: this.renderConfirmation,
      affirmativeLabel: 'Yes, Settle',
      abortLabel: "No, Don't ",
      action: () => {
        this.gaEventDispatcher({
          eventAction: `second confirmation`,
          eventLabel: `Yes,Settle | Second Confirm`,
        });
        this.onSubmit();
      },
      abort: () => {
        this.gaEventDispatcher({
          eventAction: `second confirmation`,
          eventLabel: `No, Don't | Second Confirm`,
        });
      },
    });
  };

  breakup = () => {
    const {
      isSaved,
      amount,
      instantFeePercent,
      validAmount,
      isLoadingBreakup,
      breakupShow,
      tax,
      instantFee,
    } = this.state;
    return (
      <div class="breakup">
        <div class={isSaved ? 'dropdown-1' : 'dropdown'}>
          {isSaved ? (
            <div class="currency-big-1">
              <Amount value={amount * 100} currency="INR" parentQuerySelector={'.breakup'} />
            </div>
          ) : (
            <span>
              <p class="percent">{instantFeePercent / 100}</p>
              <p class="fixed">% Additional Fee </p>
            </span>
          )}
          <AsyncBtn.Primary
            class={`drop-button ${isSaved ? 'success-breakup' : ''}`}
            disabled={isLoadingBreakup || !validAmount}
            pendingState=""
            onClick={this.fetchBreakup}
          >
            {breakupShow ? (
              <span>
                Hide Breakup <i class="i i-chevron-up" />
              </span>
            ) : (
              <span>
                Show Breakup <i class="i i-chevron-down" />
              </span>
            )}
          </AsyncBtn.Primary>
        </div>
        <div
          class={`${breakupShow ? 'dropdown-active' : 'dropdown-closed'} ${
            !isSaved ? 'dropdown-border' : ''
          }`}
        >
          <div class="p-b-5">
            <p>Total Amount</p>
            <span class="float-right currency">
              <Amount value={amount * 100} currency={'INR'} />
            </span>
          </div>
          <div class="p-b-5">
            <p>Instant Fees ({instantFeePercent / 100}%) </p>
            <i class="i i-help" />
            <Popover align="right" theme="dark" parentQuerySelector=".onmdemand-modal">
              <PopoverBody>
                <div style={{ textAlign: 'left' }}>
                  The maximum amount is calculated after the deduction of instant settlement fee and
                  taxes.
                </div>
              </PopoverBody>
            </Popover>
            <span class="float-right currency">
              {' '}
              <p>-</p>
              <Amount value={instantFee} currency="INR" />
            </span>
          </div>
          <span>
            <p>Taxes</p>
            <span class="float-right currency">
              {' '}
              <p>-</p>
              <Amount value={tax} currency="INR" />
            </span>
          </span>
        </div>
        <div class={breakupShow ? 'dropdown-active' : 'dropdown-closed'}>
          <p class="amount-to-settle">Amount to be settled</p>
          <span class="float-right currency">
            <Amount value={amount * 100 - instantFee - tax} currency={'INR'} />
          </span>
        </div>
      </div>
    );
  };

  updateFee = () => {
    const { validAmount, amount } = this.state;
    this.setState({
      isLoadingBreakup: true,
    });

    let payload = {
      amount: amount * 100,
      currency: 'INR',
    };
    if (validAmount) {
      return ajax(
        {
          url: '/settlement/ondemand/fees/dashboard',
          method: 'GET',
          data: payload,
        },
        {},
        '/merchant/api',
      )
        .then((response) => {
          this.setState({
            isLoadingBreakup: false,
            tax: response.data.items[1].amount,
            instantFeePercent: response.data.items[0].pricing_rule.percent_rate,
            taxPercent: response.data.items[1].percentage,
            instantFee: response.data.items[0].amount,
          });
          this.props.fetchCurrentBalance();
        })
        .catch((response) => {
          this.setState({
            errors: response.errors,
            validAmount: false,
          });
        });
    }
  };

  componentDidMount() {
    document.addEventListener('keydown', this.escFunction);
    this.gaEventDispatcher({
      eventAction: 'Click Settle Now',
      eventLabel: `${this.props.fromWhere} | Settle Now`,
    });
    this.updateFee();
  }

  componentWillUnmount() {
    document.removeEventListener('keydown', this.escFunction);
  }

  escFunction = (event) => {
    if (event.keyCode === 27) {
      if (this.state.isSaved) this.handleCloseModal('Close Modal Screen 2');
      else this.handleCloseModal('Close Modal Screen 1');
    }
  };

  fetchBreakup = () => {
    if (this.state.clickedConfirm) {
      this.gaEventDispatcher({
        eventAction: `Show Breakup`,
        eventLabel: `Success Screen | Show Breakup`,
      });
    }

    if (this.state.needFetch) {
      let payload = {
        amount: this.state.amount * 100,
        currency: 'INR',
      };
      if (this.state.validAmount) {
        return ajax(
          {
            url: '/settlement/ondemand/fees/dashboard',
            method: 'GET',
            data: payload,
          },
          {},
          '/merchant/api',
        )
          .then((response) => {
            this.setState({
              breakupShow: true,
              checkedBreakup: true,
              needFetch: false,
              tax: response.data.items[1].amount,
              instantFee: response.data.items[0].amount,
            });
            this.props.fetchCurrentBalance();
          })
          .catch((response) => {
            this.setState({
              breakupShow: false,
              needFetch: true,
              errors: response.errors,
            });
          });
      }
    } else {
      var st = this.state.breakupShow;
      this.setState({
        breakupShow: !st,
      });
    }
  };

  onSubmit = () => {
    let payload = {
      amount: this.state.amount * 100,
      currency: 'INR',
    };

    this.setState({
      isSaving: true,
      errors: [],
    });
    return ajax(
      {
        url: '/settlement/ondemand/dashboard',
        method: 'POST',
        data: payload,
      },
      {},
      '/merchant/api',
    )
      .then((response) => {
        this.setState({
          isSaving: false,
          isSaved: true,
        });
        this.props.fetchCurrentBalance();
      })
      .catch((response) => {
        this.setState({
          isSaving: false,
          isSaved: false,
          errors: response.errors,
        });
      });
  };

  handleChange = (e) => {
    this.setState({
      amount: e.target.value,
      needFetch: true,
      validAmount: !this.validateAmount(e.target.value),
      breakupShow: false,
      hasChangedAmount: true,
    });
    this.updateFeeDebounced();
  };

  validateAmount = (val) => {
    if (isInteger(val) && val > 0) {
      if (val <= 1) {
        this.setState({
          errors: [
            <>
              <span>Minimum Amount should be greater than </span>
              <Amount value={100} currency="INR" />
            </>,
          ],
          validAmount: false,
        });
      }
      if (val * 100 > this.props.currentBalance) {
        trackOndemand.trackAmounTooHigh(this.props.fromWhere);
        this.setState({
          errors: [
            <>
              <span>Max amount that can be settled is </span>
              <Amount value={this.props.currentBalance} currency="INR" />
            </>,
          ],
          validAmount: false,
        });
        return true;
      }
    } else {
      this.setState({ validAmount: false, errors: ['Invalid Amount'] });
      return true;
    }
  };

  amountCategory = (amount) => {
    if (amount <= 1000) return '1-1000';
    else if (amount <= 10000) return '1000-10000';
    else if (amount <= 50000) return '10000-50000';
    else if (amount <= 100000) return '50000-100000';
    else if (amount <= 200000) return '100000-200000';
    else if (amount <= 500000) return '200000-500000';
    else return '>500000';
  };

  handleCloseModal = (eventType) => {
    switch (eventType) {
      case 'Close Modal Screen 1':
        trackOndemand.trackCloseModal(this.props.fromWhere);
        break;
      case 'Close Button':
        trackOndemand.trackCloseButton(this.props.fromWhere);
        break;
      case 'Close Modal Screen 2':
        trackOndemand.trackSuccessCloseModal(this.props.fromWhere);
        break;
    }
    if (!this.state.clickedConfirm) {
      this.gaEventDispatcher({
        eventAction: `Close modal`,
        eventLabel: `${this.state.hasChangedAmount ? 'Changed amount' : 'preFilled amount'} - ${
          this.state.checkedBreakup ? 'after' : 'before'
        } show breakup| close`,
      });

      this.gaEventDispatcher({
        eventAction: `Amount`,
        eventLabel: `${this.amountCategory(this.state.amount)} - ${
          this.state.hasChangedAmount ? 'Changed amount' : 'preFilled amount'
        } -close`,
      });
    }
    if (this.state.isSaved) {
      this.props.closeModal();
    } else {
      this.setState({
        closeClicked: true,
      });
    }
  };

  successModalHeader = () => {
    return (
      <div>
        <i class="i i-done-all text-success modal-header-success" />
        Hurray!
      </div>
    );
  };

  renderPreTransaction = () => {
    const { isLoadingBreakup, validAmount, errors, isSaving, amount, instantFee, tax } = this.state;
    return (
      <div class="onmdemand-modal">
        <ModalHeader
          class="header"
          title="Instant Settlement"
          onCloseClick={() => this.handleCloseModal('Close Modal Screen 1')}
        />
        <div class="modal-body">
          <p>
            Settle to your bank account instantly 24x7, <strong>even on Holidays!&nbsp;</strong>
            Upcoming Settlements follow the existing schedule.
            <a class="btn-link" target="_blank" href="http://razorpay.com/settlement">
              {` `}Learn more
            </a>
          </p>
          <div class="overflow-box">
            <div class="InputGroup Input Input--vTop">
              <Input
                label="Amount to settle now"
                required={false}
                addonBefore={<AmountTooltip currency="INR" parentQuerySelector=".Modal" />}
                autoFocus={true}
                name="amount"
                class="Input Input--Amount"
                disabled={isSaving}
                value={amount}
                validator={this.validateAmount}
                onChange={(e) => {
                  this.handleChange(e);
                }}
              />
            </div>
            <div>
              {isLoadingBreakup && validAmount && <div class="loader" />}
              {!isLoadingBreakup && validAmount && (
                <div class="grey-border">
                  <div>
                    <p class="after-deduction"> After Deduction </p>
                    <Amount
                      parentQuerySelector=".onmdemand-modal"
                      value={amount * 100 - instantFee - tax}
                      currency="INR"
                    />
                  </div>
                </div>
              )}
              {!validAmount && <div class="error-message">{errors[0]}</div>}
              <AsyncBtn.Primary
                class="submit-btn"
                disabled={isSaving || !validAmount || isLoadingBreakup}
                pendingState="Requesting"
                onClick={this.openConfirmSettlement}
              >
                Confirm
              </AsyncBtn.Primary>
            </div>
          </div>
          {this.breakup()}
        </div>
      </div>
    );
  };

  renderPostTransaction = () => {
    return (
      <div class="onmdemand-modal">
        <ModalHeader
          title={this.successModalHeader()}
          onCloseClick={() => this.handleCloseModal('Close Modal Screen 2')}
        />
        <div class="modal-body">
          <div class="overflow-box">
            {this.breakup()}
            <div class="help-block">
              The settlement has been initiated and should be reflect on your bank account in some
              time.
              <i class="i i-info-circle" />
              <Popover align="right" theme="dark" parentQuerySelector=".onmdemand-modal">
                <PopoverBody>
                  Working hours are 9am - 6pm everyday except on Bank Holidays
                </PopoverBody>
              </Popover>
            </div>
            <Button.Primary class="close-btn" onClick={() => this.handleCloseModal('Close Button')}>
              Close
            </Button.Primary>
          </div>

          <ScheduledBanner fromWhere="Early Settlement Modal" />
        </div>
      </div>
    );
  };

  render() {
    const { showOndemandSettlementForm } = this.props;
    const { closeClicked, isSaved } = this.state;
    return (
      <div class="container-ondemand-modal">
        <React.Fragment>
          {!closeClicked ? (
            isSaved ? (
              this.renderPostTransaction()
            ) : (
              this.renderPreTransaction()
            )
          ) : (
            <ModalCloseReasons
              showOndemandSettlementForm={showOndemandSettlementForm}
              closeOrigin="OnDemand"
            />
          )}
        </React.Fragment>
      </div>
    );
  }
}
