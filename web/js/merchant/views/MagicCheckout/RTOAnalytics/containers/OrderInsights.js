import FeedbackRate from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FeedbackRate';
import RtoByGroup from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RtoBy';
import CostSaved from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/CostSaved';
import SafeOrders from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/SafeOrders';

const OrderInsightsTab = () => {
  return (
    <div className="orderInsight-container">
      <div className="row">
        <FeedbackRate />
        <CostSaved />
      </div>
      <SafeOrders />
      <RtoByGroup />
    </div>
  );
};

export default OrderInsightsTab;
