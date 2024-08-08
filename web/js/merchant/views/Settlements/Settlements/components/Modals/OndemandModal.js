import './OndemandModal.styl';
import React, { Component } from 'react';
import {
  Box,
  PlusSquareIcon,
  Text,
  Amount as BladeAmount,
  Spinner,
  Link,
} from '@razorpay/blade/components';
import axios from 'axios';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import Button, { AsyncBtn } from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import Amount, { AmountTooltip } from 'common/ui/Amount';
import ModalHeader from 'common/ui/ModalHeader';
import Popover, { PopoverBody } from 'common/ui/Popover';
import debounce from 'common/utils/debounce';
import { setItem, getItem } from 'common/utils/localStorage';
import { classList } from 'common/utils/rzp-utils';
import { isInteger } from 'common/utils/validators';
import { fetchCurrentBalance, fetchOndemandRestrictions } from 'merchant/reducers/home';
import ajax from 'merchant/utils/ajax';
import { withODSConfig } from 'merchant/views/Settlements/InstantSettlements/query-hooks/useODSConfig';
import { getHasMerchantLevelLimit } from 'merchant/views/Settlements/InstantSettlements/utils/common';
import ModalCloseReasons from 'merchant/views/Settlements/Settlements/components/Modals/ModalCloseReasons';
import SettleToLinkedAccounts from 'merchant/views/Settlements/Settlements/components/SettleToLinkedAccounts';
import EnableScheduledBanner from 'merchant/views/Settlements/Settlements/components/SettleToLinkedAccounts/EnableScheduledBanner';
import UpsellBanners from 'merchant/views/Settlements/Settlements/components/UpsellBanners';
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
} from 'merchant/views/Settlements/Settlements/ga';
import {
  closeModal as fnCloseModal,
  openModal as fnOpenModal,
} from 'merchant_common/reducers/modals';
import { onDemandModalTrackEvents } from 'merchant/views/Settlements/trackEvents';

