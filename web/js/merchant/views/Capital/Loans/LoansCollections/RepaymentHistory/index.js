import React, { useEffect, useRef } from 'react';
import moment from 'moment';
import { withRouter } from 'react-router-dom';
import RepaymentFilters from './RepaymentFilters';
import RepaymentList from './RepaymentList';
import fileDownload from 'common/utils/file-download';
import { arrayObjToCsv, getURLQueryParams } from 'common/utils/rzp-utils';
import { DEFAULT_COUNT, getBreakupByBalanceType, STATUS_LABELS } from '../../constants';
import OverviewStatus from '../Overview/OverviewStatus';
import LoanCollectionSummary from '../Overview/LoanCollectionSummary';
import { usePromise } from 'merchant/views/Capital/components/Await';
import {
  fetchRepayments,
  getCollectionMethod,
} from 'merchant/views/Capital/Loans/LoansCollections/util';
import { REPAYMENT_STATUES } from '../../../CashAdvance/constants';

const exportAsCSV = (repayments) => {
  const reportData = repayments.map(
    ({ id, amount, status, created_at, breakups, ...restProperties }) => {
      const principalRepaid = getBreakupByBalanceType(breakups, 'BALANCE_TYPE_PRINCIPAL');
      const interestRepaid = getBreakupByBalanceType(breakups, 'BALANCE_TYPE_INTEREST');
      const createdAt = created_at ? moment.unix(created_at).format('D MMM') : '--';
      const collectionMethod = getCollectionMethod(restProperties);

      return {
        id,
        createdAt,
        collectionMethod,
        amount: `₹ ${(amount / 100).toFixed(2)}`,
        principal_repaid: `₹ ${(principalRepaid / 100).toFixed(2)}`,
        interest_repaid: `₹ ${(interestRepaid / 100).toFixed(2)}`,
        status: STATUS_LABELS[status],
      };
    },
  );

  const csvData = arrayObjToCsv(reportData);
  fileDownload(csvData, 'repayments_export.csv');
};

const pendingPromise = new Promise((_) => {});
function RepaymentHistory({
  plan,
  installments,
  recentRepayments,
  upcomingPayments,
  location: { search = {} },
  onRefresh,
}) {
  // nosemgrep
  const filterConfig = useRef({
    product_entity_reference_id: plan.product_entity_reference_id,
    count: DEFAULT_COUNT,
    skip: 0,
    // then others such as statuses, repayments id etc. used when user click of pagination next/back button to remeber previous filters
  });

  const state = usePromise(pendingPromise);

  const { value, loading, error, setPromise } = state;
  let repayments = value ? value.data.repayments : [];

  const onSearch = (filters = {}, resetPagination = true) => {
    resetPagination && (filterConfig.current.skip = 0); // initiating new filter such as id, status, period so reset pagination
    const baseParams = {
      product_entity_reference_id: filterConfig.current.product_entity_reference_id,
      skip: filterConfig.current.skip,
      count: filters.count ? parseInt(filters.count, 10) : filterConfig.current.count,
    };
    ['reference_id', 'statuses'].forEach((key) => {
      if (filters[key]) {
        baseParams[key] = filters[key];
      }
    });
    if (!baseParams.statuses) {
      baseParams.statuses_not_in = REPAYMENT_STATUES.STATUS_PENDING; // status: all applied so get repaid and failed repayments only - used till multiple params is fixed
    }
    const promise = fetchRepayments(baseParams);
    filterConfig.current = baseParams;
    setPromise(promise);
    return promise;
  };

  useEffect(() => {
    onSearch(getURLQueryParams(search));
  }, []);

  const handlePaginate = (params) => {
    filterConfig.current.skip = params.skip;
    onSearch(filterConfig.current, false);
  };

  const repaymentID = filterConfig.current.reference_id; // api doesnt support reference_id filter so filter manually
  if (repaymentID) {
    repayments = repayments.filter((r) => r.id.toLowerCase().includes(repaymentID.toLowerCase()));
  }

  return (
    <div className="repayments-history">
      <div className="payment-box-wrapper flex justify-between">
        <OverviewStatus
          plan={plan}
          installment={installments}
          upcomingPayments={upcomingPayments}
          lastRepayment={recentRepayments[0]}
          onRefresh={onRefresh}
          showHeader={false}
          showFooter={false}
        />
        <LoanCollectionSummary plan={plan} installment={installments} showHeader={false} />
      </div>

      <div className="content-wrapper cash-advance-repayments loans-repayments-history__list-wrapper">
        <div className="filters-wrapper">
          <RepaymentFilters
            form="loansRepaymentListFilter"
            count={filterConfig.current.count}
            onSubmit={onSearch}
          />
          <button
            disabled={!repayments.length}
            className="btn btn-outline export-button"
            onClick={() => exportAsCSV(repayments)}
          >
            <i className="i i-download" /> Export
          </button>
        </div>
        <RepaymentList
          repayments={repayments}
          isLoading={loading}
          isError={error}
          paginationConfig={{
            count: filterConfig.current.count,
            skip: filterConfig.current.skip,
          }}
          onPaginate={handlePaginate}
        />
      </div>
    </div>
  );
}

export default withRouter(RepaymentHistory);
