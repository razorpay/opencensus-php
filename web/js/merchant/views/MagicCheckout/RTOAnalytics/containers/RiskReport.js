import OrderSplit from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/OrderSplit';
import RiskyOrders from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RiskyOrders';
import FlaggedReasons from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FlaggedReasons';
import RiskLevelOrderSplit from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RiskLevelOrderSplit';

const RiskReport = ({ isManualReviewOpted }) => {
  return (
    <div className="risk-report-container">
      {isManualReviewOpted ? (
        <>
          <RiskLevelOrderSplit />
          <FlaggedReasons />
        </>
      ) : (
        <>
          <OrderSplit />
          <FlaggedReasons />
          <RiskyOrders />
        </>
      )}
    </div>
  );
};

export default RiskReport;
