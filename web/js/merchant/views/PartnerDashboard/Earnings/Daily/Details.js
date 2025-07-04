import Amount from 'common/ui/Amount';
import React from 'react';

import CommissionDailyEntity, {
  EarningsBreakup,
} from 'merchant/views/PartnerDashboard/Commissions/Daily/Details';
import VerticalBreakup from 'merchant/views/PartnerDashboard/Earnings/Daily/VerticalBreakup';

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
  const { entity, user } = props;
  const data = entity.data;
  const currency = user.merchant.currency;

  return (
    <VerticalBreakup>
      <TotalValue data={data} currency={currency} />

      <BaseEarningsBreakup
        baseEarnings={data.baseEarnings}
        baseTax={data.baseTax}
        currency={currency}
        user={user}
      />

      <AddOnEarningsBreakup
        addonEarnings={data.addonEarnings}
        addonTax={data.addonTax}
        currency={currency}
        user={user}
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
      currency={props.currency}
      user={props.user}
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
      currency={props.currency}
      user={props.user}
    />
  );
}

function TotalValue({ data, currency }) {
  return (
    <div className="pair-group-item vertical">
      <div className="pair-label">Total Earnings</div>
      <div className="pair-value font-lg">
        <strong>
          <Amount value={data.baseEarnings + data.addonEarnings} currency={currency} />
        </strong>
      </div>
    </div>
  );
}
