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
import ModalCloseReasons from 'merchant/views/Settlements/components/Modals/ModalCloseReasons';
import ScheduledBanner from 'merchant/views/Settlements/components/ScheduledBanner';

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

    if (props.currentBalance) {
      this.state.amount = parseInt(props.currentBalance / 100);
    }
    this.updateFeeDebounced = debounce(this.updateFee, 300);
  }

  static contextTypes = {
    confirm: PropTypes.func,
  };

  renderConfirmation = () => {
    return (
      <div>
        The settlement amount is:{` `}
        <Amount
          value={this.state.amount * 100}
          currency={'INR'}
          parentQuerySelector={'.modal-body'}
        />
      </div>
    );
  };

  gaEventDispatcher = eventObject => {
    eventObject['eventCategory'] = 'Dashboard - Early Settlement';
    window.rzpAnalytics(eventObject);
  };

  openConfirmSettlement = () => {
    this.setState({
      clickedConfirm: true,
    });

    this.gaEventDispatcher({
      eventAction: `Confirm`,
      eventLabel: `${
        this.state.hasChangedAmount ? 'Changed amount' : 'preFilled amount'
      } - ${
        this.state.checkedBreakup ? 'after' : 'before'
      } show breakup| close`,
    });

    this.gaEventDispatcher({
      eventAction: `Amount`,
      eventLabel: `${this.amountCategory(this.state.amount)} - ${
        this.state.hasChangedAmount ? 'Changed amount' : 'preFilled amount'
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
    return (
      <div class="breakup">
        <div className={this.state.isSaved ? 'dropdown-1' : 'dropdown'}>
          {this.state.isSaved ? (
            <div class="currency-big-1">
              <Amount
                value={this.state.amount * 100}
                currency={'INR'}
                parentQuerySelector={'.breakup'}
              />
            </div>
          ) : (
            <span>
              <p class="percent">{this.state.instantFeePercent / 100}</p>
              <p class="fixed">% Additional Fee </p>
            </span>
          )}
          <AsyncBtn.Primary
            className={`drop-button ${
              this.state.isSaved ? 'success-breakup' : ''
            }`}
            disabled={this.state.isLoadingBreakup || !this.state.validAmount}
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
            this.state.breakupShow ? 'dropdown-active' : 'dropdown-closed'
          }
        >
          <p>Total Amount</p>
          <span class="float-right currency">
            <Amount value={this.state.amount * 100} currency={'INR'} />
          </span>
          <br />
          <span>
            <p>Instant Fees ({this.state.instantFeePercent / 100}%) </p>
            <i class="i i-help" />
            <Popover
              align="right"
              theme="dark"
              parentQuerySelector=".onmdemand-modal"
            >
              <PopoverBody>
                <div style={{ textAlign: 'left' }}>
                  The maximum amount is calculated after the deduction of
                  instant settlement fee and taxes.
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
            this.state.breakupShow ? 'dropdown-active' : 'dropdown-closed'
          }
        >
          <p>Amount to be settled</p>
          <span class="float-right currency-big">
            <Amount
              value={
                this.state.amount * 100 - this.state.instantFee - this.state.tax
              }
              currency={'INR'}
            />
          </span>
        </div>
      </div>
    );
  };

  updateFee = () => {
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

  escFunction = event => {
    if (event.keyCode === 27) {
      if (this.state.isSaved) this.handleCloseModal('Close Modal Screen 2');
      else this.handleCloseModal('Close Modal Screen 1');
    }
  };

  openSupport = () => {
    this.gaEventDispatcher({
      eventAction: 'support',
      eventLabel: `Clicks | Support`,
    });

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
            checkedBreakup: true,
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
  };

  handleChange = e => {
    this.setState({
      amount: e.target.value,
      needFetch: true,
      validAmount: !this.validateAmount(e.target.value),
      breakupShow: false,
      hasChangedAmount: true,
    });
    this.updateFeeDebounced();
  };

  validateAmount = val => {
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
  };

  amountCategory = amount => {
    if (amount <= 1000) return '1-1000';
    else if (amount <= 10000) return '1000-10000';
    else if (amount <= 50000) return '10000-50000';
    else if (amount <= 100000) return '50000-100000';
    else if (amount <= 200000) return '100000-200000';
    else if (amount <= 500000) return '200000-500000';
    else return '>500000';
  };

  handleCloseModal = eventType => {
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
        eventLabel: `${
          this.state.hasChangedAmount ? 'Changed amount' : 'preFilled amount'
        } - ${
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
        <i className="i i-done-all text-success modal-header-success" />
        Hurray
      </div>
    );
  };

  renderPreTransaction = () => {
    return (
      <div className="onmdemand-modal">
        <ModalHeader
          class="header"
          title="Instant Settlement"
          onCloseClick={() => this.handleCloseModal('Close Modal Screen 1')}
        />
        <div className="modal-body">
          <p>Settle to your bank account instantly.</p>
          <br />
          <p>
            Upcoming Settlements follow the existing schedule.
            <a
              className="btn-link"
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
          <div className="overflow-box">
            <div className="InputGroup Input Input--vTop">
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
                <div className="grey-border">
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
                      <div className="loader" />
                    )}
                  </span>
                </div>
              </span>
            </div>
          </div>
          {this.breakup()}
          <div className="border">
            <p>
              Early settlement applies to domestic settlements only. For
              International, please{' '}
            </p>
            <a className="btn-link" onClick={this.openSupport}>
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
            onClick={this.openConfirmSettlement}
          >
            Confirm
          </AsyncBtn.Primary>
        </div>
      </div>
    );
  };

  renderPostTransaction = () => {
    return (
      <div className="onmdemand-modal">
        <ModalHeader
          title={this.successModalHeader()}
          onCloseClick={() => this.handleCloseModal('Close Modal Screen 2')}
        />
        <div className="modal-body">
          <div className="overflow-box">
            {this.breakup()}
            <div className="help-block">
              Your settlement has been initiated. Amounts up to 2 Lacs will be
              settled instantly. All other amounts to be settled within 3
              working hours{` `}
              <i className="i i-info-circle" />
              <Popover
                align="right"
                theme="dark"
                parentQuerySelector=".onmdemand-modal"
              >
                <PopoverBody>
                  Working hours are 9am - 6pm everyday except on Bank Holidays
                </PopoverBody>
              </Popover>
            </div>
          </div>

          <ScheduledBanner fromWhere={'Early Settlement Modal'} />
          <Button.Primary
            class="close-btn"
            onClick={() => this.handleCloseModal('Close Button')}
          >
            Close
          </Button.Primary>
        </div>
      </div>
    );
  };

  render() {
    return (
      <div class="container-ondemand-modal">
        <React.Fragment>
          {!this.state.closeClicked ? (
            this.state.isSaved ? (
              this.renderPostTransaction()
            ) : (
              this.renderPreTransaction()
            )
          ) : (
            <ModalCloseReasons closeOrigin="OnDemand" />
          )}
        </React.Fragment>
      </div>
    );
  }
}
