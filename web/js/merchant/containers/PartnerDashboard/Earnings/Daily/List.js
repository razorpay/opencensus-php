import Amount from 'rzp/ui/Amount';
import CommissionsDailyList from '../../Commissions/Daily/List';

const amountColumn = {
  title: 'Total Earnings',
  value: item => <Amount value={item.earnings} currency={'INR'} />,
};

export default function EarningsDailyList(props) {
  return (
    <CommissionsDailyList
      amountColumn={amountColumn}
      queryType="aggregate_daily"
      {...props}
    />
  );
}