import IsPlusPlusModal from './IsPlusPlusModal';
import ScheduledModal from './ScheduledModal';
import Nudge from './ScheduledModal/components/Nudge';
import { NUDGE_TYPES, POST_ENABLE_TYPES } from './ScheduledModal/constants';
import { setEsNudgeSeen } from './ScheduledModal/utils';
import SettlementSuccessView from 'merchant/views/Settlements/Settlements/components/SettleToLinkedAccounts/SettlementSuccessView';

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
      isLinkedAccountActive: false,
      linkedAccountsSettlementBalance: 0,
      /* ISPlusPlus States */
      hasISPlusPlus:
        getItem('rzp-capital-is-plus-plus') !== 'true' && props.user.showIsPlusPlusExperiment,
      MID_LIMIT: {},
      advanceAmount: 0,
      wantsISPlusPlus: false,
      showIsPlusPlusBreakup: false,
    };

    this.updateFeeDebounced = debounce(this.updateFee, 300);
  }

  static contextTypes = {
    confirm: PropTypes.func,
  };

  renderConfirmation = () => {
    const { amount } = this.state;
    return (
      <div className="m-b">
        The settlement amount is:{` `}
        <span className="bold-amount">
          <Amount value={amount * 100} currency="INR" parentQuerySelector=".modal-body" />
        </span>
      </div>
    );
  };

  renderISConfirmation = () => {
    const { advanceAmount, amount } = this.state;
    return (
      <div className="m-b">
        Amount to be provided
        <span className="bold-amount">
          <Amount
            value={(amount + advanceAmount) * 100}
            currency="INR"
            parentQuerySelector=".modal-body"
          />
        </span>
      </div>
    );
  };

  gaEventDispatcher = (eventObject) => {
    eventObject.eventCategory = this.props.eventCategory;
    window.rzpAnalytics?.(eventObject);
  };

  onShowIsPlusPlusBreakup = () => {
    const { amount, advanceAmount } = this.state;
    const { fromWhere } = this.props;
    onDemandModalTrackEvents.trackISSettleNowFirstConfirm(fromWhere, amount, advanceAmount);
    this.setState({ showIsPlusPlusBreakup: true });
    this.fetchBreakup();
  };

  openConfirmSettlement = async () => {
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

    try {
      await this.context.confirm({
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
    } catch (error) {
      //empty catch
    }
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
      <div className="breakup">
        <div className={isSaved ? 'dropdown-1' : 'dropdown'}>
          {isSaved ? (
            <div className="currency-big-1">
              <Amount value={amount * 100} currency="INR" parentQuerySelector=".breakup" />
            </div>
          ) : (
            <span>
              <p className="percent">{instantFeePercent / 100}</p>
              <p className="fixed">% Additional Fee </p>
            </span>
          )}
          <AsyncBtn.Primary
            className={`drop-button ${isSaved ? 'success-breakup' : ''}`}
            disabled={isLoadingBreakup || !validAmount}
            pendingState=""
            onClick={this.fetchBreakup}
          >
            {breakupShow ? (
              <span>
                Hide Breakup <i className="i i-chevron-up" />
              </span>
            ) : (
              <span>
                Show Breakup <i className="i i-chevron-down" />
              </span>
            )}
          </AsyncBtn.Primary>
        </div>
        <div
          className={`${breakupShow ? 'dropdown-active' : 'dropdown-closed'} ${
            !isSaved ? 'dropdown-border' : ''
          }`}
        >
          <div className="p-b-5">
            <p>Total Amount</p>
            <span className="float-right currency">
              <Amount value={amount * 100} currency="INR" parentQuerySelector=".onmdemand-modal" />
            </span>
          </div>
          <div className="p-b-5">
            <p>Instant Fees ({instantFeePercent / 100}%) </p>
            <span className="float-right currency">
              {' '}
              <p>-</p>
              <Amount value={instantFee} currency="INR" parentQuerySelector=".onmdemand-modal" />
            </span>
          </div>
          <span>
            <p>Taxes</p>
            <span className="float-right currency">
              {' '}
              <p>-</p>
              <Amount value={tax} currency="INR" parentQuerySelector=".onmdemand-modal" />
            </span>
          </span>
        </div>
        <div className={breakupShow ? 'dropdown-active' : 'dropdown-closed'}>
          <p className="amount-to-settle">Amount to be settled</p>
          <span className="float-right currency">
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
    /* IS Plus limits fetch */
    axios
      .get('https://cdn.razorpay.com/static/assets/capital/is-plus-plus/limits.json')
      .then(({ data }) =>
        this.setState({ MID_LIMIT: data, advanceAmount: data[user.current] || 0 }),
      )
      .catch(() => {});
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
      validAmount: true,
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
        this.props.invalidateOdsQuery();

        const { user } = this.props;
        if (user.isOndemandSettlementEnabled && !user.isOndemandSettlementsRestricted) {
          setEsNudgeSeen(NUDGE_TYPES.FULL_SUCCESS);
        }

        if (this.props.checkIfFirstEverSettlement)
          this.props.checkIfFirstEverSettlement('settlementDone');
      })
      .catch((response) => {
        this.setState({
          isSaving: false,
          isSaved: false,
          validAmount: false,
          errors: response?.errors?.[0] ? response.errors : ['Something went wrong'],
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
      const merchantLevelLimit = this.props.odsQuery.data?.available_limit;
      const isMerchantLevelLimitInvalid = merchantLevelLimit > 0 && val * 100 > merchantLevelLimit;
      const isEsRestricedInvalid =
        this.props.settlableAmount > 0 && val * 100 > this.props.settlableAmount;
      if (
        this.isPartialOndemandSettlementEnabled ? isEsRestricedInvalid : isMerchantLevelLimitInvalid
      ) {
        trackOndemand.trackAmounTooHigh(this.props.fromWhere);
        trackEsAmountError();
        this.setState({
          errors: [
            <Text key="error" size="small" color="feedback.text.negative.intense">
              You can withdraw up to{' '}
              <BladeAmount
                size="small"
                weight="semibold"
                suffix="humanize"
                color="feedback.text.negative.intense"
                value={
                  (this.isPartialOndemandSettlementEnabled
                    ? this.props.settlableAmount
                    : merchantLevelLimit) / 100
                }
              />
              {this.isPartialOndemandSettlementEnabled ? ' more. ' : ' more Today. '}
              <Link size="small" variant="button" onClick={this.handleShowRestrictedReasonModal}>
                Why?
              </Link>
            </Text>,
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

  handleShowRestrictedReasonModal = () => {
    const modalType = this.isPartialOndemandSettlementEnabled
      ? POST_ENABLE_TYPES.SAMEDAY_FULL_SHIFT_PROGRESS
      : POST_ENABLE_TYPES.ODS_MERCHANT_LEVEL_LIMIT;

    this.props.openModal({
      component: <ScheduledModal enabled postModalType={modalType} />,
      size: 'small',
      disableClose: true,
    });
  };

  get hasMerchantLevelLimit() {
    return getHasMerchantLevelLimit(this.props.odsQuery.data?.available_limit);
  }

  get isPartialOndemandSettlementEnabled() {
    return (
      this.props.user.isOndemandSettlementEnabled && this.props.user.isOndemandSettlementsRestricted
    );
  }

  successModalHeader = () => {
    return (
      <div>
        <i className="i i-done-all text-success modal-header-success" />
        Hurray!
      </div>
    );
  };

  renderPreForMainAccount = () => {
    const {
      isLoadingBreakup,
      validAmount,
      errors,
      isSaving,
      amount,
      instantFee,
      tax,
      hasISPlusPlus, // Need to change, pick from localStorage
      advanceAmount,
      wantsISPlusPlus,
      showIsPlusPlusBreakup,
      MID_LIMIT,
    } = this.state;
    const { user, settlableAmount, fromWhere, openModal, odsQuery } = this.props;

    const MAX_IS_LIMIT = MID_LIMIT[user.current] || 0;

    const hasEsRestricedLimit = settlableAmount > 0;
    const merchantLevelLimit = odsQuery.data?.available_limit;
    const hasMerchantLevelLimit = merchantLevelLimit > 0;
    const shouldShowMaxLimit =
      validAmount &&
      (this.isPartialOndemandSettlementEnabled ? hasEsRestricedLimit : hasMerchantLevelLimit);

    if (showIsPlusPlusBreakup) {
      return (
        <Box>
          {isLoadingBreakup ? (
            <Spinner />
          ) : (
            <IsPlusPlusModal
              instantFee={instantFee}
              tax={tax}
              settlementAmount={Number(amount)}
              advanceAmount={Number(advanceAmount)}
              fromWhere={fromWhere}
              onFinish={() => {
                setItem('rzp-capital-is-plus-plus', true);
                this.setState({
                  hasISPlusPlus: false,
                  wantsISPlusPlus: false,
                  showIsPlusPlusBreakup: false,
                });
              }}
              onGoBack={() => {
                onDemandModalTrackEvents.trackISGoBack(fromWhere);
                this.setState({ showIsPlusPlusBreakup: false });
              }}
            />
          )}
        </Box>
      );
    }

    return (
      <>
        <div className="InputGroup Input Input--vTop">
          <Input
            label="Amount to settle now"
            required={false}
            addonBefore={<AmountTooltip currency="INR" parentQuerySelector=".Modal" />}
            autoFocus={false}
            name="amount"
            className="Input Input--Amount"
            disabled={isSaving}
            value={amount}
            validator={this.validateAmount}
            onChange={(e) => {
              this.handleChange(e);
              onDemandModalTrackEvents.trackSettleAmountUpdated(fromWhere);
            }}
          />
          {hasISPlusPlus ? (
            wantsISPlusPlus ? (
              <Box marginY="spacing.5">
                <PlusSquareIcon size="medium" color="interactive.icon.gray.normal" />
                <Input
                  label="Additional advance"
                  required={false}
                  addonBefore={<AmountTooltip currency="INR" parentQuerySelector=".Modal" />}
                  autoFocus={false}
                  name="amount"
                  className="Input Input--Amount"
                  value={advanceAmount}
                  onChange={(e) => {
                    this.setState({ advanceAmount: Number(e.target.value) });
                  }}
                />
                <Box display="flex" flexWrap="wrap" flexDirection="row">
                  <Text size="medium">Maximum amount available:</Text>
                  <BladeAmount size="small" value={MAX_IS_LIMIT} />
                </Box>
              </Box>
            ) : (
              <Box marginY="spacing.5" display="flex" justifyContent="space-between">
                <Link
                  icon={PlusSquareIcon}
                  iconPosition="left"
                  variant="button"
                  onClick={() => {
                    this.setState({ wantsISPlusPlus: true });
                    onDemandModalTrackEvents.trackISCheckbox(fromWhere);
                  }}
                >
                  Get additional advance
                </Link>

                <Popover theme="dark" align="top" parentQuerySelector=".onmdemand-modal">
                  <PopoverBody>
                    Activate additional credit above desired settlement amount
                  </PopoverBody>
                </Popover>

                <BladeAmount size="medium" value={MAX_IS_LIMIT} />
              </Box>
            )
          ) : null}
        </div>
        <div>
          {isLoadingBreakup && validAmount && !shouldShowMaxLimit && <div className="loader" />}
          {shouldShowMaxLimit ? (
            <div className="grey-border">
              <Text size="small" color="surface.text.gray.subtle">
                You can withdraw up to{' '}
                <BladeAmount
                  size="small"
                  weight="semibold"
                  suffix="humanize"
                  value={
                    (this.isPartialOndemandSettlementEnabled
                      ? settlableAmount
                      : merchantLevelLimit) / 100
                  }
                />
                {this.isPartialOndemandSettlementEnabled ? ' more. ' : ' more Today. '}
                <Link size="small" variant="button" onClick={this.handleShowRestrictedReasonModal}>
                  Why?
                </Link>
              </Text>
            </div>
          ) : null}
          {!isLoadingBreakup && validAmount && !shouldShowMaxLimit && (
            <div className="grey-border">
              <div>
                <p className="after-deduction"> After Deduction </p>
                <Amount
                  parentQuerySelector=".onmdemand-modal"
                  value={amount * 100 - instantFee - tax}
                  currency="INR"
                />
              </div>
            </div>
          )}
          {!validAmount && <div className="error-message">{errors[0]}</div>}

          <Nudge user={user} openModal={openModal} hasMIDLevelLimit={this.hasMerchantLevelLimit} />

          <AsyncBtn.Primary
            className="submit-btn"
            disabled={isSaving || !validAmount || (isLoadingBreakup && !shouldShowMaxLimit)}
            isPending={isLoadingBreakup && shouldShowMaxLimit}
            pendingState="Requesting"
            onClick={wantsISPlusPlus ? this.onShowIsPlusPlusBreakup : this.openConfirmSettlement}
          >
            Confirm
          </AsyncBtn.Primary>
        </div>
      </>
    );
  };

  onLinkedAccountsSettlementSuccess = (amount) => {
    this.setState({
      isSaved: true,
      linkedAccountsSettlementBalance: amount,
    });
  };

  renderPreForLinkedAccounts = () => {
    return (
      <SettleToLinkedAccounts
        confirm={this.context.confirm}
        onLinkedAccountsSettlementSuccess={this.onLinkedAccountsSettlementSuccess}
      />
    );
  };

  renderPreTransaction = () => {
    const { isLinkedAccountActive, showIsPlusPlusBreakup } = this.state;
    const { isOndemandRouteSettlementsEnabled } = this.props.user;

    const renderMainContent = () => {
      if (isLinkedAccountActive) {
        return this.renderPreForLinkedAccounts();
      } else {
        return this.renderPreForMainAccount();
      }
    };

    const handleSettleToMainAccountClick = () => this.setState({ isLinkedAccountActive: false });
    const handleSettleToLinkedAccountsClick = () => this.setState({ isLinkedAccountActive: true });
    const isLoading = this.props.odsQuery.isFetching;

    return (
      <div className="onmdemand-modal">
        <ModalHeader
          className="header"
          title="Instant Settlements"
          onCloseClick={() => {
            this.handleCloseModal('Close Modal Screen 1');
            onDemandModalTrackEvents.trackSettleNowCloseClick(this.props.fromWhere);
          }}
        />
        <div className="modal-body">
          <p>
            Settle to your bank account instantly, <strong>even on Holidays!&nbsp;</strong>
            <a
              className="btn-link"
              target="_blank"
              rel="noopener noreferrer"
              href="http://razorpay.com/settlement"
            >
              {` `}Learn more
            </a>
          </p>

          <div
            className={classList(
              'overflow-box',
              isOndemandRouteSettlementsEnabled && 'extended-overflow-box',
            )}
          >
            {!isLoading ? (
              <>
                {isOndemandRouteSettlementsEnabled && (
                  <div className="settlement-options">
                    <p
                      onClick={handleSettleToMainAccountClick}
                      className={classList(
                        'settlement-options__item',
                        !isLinkedAccountActive && 'active',
                      )}
                    >
                      Settle to your account
                    </p>
                    <p
                      onClick={handleSettleToLinkedAccountsClick}
                      className={classList(
                        'settlement-options__item',
                        isLinkedAccountActive && 'active',
                      )}
                    >
                      <span>Settle to linked accounts </span>
                      <span className="new-badge">NEW</span>
                    </p>
                  </div>
                )}
                {renderMainContent()}
              </>
            ) : (
              <Box
                display="flex"
                justifyContent="center"
                paddingTop="spacing.11"
                paddingBottom="spacing.8"
              >
                <Spinner accessibilityLabel="Checking Balance" size="large" />
              </Box>
            )}
          </div>
          {!isLoading ? (
            isLinkedAccountActive ? (
              <EnableScheduledBanner />
            ) : showIsPlusPlusBreakup ? null : (
              this.breakup()
            )
          ) : null}
        </div>
      </div>
    );
  };

  renderPostTransaction = () => {
    const { closeModal } = this.props;
    const { hideCloseButton, isLinkedAccountActive, linkedAccountsSettlementBalance } = this.state;

    return (
      <div className="onmdemand-modal">
        <ModalHeader
          title={this.successModalHeader()}
          onCloseClick={() => this.handleCloseModal('Close Modal Screen 2')}
        />
        <div className="modal-body">
          <div className="overflow-box">
            {isLinkedAccountActive ? (
              <SettlementSuccessView amount={linkedAccountsSettlementBalance} />
            ) : (
              <>
                {this.breakup()}
                <div className="help-block">
                  The settlement has been initiated and should soon reflect in your bank account.
                </div>
                {hideCloseButton ? null : (
                  <Button.Primary
                    className="close-btn"
                    onClick={() => this.handleCloseModal('Close Button')}
                  >
                    Close
                  </Button.Primary>
                )}
              </>
            )}
          </div>

          {isLinkedAccountActive ? (
            <EnableScheduledBanner />
          ) : (
            <UpsellBanners
              closeModal={closeModal}
              hideCloseButton={() => this.setState({ hideCloseButton: true })}
            />
          )}
        </div>
      </div>
    );
  };

  render() {
    const { goBackToInitialModalView, openModal } = this.props;
    const { closeClicked, isSaved, hasISPlusPlus, wantsISPlusPlus } = this.state;
    return (
      <div className="container-ondemand-modal">
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
            openModal={openModal}
            eventCategory={this.props.eventCategory}
            fromWhere={this.props.fromWhere}
            hasMIDLevelLimit={this.hasMerchantLevelLimit}
            showISPlusPlus={hasISPlusPlus && wantsISPlusPlus}
            onFinish={() => {
              setItem('rzp-capital-is-plus-plus', true);
              this.setState({
                closeClicked: false,
                hasISPlusPlus: false,
                wantsISPlusPlus: false,
                showIsPlusPlusBreakup: false,
              });
            }}
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
      openModal: fnOpenModal,
      closeModal: fnCloseModal,
      fetchCurrentBalance,
      fetchOndemandRestrictions,
    },
    dispatch,
  );
};

const WrappedODSQuery = withODSConfig(OndemandModal);

export default connect(mapStateToProps, mapDispatchToProps)(WrappedODSQuery);
