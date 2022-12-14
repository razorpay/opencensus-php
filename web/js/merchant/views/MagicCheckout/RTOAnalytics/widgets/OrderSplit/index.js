import { useEffect, useState, useCallback } from 'react';
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
  ORDERS_SPLIT_CHARTS,
  NO_GRAPH_DATA,
  BREAKDOWN,
} from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

const OrderSplitChartOptions = {
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
        const totalUsers = tooltipItem.reduce((count, cval) => {
          return count + cval.yLabel;
        }, 0);
        return `Total users: ${totalUsers}`;
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

const OrderSplit = ({
  user,
  widgetData,
  startTime,
  endTime,
  fetchWidgets,
  fetchingTimedWidgetsData,
}) => {
  const [breakdown, setBreakdown] = useState(BREAKDOWN.weeks);
  const [chartData, setChartData] = useState(null);
  const [requestCount, setRequestCount] = useState(0);

  const { data, loading, updatedAt } = widgetData;
  const widgetName = 'order_split';

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
      chartsDataFormatter(data, breakdown, startTime, endTime, ORDERS_SPLIT_CHARTS, true),
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
    onRequestCountChange(user, requestCount, fetchData, setRequestCount);
  }, [requestCount]);

  return (
    <GenericPanel
      className="analytics-panel order-split"
      isLoading={loading}
      hasNoData={!data || data.length === 0}
    >
      <PanelTopbar>
        <div className="panel-info">
          <p className="panel-topbar-heading">Safe vs risky users</p>
          <p className="panel-heading-subtext">
            Risky users are the ones for whom the COD option is disabled on the payments screen by
            Magic’s COD Intelligence.
          </p>
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
            customOptions={OrderSplitChartOptions}
          />
        ) : null}
        <div className="legend">
          <div className="item-box">
            <div className="colored risky" />
            <span>Risky users</span>
          </div>
          <div className="item-box">
            <div className="colored safe" />
            <span>Safe users</span>
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
  widgetData: state.magicRTOAnalytics.order_split,
  startTime: state.magicRTOAnalytics.startTime,
  endTime: state.magicRTOAnalytics.endTime,
  fetchingTimedWidgetsData: state.magicRTOAnalytics.timedWidgetsFetching,
  user: state.session.user,
});

export default connect(mapStateToProps, mapDispatchToProps)(OrderSplit);
