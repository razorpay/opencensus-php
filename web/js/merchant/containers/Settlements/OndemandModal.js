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
      errors: [],
      breakupShow: false,
      isLoadingBreakup: false,
      hasChangedAmount: false,
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
  }

  // resetInterval() {
  //   console.log(hiaghaho);
  //   clearInterval(timer);

  //   timer = setInterval(function() {
  //     this.fetchFee;
  //     console.log('restarted interval');
  //     test1();
  //   }, 400);
  // }

  updateFee() {
    console.log(this.state.errors);
    if (this.state.hasChangedAmount) {
      const analyticsPayload = {
        eventCategory: 'Dashboard - Instant Settlement Modal',
        eventAction: `Input - Amout - ${this.state.amount * 100}`,
        Currentbalance: `Balance - ${this.props.currentBalance}`,
      };

      console.log(analyticsPayload);
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
        //TODDO add analytics
        console.log(response.data.items[0].pricing_rule.percent_rate);
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

  dropdown = () => {
    const analyticsPayload = {
      eventCategory: 'Dashboard - Instant Settlement Modal',
      eventAction: `Check - Breakup -amount - ${this.state.amount * 100}`,
    };

    console.log(analyticsPayload);
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

    console.log(analyticsPayload);
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
        return 'Please ensure that the entered amount is not more than the balance';
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

      console.log(analyticsPayload);
      window.rzpAnalytics(analyticsPayload);
    } else {
      const analyticsPayload = {
        eventCategory: 'Dashboard - Instant Settlement Modal',
        eventAction: `Close -Before -InputAmount`,
      };

      console.log(analyticsPayload);
      window.rzpAnalytics(analyticsPayload);
    }
    this.props.closeModal();
  }

  render() {
    return (
      <div class="container-ondemand-modal">
        <React.Fragment>
          {this.state.isSaved ? (
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
                    Learn more
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
                            <div>
                              <p> After Deduction </p>
                              <Amount
                                value={
                                  this.state.amount * 100 -
                                  this.state.instantFee -
                                  this.state.tax
                                }
                                currency={'INR'}
                              />{' '}
                            </div>
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
                      onClick={this.dropdown}
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
                            The maximum amount is calculated after the deduction
                            of instant settlement fee and taxes.
                          </div>
                        </PopoverBody>
                      </Popover>
                    </span>
                    <span class="float-right currency">
                      {' '}
                      <p>-</p>
                      <Amount value={this.state.instantFee} currency={'INR'} />
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
                      />
                    </span>
                  </div>
                </div>
                <div class="border">
                  <p>
                    Early settlement applies to domestic setelments only . For
                    International, please{' '}
                  </p>
                  <a
                    class="btn-link"
                    target="_blank"
                    href="https://razorpay.com/support/#request"
                  >
                    Contact support
                  </a>
                </div>
                <AsyncBtn.Primary
                  class="submit-btn"
                  disabled={this.state.isSaving || !this.state.validAmount}
                  pendingState="Requesting"
                  onClick={this.onSubmit}
                >
                  Confirm
                </AsyncBtn.Primary>
              </div>
            </div>
          )}
        </React.Fragment>
      </div>
    );
  }
}
