import CODPrepaidOrders from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/CODPrepaidOrders';
import FeedbackRate from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FeedbackRate';
import RTORate from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RTORate';
import ManualReviewOrderSplit from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/ManualReviewOrderSplit';
import PrepayInsignts from 'merchant/views/MagicCheckout/RTOAnalytics/common/PrepayInsights';
import PreAndPostMagic from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/PreAndPostMagic';

const OverviewTab = ({ isManualReviewOpted, isPrepayCODOpted }) => {
  return (
    <div className="overview-container">
      <RTORate />
      <div className="row">
        <FeedbackRate />
        <CODPrepaidOrders />
      </div>
      {isPrepayCODOpted ? <PrepayInsignts /> : null}
      <PreAndPostMagic />
      {isManualReviewOpted ? <ManualReviewOrderSplit /> : null}
    </div>
  );
};

export default OverviewTab;
