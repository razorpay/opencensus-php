import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import Amount from 'common/ui/Amount';
import ListContainer from 'merchant/containers/ListContainer';
import PaymentsListFilter from './PaymentsListFilter';
import { paymentId, amount, customer, createdAtShort, status } from 'common/ui/item/pair';
import EntityTable from 'merchant/components/EntityTable';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { reportFormatOptions } from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/SelectFormat';
import { _paymentId } from 'merchant/views/Transactions/v1/Payments/Utils';
import { SelfServeActionPages } from 'common/constant/enums';
import { withSplitzService } from 'common/splitz';
import {
  fetchPaymentPagePayments,
  isFetchViaNCA,
} from 'merchant/views/PaymentPages/PaymentPages/utils';
import { onPaginate } from 'merchant/views/Transactions/v2/common/utils';

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

@connect(
  (state) => ({
    payments: state.payments,
    ncaPayments: state.invoices.storefrontPayments,
  }),
  { fetchPaymentPagePayments },
)
class PaymentsList extends ListContainer {
  // Hook to modify fetchAll of ListContainer
  fetchEntityList = (params) => {
    const {
      splitz,
      entity: { id },
    } = this.props;

    return this.props.fetchPaymentPagePayments('subscription_button', splitz, id, params);
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
    const {
      children,
      entity,
      downloadReport,
      splitz,
      payments,
      ncaPayments,
      isExportInProgress,
      ...restProps
    } = this.props;

    const entityData = isFetchViaNCA(splitz) ? ncaPayments : payments;
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
            paginate={onPaginate(this.paginate)}
            paymentPageId={entity.id}
            {...restProps}
            {...entityData}
          />
        </div>
      </div>
    );
  }
}

export default withSplitzService(withRouter(PaymentsList));
