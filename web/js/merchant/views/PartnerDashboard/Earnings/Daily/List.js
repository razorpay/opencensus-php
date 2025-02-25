import Amount from 'common/ui/Amount';
import CommissionsDailyList from 'merchant/views/PartnerDashboard/Commissions/Daily/List';
import { connect } from 'react-redux';
import React from "react";

function EarningsDailyList(props) {
  const { user } = props;
  const currency = user.merchant.currency;
  const amountColumn = {
    title: 'Total Earnings',
    value: (item) => <Amount value={item.earnings} currency={currency} />,
  };

  console.log(props);

  return (
    <CommissionsDailyList
      amountColumn={amountColumn}
      queryType="aggregate_daily"
      dailyEntityRoute="earnings"
      {...props}
    />
  );
}
export default connect((state) => ({ user: state.session.user }), null)(EarningsDailyList);
