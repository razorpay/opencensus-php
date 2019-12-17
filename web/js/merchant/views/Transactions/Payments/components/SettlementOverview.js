import { Link } from 'react-router-dom';
import Amount from 'common/ui/Amount';

function SettlementOverview({ payment }) {
  const rzp_fees =
    payment.transaction.settlement.fees - payment.transaction.settlement.tax;

  return (
    <div>
      <div>
        <Link to={`/settlements/${payment.transaction.settlement.id}`}>
          <code>{payment.transaction.settlement.id}</code>
        </Link>
      </div>
      <div class="settlement-detail-row">
        <span>Settlement Amount</span>
        <span>
          <Amount
            value={payment.transaction.settlement.amount}
            currency={payment.currency}
          />
        </span>
      </div>
      <div class="settlement-detail-row">
        <span>Total Fee</span>
        <span>
          <Amount
            value={payment.transaction.settlement.fees}
            currency={payment.currency}
          />
        </span>
      </div>
      <div class="settlement-detail-row settlement-sub-row">
        <span>Razorpay Fee</span>
        <span>
          <Amount value={rzp_fees} currency={payment.currency} />
        </span>
      </div>
      <div class="settlement-detail-row settlement-sub-row">
        <span>GST(18%) Fee</span>
        <span>
          <Amount
            value={payment.transaction.settlement.tax}
            currency={payment.currency}
          />
        </span>
      </div>
    </div>
  );
}

export default SettlementOverview;
