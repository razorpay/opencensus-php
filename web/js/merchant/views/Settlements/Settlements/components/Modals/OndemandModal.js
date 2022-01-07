import React, { Component } from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import { closeModal as fnCloseModal } from 'merchant_common/reducers/modals';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { isInteger } from 'common/utils/validators';
import ajax from 'merchant/utils/ajax';
import {
  trackOndemand,
  EVENT_CATEGORY_DASHBOARD_EARLY_SETTLEMENT,
  trackEsSettlementAction,
  trackModalOpen,
  trackEsShowBreakup,
  trackEsModalCloseCTA,
  trackEsModalCloseIcon,
  trackEsModalCloseIconChurn,
  trackAnimatedSettleBtnClick,
  trackAnimatedSettleBtnClickType,
  trackEsAmountError,
  trackEsConfirm,
  trackEsAmountUpdated,
  trackEsInfoHover,
} from '../../ga';
import { fetchCurrentBalance, fetchOndemandRestrictions } from 'merchant/reducers/home';
import Input from 'common/new-ui/Input';
import Amount, { AmountTooltip } from 'common/ui/Amount';
import debounce from 'common/utils/debounce';
import PropTypes from 'prop-types';
import ModalCloseReasons from 'merchant/views/Settlements/Settlements/components/Modals/ModalCloseReasons';
import SettlementsUpsellBanner from 'merchant/views/Settlements/Settlements/components/SettlementsUpsellBanner';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import { setItem, getItem } from 'common/utils/localStorage';
import { onDemandModalTrackEvents } from '../../../trackEvents';
import { bindActionCreators } from 'redux';

class OndemandModal extends Component {
  constructor(props) {
    super(props);

    this.state = {
      isSaving: false,
      isSaved: false,
      amount: props.settlableAmount
        ? parseInt(props.settlableAmount / 100, 10)
        : props.currentBalance
        ? props.currentBalance > 2000000000
          ? 20000000
          : parseInt(props.currentBalance / 100, 10)
        : 0,
      validAmount: true,
      closeClicked: false,
      errors: [],
      breakupShow: false,
      checkedBreakup: false,
      isLoadingBreakup: false,
      hasChangedAmount: false,
      clickedConfirm: false,
      needFetch: true,
      instantFeePercent: 0,
      tax: 0,
      instantFee: 0,
      prefilledAmountUpdated: false,
    };

    this.inputTooltipRef = null;
    this.setInputTooltipRef = (el) => {
      this.inputTooltipRef = el;
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
          <Amount value={amount * 100} currency="INR" parentQuerySelector=".modal-body" />
        </span>
      </div>
    );
  };

  gaEventDispatcher = (eventObject) => {
    eventObject.eventCategory = this.props.eventCategory;
    window.rzpAnalytics(eventObject);
  };

