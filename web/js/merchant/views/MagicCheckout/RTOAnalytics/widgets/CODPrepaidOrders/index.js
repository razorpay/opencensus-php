import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import { fetchWidgetData } from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';
import { Btn, BtnGroup } from 'common/ui/BtnGroup/index';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import Graph from 'merchant/views/MagicCheckout/RTOAnalytics/common/Graph';

import {
  chartsDataFormatter,
  onBreakdownChange,
  getWidgetData,
  onRequestCountChange,
} from 'merchant/views/MagicCheckout/RTOAnalytics/utils';

import {
  BREAKDOWN_MAP,
  COD_RATE_CHARTS,
  NO_GRAPH_DATA,
  BREAKDOWN,
} from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

const CODPrepaidOrdersChartOptions = {
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
      title() {},
      label: (tooltipItem, { datasets }) => {
        const { datasetIndex, yLabel } = tooltipItem;
        const label = datasets[datasetIndex]?.label;

        return `${label}           ${yLabel}%`;
      },
      labelColor: (item, chart) => {
        const color =
          chart?.config?.data?.datasets[item.datasetIndex]?.borderColor ||
          chart?.config?.data?.datasets[item.datasetIndex]?.backgroundColor;
        return { backgroundColor: color, borderColor: 'transparent' };
      },
    },
  },
  scales: {
    xAxes: [
      {
        type: 'time',
        distribution: 'series',
        time: {
          displayFormats: {
            hour: 'MMM D',
            month: 'MMM YYYY',
            day: 'MMM D',
            week: 'MMM YYYY',
            second: 'MMM D',
            millisecond: 'MMM D',
          },
          tooltipFormat: 'ddd DD MMM YYYY',
        },
        gridLines: {
          offsetGridLines: true,
          display: true,
          drawOnChartArea: false,
          drawTicks: true,
        },
        ticks: {
          autoSkip: true,
          fontSize: 12,
          fontColor: '#858C9A',
          maxRotation: 0,
          autoSkipPadding: 15,
        },
      },
    ],
    yAxes: [
      {
        ticks: {
          beginAtZero: true,
          padding: 10,
          fontSize: 12,
          maxTicksLimit: 5,
          fontColor: '#858C9A',
          callback: (value) => {
            return `${value}%`;
          },
        },
        gridLines: {
          color: '#F1F3F6',
          zeroLineColor: '#E0E8F4',
          display: true,
          drawTicks: false,
          drawBorder: false,
        },
      },
    ],
  },
};

const CODPrepaidOrders = ({
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
  const widgetName = 'cod_rate';

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
      chartsDataFormatter(data, breakdown, startTime, endTime, COD_RATE_CHARTS, false, widgetName),
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
      className="analytics-panel cod-rate col-md-9"
      isLoading={loading}
      hasNoData={!data || data.length === 0}
    >
      <PanelTopbar>
        Share of COD
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
            key="cod-prepaid-orders"
            breakdown={breakdown}
            data={chartData}
            customOptions={CODPrepaidOrdersChartOptions}
          />
        ) : null}
        <div className="legend">
          <div className="item-box">
            <div className="colored safe" />
            <span>COD Orders</span>
          </div>
        </div>
      </PanelBody>
      <PanelFooter id="safe-orders-footer">
        <LastUpdated at={updatedAt} customIcon="i-clock" />
      </PanelFooter>
    </GenericPanel>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ fetchWidgets: fetchWidgetData }, dispatch);

const mapStateToProps = (state) => ({
  widgetData: state.magicRTOAnalytics.cod_rate,
  isLoading: state.magicRTOAnalytics.cod_rate.loading,
  startTime: state.magicRTOAnalytics.startTime,
  endTime: state.magicRTOAnalytics.endTime,
  fetchingTimedWidgetsData: state.magicRTOAnalytics.timedWidgetsFetching,
});

export default connect(mapStateToProps, mapDispatchToProps)(CODPrepaidOrders);
