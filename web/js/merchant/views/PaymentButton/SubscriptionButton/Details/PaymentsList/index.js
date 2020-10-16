import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import { fetchPayments as fetchAll } from 'merchant/reducers/collection';

import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import ListContainer from 'merchant/containers/ListContainer';
import PaymentsListFilter from './PaymentsListFilter';

import {
  paymentId,
  amount,
  customer,
  createdAtShort,
  status,
} from 'common/ui/item/pair';

import EntityTable from 'merchant/components/EntityTable';

const PaymentsTable = props => {
  let paymentColumns = [paymentId, amount, customer, createdAtShort, status];

  return <EntityTable title="Payments" columns={paymentColumns} {...props} />;
};

@withRouter
@connect(state => state.payments, { fetchAll })
export default class PaymentsList extends ListContainer {
  constructor(props) {
    super(props);
  }

  // Hook to modify fetchAll of ListContainer
  fetchEntityList = params => {
    return this.props.fetchAll({
      ...params,
      payment_link_id: this.props.entity.id,
    });
  };

  getStatsTable(entity) {
    return [
      {
        title: 'Total Payments',
        value: entity.captured_payments_count,
      },
      {
        title: 'Total revenue',
        value: (
          <Amount
            value={entity.total_amount_paid}
            currency={entity.currency}
          />
        ),
      },
    ];
  }

  render() {
    const { children, entity, downloadReport, isExportInProgress, ...restProps } = this.props;

    return (
      <div>
        <div class="stats">
          <b class="bold">Transactions</b>
          {this.getStatsTable(entity).map((st, ix) => (
            <div key={ix}>
              {st.title}
              <b class="bold">{st.value}</b>
            </div>
          ))}

          <div class="btn-toolbar pull-right">
            <Button
              class="Button--primary--invert"
              onClick={downloadReport}
              disabled={isExportInProgress}
            >
              <i class="i i-download m-r"/>
              Export All (CSV)
            </Button>
          </div>
        </div>

        <div class="content-wrapper">
          {children}

          <PaymentsListFilter
            key="payments"
            form="paymentListFilter"
            count={this.state.count}
            onSubmit={this.search}
            fetchAll={this.fetchAll}
          />
          <PaymentsTable
            count={this.state.count}
            skip={this.state.skip}
            paginate={this.paginate}
            paymentPageId={entity.id}
            {...restProps}
          />
        </div>
      </div>
    );
  }
}
