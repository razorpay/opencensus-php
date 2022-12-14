import RtoByGroup from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RtoBy';
import CostSaved from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/CostSaved';
import FeedbackRate from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FeedbackRate';

const OrderInsightsTab = ({ user }) => {
  return (
    <div className="orderInsight-container">
      {user.isMagicRTOAnalyticsV2Enabled ? (
        <>
          <CostSaved />
          <RtoByGroup />
        </>
      ) : (
        <>
          <div className="row experimentation-row-container">
            <FeedbackRate />
            <CostSaved />
          </div>
          <RtoByGroup />
        </>
      )}
    </div>
  );
};

export default OrderInsightsTab;
