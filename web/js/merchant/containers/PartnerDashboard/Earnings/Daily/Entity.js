import Amount from 'rzp/ui/Amount';

import CommissionDailyEntity, {
  EarningsBreakup,
} from '../../Commissions/Daily/Entity';
import VerticalBreakup from './VerticalBreakup';

export default function EarningsDailyEntity(props) {
  return (
    <CommissionDailyEntity
      queryType="aggregate_detail"
      renderBreakups={renderBreakups}
      subHeading="Earnings"
      {...props}
    />
  );
}

function renderBreakups(props) {
  const { entity } = props;
  const data = entity.data;
  return (
    <VerticalBreakup>
      <TotalValue data={data} />

      <BaseEarningsBreakup
        baseEarnings={data.baseEarnings}
        baseTax={data.baseTax}
      />

      <AddOnEarningsBreakup
        addonEarnings={data.addonEarnings}
        addonTax={data.addonTax}
      />
    </VerticalBreakup>
  );
}

function BaseEarningsBreakup(props) {
  return (
    <EarningsBreakup
      label="Base Earnings"
      feeBreakupType="primary"
      value={props.baseEarnings}
      tax={props.baseTax}
    />
  );
}

function AddOnEarningsBreakup(props) {
  return (
    <EarningsBreakup
      label="Add-on Earnings"
      feeBreakupType="warning"
      value={props.addonEarnings}
      tax={props.addonTax}
    />
  );
}

function TotalValue({ data }) {
  return (
    <div class="pair-group-item vertical">
      <div class="pair-label">Total Earnings</div>
      <div class="pair-value font-lg">
        <strong>
          <Amount
            value={data.baseEarnings + data.addonEarnings}
            currency={'INR'}
          />
        </strong>
      </div>
    </div>
  );
}
