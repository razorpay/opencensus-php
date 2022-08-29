import { connect } from 'react-redux';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import FeedbackDoughnut from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FeedbackRate/FeedbackDoughnut';
import {
  FEEDBACKRATE_FOOTER_TEXTS,
  FEEDBACKRATE_INFO_TEXTS,
} from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

const FeedbackRate = ({ feedbackRateData, isloading }) => {
  const { feedbackPercentage } =
    feedbackRateData && !Array.isArray(feedbackRateData[0])
      ? feedbackRateData[0]
      : { feedbackPercentage: 0 };
  const footerStatus =
    feedbackPercentage === 100 ? 'complete' : feedbackPercentage === 0 ? 'empty' : 'partial';

  const infoStatus = footerStatus === 'complete' ? 'complete' : 'incomplete';

  return (
    <div className="feedbackRate-container col-md-3">
      <GenericPanel className="feedbackRate-panel" isLoading={isloading}>
        <PanelTopbar>Order data availability</PanelTopbar>
        <PanelBody>
          <div className="feedbackRate-panel-info">{FEEDBACKRATE_INFO_TEXTS[infoStatus]}</div>
          <FeedbackDoughnut feedbackPercentage={feedbackPercentage} />
        </PanelBody>
        <PanelFooter className={`feedbackRate-${footerStatus}-info`}>
          {FEEDBACKRATE_FOOTER_TEXTS[footerStatus]}
        </PanelFooter>
      </GenericPanel>
    </div>
  );
};

const mapStateToProps = (state) => ({
  feedbackRateData: state.magicRTOAnalytics.feedback_rate.data,
  isloading: state.magicRTOAnalytics.feedback_rate.loading,
});

export default connect(mapStateToProps, null)(FeedbackRate);
