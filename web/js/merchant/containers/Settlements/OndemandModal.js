import React, { Component } from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'rzp/ui/ModalHeader';
import { closeModal } from 'rzp/modules/modals';
import Button, { AsyncBtn } from 'component/Button';
import { isInteger } from 'rzp/utils/validators';
import ajax from 'merchant/utils/ajax';
import { trackOndemand } from './ga';
import { fetchCurrentBalance } from 'merchant/modules/home';
import Input from 'component/Input';
import Alert from 'rzp/ui/Forms/Alert';
import { AmountTooltip } from 'rzp/ui/Amount';
import Amount from 'rzp/ui/Amount';
import debounce from 'rzp/utils/debounce';
import Popover, { PopoverBody } from 'rzp/ui/Popover';
import { CLOSEOPTIONS } from './CloseReasons';

@connect(state => ({ user: state.session.user }), {
  closeModal,
  fetchCurrentBalance,
})
export default class OndemandModal extends Component {
  constructor(props) {
    super(props);

    this.state = {
      isSaving: false,
      isSaved: false,
      amount: 0,
      validAmount: true,
      closeClicked: false,
      errors: [],
      breakupShow: false,
      isLoadingBreakup: false,
      hasChangedAmount: false,
      closeReason: '',
      needFetch: true,
      taxPercent: 0,
      instantFeePercent: 0,
      tax: 0,
      instantFee: 0,
    };
    if (props.currentBalance) {
      this.state.amount = parseInt(props.currentBalance / 100);
    }
    this.validateAmount = this.validateAmount.bind(this);
    this.onSubmit = this.onSubmit.bind(this);
    this.updateFee = this.updateFee.bind(this);
    this.handleChange = this.handleChange.bind(this);
    this.updateFeeDebounced = debounce(this.updateFee, 300);
    this.handleCloseModal = this.handleCloseModal.bind(this);
    this.openSupport = this.openSupport.bind(this);
    this.renderClose = this.renderClose.bind(this);
    this.handleReasonChange = this.handleReasonChange.bind(this);
    this.submitCloseReason = this.submitCloseReason.bind(this);
  }

  handleReasonChange(e) {
    this.setState({ closeReason: e.target.value });
  }

  submitCloseReason() {
    const analyticsPayload = {
      eventCategory: 'Dashboard - Instant Settlement Modal',
      eventAction: `Close - Instant Settlement Modal`,
      eventLabel: `Reason: ${this.state.closeReason}`,
    };

    window.rzpAnalytics(analyticsPayload);
    this.props.closeModal();
  }

  renderClose() {
    return (
      <div className="onmdemand-close-modal">
        <ModalHeader
          class="header"
          title="Reason"
          onCloseClick={() => {
            this.props.closeModal();
          }}
        />
        <div className="modal-body">
          {CLOSEOPTIONS.map(choice => {
            return (
              <div
                key={'parent-choice-' + choice.value}
                className="es-close-choices"
              >
                <label key={'lab-' + choice.value}>
                  <input
                    type="radio"
                    name="close-reason"
                    value={choice.value}
                    key={'inp-choice' + choice.value}
                    onChange={this.handleReasonChange}
                  />
                  {choice.label}
                </label>
              </div>
            );
          })}
        </div>
        <Button.Primary
          onClick={this.submitCloseReason}
          disabled={!this.state.closeReason}
          className="pull-right confirm-close"
        >
          Confirm & Close
        </Button.Primary>
      </div>
    );
  }

  updateFee() {
    if (this.state.hasChangedAmount) {
      const analyticsPayload = {
        eventCategory: 'Dashboard - Instant Settlement Modal',
        eventAction: `Input - Amout - ${this.state.amount * 100}`,
        Currentbalance: `Balance - ${this.props.currentBalance}`,
      };
      window.rzpAnalytics(analyticsPayload);
    }
    this.setState({
      errors: [],
      isLoadingBreakup: true,
    });

    let payload = {
      amount: this.state.amount * 100,
      currency: 'INR',
    };
    return ajax(
      {
        url: '/merchant/payout/demand/fees',
        method: 'GET',
        data: payload,
      },
      {},
      '/merchant/api'
    )
      .then(response => {
        this.setState({
          isLoadingBreakup: false,
          tax: response.data.items[1].amount,
          instantFeePercent: response.data.items[0].pricing_rule.percent_rate,
          taxPercent: response.data.items[1].percentage,
          instantFee: response.data.items[0].amount,
        });
        this.props.fetchCurrentBalance();
      })
      .catch(response => {
        this.setState({
          errors: response.errors,
        });
      });
  }

  componentDidMount() {
    this.updateFee();
  }

