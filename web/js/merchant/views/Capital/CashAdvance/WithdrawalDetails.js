import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import moment from 'moment';

import Amount from 'common/ui/Amount';
import Spinner from 'common/ui/Spinner';
import Button from 'common/new-ui/Button';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  fetchWithdrawalDetails,
  fetchFunctionalWithdrawalConfigByMerchantID,
} from 'merchant/reducers/capital/withdrawals';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import Repayments from 'merchant/models/Capital/Repayments';
import WithdrawalStatus from './WithdrawalStatus';
import {
  STATUSES,
  STATUS_DESCRIPTIONS,
  COLLECTIONS_BALANCE_TYPE,
  COLLECTIONS_PRODUCT_TYPES,
  COLLECTIONS_PRODUCT_ENTITY_TYPE,
} from './constants';

@withRouter
@connect(
  (state) => ({
    user: state.session.user,
    withdrawalConfigurationDetails: state.withdrawals.withdrawalConfiguration,
    withdrawalDetails: state.withdrawals.withdrawalDetails,
    seedData: state.withdrawals.seedData,
    showNotification,
  }),
  {
    fetchWithdrawalDetails,
    fetchFunctionalWithdrawalConfigByMerchantID,
    closeModal,
    openModal,
  },
)
class WithdrawalDetails extends Component {
  state = {
    showBreakdown: false,
    showPartialRepaymentBreakdown: false,
    breakdownDetails: {
      totalRepaidSoFar: 0,
      principalRepaidSoFar: 0,
      interestRepaidSoFar: 0,
    },
  };

  gaEventDispatcher = (eventObject) => {
    eventObject.eventCategory = 'Dashboard CA - Withdraw';
    window.rzpAnalytics?.(eventObject);
  };

  componentWillMount() {
    const { fetchFunctionalWithdrawalConfigByMerchantID, user } = this.props;

    fetchFunctionalWithdrawalConfigByMerchantID({
      owner_id: user.current,
      owner_type: 'RZP_MERCHANT',
    });

    this.fetchWithdrawalDetails();
  }

  componentWillUnmount() {
    this.gaEventDispatcher({
      eventAction: 'Details | Close',
    });
  }

  fetchWithdrawalDetails = () => {
    this.props.fetchWithdrawalDetails({
      reference_type: 'ID',
      reference_id: this.props.id,
    });
    this.fetchBreakdownDetails();
  };

  fetchBreakdownDetails = () => {
    const {
      user: { current },
      id,
    } = this.props;
    const repaymentInstance = new Repayments();

    return repaymentInstance
      .fetchRepayments({
        product_type: COLLECTIONS_PRODUCT_TYPES.CASH_ADVANCE,
        credit_id: current,
        product_entity_type: COLLECTIONS_PRODUCT_ENTITY_TYPE.WITHDRAWALS,
        product_entity_reference_id: id,
        order_by_type: 'ORDER_BY_TYPE_DESC',
        order_by_field: 'ORDER_BY_FIELD_CREATED_AT',
        count: 50,
      })
      .then(({ data: { repayments = [] } = {} } = {}) => {
        const response = {
          totalRepaidSoFar: 0,
          principalRepaidSoFar: 0,
          interestRepaidSoFar: 0,
        };

        repayments.forEach(({ breakups }) => {
          breakups.forEach(({ breakup_amount, balance_type, product_entity_reference_id }) => {
            if (product_entity_reference_id === id) {
              if (balance_type === COLLECTIONS_BALANCE_TYPE.BALANCE_TYPE_PRINCIPAL) {
                response.principalRepaidSoFar += Number(breakup_amount);
              } else if (balance_type === COLLECTIONS_BALANCE_TYPE.BALANCE_TYPE_INTEREST) {
                response.interestRepaidSoFar += Number(breakup_amount);
              }
            }
          });
        });

        response.totalRepaidSoFar = response.principalRepaidSoFar + response.interestRepaidSoFar;

        this.setState({
          breakdownDetails: response,
        });
      })
      .catch(() => {
        this.setState({
          breakdownDetails: {
            totalRepaidSoFar: 0,
            principalRepaidSoFar: 0,
            interestRepaidSoFar: 0,
          },
        });
      });
  };

