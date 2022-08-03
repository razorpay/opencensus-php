import FlaggedReasons from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FlaggedReasons';
import OrderSplit from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/OrderSplit';

const OverviewTab = () => {
  return (
    <div className="overview-container">
      <OrderSplit />
      <FlaggedReasons />
    </div>
  );
};

export default OverviewTab;
