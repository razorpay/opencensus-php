import React, { Component } from 'react';
import moment from 'moment';
import AmountWithdraw from './AmountWithdraw';
import WithdrawalListFilter from './CommonListFilter';
import WithdrawalsTable from './WithdrawalsTable';
import { connect } from 'react-redux';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  fetchSeedData,
  fetchWithdrawalConfiguration,
  fetchWithdrawals,
} from 'merchant/reducers/capital/withdrawals';
import Amount from 'common/ui/Amount';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import RepaymentTicketSuccessModal from './RepaymentTicketSuccessModal';
import Pager from 'common/ui/Pager';
import WithdrawalsRoot from './index';
import { Navigate } from 'react-router-dom';
import { getProductType } from 'merchant/views/Capital/utils';

@connect(
  (state) => ({
    user: state.session.user,
    withdrawalConfigurationDetails: state.withdrawals.withdrawalConfiguration,
    list: state.withdrawals.list,
    seedData: state.withdrawals.seedData,
  }),
  {
    fetchWithdrawalConfiguration,
    fetchSeedData,
    fetchWithdrawals,
    openModal,
    closeModal,
    showNotification,
  },
)
class Withdrawals extends Component {
  gaEventDispatcher = (eventObject) => {
    const { state: { eventCategory = null } = {} } = this.props.location || {};
    eventObject.eventCategory = eventCategory ? eventCategory : 'Dashboard CA - Withdraw';
    window.rzpAnalytics?.(eventObject);
  };

  search = (filters) => {
    const payload = {
      skip: 0,
      count: filters.count ? parseInt(filters.count, 10) : 25,
      order_by: 'CREATED_AT',
      order_direction: 'desc',
      reference: [
        {
          reference_id: this.props.user.current,
          reference_type: 'OWNER_ID',
        },
      ],
    };
    if (filters.reference_id) {
      payload.reference.push({
        reference_type: 'ID',
        reference_id: filters.reference_id,
      });
    }
    if (filters.status) {
      payload.reference.push({
        reference_type: 'STATUS',
        reference_id: filters.status,
      });
    }
    if (filters.due_date) {
      payload.reference.push({
        reference_type: 'DUE_DATE',
        reference_id: filters.status,
      });
    }

    this.props.fetchWithdrawals(payload);
    this.props.setSkip({ skip: 0 });
  };

  onSearchAnalytics = () => {
    this.gaEventDispatcher({
      eventAction: 'List View | Search',
    });
  };

  onClearAnalytics = () => {
    this.gaEventDispatcher({
      eventAction: 'List View | Clear Search',
    });
  };

  fetchWithdrawals = ({ skip = 0, count = 25 }) => {
    this.props.fetchWithdrawals({
      reference: [
        {
          reference_id: this.props.user.current,
          reference_type: 'OWNER_ID',
        },
      ],
      skip,
      count,
      order_by: 'CREATED_AT',
      order_direction: 'desc',
      product_type: getProductType(this.props.user),
    });
  };

  componentDidMount() {
    const {
      list: { loading, data },
    } = this.props;

    if (!loading && !data) {
      this.fetchWithdrawals();
    }
  }

  getRepaidAmount = (withdrawalDetails) => {
    if (!withdrawalDetails.repayments || withdrawalDetails.repayments.length === 0) {
      return {
        total: 0,
        principal: 0,
        interest: 0,
      };
    }

    const totalAmount = withdrawalDetails.repayments.reduce(
      (acc, curr) => acc + parseInt(curr.amount, 10),
      0,
    );

    const totalPrincipal = withdrawalDetails.repayments
      .reduce((acc, curr) => [...acc, ...curr.repayment_breakdowns], [])
      .filter((repayment) => repayment.category === 'PRINCIPAL')
      .reduce((acc, curr) => acc + parseInt(curr.amount, 10), 0);

    const totalInterest = withdrawalDetails.repayments
      .reduce((acc, curr) => [...acc, ...curr.repayment_breakdowns], [])
      .filter((repayment) => repayment.category === 'INTEREST')
      .reduce((acc, curr) => acc + parseInt(curr.amount, 10), 0);

    return {
      total: totalAmount,
      principal: totalPrincipal,
      interest: totalInterest,
    };
  };

