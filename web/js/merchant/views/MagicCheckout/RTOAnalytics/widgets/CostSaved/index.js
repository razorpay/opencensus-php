import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import moment from 'moment';

import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import { Btn, BtnGroup } from 'common/ui/BtnGroup/index';
import Input from 'common/new-ui/Input';
import Graph from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/CostSaved/Graph';

import { fetchWidgetData } from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';

import { costSavedFormatter } from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/CostSaved/utils';
import {
  onBreakdownChange,
  getWidgetData,
  onRequestCountChange,
} from 'merchant/views/MagicCheckout/RTOAnalytics/utils';
import { getItem, setItem } from 'common/utils/localStorage';

import {
  BREAKDOWN_MAP,
  NO_GRAPH_DATA,
  DEFAULT_SHIPPING_CHARGE,
  BREAKDOWN,
  COST_SAVED_WIDGET_TEXTS,
} from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

const CostSaved = ({
  widgetData,
  startTime,
  endTime,
  fetchWidgets,
  fetchingTimedWidgetsData,
  isManualReviewOpted,
}) => {
  const [chartData, setChartData] = useState(null);
  const [breakdown, setBreakdown] = useState(BREAKDOWN.weeks);
  const [requestCount, setRequestCount] = useState(0);
  const [shippingCharge, setShippingCharge] = useState(
    getItem('magic-analytics-shipping-charge') || DEFAULT_SHIPPING_CHARGE,
  );

  const { data, loading, updatedAt } = widgetData;

  const widgetName = 'cost_saving';

  const onBtnChange = useCallback(
    (value) => {
      const additionalInfo = {
        [widgetName]: {
          shipping_charges: shippingCharge,
        },
      };
      onBreakdownChange(
        value,
        breakdown,
        startTime,
        endTime,
        fetchWidgets,
        setBreakdown,
        widgetName,
        additionalInfo,
      );
    },
    [breakdown, startTime, endTime, fetchWidgets, shippingCharge],
  );

  useEffect(() => {
    if (fetchingTimedWidgetsData) {
      setChartData(null);

      return;
    }

    setChartData(costSavedFormatter(data, breakdown, startTime, endTime));
  }, [fetchingTimedWidgetsData, data, breakdown, startTime, endTime, widgetData]);

  const fetchData = useCallback(() => {
    setBreakdown(BREAKDOWN.weeks);
    const additionalInfo = {
      [widgetName]: {
        shipping_charges: shippingCharge,
      },
      manual_flag: isManualReviewOpted,
    };
    getWidgetData(
      widgetName,
      BREAKDOWN.weeks,
      startTime,
      endTime,
      fetchWidgets,
      setRequestCount,
      additionalInfo,
    );
  }, [endTime, startTime, fetchWidgets, shippingCharge, isManualReviewOpted]);

  useEffect(() => {
    if (startTime && endTime) {
      fetchData();
    }
  }, [startTime, endTime]);

  useEffect(() => {
    onRequestCountChange(requestCount, fetchData, setRequestCount);
  }, [requestCount]);

  const updateShippingCharge = useCallback(
    (e) => {
      let val = e?.target?.value;
      if (!isNaN(parseInt(val, 10))) {
        val = parseInt(val, 10);
        setShippingCharge(val);
      }
    },
    [shippingCharge],
  );

  const fetchCostSaved = useCallback(() => {
    const endDate = moment(endTime).unix();
    const startDate = moment(startTime).add(5, 'hours').add(30, 'minutes').unix();
    setItem('magic-analytics-shipping-charge', shippingCharge);
    const additionalInfo = {
      [widgetName]: {
        shipping_charges: shippingCharge,
      },
      manual_flag: isManualReviewOpted,
    };
    fetchWidgets(widgetName, breakdown, startDate, endDate, additionalInfo);
  }, [shippingCharge, startTime, endTime, isManualReviewOpted]);

  return (
    <div className="costSaved-container">
      <GenericPanel
        className="analytics-panel cost-saved"
        isLoading={loading}
        hasNoData={!data || data.length === 0}
      >
        <PanelTopbar>
          <div className="panel-info">
            <p className="panel-topbar-heading">
              {isManualReviewOpted
                ? COST_SAVED_WIDGET_TEXTS.manualReview.header
                : COST_SAVED_WIDGET_TEXTS.intelligence.header}
            </p>
            <p className="panel-heading-subtext">
              {isManualReviewOpted
                ? COST_SAVED_WIDGET_TEXTS.manualReview.subtext
                : COST_SAVED_WIDGET_TEXTS.intelligence.subtext}
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
          id="cost-saved-body"
          customTitle={NO_GRAPH_DATA.customTitle}
          customSubtitle={NO_GRAPH_DATA.customSubtitle}
        >
          {data && data.length > 0 && !loading ? (
            <>
              <div className="shipping-charge-container">
                <Input
                  label="Cost saved is calculated based on your shipping charge"
                  className="Input--small"
                  defaultValue={shippingCharge}
                  addonBefore="₹"
                  onChange={updateShippingCharge}
                  type="number"
                  onBlur={fetchCostSaved}
                />
              </div>
              <Graph
                key="cost-saving"
                breakdown={breakdown}
                data={chartData}
                isManualReviewOpted={isManualReviewOpted}
              />
            </>
          ) : null}
        </PanelBody>
        <PanelFooter id="cost-saved-footer">
          <LastUpdated at={updatedAt} customIcon="i-clock" />
        </PanelFooter>
      </GenericPanel>
    </div>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ fetchWidgets: fetchWidgetData }, dispatch);

const mapStateToProps = (state) => ({
  widgetData: state.magicRTOAnalytics.cost_saving,
  startTime: state.magicRTOAnalytics.startTime,
  endTime: state.magicRTOAnalytics.endTime,
  fetchingTimedWidgetsData: state.magicRTOAnalytics.timedWidgetsFetching,
  isManualReviewOpted: state.magicCheckout.cod_order_control,
});

export default connect(mapStateToProps, mapDispatchToProps)(CostSaved);
