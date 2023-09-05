import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import { fetchPayments as fetchAll } from 'merchant/reducers/collection';
import Amount from 'common/ui/Amount';
import ListContainer from 'merchant/containers/ListContainer';
import PaymentsListFilter from './PaymentsListFilter';
import { paymentId, amount, customer, createdAtShort, status } from 'common/ui/item/pair';
import EntityTable from 'merchant/components/EntityTable';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { reportFormatOptions } from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/SelectFormat';
import { _paymentId } from 'merchant/views/Transactions/v1/Payments/Utils';
import { SelfServeActionPages } from 'common/constant/enums';

const PaymentsTable = (props) => {
  const paymentColumns = [
    {
      title: paymentId.title,
      value: (item) => _paymentId(item, SelfServeActionPages.SubscriptionbuttonPayments),
    },
    amount,
    customer,
    createdAtShort,
    status,
  ];

  return <EntityTable title="Payments" columns={paymentColumns} {...props} />;
};

@withRouter
@connect((state) => state.payments, { fetchAll })
export default class PaymentsList extends ListContainer {
  // Hook to modify fetchAll of ListContainer
  fetchEntityList = (params) => {
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
        value: <Amount value={entity.total_amount_paid} currency={entity.currency} />,
      },
    ];
  }

  render() {
    const { children, entity, downloadReport, isExportInProgress, ...restProps } = this.props;

    return (
      <div>
        <div class="stats">
          <div class="info">
            <b class="bold">Transactions</b>
            {this.getStatsTable(entity).map((st, ix) => (
              <div key={ix}>
                {st.title}
                <b class="bold">{st.value}</b>
              </div>
            ))}
          </div>

          <div class="report-download btn-toolbar pull-right">
            <div
              class="btn btn-default Button--invert report-download-trigger"
              disabled={isExportInProgress}
            >
              <i class="i i-download m-r" />
              {isExportInProgress ? 'Downloading...' : 'Download Report'}
            </div>
            <Popover align="bottom">
              <PopoverBody>
                {reportFormatOptions.map((o, index) => (
                  <li
                    key={index}
                    type="button"
                    class="btn"
                    onClick={() => this.props.downloadReport(o.name)}
                    disabled={isExportInProgress}
                  >
                    {o.label}
                  </li>
                ))}
              </PopoverBody>
            </Popover>
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
