import React from 'react';
import GenericPanel, { PanelTopbar, PanelBody } from 'merchant/components/Home/GenericPanel';
import { useOrderAnalyticsContext } from 'merchant/views/MagicCheckout/OrderAnalytics/OrderAnalyticsContext';
import FunnelChart from './FunnelChart';
import { buildFunnelChartData } from './utils';
import { NO_GRAPH_DATA } from 'merchant/views/MagicCheckout/OrderAnalytics/constants';

const ConversionFunnel = (): JSX.Element => {
  const { isFetching, analyticsData } = useOrderAnalyticsContext();
  const data = buildFunnelChartData(analyticsData?.metrics?.conversion_funnel);
  return (
    <GenericPanel
      className="analytics-panel chart-item conversion-chart"
      isLoading={isFetching}
      hasNoData={data && !Object.keys(data)?.length}
    >
      <PanelTopbar>
        <div className="panel-info">
          <p className="panel-topbar-heading">Conversion Funnel</p>
        </div>
      </PanelTopbar>
      <PanelBody
        customTitle={NO_GRAPH_DATA.customTitle}
        customSubtitle={NO_GRAPH_DATA.customSubtitle}
      >
        {data && Object.keys(data)?.length > 0 && !isFetching ? <FunnelChart data={data} /> : null}
      </PanelBody>
    </GenericPanel>
  );
};

export default ConversionFunnel;
