import InfoCard from './InfoCard';
import Amount from 'common/ui/Amount';
import { formatFromNow } from 'common/utils/rzp-utils';

export default props => {
  let {
    entity_totals,
    transfer_breakup,
    current_balance,
    transfers,
    revresals,
    settlements,
  } = props;
  return (
    <div className="col-md-12 col-lg-6">
      <div className="row row-sm text-center">
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
          loading={transfers.loading}
          content={
            transfers && transfers.items.length
              ? formatFromNow(transfers.items[0].created_at)
              : 'Never'
          }
          error={transfers.error}
          title="Last Transaction"
        />
        <InfoCard
          className="bg-info"
          loading={entity_totals.loading}
          content={
            entity_totals.data.transfer
              ? entity_totals.data.transfer.successful_txn_count
              : 0
          }
          error={entity_totals.error}
          title="Total Transfers"
        />
        <InfoCard
          className="bg-primary"
          loading={entity_totals.loading}
          content={
            entity_totals.data.reversal
              ? entity_totals.data.reversal.successful_txn_count
              : 0
          }
          error={entity_totals.error}
          title="Total Reversals"
        />
        <InfoCard
          loading={entity_totals.loading}
          content={
            <Amount
              value={
                entity_totals.data.transfer
                  ? entity_totals.data.transfer.total_amount
                  : 0
              }
              currency={
                entity_totals.data.transfer
                  ? entity_totals.data.transfer.currency
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
