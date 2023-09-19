import React, { useState, useEffect, useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch, AnyAction } from 'redux';

import { getWidgetData } from 'merchant/views/MagicCheckout/RTOAnalytics/utils';
import { i18HumanReadableCurrency } from 'common/utils/numerals';

import {
  fetchWidgetData,
  setTimeRange,
} from 'merchant/reducers/magicCheckout/rtoAnalytics/actions';

import { REQUEST_LIMIT, BREAKDOWN } from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

import {
  PrepayInsightsHeader,
  Partition,
} from 'merchant/views/MagicCheckout/RTOAnalytics/common/styled';

type PrepayInsightsPropTypes = {
  startTime: number;
  endTime: number;
  fetchWidgets: () => any;
  data: {
    prepay_percentage?: number;
    total_discount?: number;
    total_order_amount?: number;
  };
};
const PrepayInsights = ({
  startTime,
  endTime,
  fetchWidgets,
  data,
}: PrepayInsightsPropTypes): JSX.Element => {
  const widgetName = 'prepay_order';

  const [requestCount, setRequestCount] = useState<number>(0);

  const fetchData = useCallback((): void => {
    getWidgetData(
      widgetName,
      BREAKDOWN.cumulative,
      startTime,
      endTime,
      fetchWidgets,
      setRequestCount,
    );
  }, [endTime, startTime, fetchWidgets]);

  useEffect((): void => {
    if (startTime && endTime) {
      fetchData();
    }
  }, [startTime, endTime]);

  useEffect((): void => {
    if (requestCount > 0 && requestCount <= REQUEST_LIMIT) {
      fetchData();
    } else if (requestCount > REQUEST_LIMIT) {
      setRequestCount(0);
    }
  }, [requestCount]);

  return (
    <PrepayInsightsHeader>
      <div className="cumulative">
        <div className="content-container widget-header">
          <span className="heading">COD to Prepaid Conversion</span>
        </div>
        <Partition />
        <div className="content-container">
          <div>
            <span className="total-color" />
            <span className="title total">% of COD orders converted to Prepaid</span>
          </div>
          <div className="content-value">
            <span className="number">
              {data && data[0]?.prepay_percentage ? `${data[0]?.prepay_percentage} %` : '--'}
            </span>
          </div>
        </div>
        <Partition />
        <div className="content-container">
          <div className="content-label">
            <span className="medium-color" />
            <span className="title maedium">Total discount provided to customers</span>
          </div>
          <div className="content-value">
            <span className="number">
              {data && data[0]?.total_discount
                ? i18HumanReadableCurrency(data[0]?.total_discount / 100, 'INR')
                : '--'}
            </span>
          </div>
        </div>
        <Partition />
        <div className="content-container">
          <div className="content-label">
            <span className="safe-color" />
            <span className="title safe">Additional Prepaid GMV acquired via conversion</span>
          </div>
          <div className="content-value">
            <span className="number">
              {data && data[0]?.total_order_amount
                ? i18HumanReadableCurrency(data[0]?.total_order_amount / 100, 'INR')
                : '--'}
            </span>
          </div>
        </div>
      </div>
    </PrepayInsightsHeader>
  );
};

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators(
    {
      setTimeRange,
      fetchWidgets: fetchWidgetData,
    },
    dispatch,
  );

const mapStateToProps = (state: Record<string, any>) => ({
  startTime: state.magicRTOAnalytics.startTime,
  endTime: state.magicRTOAnalytics.endTime,
  data: state.magicRTOAnalytics.prepay_order.data,
});

export default connect(mapStateToProps, mapDispatchToProps)(PrepayInsights);
