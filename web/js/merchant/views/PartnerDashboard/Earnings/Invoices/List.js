import React from 'react';
import { connect } from 'react-redux';
import { Field } from 'redux-form';
import { Link } from 'react-router-dom';

import ListContainer from 'merchant/containers/ListContainer';
import { withRouter } from 'common/deprecated/withRouter';
import ListFilter from 'merchant/components/ListFilter';
import EmptyList from 'merchant/components/EmptyList';
import { CommissionInvoiceStatusLabel } from 'merchant/components/StatusLabel';
import DataTable from 'common/ui/Table/DataTable';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Pager from 'common/ui/Pager';
import Alert from 'common/ui/Forms/Alert';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Banner from 'common/ui/Banner';
import ProcessInvoice from './ProcessInvoice';

import { fetchCommissionInvoices } from 'merchant/reducers/commissionInvoices/list';
import { getCurrentFinancialYear } from 'common/utils/rzp-utils';

const generateColumns = (user) => {
  const currency = user.merchant.currency;
  return {
    invoiceId: {
      title: 'Invoice ID',
      value: (item) => <Link to={`/partners/earnings/invoices/${item.id}`}>{item.id}</Link>,
    },

    createdDate: {
      title: 'Created Date',
      value: (item) => <Time value={item.created_at} />,
    },

    status: {
      title: 'Status',
      value: (item) => <CommissionInvoiceStatusLabel status={item.status} />,
    },

    amount: {
      title: (
        <>
          <span>Amount &nbsp;</span>
          <small className="help-content">
            <i class="i i-info-circle" />
            <Popover align="right" theme="dark">
              <PopoverBody>
                <div>Amount will be paid after TDS has been deducted.</div>
              </PopoverBody>
            </Popover>
          </small>
        </>
      ),
      value: (item) => (
        <Amount value={item.gross_amount} currency={currency} testId={`amount-${item.id}`} />
      ),
    },

    ProcessInvoice: {
      title: '',
      value: (item) =>
        item.status === 'issued' && <ProcessInvoice commissionInvoice={item} className="btn-xs" />,
    },
  };
};

const MONTHLY_COMMISSION_CURRENCY_NAME = {
  rzp: 'Rupee',
  curlec: 'MYR',
};

@connect(
  (state) => ({ ...state.commissionInvoices, user: state.session.user, org: state.session.org }),
  {
    fetchCommissionInvoices,
  },
)
class CommissionInvoicesList extends ListContainer {
  updateParams = (params) => {
    const { user } = this.props;
    const { isShowInvoiceCurrentFY } = user;
    const newParams = { ...params };

    // range for a financial year (excluding April)
    if (isShowInvoiceCurrentFY) {
      const currentFinancialYear = getCurrentFinancialYear();
      const currentMonth = new Date().getMonth();
      if (currentMonth < 3 || currentMonth > 5)
        // show invoices of previous Q4 in the Q1 quarter of current financial year
        newParams.from = new Date(currentFinancialYear, 4, 1).getTime() / 1000;
      else newParams.from = new Date(currentFinancialYear, 1, 1).getTime() / 1000;
      newParams.to = new Date(currentFinancialYear + 1, 3, 30).getTime() / 1000;
    }

    return newParams;
  };

  fetchEntityList(params) {
    return this.props.fetchCommissionInvoices(this.updateParams(params));
  }

  render() {
    const status = this.state.status;
    const { loading, commissionInvoices, user, org } = this.props;
    const Columns = generateColumns(user);
    const customCode = org?.custom_code || 'rzp';
    const currencyName = MONTHLY_COMMISSION_CURRENCY_NAME[customCode];
    return (
      <>
        <div className="TestModeBanner">
          <Banner>
            <i className="i i-info-outline" />
            &nbsp; Invoices are generated only if the monthly commission is greater than 1{' '}
            {currencyName}
          </Banner>
        </div>
        <div class="content-wrapper">
          <ListFilter form="CommissionInvoicesListFilter" count={this.state.count}>
            <div class="form-group list-filter-item">
              <label>Invoice Status</label>
              <Field name="status" component="select" class="form-control input-sm">
                <option value="">All</option>
                <option value="issued">Issued</option>
                <option value="under_review">Under Review</option>
                <option value="processed">Processed</option>
              </Field>
            </div>

            <div class="form-group list-filter-item">
              <label>Invoice Id</label>
              <Field name="id" component="input" class="form-control input-sm" />
            </div>
          </ListFilter>

          <Alert type={status.type} message={status.message} />

          <DataTable
            loading={loading}
            items={commissionInvoices}
            title="CommissionInvoicesList"
            columns={[
              Columns.invoiceId,
              Columns.amount,
              Columns.createdDate,
              Columns.status,
              Columns.ProcessInvoice,
            ]}
            EmptyComponent={EmptyListComponent}
          />

          <Pager
            count={this.state.count}
            skip={this.state.skip}
            length={this.props.commissionInvoices.length}
            onClick={(params) => {
              this.paginate(params);
            }}
          />
        </div>
      </>
    );
  }
}

function EmptyListComponent() {
  return (
    <EmptyList
      description={
        <React.Fragment>
          <div>You don't have any invoices yet!</div>
          <div>Invoices for your commissions will show up here on 3rd of every month!</div>
        </React.Fragment>
      }
    />
  );
}

export default withRouter(CommissionInvoicesList);
