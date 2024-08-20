import React, { useEffect, useState, useCallback } from 'react';
import { NavLink } from 'react-router-dom';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import { Btn, BtnGroup } from 'common/ui/BtnGroup/index';
import Graph from 'merchant/views/MagicCheckout/RTOAnalytics/common/Graph';
import LastUpdated from 'merchant/components/Home/LastUpdated';

import { fetchWidgetData } from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';

import {
  chartsDataFormatter,
  onBreakdownChange,
  getWidgetData,
  onRequestCountChange,
} from 'merchant/views/MagicCheckout/RTOAnalytics/utils';

import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';

import { MAGIC_DASHBOARD_REVAMP_EXPERIMENT } from 'merchant/views/MagicCheckout/constants';
import {
  BREAKDOWN_MAP,
  MANUAL_REVIEW_ORDERS_SPLIT_CHARTS,
  NO_GRAPH_DATA,
  BREAKDOWN,
} from 'merchant/views/MagicCheckout/RTOAnalytics/constants';
import {
  COD_REVIEW_WORKFLOW_ROUTE_V2,
  COD_REVIEW_WORKFLOW_ROUTE,
  RTO_REDUCTION_SETUP_ROUTE_V2,
  RTO_REDUCTION_SETUP_ROUTE,
} from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/constants';

const ManualReviewOrderSplitChartOptions = {
  tooltips: {
    enabled: true,
    backgroundColor: '#ffffff',
    borderColor: '#e0e8f4',
    borderWidth: 1,
    bodySpacing: 12,
    position: 'average',
    bodyFontColor: '#262D3A',
    footerFontColor: '#8A91AC',
    xPadding: 12,
    yPadding: 12,
    cornerRadius: 2,
    footerMarginTop: 14,
    footerFontStyle: 'normal',
    titleMarginBottom: 12,
    titleFontColor: '#262D3A',
    callbacks: {
      title() {
        return `Total review rate %`;
      },
      label: (tooltipItem, { datasets }) => {
        const { datasetIndex, yLabel } = tooltipItem;
        const label = datasets[datasetIndex]?.label;

        return `${label}: ${yLabel}%`;
      },
      labelColor: (item, chart) => {
        const color =
          chart?.config?.data?.datasets[item.datasetIndex]?.borderColor ||
          chart?.config?.data?.datasets[item.datasetIndex]?.backgroundColor;
        return { backgroundColor: color, borderColor: 'transparent' };
      },
    },
  },
  layout: {
    padding: {
      top: 24,
      left: 0,
      right: 0,
      bottom: 0,
    },
  },
};

type ManualReviewOrderSplitProps = {
  widgetData: {
    loading: boolean;
    updatedAt: string;
    data: [];
  };
  startTime: number | null;
  endTime: number | null;
  fetchWidgets: () => void;
  fetchingTimedWidgetsData: boolean;
};

