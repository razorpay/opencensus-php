import { useEffect, useState, useCallback } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';

import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import FeedbackDoughnut from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FeedbackRate/FeedbackDoughnut';
import FeedbackFooterText from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FeedbackRate/components/FeedbackFooterText';
import LastUpdated from 'merchant/components/Home/LastUpdated';

import { fetchWidgetData } from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';

import {
  getWidgetData,
  onRequestCountChange,
} from 'merchant/views/MagicCheckout/RTOAnalytics/utils';
import { BREAKDOWN } from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

const FeedbackRate = ({
  feedbackRateData,
  isloading,
  updatedAt,
  fetchWidgets,
  shippingProviders,
  startTime,
  endTime,
}) => {
  const [requestCount, setRequestCount] = useState(0);

  const { feedback_percentage: feedbackPercentage } =
    feedbackRateData?.length && !Array.isArray(feedbackRateData[0])
      ? feedbackRateData[0]
      : { feedback_percentage: 0 };
  const infoStatus =
    feedbackPercentage === 100 ? 'complete' : feedbackPercentage === 0 ? 'empty' : 'partial';

  const fetchData = useCallback(() => {
    getWidgetData(
      'feedback_rate',
      BREAKDOWN.cumulative,
      startTime,
      endTime,
      fetchWidgets,
      setRequestCount,
    );
  }, [fetchWidgets, startTime, endTime]);

  useEffect(() => {
    if (fetchWidgets) {
      fetchData();
    }
  }, [fetchWidgets]);

  useEffect(() => {
    onRequestCountChange(requestCount, fetchData, setRequestCount);
  }, [requestCount]);

  return (
    <div className="feedbackRate-container col-md-3">
      <GenericPanel className="feedbackRate-panel" isLoading={isloading}>
        <PanelTopbar>
          <p className="panel-topbar-heading">Delivery data</p>
          <p className="panel-heading-subtext">Data received for orders shipped by you</p>
        </PanelTopbar>
        <PanelBody>
          <FeedbackDoughnut feedbackPercentage={feedbackPercentage} />
        </PanelBody>
        {!isloading ? (
          <PanelFooter
            className={`feedbackRate-${infoStatus}-info${
              feedbackPercentage >= 80 && feedbackPercentage !== 100 ? ' feedbackRate-footer' : ''
            }`}
          >
            {feedbackPercentage < 80 || feedbackPercentage === 100 ? (
              <FeedbackFooterText
                isShippingProviderAvailable={Object.keys(shippingProviders).length}
                footerStatus={infoStatus}
              />
            ) : (
              <LastUpdated at={updatedAt} customIcon="i-clock" />
            )}
          </PanelFooter>
        ) : null}
      </GenericPanel>
    </div>
  );
};

const mapStateToProps = (state) => ({
  feedbackRateData: state.magicRTOAnalytics.feedback_rate.data,
  isloading: state.magicRTOAnalytics.feedback_rate.loading,
  updatedAt: state.magicRTOAnalytics.feedback_rate.updatedAt,
  startTime: state.magicRTOAnalytics.startTime,
  endTime: state.magicRTOAnalytics.endTime,
  shippingProviders: state.shippingService.shippingProviders,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchWidgets: fetchWidgetData,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(FeedbackRate);
