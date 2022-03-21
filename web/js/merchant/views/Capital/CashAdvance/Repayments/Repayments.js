import React from 'react';
import { connect } from 'react-redux';
import moment from 'moment';

import { CASH_ADVANCE_SECTIONS, COLLECTIONS_PRODUCT_TYPES } from '../constants';
import RepaymentListFilter from '../CommonListFilter';
import RepaymentList from './RepaymentsList';
import { fetchRepayments as fetchRepaymentsList } from 'merchant/reducers/capital/repayments';
import { showNotification } from 'merchant_common/reducers/notifications';
import { arrayObjToCsv } from 'common/utils/rzp-utils';
import fileDownload from 'common/utils/file-download';
import OverviewFooter from '../OverviewFooter/index';

const getBreakupByBalanceType = (breakups, balanceType) => {
  return breakups
    .filter((breakup) => breakup.balance_type === balanceType)
    .reduce((total, currentBreakup) => {
      return total + currentBreakup.breakup_amount;
    }, 0);
};

const exportAsCSV = (repayments) => {
  const reportData = repayments.map(({ id, amount, status, created_at, breakups }) => {
    //TODO: Use this from constants when merged. not present in this branch
    const principalRepaid = getBreakupByBalanceType(breakups, 'PRINCIPAL');
    const interestRepaid = getBreakupByBalanceType(breakups, 'INSTALLMENT');
    return {
      id,
      amount,
      status,
      created_at: moment(created_at).format('DD-MM-YYYY'),
      interest_repaid: interestRepaid,
      principal_repaid: principalRepaid,
    };
  });

  const csvData = arrayObjToCsv(reportData);
  fileDownload(csvData, 'repayments_export.csv');
};

@connect(
  (state) => ({
    user: state.session.user,
    data: state.repayments.list.data,
    loading: state.repayments.list.loading,
    withdrawalConfigurationDetails: state.withdrawals.withdrawalConfiguration,
  }),
  {
    fetchRepayments: fetchRepaymentsList,
    showNotification,
  },
)
class Repayments extends React.Component {
  defaultCount = 15;
  pagination = {
    count: this.defaultCount,
    skip: 0,
  };
  filters = {};

  componentDidMount() {
    const {
      loading,
      fetchRepayments,
      user: { current: creditId },
    } = this.props;

    this.commonRequestParams = {
      product_type: COLLECTIONS_PRODUCT_TYPES.CASH_ADVANCE,
      skip: this.pagination.skip,
      count: this.pagination.count,
      credit_id: creditId,
      order_by_type: 'ORDER_BY_TYPE_DESC',
      order_by_field: 'ORDER_BY_FIELD_CREATED_AT',
    };

    if (!loading) {
      fetchRepayments(this.commonRequestParams);
    }
  }

  search = (filters, resetPagination = true) => {
    resetPagination && (this.pagination.skip = 0); // initiating new filter such as id, status, period so reset pagination
    const payload = {
      ...this.commonRequestParams,
      skip: this.pagination.skip,
      count: filters.count ? parseInt(filters.count, 10) : this.pagination.count,
    };
    this.pagination.count = payload.count;
    if (filters.reference_id) {
      payload.repayment_id = filters.reference_id;
    }

    if (filters.status) {
      payload.statuses = filters.status;
    }

    if (filters.from) {
      payload.from = filters.from;
    }

    if (filters.to) {
      payload.to = filters.to;
    }

    this.props.fetchRepayments(payload);
    this.filters = payload;
  };

  handlePaginate = (params) => {
    this.pagination.skip = params.skip;
    this.search(this.filters, false);
  };

  render() {
    const { data: repayments, loading } = this.props;
    return (
      <React.Fragment>
        <div className="cash-advance-repayments-header flex">
          <OverviewFooter hideSummaryTab />
        </div>
        <div className="content-wrapper cash-advance-repayments">
          <div className="filters-wrapper">
            <RepaymentListFilter
              form="withdrawalListFilter"
              count={this.defaultCount}
              maxCountLimit={25}
              onSubmit={this.search}
              view={CASH_ADVANCE_SECTIONS.REPAYMENTS}
              showPeriodSelect
            />
          </div>
          <RepaymentList
            paginationConfig={this.pagination}
            repayments={repayments}
            loading={loading}
            onPaginate={this.handlePaginate}
          />
          <div className="right-cta">
            <button className="btn btn-outline" onClick={() => exportAsCSV(repayments)}>
              <i className="i i-download" />
              Export
            </button>
          </div>
        </div>
      </React.Fragment>
    );
  }
}

export default Repayments;
