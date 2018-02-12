import InfoCard from './InfoCard';
import Amount from 'rzp/ui/Amount';
import { formatFromNow } from 'rzp/utils/rzp-utils';

export default props => {
  let {
    entity_totals,
    payment_breakup,
    current_balance,
    payments,
    refunds,
    settlements,
  } = props;
  return (
    <div class="col-md-12 col-lg-6">
      <div class="row row-sm text-center">
        <InfoCard
          loading={entity_totals.loading}
          content={
            entity_totals.data.settlement
              ? entity_totals.data.settlement.successful_txn_count
              : 0
          }
          error={entity_totals.error}
          title="Total Settlements"
        />
        <InfoCard
          loading={payments.loading}
          content={
            payments && payments.items.length
              ? formatFromNow(payments.items[0].created_at)
              : 'Never'
          }
          error={payments.error}
          title="Last Transaction"
        />
        <InfoCard
          class="bg-info"
          loading={entity_totals.loading}
          content={
            entity_totals.data.payment
              ? entity_totals.data.payment.successful_txn_count
              : 0
          }
          error={entity_totals.error}
          title="Total Payments"
        />
        <InfoCard
          class="bg-primary"
          loading={entity_totals.loading}
          content={
            entity_totals.data.refund
              ? entity_totals.data.refund.successful_txn_count
              : 0
          }
          error={entity_totals.error}
          title="Total Refunds"
        />
        <InfoCard
          loading={entity_totals.loading}
          content={
            <Amount
              value={
                entity_totals.data.payment
                  ? entity_totals.data.payment.total_amount
                  : 0
              }
              currency={
                entity_totals.data.payment
                  ? entity_totals.data.payment.currency
                  : 'INR'
              }
            />
          }
          error={entity_totals.error}
          title="Total Volume"
        />
        <InfoCard
          amount
          loading={current_balance.loading}
          content={<Amount value={current_balance.data.balance || 0} />}
          error={current_balance.error}
          title="Current Balance"
        />
      </div>
    </div>
  );
};