  getDueAmount = (withdrawalDetails, withdrawalConfigurationDetails) => {
    const { configuration } = withdrawalConfigurationDetails;
    const principal = parseInt(withdrawalDetails.amount, 10);
    const repaidSoFar = this.getRepaidAmount(withdrawalDetails).total;

    const lastRepaid =
      withdrawalDetails.repayments && withdrawalDetails.repayments.length > 0
        ? moment(withdrawalDetails.repayments[withdrawalDetails.repayments.length - 1].created_at)
        : withdrawalDetails.drawn_at;

    const diffDays =
      moment(withdrawalDetails.due_date).diff(lastRepaid, 'days') > 0
        ? moment(withdrawalDetails.due_date).diff(lastRepaid, 'days')
        : 0;

    const interest = (((diffDays * parseInt(configuration.interest, 10)) / 100) * principal) / 100;

    return {
      principal: principal - repaidSoFar,
      interest,
    };
  };

  showRepayTicketCreated = (ticketNumber, amount) => {
    this.props.openModal({
      size: 'small',
      component: (
        <RepaymentTicketSuccessModal
          amount={amount}
          ticketNumber={ticketNumber}
          email={this.props.user.email}
          closeModal={this.props.closeModal}
        />
      ),
    });
  };

  createRepayTicket = (id) => {
    let content;
    if (!!id) {
      content = `I am requesting here to repay my due amounts against this withdrawal[${id}]`;
    } else {
      content = `I am requesting here to repay my dues`;
    }
    return WithdrawalsRoot.createCapitalFDTicket(content, this.props.user);
  };

  repay = (withdrawal) => {
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    const isRepaymentForWithdrawal = withdrawal && withdrawal.id;

    this.gaEventDispatcher({
      eventAction: isRepaymentForWithdrawal
        ? 'List View | Repay Specific'
        : 'List View | Repay Dues',
    });

    const amount = isRepaymentForWithdrawal
      ? this.getDueAmount(withdrawal, withdrawalConfigurationDetails).interest +
        this.getDueAmount(withdrawal, withdrawalConfigurationDetails).principal
      : withdrawalConfigurationDetails.principal_outstanding_balance;

    return this.createRepayTicket(withdrawal ? withdrawal.id : null).then((res) => {
      this.showRepayTicketCreated(res.data.ticketNo, amount);
      this.gaEventDispatcher({
        eventAction: 'Repayment Request | Done',
      });
    });
  };

  paginate = (params) => {
    this.props.setSkip(params, () => this.fetchWithdrawals({ skip: this.props.skip }));
  };

  render() {
    const withdrawalConfigurationDetails = this.props.withdrawalConfigurationDetails.data;
    const withdrawConfigLoading = this.props.withdrawalConfigurationDetails.loading;
    if (!this.props.user.isWithdrawFeatureEnabled) return <Navigate to="/" replace />;
    return (
      <div class="FlashWithdrawals--Container">
        <AmountWithdraw repayDues={this.repay} />
        <tabbed-container>
          <content>
            <div class="content-wrapper">
              <div class="filters-wrapper">
                <WithdrawalListFilter
                  form="withdrawalListFilter"
                  count={25}
                  onSubmit={this.search}
                  onSearchAnalytics={this.onSearchAnalytics}
                  onClearAnalytics={this.onClearAnalytics}
                />
                {withdrawConfigLoading || !withdrawalConfigurationDetails ? (
                  <div className="flex repay-cta-container">
                    <span class="PlaceholderLoader" />
                    <span className="PlaceholderLoader m-l" />
                  </div>
                ) : (
                  <div
                    className={`flex repay-cta-container right-border warning thick ${
                      parseInt(
                        withdrawalConfigurationDetails.principal_outstanding_balance || 0,
                        10,
                      ) > 0
                        ? 'block-note'
                        : 'm-r'
                    }`}
                  >
                    <strong>
                      <span class="text-muted">Due Repayments:</span>
                      &nbsp;
                    </strong>
                    <Amount
                      value={withdrawalConfigurationDetails.principal_outstanding_balance || 0}
                    />
                  </div>
                )}
              </div>
              <WithdrawalsTable
                count={25}
                skip={false}
                paginate={false}
                withdrawals={this.props.list.data || []}
                loading={this.props.list.loading}
                repay={this.repay}
                trackGA={this.gaEventDispatcher}
              />
              <Pager
                count={25}
                skip={this.props.skip}
                length={this.props.list.data.length}
                onClick={this.paginate}
              />
            </div>
          </content>
        </tabbed-container>
      </div>
    );
  }
}

export default Withdrawals;
