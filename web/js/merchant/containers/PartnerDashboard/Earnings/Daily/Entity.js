import CommissionDailyEntity from '../../Commissions/Daily/Entity';

export default function EarningsDailyEntity(props) {
  return <CommissionDailyEntity queryType="aggregate_detail" {...props} />;
}
