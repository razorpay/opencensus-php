import React, { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { AnyAction, bindActionCreators, Dispatch } from 'redux';
import { useNavigate } from 'react-router-dom';

import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import Graph from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/PreAndPostMagic/Graph';
import { Link } from '@razorpay/blade/components';

import { fetchWidgetData } from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';

import { preAndPostMagicFormatter } from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/PreAndPostMagic/utils';
import {
  getWidgetData,
  onRequestCountChange,
} from 'merchant/views/MagicCheckout/RTOAnalytics/utils';
import { getStartAndEndTime } from 'merchant/views/MagicCheckout/helper';

import { NO_GRAPH_DATA, BREAKDOWN } from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

import {
  StyledLegendWrapper,
  StyledNudgingMessage,
} from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/PreAndPostMagic/styled';

import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';
import { addFPVSupportToPath } from 'merchant/views/MagicCheckout/utils/Configuration';

import { MAGIC_DASHBOARD_REVAMP_EXPERIMENT } from 'merchant/views/MagicCheckout/constants';
import {
  RTO_HISTORY_UPLOAD_ROUTE,
  RTO_DELIVERY_DATA_UPLOAD_ROUTE,
} from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/constants';

type InitialDateType = {
  startTime: number;
  endTime: number;
};

const DEFAULT_DURATION = [-90, 'days'];
const widgetName = 'rto_rate';
const RTO_DATA_TIME_RANGE = 'Displaying data for the last 2 months.';

const PreAndPostMagic = ({ widgetData, fetchWidgets, fetchingTimedWidgetsData }): JSX.Element => {
  const [chartData, setChartData] = useState<Record<string, any> | null>(null);

  const breakdown: string = BREAKDOWN.months;
  const [requestCount, setRequestCount] = useState<number>(0);
  const navigate = useNavigate();
  const [isPreMagicRTORateAvailable, setIsPreMagicRTORateAvailable] = useState<boolean>(false);
  const [isPostMagicRTORateAvailable, setIsPostMagicRTORateAvailable] = useState<boolean>(false);

  const { data, loading } = widgetData;

  const { startTime, endTime }: InitialDateType = getStartAndEndTime(DEFAULT_DURATION);
  const fetchData = useCallback((): void => {
    const additionalInfo = {
      premagic_flag: true,
    };
    getWidgetData(
      widgetName,
      BREAKDOWN.months,
      startTime,
      endTime,
      fetchWidgets,
      setRequestCount,
      additionalInfo,
    );
  }, [fetchWidgets]);

  //updating states according to pre and post magic RTO data availability
  useEffect((): void => {
    if (data?.postmagic_rto_rate?.length > 0) {
      setIsPostMagicRTORateAvailable(true);
    }

    if (data?.premagic_rto_rate?.rto_rate > 0) {
      setIsPreMagicRTORateAvailable(true);
    }
  }, [data]);

  //updating the state which the pre and post magic RTO data
  useEffect((): void => {
    if (fetchingTimedWidgetsData) {
      setChartData(null);

      return;
    }

    if (isPreMagicRTORateAvailable && isPostMagicRTORateAvailable)
      setChartData(preAndPostMagicFormatter(data, breakdown, startTime, endTime));
  }, [
    fetchingTimedWidgetsData,
    data,
    widgetData,
    isPreMagicRTORateAvailable,
    isPostMagicRTORateAvailable,
  ]);

  //to fetch pre and post magic RTO data
  useEffect((): void => {
    if (startTime && endTime) {
      fetchData();
    }
  }, []);

  //retrying to fetch data if api call fails at first time
  useEffect((): void => {
    onRequestCountChange(requestCount, fetchData, setRequestCount);
  }, [requestCount]);

  const DeliveryDataUploadCTA = ({ subtitle, link }) => {
    return (
      <>
        <span>
          {subtitle}
          {useMagicExperiment(MAGIC_DASHBOARD_REVAMP_EXPERIMENT) && (
            <span>
              <Link onClick={() => navigate(addFPVSupportToPath(link))}>Click here</Link> to Upload
            </span>
          )}
        </span>
      </>
    );
  };

  const PreMagicDataUploadCTA = (
    <DeliveryDataUploadCTA
      subtitle={NO_GRAPH_DATA.preMagicSubtitle}
      link={RTO_HISTORY_UPLOAD_ROUTE}
    />
  );

  const PostMagicDataUploadCTA = (
    <DeliveryDataUploadCTA
      subtitle={NO_GRAPH_DATA.postMagicSubtitle}
      link={RTO_DELIVERY_DATA_UPLOAD_ROUTE}
    />
  );

  return (
    <div className="costSaved-container">
      <GenericPanel
        className="analytics-panel cost-saved"
        isLoading={loading}
        hasNoData={!isPostMagicRTORateAvailable || !isPreMagicRTORateAvailable}
      >
        <PanelTopbar>
          <div className="panel-info">
            <p className="panel-topbar-heading">RTO Rate Comparison</p>
          </div>
        </PanelTopbar>
        <PanelBody
          id="cost-saved-body"
          customTitle={NO_GRAPH_DATA.customTitle}
          customSubtitle={
            !isPreMagicRTORateAvailable ? PreMagicDataUploadCTA : PostMagicDataUploadCTA
          }
        >
          {isPreMagicRTORateAvailable && isPostMagicRTORateAvailable && !loading ? (
            <>
              {!isNaN(data.premagic_rto_rate.reduction_percentage) &&
              data.premagic_rto_rate.reduction_percentage > 0 ? (
                <StyledNudgingMessage>
                  <p className="message">
                    <span className="percentage">
                      {Math.abs(data.premagic_rto_rate.reduction_percentage)} %{' '}
                    </span>
                    Reduction in RTO rate post Magic.
                  </p>
                </StyledNudgingMessage>
              ) : null}
              <Graph key="pre-post-magic-rto-rate" breakdown={breakdown} data={chartData} />
              <StyledLegendWrapper>
                <div className="legend">
                  <div className="item-box">
                    <div className="colored risky" />
                    <span>Previous RTO rate</span>
                  </div>
                  <div className="item-box">
                    <div className="colored postmagic" />
                    <span>RTO rate with Magic Checkout</span>
                  </div>
                </div>
              </StyledLegendWrapper>
            </>
          ) : null}
        </PanelBody>
        <PanelFooter id="cost-saved-footer">
          <small>
            <i className="i i-info-circle" />
            &nbsp;
            <span>{RTO_DATA_TIME_RANGE}</span>
          </small>
        </PanelFooter>
      </GenericPanel>
    </div>
  );
};

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators({ fetchWidgets: fetchWidgetData }, dispatch);

const mapStateToProps = (state: Record<string, any>) => ({
  widgetData: state.magicRTOAnalytics.pre_vs_post_magic_rto_rate,
  fetchingTimedWidgetsData: state.magicRTOAnalytics.timedWidgetsFetching,
});

export default connect(mapStateToProps, mapDispatchToProps)(PreAndPostMagic);
