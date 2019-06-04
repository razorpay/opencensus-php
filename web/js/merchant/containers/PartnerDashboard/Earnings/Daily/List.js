import CommissionsDailyList from '../../Commissions/Daily/List';

export default function EarningsDailyList(props) {
  return (
    <CommissionsDailyList
      amountTitle="Total Earnings"
      queryType="aggregate_daily"
      {...props}
    />
  );
}
