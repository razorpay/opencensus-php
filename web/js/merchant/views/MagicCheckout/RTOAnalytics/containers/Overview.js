import CODPrepaidOrders from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/CODPrepaidOrders';
import FeedbackRate from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FeedbackRate';
import RTORate from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RTORate';
import ManualReviewOrderSplit from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/ManualReviewOrderSplit';

const OverviewTab = ({ isManualReviewOpted }) => {
  return (
    <div className="overview-container">
      <RTORate />
      <div className="row">
        <FeedbackRate />
        <CODPrepaidOrders />
      </div>
      {isManualReviewOpted ? <ManualReviewOrderSplit /> : null}
    </div>
  );
};

export default OverviewTab;
