import React, { Component } from 'react';
import Spinner from 'common/ui/Spinner';
import { connect } from 'react-redux';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  fetchWithdrawalDetails,
  fetchWithdrawalConfigurationByMerchantID,
} from 'merchant/reducers/capital/withdrawals';
import { withRouter } from 'react-router-dom';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Amount from 'common/ui/Amount';
import { STATUS_DESCRIPTIONS, STATUSES } from './constants';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import WithdrawalStatus from './WithdrawalStatus';
import WithdrawalsRoot from './index';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import RepaymentTicketSuccessModal from './RepaymentTicketSuccessModal';

@withRouter
@connect(
  state => ({
    user: state.session.user,
    withdrawalConfigurationDetails: state.withdrawals.withdrawalConfiguration,
    withdrawalDetails: state.withdrawals.withdrawalDetails,
    seedData: state.withdrawals.seedData,
    showNotification,
  }),
  {
    fetchWithdrawalDetails,
    fetchWithdrawalConfigurationByMerchantID,
    closeModal,
    openModal,
  }
)
class WithdrawalDetails extends Component {
  state = {
    showBreakdown: false,
    showPartialRepaymentBreakdown: false,
  };

  gaEventDispatcher = eventObject => {
    eventObject['eventCategory'] = 'Dashboard CA - Withdraw';
    window.rzpAnalytics(eventObject);
  };

  componentWillMount() {
    const { fetchWithdrawalConfigurationByMerchantID, user } = this.props;
    fetchWithdrawalConfigurationByMerchantID({
      owner_type: 'RZP_MERCHANT',
      owner_id: user.current,
      status: 'ACTIVE',
      skip: 0,
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
  };

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchWithdrawalDetails({
        reference_type: 'ID',
        reference_id: nextProps.id,
      });
    }
  }

  showRepayTicketCreated = (
    ticketNumber,
    withdrawalDetails,
    withdrawalConfigurationDetails
  ) => {
    const dueAmount = this.getDueAmount(
      withdrawalDetails,
      withdrawalConfigurationDetails
    );
    this.props.openModal({
      size: 'small',
      component: (
        <RepaymentTicketSuccessModal
          amount={dueAmount.principal + dueAmount.interest}
          ticketNumber={ticketNumber}
          email={this.props.user.email}
          closeModal={this.props.closeModal}
        />
      ),
    });
  };

  createRepayTicket = () => {
    let content = `I am requesting here to repay my due amounts against this withdrawal[${this.props.withdrawalDetails.data.id}]`;

    return WithdrawalsRoot.createCapitalFDTicket(content, this.props.user);
  };

  repay = (withdrawalDetails, withdrawalConfigurationDetails) => {
    return this.createRepayTicket().then(res => {
      this.showRepayTicketCreated(
        res.data.ticketNo,
        withdrawalDetails,
        withdrawalConfigurationDetails
      );
    });
  };

  toggleBreakdownVisibility = () => {
    this.gaEventDispatcher({
      eventAction: this.state.showRepaymentDetailsBreakup
        ? 'Details | Hide Breakup'
        : 'Details | Show Breakup',
    });

    this.setState(prevState => ({
      showBreakdown: !prevState.showBreakdown,
    }));
  };

  getRepaidAmount = withdrawalDetails => {
    if (!withdrawalDetails.repayments) {
      return {
        total: 0,
        principal: 0,
        interest: 0,
      };
    }
    const totalAmount = withdrawalDetails.repayments.reduce(
      (acc, curr) => acc + curr.amount,
      0
    );

    const totalPrincipal = withdrawalDetails.repayments
      .reduce((acc, curr) => [...acc, ...curr.repayment_breakdowns], [])
      .filter(repayment => repayment.category === 'PRINCIPAL')
      .reduce((acc, curr) => acc + parseInt(curr.amount), 0);

    const totalInterest = withdrawalDetails.repayments
      .reduce((acc, curr) => [...acc, ...curr.repayment_breakdowns], [])
      .filter(repayment => repayment.category === 'INTEREST')
      .reduce((acc, curr) => acc + parseInt(curr.amount), 0);

    return {
      total: totalAmount,
      principal: totalPrincipal,
      interest: totalInterest,
    };
  };

  getDueAmount = (withdrawalDetails, withdrawalConfigurationDetails) => {
    const { configuration } = withdrawalConfigurationDetails;
    const principal = parseInt(withdrawalDetails.amount);
    const principalRepaidSoFar = this.getRepaidAmount(withdrawalDetails)
      .principal;
    const interestRepaidSoFar = this.getRepaidAmount(withdrawalDetails)
      .interest;

    const lastRepaid =
      withdrawalDetails.repayments && withdrawalDetails.repayments.length > 0
        ? moment(
            withdrawalDetails.repayments[
              withdrawalDetails.repayments.length - 1
            ].created_at
          )
        : withdrawalDetails.drawn_at;

    const diffDays =
      moment(withdrawalDetails.due_date).diff(lastRepaid, 'days') > 0
        ? moment(withdrawalDetails.due_date).diff(lastRepaid, 'days')
        : 0;

    const roi = parseInt(configuration.interest) / 100;

    const interest = (diffDays * roi * principal) / 100;

    return {
      principal: principal - principalRepaidSoFar,
      interest: interest - interestRepaidSoFar,
    };
  };

