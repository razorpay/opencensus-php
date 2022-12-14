import CODPrepaidOrders from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/CODPrepaidOrders';
import FeedbackRate from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FeedbackRate';
import RTORate from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RTORate';
import OrderSplit from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/OrderSplit';
import FlaggedReasons from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FlaggedReasons';

const OverviewTab = ({ user }) => {
  return (
    <div className="overview-container">
      {user.isMagicRTOAnalyticsV2Enabled ? (
        <>
          <RTORate />
          <div className="row">
            <FeedbackRate />
            <CODPrepaidOrders />
          </div>
        </>
      ) : (
        <>
          <OrderSplit />
          <FlaggedReasons />
        </>
      )}
    </div>
  );
};

export default OverviewTab;