  openSupport() {
    if (window.rzpTicketSystem) {
      const rzpTicketSystem = window.rzpTicketSystem;
      rzpTicketSystem.setPrefill('#request', [
        'merchant',
        'international-early-settlement',
      ]);
      rzpTicketSystem.openModal('#ticket');
      setTimeout(() => {
        rzpTicketSystem.modal.next();
      }, 0);
    }
  }

  fetchBreakup = () => {
    const analyticsPayload = {
      eventCategory: 'Dashboard - Instant Settlement Modal',
      eventAction: `Check - Breakup -amount - ${this.state.amount * 100}`,
    };

    window.rzpAnalytics(analyticsPayload);

    if (this.state.needFetch) {
      let payload = {
        amount: this.state.amount * 100,
        currency: 'INR',
      };
      return ajax(
        {
          url: '/merchant/payout/demand/fees',
          method: 'GET',
          data: payload,
        },
        {},
        '/merchant/api'
      )
        .then(response => {
          this.setState({
            breakupShow: true,
            needFetch: false,
            tax: response.data.items[1].amount,
            instantFee: response.data.items[0].amount,
          });
          this.props.fetchCurrentBalance();
        })
        .catch(response => {
          this.setState({
            breakupShow: false,
            needFetch: true,
            errors: response.errors,
          });
        });
    } else {
      var st = this.state.breakupShow;
      this.setState({
        breakupShow: !st,
      });
    }
  };

