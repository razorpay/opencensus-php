import OrderSplit from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/OrderSplit';
import RiskyOrders from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RiskyOrders';
import FlaggedReasons from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FlaggedReasons';

const RiskReport = () => {
  return (
    <div className="risk-report-container">
      <OrderSplit />
      <FlaggedReasons />
      <RiskyOrders />
    </div>
  );
};

export default RiskReport;
