import Amount from 'common/ui/Amount';
import CommissionsDailyList from '../../Commissions/Daily/List';
import React from "react";

const amountColumn = {
  title: 'Total Subvention',
  value: item => <Amount value={Math.abs(item.earnings)} currency={'INR'} />,
};

export default function SubventionsDailyList(props) {
  return (
    <CommissionsDailyList
      amountColumn={amountColumn}
      queryType="subvention_daily"
      dailyEntityRoute="subventions"
      {...props}
    />
  );
}
