import Amount from 'rzp/ui/Amount';
import CommissionsDailyList from '../../Commissions/Daily/List';

const amountColumn = {
  title: 'Total Subvention',
  value: item => <Amount value={Math.abs(item.earnings)} currency={'INR'} />,
};

export default function SubventionsDailyList(props) {
  return (
    <CommissionsDailyList
      amountColumn={amountColumn}
      queryType="subvention_daily"
      {...props}
    />
  );
}
