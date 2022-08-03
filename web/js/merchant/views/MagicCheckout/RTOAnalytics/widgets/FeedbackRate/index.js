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

const FeedbackRate = ({ feedbackRate_data, isloading }) => {
  const { feedback_percentage } = feedbackRate_data
    ? feedbackRate_data[0]
    : { feedback_percentage: 0 };
  const feedback_footer_status =
    feedback_percentage === 100 ? 'complete' : feedback_percentage === 0 ? 'empty' : 'partial';

  const feedback_info_status = feedback_footer_status === 'complete' ? 'complete' : 'incomplete';

  return (
    <div className="feedbackRate-container col-md-3">
      <GenericPanel className="feedbackRate-panel" isLoading={isloading}>
        <PanelTopbar>Order data availability</PanelTopbar>
        <PanelBody>
          <div className="feedbackRate-panel-info">
            {FEEDBACKRATE_INFO_TEXTS[feedback_info_status]}
          </div>
          <FeedbackDoughnut feedbackRateData={feedbackRate_data} />
        </PanelBody>
        <PanelFooter className={`feedbackRate-${feedback_footer_status}-info`}>
          {FEEDBACKRATE_FOOTER_TEXTS[feedback_footer_status]}
        </PanelFooter>
      </GenericPanel>
    </div>
  );
};

const mapStateToProps = (state) => ({
  feedbackRate_data: state.magicRTOAnalytics.feedback_rate.data,
  isloading: state.magicRTOAnalytics.feedback_rate.loading,
});

export default connect(mapStateToProps, null)(FeedbackRate);
