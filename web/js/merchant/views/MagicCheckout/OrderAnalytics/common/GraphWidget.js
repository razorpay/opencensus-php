import React, { useMemo } from 'react';
import GenericPanel, { PanelTopbar, PanelBody } from 'merchant/components/Home/GenericPanel';
// eslint-disable-next-line import/no-cycle
import Graph from 'merchant/views/MagicCheckout/OrderAnalytics/common/Graph';
import moment from 'moment';
import {
  AGGERGATE_OPERATION,
  NO_GRAPH_DATA,
} from 'merchant/views/MagicCheckout/OrderAnalytics/constants';
import {
  getChartDatasets,
  getAggregatedValue,
} from 'merchant/views/MagicCheckout/OrderAnalytics/utils';
import Shimmer from 'merchant/views/MagicCheckout/OrderAnalytics/widgets/Shimmer';

function GraphWidget({
  isFetching,
  label,
  subtext,
  aggregationType = AGGERGATE_OPERATION.SUM,
  aggregationUnit,
  data,
  isChartStacked,
  customOptions,
}) {
  const chartData = useMemo(() => {
    const datasets = data?.values
      ? getChartDatasets(data.values, label, isChartStacked, data.unit)
      : [];
    const labels = data?.timestamps?.map((t) => moment.unix(t).local()) || [];
    return {
      datasets,
      labels,
    };
  }, [data, isChartStacked, label]);

  return (
    <GenericPanel
      className="analytics-panel chart-item"
      isLoading={isFetching}
      hasNoData={!data?.values?.length}
    >
      <PanelTopbar>
        <div className="panel-info">
          <p className="panel-topbar-heading">{data?.title}</p>
          {aggregationUnit && data?.values.length && (
            <div>
              {isFetching ? (
                <Shimmer />
              ) : (
                <p className="panel-heading-aggregate-value">
                  {getAggregatedValue(data.values, aggregationUnit, aggregationType)}
                </p>
              )}
            </div>
          )}
          {subtext && <p className="panel-heading-subtext">{subtext}</p>}
        </div>
      </PanelTopbar>
      <PanelBody
        customTitle={NO_GRAPH_DATA.customTitle}
        customSubtitle={NO_GRAPH_DATA.customSubtitle}
      >
        {data?.values?.length > 0 && !isFetching ? (
          <Graph
            key="order-split"
            widgetName={label}
            data={chartData}
            isChartStacked={isChartStacked}
            customOptions={customOptions}
          />
        ) : null}
      </PanelBody>
    </GenericPanel>
  );
}

export default GraphWidget;
