import CommissionDailyEntity, {
  EarningsBreakup,
} from '../../Commissions/Daily/Details';
import React from "react";

export default function SubventionDailyEntity(props) {
  return (
    <CommissionDailyEntity
      queryType="subvention_detail"
      subHeading="Subventions"
      renderBreakups={renderBreakups}
      {...props}
    />
  );
}

function renderBreakups(props) {
  const { entity } = props;
  const data = entity.data;

  return (
    <EarningsBreakup
      // since analytics api returns this value as negative
      value={Math.abs(data.baseEarnings)}
      tax={data.baseTax}
      feeBreakupType="danger"
      label="Subvention"
    />
  );
}