const ManualReviewOrderSplit = ({
  widgetData,
  startTime,
  endTime,
  fetchWidgets,
  fetchingTimedWidgetsData,
}: ManualReviewOrderSplitProps): JSX.Element => {
  const [breakdown, setBreakdown] = useState(BREAKDOWN.weeks);
  const [chartData, setChartData] = useState<Record<string, unknown> | null>(null);
  const [requestCount, setRequestCount] = useState(0);

  // eslint-disable-next-line @typescript-eslint/naming-convention
  const { data, loading, updatedAt } = widgetData;
  const widgetName = 'manual_review_order_split';

  const onBtnChange = useCallback(
    (value) => {
      onBreakdownChange(
        value,
        breakdown,
        startTime,
        endTime,
        fetchWidgets,
        setBreakdown,
        widgetName,
      );
    },
    [breakdown, startTime, endTime, fetchWidgets],
  );

  useEffect(() => {
    if (fetchingTimedWidgetsData) {
      setChartData(null);

      return;
    }

    setChartData(
      chartsDataFormatter(
        data,
        breakdown,
        startTime,
        endTime,
        MANUAL_REVIEW_ORDERS_SPLIT_CHARTS,
        true,
      ),
    );
  }, [fetchingTimedWidgetsData, data, breakdown, startTime, endTime, widgetData]);

  const fetchData = useCallback(() => {
    setBreakdown(BREAKDOWN.weeks);
    getWidgetData(widgetName, BREAKDOWN.weeks, startTime, endTime, fetchWidgets, setRequestCount);
  }, [endTime, startTime, fetchWidgets]);

  useEffect(() => {
    if (startTime && endTime) {
      fetchData();
    }
  }, [startTime, endTime]);

  useEffect(() => {
    onRequestCountChange(requestCount, fetchData, setRequestCount);
  }, [requestCount]);

  return (
    <GenericPanel
      className="analytics-panel manual-review-order-split"
      isLoading={loading}
      hasNoData={!data || data.length === 0}
    >
      <PanelTopbar>
        <div>
          <div className="panel-info">
            <p className="panel-topbar-heading">COD Order Review Report</p>
          </div>
          <div className="panel-actions pull-right">
            <BtnGroup
              className="panel-action-item time-breakdown"
              value={breakdown}
              onChange={onBtnChange}
            >
              {Object.keys(BREAKDOWN_MAP).map((breakdown) => (
                <Btn key={breakdown} value={breakdown} className="btn-default">
                  <span>{BREAKDOWN_MAP[breakdown].text}</span>
                </Btn>
              ))}
            </BtnGroup>
          </div>
        </div>
        <div className="manual-review-order-split-nudging-message">
          <p>
            Reduce your RTOs by enabling{' '}
            <NavLink
              to={
                useMagicExperiment(MAGIC_DASHBOARD_REVAMP_EXPERIMENT)
                  ? COD_REVIEW_WORKFLOW_ROUTE_V2
                  : COD_REVIEW_WORKFLOW_ROUTE
              }
              className="magic-link"
            >
              Automation
            </NavLink>{' '}
            or turn on{' '}
            <NavLink
              to={
                useMagicExperiment(MAGIC_DASHBOARD_REVAMP_EXPERIMENT)
                  ? RTO_REDUCTION_SETUP_ROUTE_V2
                  : RTO_REDUCTION_SETUP_ROUTE
              }
              className="magic-link"
            >
              COD Intelligence
            </NavLink>{' '}
            to auto block risky COD orders.
          </p>
        </div>
      </PanelTopbar>
      <PanelBody
        customTitle={NO_GRAPH_DATA.customTitle}
        customSubtitle={NO_GRAPH_DATA.customSubtitle}
      >
        {data && data.length > 0 && !loading ? (
          <Graph
            key="order-split"
            breakdown={breakdown}
            data={chartData}
            isChartStacked
            customOptions={ManualReviewOrderSplitChartOptions}
          />
        ) : null}
        <div className="legend">
          <div className="item-box">
            <div className="colored total" />
            <span>No action taken</span>
          </div>
          <div className="item-box">
            <div className="colored neutral" />
            <span>Order put on hold</span>
          </div>
          <div className="item-box">
            <div className="colored risky" />
            <span>Order cancelled</span>
          </div>
          <div className="item-box">
            <div className="colored safe" />
            <span>Order approved</span>
          </div>
        </div>
      </PanelBody>
      <PanelFooter>
        <LastUpdated at={updatedAt} customIcon="i-clock" />
      </PanelFooter>
    </GenericPanel>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ fetchWidgets: fetchWidgetData }, dispatch);

const mapStateToProps = (state) => ({
  widgetData: state.magicRTOAnalytics.manual_review_order_split,
  startTime: state.magicRTOAnalytics.startTime,
  endTime: state.magicRTOAnalytics.endTime,
  fetchingTimedWidgetsData: state.magicRTOAnalytics.timedWidgetsFetching,
});

export default connect(mapStateToProps, mapDispatchToProps)(ManualReviewOrderSplit);
