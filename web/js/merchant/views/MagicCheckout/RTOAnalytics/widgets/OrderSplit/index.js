import { useEffect, useState, useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import { fetchWidgetData } from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';
import { Btn, BtnGroup } from 'common/ui/BtnGroup/index';
import Graph from 'merchant/views/MagicCheckout/RTOAnalytics/common/Graph';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import {
  chartsDataFormatter,
  onBreakdownChange,
} from 'merchant/views/MagicCheckout/RTOAnalytics/utils';
import {
  BREAKDOWN_MAP,
  ORDERS_SPLIT_CHARTS,
  NO_GRAPH_DATA,
} from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

const OrderSplit = ({ widgetData, startTime, endTime, fetch, fetchingTimedWidgetsData }) => {
  const [breakdown, setBreakdown] = useState('weekly');
  const [chartData, setChartData] = useState(null);
  const { data, loading, updatedAt } = widgetData;
  const widgetName = 'order_split';

  const onBtnChange = useCallback(
    (value) => {
      onBreakdownChange(value, breakdown, startTime, endTime, fetch, setBreakdown, widgetName);
    },
    [breakdown, startTime, endTime, fetch],
  );

  useEffect(() => {
    if (fetchingTimedWidgetsData) {
      setBreakdown('weekly');
      setChartData(null);

      return;
    }

    setChartData(chartsDataFormatter(data, breakdown, startTime, endTime, ORDERS_SPLIT_CHARTS));
  }, [fetchingTimedWidgetsData, data, breakdown, startTime, endTime, widgetData]);

  return (
    <GenericPanel
      className="analytics-panel order-split"
      isLoading={loading}
      hasNoData={!data || data.length === 0}
    >
      <PanelTopbar>
        Orders over time
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
          <Graph key="order-split" breakdown={breakdown} data={chartData} />
        ) : null}
        <div className="legend">
          <div className="item-box">
            <div className="colored total" />
            <span>Total Orders</span>
          </div>
          <div className="item-box">
            <div className="colored risky" />
            <span>Risky Orders</span>
          </div>
          <div className="item-box">
            <div className="colored safe" />
            <span>Safe Orders</span>
          </div>
        </div>
      </PanelBody>
      <PanelFooter>
        <LastUpdated at={updatedAt} customIcon="i-clock" />
      </PanelFooter>
    </GenericPanel>
  );
};

const mapDispatchToProps = (dispatch) => bindActionCreators({ fetch: fetchWidgetData }, dispatch);

const mapStateToProps = (state) => ({
  widgetData: state.magicRTOAnalytics.order_split,
  startTime: state.magicRTOAnalytics.startTime,
  endTime: state.magicRTOAnalytics.endTime,
  fetchingTimedWidgetsData: state.magicRTOAnalytics.timedWidgetsFetching,
});

export default connect(mapStateToProps, mapDispatchToProps)(OrderSplit);