  componentDidUpdate(prevProps) {
    if (this.props.id !== prevProps.id) {
      this.fetchWithdrawalDetails();
    }
  }

  toggleBreakdownVisibility = () => {
    this.gaEventDispatcher({
      eventAction: this.state.showRepaymentDetailsBreakup
        ? 'Details | Hide Breakup'
        : 'Details | Show Breakup',
    });

    this.setState((prevState) => ({
      showBreakdown: !prevState.showBreakdown,
    }));
  };

  getDueAmount = (withdrawalDetails, withdrawalConfigurationDetails) => {
    const { configuration } = withdrawalConfigurationDetails;
    // eslint-disable-next-line radix
    const principal = parseInt(withdrawalDetails.amount);
    const {
      breakdownDetails: { principalRepaidSoFar, interestRepaidSoFar },
    } = this.state;

    const lastRepaid =
      withdrawalDetails.repayments && withdrawalDetails.repayments.length > 0
        ? moment(withdrawalDetails.repayments[withdrawalDetails.repayments.length - 1].created_at)
        : withdrawalDetails.drawn_at;

    const diffDays =
      moment(withdrawalDetails.due_date).diff(lastRepaid, 'days') > 0
        ? moment(withdrawalDetails.due_date).diff(lastRepaid, 'days')
        : 0;

    // eslint-disable-next-line radix
    const roi = parseInt(configuration.interest) / 100;

    const interest = (diffDays * roi * principal) / 100;

    return {
      principal: principal - principalRepaidSoFar,
      interest: interest - interestRepaidSoFar,
    };
  };

  toggleRepaidBreakdownVisibility = () => {
    this.setState((prevState) => ({
      showPartialRepaymentBreakdown: !prevState.showPartialRepaymentBreakdown,
    }));
  };

  shouldShowRepaymentDetails = () => {
    const {
      withdrawalDetails: { data },
    } = this.props;
    return data.status !== STATUSES.FAILED && data.status !== STATUSES.REJECTED;
  };

  getLastRepaidDate = () => {
    const {
      withdrawalDetails: { data },
    } = this.props;

    if (!data.repayments || (data.repayments && data.repayments.length === 0)) return '--';

    const repaymentDates = data.repayments
      .reduce((acc, curr) => [...acc, ...curr.repayment_breakdowns], [])
      .map((repayment) => moment(repayment.created_at));
    return moment.max(repaymentDates).format('LL');
  };

