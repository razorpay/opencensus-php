import React, { useCallback, useEffect, useState } from 'react';
import { get } from 'lodash';
import Input from 'common/new-ui/Input';
import {
  CHART_LABEL_MAPPING,
  METRIC_TYPE,
  AGGERGATE_OPERATION,
  RATE_TYPES,
  RATE_FILTER,
} from 'merchant/views/MagicCheckout/OrderAnalytics/constants';
import GraphWidget from 'merchant/views/MagicCheckout/OrderAnalytics/common/GraphWidget';
import { customTooltip } from 'merchant/views/MagicCheckout/OrderAnalytics/utils';
import { useOrderAnalyticsContext } from 'merchant/views/MagicCheckout/OrderAnalytics/OrderAnalyticsContext';
import { RateTypes } from 'merchant/views/MagicCheckout/OrderAnalytics/constants/types';
import { ConversionRateWrapper } from './styled';

const CHART_OPTIONS = {
  scales: {
    yAxes: [
      {
        ticks: {
          callback: (value) => {
            return `${value}%`;
          },
        },
      },
    ],
  },
  tooltips: {
    custom: function custom(tooltipModel) {
      customTooltip(tooltipModel, this, CHART_LABEL_MAPPING.CONVERSION_RATE);
    },
  },
};

const ConversionRate = () => {
  const { isFetching, analyticsData } = useOrderAnalyticsContext();

  const [rateType, setRateType] = useState<RateTypes>(RATE_TYPES.RATE);
  const [data, setData] = useState<any>(get(analyticsData, `metrics[${RATE_TYPES.RATE}]`));

  const showDropdown =
    analyticsData?.metrics &&
    analyticsData.metrics.hasOwnProperty('conversion_rate_logged_in') &&
    analyticsData.metrics.hasOwnProperty('conversion_rate_logged_out');

  useEffect(() => {
    if (get(analyticsData, `metrics[${rateType}]`)) {
      setData(get(analyticsData, `metrics[${rateType}]`));
    }
  }, [analyticsData?.metrics, rateType]);

  const handleRateChange = useCallback(
    (e) => {
      setRateType(RATE_TYPES[e?.target?.value]);
    },
    [setRateType],
  );

  return (
    <ConversionRateWrapper>
      <GraphWidget
        data={data}
        isFetching={isFetching}
        aggregationUnit={METRIC_TYPE.PERCENTAGE}
        aggregationType={AGGERGATE_OPERATION.AVERAGE}
        label={CHART_LABEL_MAPPING.CONVERSION_RATE}
        customOptions={CHART_OPTIONS}
        className="conversion-chart"
      />
      {showDropdown && (
        <Input.Select
          name="conversionRateFilter"
          options={RATE_FILTER}
          value={RATE_TYPES[rateType]}
          onChange={handleRateChange}
          className="conversion-rate-dropdown"
          data-testid="conversion-rate-dropdown"
        />
      )}
    </ConversionRateWrapper>
  );
};

export default ConversionRate;