  getRepayableAmount = (withdrawalDetails, withdrawalConfigurationDetails) => {
    const { configuration } = withdrawalConfigurationDetails;
    const principal = parseInt(withdrawalDetails.amount);
    const repaidSoFar = this.getRepaidAmount(withdrawalDetails).total;

    const lastRepaid =
      withdrawalDetails.repayments && withdrawalDetails.repayments.length > 0
        ? moment(
            withdrawalDetails.repayments[
              withdrawalDetails.repayments.length - 1
            ].created_at
          )
        : withdrawalDetails.due_date;

    const diffDays = moment(lastRepaid).diff(
      withdrawalDetails.drawn_at,
      'days'
    );

    const roi = parseInt(configuration.interest) / 100;

    const interest = (diffDays * roi * principal) / 100;

    return {
      principal: principal - repaidSoFar,
      interest,
    };
  };

  toggleRepaidBreakdownVisibility = () => {
    this.setState(prevState => ({
      showPartialRepaymentBreakdown: !prevState.showPartialRepaymentBreakdown,
    }));
  };

  shouldShowRepaymentDetails = () => {
    const {
      withdrawalDetails: { data, loading: withdrawalDetailsLoading },
      id,
    } = this.props;
    return data.status !== STATUSES.FAILED && data.status !== STATUSES.REJECTED;
  };

  getLastRepaidDate = () => {
    const {
      withdrawalDetails: { data, loading: withdrawalDetailsLoading },
      id,
    } = this.props;

    if (!data.repayments) return '--';

    const repaymentDates = data.repayments
      .reduce((acc, curr) => [...acc, ...curr.repayment_breakdowns], [])
      .map(repayment => moment(repayment.created_at));
    return moment.max(repaymentDates);
  };

  render() {
    const {
      withdrawalDetails: { data, loading: withdrawalDetailsLoading },
      id,
    } = this.props;

    const { showBreakdown, showPartialRepaymentBreakdown } = this.state;
    const withdrawalConfigurationDetails = this.props
      .withdrawalConfigurationDetails.data;
    const loading =
      !withdrawalConfigurationDetails ||
      withdrawalDetailsLoading ||
      this.props.withdrawalConfigurationDetails.loading;

    return (
      <div className="content-wrapper content-sm txn-details Withdrawal--Details">
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
                {(data.status === STATUSES.PROCESSED ||
                  data.status === STATUSES.PARTIALLY_REPAID) && (
                  <AsyncBtn.Primary
                    class="pull-right"
                    onClick={() =>
                      this.repay(data, withdrawalConfigurationDetails)
                    }
                  >
                    Repay
                  </AsyncBtn.Primary>
                )}
              </div>
            </div>
            <div className="SliderPanel__Body">
              <div className="">
                <div className="list-group details-row-container">
                  <WithdrawalStatus
                    status={data.status}
                    withdrawalDetails={data}
                  />
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
                              <Amount
                                value={this.getRepaidAmount(data).total}
                              />
                              {!showPartialRepaymentBreakdown && (
                                <div>
                                  <Button.Transparent
                                    onClick={
                                      this.toggleRepaidBreakdownVisibility
                                    }
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
                                  <EntityDetailRow label="Principle Amount">
                                    <Amount
                                      value={
                                        this.getRepaidAmount(data).principal
                                      }
                                    />
                                  </EntityDetailRow>
                                  <EntityDetailRow label="Interest">
                                    <div>
                                      <Amount
                                        value={
                                          this.getRepaidAmount(data).interest
                                        }
                                      />
                                    </div>
                                    <Button.Transparent
                                      onClick={
                                        this.toggleRepaidBreakdownVisibility
                                      }
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
                                <Amount
                                  value={
                                    this.getDueAmount(
                                      data,
                                      withdrawalConfigurationDetails
                                    ).interest +
                                    this.getDueAmount(
                                      data,
                                      withdrawalConfigurationDetails
                                    ).principal
                                  }
                                />
                                {!showBreakdown && (
                                  <div>
                                    <Button.Transparent
                                      onClick={this.toggleBreakdownVisibility}
                                    >
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
                            <Amount
                              value={
                                this.getDueAmount(
                                  data,
                                  withdrawalConfigurationDetails
                                ).interest +
                                this.getDueAmount(
                                  data,
                                  withdrawalConfigurationDetails
                                ).principal
                              }
                            />
                            {!showBreakdown && (
                              <div>
                                <Button.Transparent
                                  onClick={this.toggleBreakdownVisibility}
                                >
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
                              <EntityDetailRow label="Principle Amount">
                                <Amount
                                  value={
                                    this.getDueAmount(
                                      data,
                                      withdrawalConfigurationDetails
                                    ).principal
                                  }
                                />
                              </EntityDetailRow>
                              <EntityDetailRow label="Interest">
                                <div>
                                  <Amount
                                    value={
                                      this.getDueAmount(
                                        data,
                                        withdrawalConfigurationDetails
                                      ).interest
                                    }
                                  />
                                </div>
                                <Button.Transparent
                                  onClick={this.toggleBreakdownVisibility}
                                >
                                  Hide Breakdown
                                  <i className="i i-chevron-up" />
                                </Button.Transparent>
                              </EntityDetailRow>
                            </div>
                          </div>
                        )}
                        {data.status === STATUSES.REPAID ? (
                          <EntityDetailRow label="Repaid at">
                            {this.getLastRepaidDate().format('LL')}
                          </EntityDetailRow>
                        ) : (
                          <EntityDetailRow label="To be Repaid at">
                            {moment(data.due_date)
                              .utc()
                              .format('LL')}
                          </EntityDetailRow>
                        )}
                        <EntityDetailRow label="Rate of Interest">
                          {parseInt(
                            withdrawalConfigurationDetails.configuration
                              .interest
                          ) / 100}
                          % per day
                        </EntityDetailRow>
                        <EntityDetailRow label="Repayment Method">
                          <p className="no-margin text--secondary KeyboardShortcutRow_action">
                            Repayment amount will be deducted from your
                            settlement Balance
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