  openConfirmSettlement = () => {
    const { hasChangedAmount, checkedBreakup, amount } = this.state;
    const { user, fromWhere } = this.props;
    this.setState({
      clickedConfirm: true,
    });
    onDemandModalTrackEvents.trackSettleNowFirstConfirm(fromWhere);
    trackEsConfirm(user.current);
    if (hasChangedAmount) trackEsAmountUpdated();

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
        trackEsSettlementAction(user.current, fromWhere, true);
        onDemandModalTrackEvents.trackSettleNowSecondConfirm(fromWhere);
        this.onSubmit();
      },
      abort: () => {
        this.gaEventDispatcher({
          eventAction: `second confirmation`,
          eventLabel: `No, Don't | Second Confirm`,
        });
        trackEsSettlementAction(user.current, fromWhere);
        onDemandModalTrackEvents.trackSettleNowCancelConfirm(fromWhere);
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
              <Amount value={amount * 100} currency="INR" parentQuerySelector=".breakup" />
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
              <Amount value={amount * 100} currency="INR" parentQuerySelector=".onmdemand-modal" />
            </span>
          </div>
          <div class="p-b-5">
            <p>Instant Fees ({instantFeePercent / 100}%) </p>
            <span class="float-right currency">
              {' '}
              <p>-</p>
              <Amount value={instantFee} currency="INR" parentQuerySelector=".onmdemand-modal" />
            </span>
          </div>
          <span>
            <p>Taxes</p>
            <span class="float-right currency">
              {' '}
              <p>-</p>
              <Amount value={tax} currency="INR" parentQuerySelector=".onmdemand-modal" />
            </span>
          </span>
        </div>
        <div class={breakupShow ? 'dropdown-active' : 'dropdown-closed'}>
          <p class="amount-to-settle">Amount to be settled</p>
          <span class="float-right currency">
            <Amount
              value={amount * 100 - instantFee - tax}
              currency="INR"
              parentQuerySelector=".onmdemand-modal"
            />
          </span>
        </div>
      </div>
    );
  };

  // eslint-disable-next-line consistent-return
  updateFee = () => {
    const { validAmount, amount } = this.state;
    this.setState({
      isLoadingBreakup: true,
    });

    const payload = {
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
            instantFee: response.data.items[0].amount,
          });
          this.props.fetchCurrentBalance();
          this.props.fetchOndemandRestrictions();
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
    const { user, fromWhere, animatedSettlemnetBtn } = this.props;
    document.addEventListener('keydown', this.escFunction);
    this.gaEventDispatcher({
      eventAction: 'Click Settle Now',
      eventLabel: `${fromWhere} | Settle Now`,
    });

    if (animatedSettlemnetBtn) {
      trackAnimatedSettleBtnClick(user.current, fromWhere);
      trackAnimatedSettleBtnClickType(
        user.current,
        fromWhere,
        user.isFeatureEnabled('es_on_demand_restricted'),
      );
    }

    trackModalOpen(user.current);
    this.updateFee();
    this.showInputTooltip();
  }

  componentWillUnmount() {
    document.removeEventListener('keydown', this.escFunction);
  }

  handleMouseOverTooltip = () => {
    if (this.props.user.isFeatureEnabled('es_on_demand_restricted')) {
      trackEsInfoHover();
      onDemandModalTrackEvents.trackSettleNowInfoHover(this.props.fromWhere);
    }
  };

  showInputTooltip = () => {
    if (!getItem('es-ondemand-input-tooltip')) {
      this.inputTooltipRef.classList.add('input-tooltip-custom');
      setItem('es-ondemand-input-tooltip', true);
    }
  };

  hideInputTooltip = () => {
    this.inputTooltipRef.classList.remove('input-tooltip-custom');
  };

  escFunction = (event) => {
    if (event.keyCode === 27) {
      if (this.state.isSaved) this.handleCloseModal('Close Modal Screen 2');
      else this.handleCloseModal('Close Modal Screen 1');
    }
  };

  // eslint-disable-next-line consistent-return
  fetchBreakup = () => {
    const { clickedConfirm } = this.state;
    trackEsShowBreakup(!clickedConfirm);
    onDemandModalTrackEvents.trackSettleNowShowBreakup(this.props.fromWhere, clickedConfirm);
    if (this.state.clickedConfirm) {
      this.gaEventDispatcher({
        eventAction: `Show Breakup`,
        eventLabel: `Success Screen | Show Breakup`,
      });
    }

    if (this.state.needFetch) {
      const payload = {
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
            this.props.fetchOndemandRestrictions();
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
      const st = this.state.breakupShow;
      this.setState({
        breakupShow: !st,
      });
    }
  };

  onSubmit = () => {
    const payload = {
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
      .then(() => {
        this.setState({
          isSaving: false,
          isSaved: true,
        });
        this.props.fetchCurrentBalance();
        this.props.fetchOndemandRestrictions();

        if (this.props.checkIfFirstEverSettlement)
          this.props.checkIfFirstEverSettlement('settlementDone');
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
      prefilledAmountUpdated: true,
    });
    if (!this.state.prefilledAmountUpdated) trackEsAmountUpdated();
    this.updateFeeDebounced();
  };

  // eslint-disable-next-line consistent-return
  validateAmount = (val) => {
    if (isInteger(val) && val > 0) {
      if (val < 100) {
        this.setState({
          errors: [
            <>
              <span>Minimum Amount should be </span>
              <Amount value={10000} parentQuerySelector=".onmdemand-modal" currency="INR" />
            </>,
          ],
          validAmount: false,
        });
      }
      if (this.props.settlableAmount > 0 && val * 100 > this.props.settlableAmount) {
        trackOndemand.trackAmounTooHigh(this.props.fromWhere);
        trackEsAmountError();
        this.setState({
          errors: [
            <>
              <span>Max amount that can be settled is </span>
              <Amount
                parentQuerySelector=".onmdemand-modal"
                value={this.props.settlableAmount}
                currency="INR"
              />
              <span className="limit-warning">
                We shall increase and remove the limit based on the usage.
              </span>
            </>,
          ],
          validAmount: false,
        });
        return true;
      }
      if (val * 100 > this.props.currentBalance) {
        trackOndemand.trackAmounTooHigh(this.props.fromWhere);
        trackEsAmountError();
        this.setState({
          errors: [
            <>
              <span>Max amount that can be settled is </span>
              <Amount
                parentQuerySelector=".onmdemand-modal"
                value={this.props.currentBalance}
                currency="INR"
              />
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
    // eslint-disable-next-line default-case
    switch (eventType) {
      case 'Close Modal Screen 1':
        trackEsModalCloseIcon();
        trackOndemand.trackCloseModal(this.props.fromWhere);
        break;
      case 'Close Button':
        trackEsModalCloseCTA();
        trackOndemand.trackCloseButton(this.props.fromWhere);
        break;
      case 'Close Modal Screen 2':
        trackEsModalCloseIconChurn(this.props.user.current);
        trackOndemand.trackSuccessCloseModal(this.props.fromWhere);
        break;
    }

    if (!this.state.clickedConfirm) {
      if (this.state.hasChangedAmount) trackEsAmountUpdated();
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
    const { settlableAmount, currentBalance, fromWhere } = this.props;
    return (
      <div class="onmdemand-modal">
        <ModalHeader
          class="header"
          title="Instant Settlement"
          onCloseClick={() => {
            this.handleCloseModal('Close Modal Screen 1');
            onDemandModalTrackEvents.trackSettleNowCloseClick(this.props.fromWhere);
          }}
        />
        <div class="modal-body">
          <p>
            Settle to your bank account instantly 24x7, <strong>even on Holidays!&nbsp;</strong>
            Upcoming Settlements follow the existing schedule.
            <a
              class="btn-link"
              target="_blank"
              rel="noopener noreferrer"
              href="http://razorpay.com/settlement"
            >
              {` `}Learn more
            </a>
          </p>
          <div class="overflow-box">
            <div class="InputGroup Input Input--vTop">
              <Input
                label="Amount to settle now"
                required={false}
                addonBefore={<AmountTooltip currency="INR" parentQuerySelector=".Modal" />}
                autoFocus={false}
                onFocus={this.hideInputTooltip}
                name="amount"
                class="Input Input--Amount"
                disabled={isSaving}
                value={amount}
                validator={this.validateAmount}
                onChange={(e) => {
                  this.handleChange(e);
                  onDemandModalTrackEvents.trackSettleAmountUpdated(fromWhere);
                }}
              />
              <span
                onMouseEnter={this.handleMouseOverTooltip}
                data-tooltip={`To help you get started, you can immediately settle up to ${getFormattedAmountNew(
                  settlableAmount || currentBalance,
                  true,
                  'INR',
                )}. Keep using Razorpay to increase and remove your limit`}
                data-tooltip-position="top"
                className="input-tooltip"
                ref={this.setInputTooltipRef}
              >
                <i className="i i-info-outline" />
              </span>
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
    const { closeModal } = this.props;
    const { hideCloseButton } = this.state;

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
              The settlement has been initiated and should soon reflect in your bank account.
            </div>
            {hideCloseButton ? null : (
              <Button.Primary
                class="close-btn"
                onClick={() => this.handleCloseModal('Close Button')}
              >
                Close
              </Button.Primary>
            )}
          </div>

          <SettlementsUpsellBanner
            closeModal={closeModal}
            hideCloseButton={() => this.setState({ hideCloseButton: true })}
          />
        </div>
      </div>
    );
  };

  render() {
    const { goBackToInitialModalView } = this.props;
    const { closeClicked, isSaved } = this.state;
    return (
      <div class="container-ondemand-modal">
        {!closeClicked ? (
          isSaved ? (
            this.renderPostTransaction()
          ) : (
            this.renderPreTransaction()
          )
        ) : (
          <ModalCloseReasons
            goBackToInitialModalView={goBackToInitialModalView}
            closeOrigin="OnDemand"
            eventCategory={this.props.eventCategory}
            fromWhere={this.props.fromWhere}
          />
        )}
      </div>
    );
  }
}

OndemandModal.defaultProps = {
  eventCategory: EVENT_CATEGORY_DASHBOARD_EARLY_SETTLEMENT,
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      closeModal: fnCloseModal,
      fetchCurrentBalance,
      fetchOndemandRestrictions,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(OndemandModal);
