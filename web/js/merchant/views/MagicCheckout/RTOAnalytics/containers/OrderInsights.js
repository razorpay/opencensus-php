import RtoByGroup from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RtoBy';
import CostSaved from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/CostSaved';

const OrderInsightsTab = () => {
  return (
    <div className="orderInsight-container">
      <CostSaved />
      <RtoByGroup />
    </div>
  );
};

export default OrderInsightsTab;