  render() {
    const {
      withdrawalDetails: { data, loading: withdrawalDetailsLoading },
      id,
    } = this.props;

    const {
      showBreakdown,
      showPartialRepaymentBreakdown,
      breakdownDetails: { totalRepaidSoFar, principalRepaidSoFar, interestRepaidSoFar },
    } = this.state;
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    const loading =
      !withdrawalConfigurationDetails ||
      withdrawalDetailsLoading ||
      this.props.withdrawalConfigurationDetails.loading;

    const { interest: interestAmount, principal: principalAmount } = this.getDueAmount(
      data,
      withdrawalConfigurationDetails,
    );

    return (
      <div className="content-wrapper content-sm txn-details CA--entity-details">
        {loading ? (
          <div className="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div className="panel panel-default SliderPanel">
            <div className="panel-heading">
              <div class="settlement-actions-wrapper">
                <span class="full-width no-margin">
                  <strong>{id}</strong>
                </span>
              </div>
            </div>
            <div className="SliderPanel__Body">
              <div className="">
                <div className="list-group details-row-container">
                  <WithdrawalStatus status={data.status} withdrawalDetails={data} />
                  <div class="m-all p-all">
                    <div className="block-note purple m-b">
                      <strong>Withdrawal Details</strong>
                    </div>
                    <EntityDetailRow label="Withdrawn Amount">
                      <Amount value={data.amount} />
                    </EntityDetailRow>
                    <EntityDetailRow label="Status Description">
                      <p className="no-margin text--secondary KeyboardShortcutRow_action">
                        {STATUS_DESCRIPTIONS[data.status]}
                      </p>
                    </EntityDetailRow>
                    {data.disbursal_utr && (
                      <EntityDetailRow label="Disbursal UTR">
                        <p className="text--secondary">{data.disbursal_utr}</p>
                      </EntityDetailRow>
                    )}
                    {this.shouldShowRepaymentDetails() && (
                      <React.Fragment>
                        <hr />
                        <div className="block-note warning m-b">
                          <strong>Repayment Details</strong>
                        </div>
                        {data.status === STATUSES.PARTIALLY_REPAID ||
                        data.status === STATUSES.REPAID ? (
                          <React.Fragment>
                            <EntityDetailRow label="Amount Repaid">
                              <Amount value={totalRepaidSoFar} />
                              {!showPartialRepaymentBreakdown && (
                                <div>
                                  <Button.Transparent
                                    onClick={this.toggleRepaidBreakdownVisibility}
                                  >
                                    Show Breakdown
                                    <i className="i i-chevron-down" />
                                  </Button.Transparent>
                                </div>
                              )}
                            </EntityDetailRow>
                            {showPartialRepaymentBreakdown && (
                              <div>
                                <div className="block-note purple m-b">
                                  <EntityDetailRow label="Principal Amount">
                                    <Amount value={principalRepaidSoFar} />
                                  </EntityDetailRow>
                                  <EntityDetailRow label="Interest">
                                    <div>
                                      <Amount value={interestRepaidSoFar} />
                                    </div>
                                    <Button.Transparent
                                      onClick={this.toggleRepaidBreakdownVisibility}
                                    >
                                      Hide Breakdown
                                      <i className="i i-chevron-up" />
                                    </Button.Transparent>
                                  </EntityDetailRow>
                                </div>
                              </div>
                            )}
                            {data.status === STATUSES.PARTIALLY_REPAID && (
                              <EntityDetailRow label="Amount to be Repaid">
                                <Amount value={parseFloat(interestAmount + principalAmount)} />
                                {!showBreakdown && (
                                  <div>
                                    <Button.Transparent onClick={this.toggleBreakdownVisibility}>
                                      Show Breakdown
                                      <i className="i i-chevron-down" />
                                    </Button.Transparent>
                                  </div>
                                )}
                              </EntityDetailRow>
                            )}
                          </React.Fragment>
                        ) : (
                          <EntityDetailRow label="Amount to be Repaid">
                            <Amount value={parseFloat(interestAmount + principalAmount)} />
                            {!showBreakdown && (
                              <div>
                                <Button.Transparent onClick={this.toggleBreakdownVisibility}>
                                  Show Breakdown
                                  <i className="i i-chevron-down" />
                                </Button.Transparent>
                              </div>
                            )}
                          </EntityDetailRow>
                        )}
                        {showBreakdown && (
                          <div>
                            <div className="block-note purple m-b">
                              <EntityDetailRow label="Principal Amount">
                                <Amount value={principalAmount} />
                              </EntityDetailRow>
                              <EntityDetailRow label="Interest">
                                <div>
                                  <Amount value={interestAmount} />
                                </div>
                                <Button.Transparent onClick={this.toggleBreakdownVisibility}>
                                  Hide Breakdown
                                  <i className="i i-chevron-up" />
                                </Button.Transparent>
                              </EntityDetailRow>
                            </div>
                          </div>
                        )}
                        {data.status === STATUSES.REPAID ? (
                          <EntityDetailRow label="Repaid at">
                            {this.getLastRepaidDate()}
                          </EntityDetailRow>
                        ) : (
                          <EntityDetailRow label="To be Repaid at">
                            {moment(data.due_date).utc().format('LL')}
                          </EntityDetailRow>
                        )}
                        <EntityDetailRow label="Rate of Interest">
                          {/* eslint-disable-next-line radix */}
                          {parseInt(withdrawalConfigurationDetails.configuration.interest) / 100}%
                          per day
                        </EntityDetailRow>
                        <EntityDetailRow label="Repayment Method">
                          <p className="no-margin text--secondary KeyboardShortcutRow_action">
                            Repayment amount will be deducted from your settlement Balance
                          </p>
                        </EntityDetailRow>
                      </React.Fragment>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }
}

export default WithdrawalDetails;