  onSubmit() {
    const analyticsPayload = {
      eventCategory: 'Dashboard - Instant Settlement Modal',
      eventAction: `Confirm - Click`,
    };

    window.rzpAnalytics(analyticsPayload);

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
        url: '/merchant/payout/demand',
        method: 'POST',
        data: payload,
      },
      {},
      '/merchant/api'
    )
      .then(response => {
        this.setState({
          isSaving: false,
          isSaved: true,
        });
        this.props.fetchCurrentBalance();
      })
      .catch(response => {
        this.setState({
          isSaving: false,
          isSaved: false,
          errors: response.errors,
        });
      });
  }

  handleChange(e) {
    //this.resetInterval();
    this.setState({
      amount: e.target.value,
      needFetch: true,
      validAmount: !this.validateAmount(e.target.value),
      breakupShow: false,
      hasChangedAmount: true,
    });
    this.updateFeeDebounced();
  }

  validateAmount(val) {
    if (isInteger(val) && val > 0) {
      if (val * 100 > this.props.currentBalance) {
        trackOndemand.trackAmounTooHigh(this.props.fromWhere);
        return (
          <>
            <span>Max amount that can be settled is </span>
            <Amount value={this.props.currentBalance} currency={'INR'} />
          </>
        );
      }
    } else {
      return 'Invalid Amount';
    }
  }

  handleCloseModal(eventType) {
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
    if (this.state.hasChangedAmount) {
      const analyticsPayload = {
        eventCategory: 'Dashboard - Instant Settlement Modal',
        eventAction: `Close -After -InputAmount`,
      };

      window.rzpAnalytics(analyticsPayload);
    } else {
      const analyticsPayload = {
        eventCategory: 'Dashboard - Instant Settlement Modal',
        eventAction: `Close -Before -InputAmount`,
      };

      window.rzpAnalytics(analyticsPayload);
    }
    if (this.state.isSaved) {
      this.props.closeModal();
    } else {
      this.setState({
        closeClicked: true,
      });
    }
  }

  render() {
    return (
      <div class="container-ondemand-modal">
        <React.Fragment>
          {!this.state.closeClicked ? (
            this.state.isSaved ? (
              <div class="onmdemand-modal">
                <ModalHeader
                  title="Early Settlement Requested"
                  onCloseClick={() =>
                    this.handleCloseModal('Close Modal Screen 2')
                  }
                />
                <div class="modal-body">
                  <div class="help-block">
                    The requested balance will be settled in the next 3 working
                    hours
                    <i className="i i-info-circle" />
                    <Popover
                      align="right"
                      theme="dark"
                      parentQuerySelector=".onmdemand-modal"
                    >
                      <PopoverBody>
                        Working hours are 9am - 6pm everyday except on Bank
                        Holidays
                      </PopoverBody>
                    </Popover>
                  </div>
                  <Button.Primary
                    class="close-btn"
                    onClick={() => this.handleCloseModal('Close Button')}
                  >
                    Close
                  </Button.Primary>
                </div>
              </div>
            ) : (
              <div class="onmdemand-modal">
                <ModalHeader
                  class="header"
                  title="Instant Settlement"
                  onCloseClick={() =>
                    this.handleCloseModal('Close Modal Screen 1')
                  }
                />
                <div class="modal-body">
                  <p>Settle to your bank account instantly.</p>
                  <br />
                  <p>
                    Upcoming Settlements follow the existing schedule.
                    <a
                      class="btn-link"
                      target="_blank"
                      href="http://razorpay.com/settlement"
                    >
                      {` `}Learn more
                    </a>
                  </p>
                  {this.state.errors && (
                    <div>
                      {this.state.errors.map((item, key) => {
                        return <Alert key={key} type="error" message={item} />;
                      })}
                    </div>
                  )}
                  <div class="overflow-box">
                    <div class="InputGroup Input Input--vTop">
                      <Input
                        label="Amount to settle now"
                        required={false}
                        addonBefore={
                          <AmountTooltip
                            currency={'INR'}
                            parentQuerySelector=".Modal"
                          />
                        }
                        autoFocus={true}
                        name="amount"
                        class="Input Input--Amount"
                        disabled={this.state.isSaving}
                        value={this.state.amount}
                        validator={this.validateAmount}
                        onChange={e => {
                          this.handleChange(e);
                        }}
                      />
                    </div>
                    <div>
                      <span>
                        <div class="grey-border">
                          <span>
                            {this.state.isLoadingBreakup === false ? (
                              this.state.validAmount ? (
                                <div>
                                  <p> After Deduction : </p>
                                  <Amount
                                    parentQuerySelector=".onmdemand-modal"
                                    value={
                                      this.state.amount * 100 -
                                      this.state.instantFee -
                                      this.state.tax
                                    }
                                    currency={'INR'}
                                  />
                                </div>
                              ) : (
                                <React.Fragment />
                              )
                            ) : (
                              <div class="loader" />
                            )}
                          </span>
                        </div>
                      </span>
                    </div>
                  </div>
                  <div class="breakup">
                    <div class="dropdown">
                      <span>
                        <p class="percent">
                          {this.state.instantFeePercent / 100}
                        </p>
                        <p class="fixed">% Additional Fee </p>
                      </span>
                      <AsyncBtn.Primary
                        class="drop-button"
                        disabled={
                          this.state.isLoadingBreakup || !this.state.validAmount
                        }
                        pendingState=""
                        onClick={this.fetchBreakup}
                      >
                        {this.state.breakupShow ? (
                          <span>
                            Close Breakup <i class="i i-chevron-up" />
                          </span>
                        ) : (
                          <span>
                            Show Breakup <i class="i i-chevron-down" />
                          </span>
                        )}
                      </AsyncBtn.Primary>
                    </div>
                    <div
                      className={
                        this.state.breakupShow
                          ? 'dropdown-active'
                          : 'dropdown-closed'
                      }
                    >
                      <p>Totoal Amount</p>
                      <span class="float-right currency">
                        <Amount
                          value={this.state.amount * 100}
                          currency={'INR'}
                        />
                      </span>
                      <br />
                      <span>
                        <p>
                          Instant Fees ({this.state.instantFeePercent / 100}%){' '}
                        </p>
                        <i class="i i-help" />
                        <Popover
                          align="right"
                          theme="dark"
                          parentQuerySelector=".onmdemand-modal"
                        >
                          <PopoverBody>
                            <div style={{ textAlign: 'left' }}>
                              The maximum amount is calculated after the
                              deduction of instant settlement fee and taxes.
                            </div>
                          </PopoverBody>
                        </Popover>
                      </span>
                      <span class="float-right currency">
                        {' '}
                        <p>-</p>
                        <Amount
                          value={this.state.instantFee}
                          currency={'INR'}
                        />
                      </span>
                      <br />
                      <p>Taxes</p>
                      <span class="float-right currency">
                        {' '}
                        <p>-</p>
                        <Amount value={this.state.tax} currency={'INR'} />
                      </span>
                    </div>
                    <div
                      className={
                        this.state.breakupShow
                          ? 'dropdown-active'
                          : 'dropdown-closed'
                      }
                    >
                      <p>Amount to be settled</p>
                      <span class="float-right currency-big">
                        <Amount
                          value={
                            this.state.amount * 100 -
                            this.state.instantFee -
                            this.state.tax
                          }
                          currency={'INR'}
                          parentQuerySelector=".Modal"
                        />
                      </span>
                    </div>
                  </div>
                  <div class="border">
                    <p>
                      Early settlement applies to domestic setelments only. For
                      International, please{' '}
                    </p>
                    <a class="btn-link" onClick={this.openSupport}>
                      Contact support
                    </a>
                  </div>
                  <AsyncBtn.Primary
                    class="submit-btn"
                    disabled={
                      this.state.isSaving ||
                      !this.state.validAmount ||
                      this.state.isLoadingBreakup
                    }
                    pendingState="Requesting"
                    onClick={this.onSubmit}
                  >
                    Confirm
                  </AsyncBtn.Primary>
                </div>
              </div>
            )
          ) : (
            this.renderClose()
          )}
        </React.Fragment>
      </div>
    );
  }
}
