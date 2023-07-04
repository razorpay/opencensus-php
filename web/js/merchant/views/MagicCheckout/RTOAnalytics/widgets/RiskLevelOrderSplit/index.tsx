import React, { useEffect, useState, useCallback } from 'react';
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

import {
  BREAKDOWN_MAP,
  RISK_LEVEL_ORDERS_SPLIT_CHARTS,
  NO_GRAPH_DATA,
  BREAKDOWN,
} from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

const RiskLevelOrderSplitChartOptions = {
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
      title(tooltipItem) {
        const totalOrders = tooltipItem.reduce((count, cval) => {
          return count + cval.yLabel;
        }, 0);
        return `Total orders: ${totalOrders}`;
      },
      labelColor: (item, chart) => {
        const color =
          chart?.config?.data?.datasets[item.datasetIndex]?.borderColor ||
          chart?.config?.data?.datasets[item.datasetIndex]?.backgroundColor;
        return { backgroundColor: color, borderColor: 'transparent' };
      },
    },
  },
};

type RiskLevelOrderSplitPropType = {
  widgetData: {
    data: Record<string, unknown>[];
    loading: boolean;
    updatedAt: number;
  };
  startTime: number;
  endTime: number;
  fetchWidgets: () => void;
  fetchingTimedWidgetsData: boolean;
};

const RiskLevelOrderSplit = ({
  widgetData,
  startTime,
  endTime,
  fetchWidgets,
  fetchingTimedWidgetsData,
}: RiskLevelOrderSplitPropType): JSX.Element => {
  const [breakdown, setBreakdown] = useState(BREAKDOWN.weeks);
  const [chartData, setChartData] = useState(null);
  const [requestCount, setRequestCount] = useState(0);

  // eslint-disable-next-line @typescript-eslint/naming-convention
  const { data, loading, updatedAt } = widgetData;
  const widgetName = 'manual_risk_order_split';

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
        RISK_LEVEL_ORDERS_SPLIT_CHARTS,
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
      className="analytics-panel risk-level-order-split"
      isLoading={loading}
      hasNoData={!data || data.length === 0}
    >
      <PanelTopbar className="display-flex justify-space-between">
        <div className="panel-info">
          <p className="panel-topbar-heading">RTO risk distribution</p>
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
            customOptions={RiskLevelOrderSplitChartOptions}
          />
        ) : null}
        <div className="legend">
          <div className="item-box">
            <div className="colored safe" />
            <span>Low risk orders</span>
          </div>
          <div className="item-box">
            <div className="colored neutral" />
            <span>Medium risk orders</span>
          </div>
          <div className="item-box">
            <div className="colored risky" />
            <span>High risk orders</span>
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
  widgetData: state.magicRTOAnalytics.manual_risk_order_split,
  startTime: state.magicRTOAnalytics.startTime,
  endTime: state.magicRTOAnalytics.endTime,
  fetchingTimedWidgetsData: state.magicRTOAnalytics.timedWidgetsFetching,
});

export default connect(mapStateToProps, mapDispatchToProps)(RiskLevelOrderSplit);
