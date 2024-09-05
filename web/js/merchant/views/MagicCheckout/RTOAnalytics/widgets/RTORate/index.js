import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { NavLink } from 'react-router-dom';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import Input from 'common/new-ui/Input';
import { Btn, BtnGroup } from 'common/ui/BtnGroup/index';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import Graph from 'merchant/views/MagicCheckout/RTOAnalytics/common/Graph';

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
  RTO_RATE_CHARTS,
  NO_GRAPH_DATA,
  RTO_RATE_FILTER,
  BREAKDOWN,
} from 'merchant/views/MagicCheckout/RTOAnalytics/constants';
import {
  RTO_REDUCTION_SETUP_ROUTE_V2,
  RTO_REDUCTION_SETUP_ROUTE,
} from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/constants';

const RTORateChartOptions = {
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

        return `${label}: ${yLabel}%`;
      },
      labelColor: (item, chart) => {
        const color = chart?.config?.data?.datasets[item.datasetIndex]?.borderColor;
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

const RTORate = ({
  widgetData,
  startTime,
  endTime,
  fetchWidgets,
  fetchingTimedWidgetsData,
  isManualReviewOpted,
}) => {
  const [breakdown, setBreakdown] = useState(BREAKDOWN.weeks);
  const [selectedDropDown, setSelectedDropDown] = useState('COD_RTO_RATE');
  const [requestCount, setRequestCount] = useState(0);
  const [chartData, setChartData] = useState(null);
  const isMagicDashboardV2Enabled = useMagicExperiment(MAGIC_DASHBOARD_REVAMP_EXPERIMENT);

  const { data, loading, updatedAt } = widgetData;

  const widgetName = 'rto_rate';

  const onBtnChange = useCallback(
    (value) => {
      setSelectedDropDown('COD_RTO_RATE');
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
        RTO_RATE_CHARTS,
        false,
        widgetName,
        selectedDropDown,
      ),
    );
  }, [fetchingTimedWidgetsData, data, breakdown, startTime, endTime, widgetData, selectedDropDown]);

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

  const changeRTORateGraph = useCallback(
    (e) => {
      setSelectedDropDown(e?.target?.value);
    },
    [setSelectedDropDown],
  );

  return (
    <GenericPanel
      className="analytics-panel rto-rate"
      isLoading={loading}
      hasNoData={!data || data.length === 0}
    >
      <PanelTopbar>
        <div className="display-flex justify-space-between">
          RTO rate with Magic Checkout
          <div className="panel-actions pull-right">
            <Input.Select
              name="rtoRateView"
              options={RTO_RATE_FILTER}
              value={selectedDropDown}
              onChange={changeRTORateGraph}
              className="rto-rate-input"
            />
            <BtnGroup
              className="panel-action-item time-breakdown"
              value={breakdown}
              onChange={onBtnChange}
            >
              {Object.keys(BREAKDOWN_MAP)
                .slice(1)
                .map((breakdown) => (
                  <Btn key={breakdown} value={breakdown} className="btn-default">
                    <span>{BREAKDOWN_MAP[breakdown].text}</span>
                  </Btn>
                ))}
            </BtnGroup>
          </div>
        </div>
        {isManualReviewOpted ? (
          <div className="rto-rate-nudging-message">
            <p>
              Enable{' '}
              <NavLink
                to={
                  isMagicDashboardV2Enabled
                    ? RTO_REDUCTION_SETUP_ROUTE_V2
                    : RTO_REDUCTION_SETUP_ROUTE
                }
                className="magic-link"
              >
                COD Intelligence
              </NavLink>{' '}
              and reduce RTOs by an additional 10% without any manual actions.
            </p>
          </div>
        ) : null}
      </PanelTopbar>
      <PanelBody
        customTitle={NO_GRAPH_DATA.customTitle}
        customSubtitle={NO_GRAPH_DATA.customSubtitle}
      >
        {data && data.length > 0 && !loading ? (
          <Graph
            widgetName={widgetName}
            breakdown={breakdown}
            data={chartData}
            customOptions={RTORateChartOptions}
          />
        ) : null}
        <div className="legend">
          {selectedDropDown === 'COD_RTO_RATE' || selectedDropDown === 'ALL_RTO_RATES' ? (
            <div className="item-box">
              <div className="colored safe" />
              <span>COD RTO%</span>
            </div>
          ) : null}
          {selectedDropDown === 'PREPAID_RTO_RATE' || selectedDropDown === 'ALL_RTO_RATES' ? (
            <div className="item-box">
              <div className="colored neutral" />
              <span>Prepaid RTO%</span>
            </div>
          ) : null}
          {selectedDropDown === 'TOTAL_RTO_RATE' || selectedDropDown === 'ALL_RTO_RATES' ? (
            <div className="item-box">
              <div className="colored total" />
              <span>Overall RTO%</span>
            </div>
          ) : null}
        </div>
      </PanelBody>
      <PanelFooter id="rto-rate-footer">
        <LastUpdated at={updatedAt} customIcon="i-clock" />
        {data && data.length ? (
          <small className="reimbursement-callout pull-right">
            <i className="i i-info-circle" />
            <p>Reimbursement will be provided for COD RTOs if opted for Magic reimbursement plan</p>
          </small>
        ) : null}
      </PanelFooter>
    </GenericPanel>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ fetchWidgets: fetchWidgetData }, dispatch);

const mapStateToProps = (state) => ({
  widgetData: state.magicRTOAnalytics.rto_rate,
  isLoading: state.magicRTOAnalytics.rto_rate.loading,
  startTime: state.magicRTOAnalytics.startTime,
  endTime: state.magicRTOAnalytics.endTime,
  fetchingTimedWidgetsData: state.magicRTOAnalytics.timedWidgetsFetching,
  isManualReviewOpted: state.magicCheckout.cod_order_control,
});

export default connect(mapStateToProps, mapDispatchToProps)(RTORate);
