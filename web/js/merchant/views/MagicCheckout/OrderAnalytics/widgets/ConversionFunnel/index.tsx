import React, { useCallback, useEffect, useState } from 'react';
import GenericPanel, { PanelTopbar, PanelBody } from 'merchant/components/Home/GenericPanel';
import { useOrderAnalyticsContext } from 'merchant/views/MagicCheckout/OrderAnalytics/OrderAnalyticsContext';
import FunnelChart from './FunnelChart';
import { buildFunnelChartData } from './utils';
import { NO_GRAPH_DATA } from 'merchant/views/MagicCheckout/OrderAnalytics/constants';
import { FUNNEL_FILTER, FUNNEL_TYPES } from './constants';
import Input from 'common/new-ui/Input';
import { get } from 'lodash';
import { FunnelTypes } from 'merchant/views/MagicCheckout/OrderAnalytics/widgets/ConversionFunnel/types';
import { ConversionFunnelHeaderWrapper } from './styled';

const ConversionFunnel = (): JSX.Element => {
  const { isFetching, analyticsData } = useOrderAnalyticsContext();

  const [data, setData] = useState<any>(null);
  const [funnelType, setFunnelType] = useState<FunnelTypes>(FUNNEL_TYPES.FUNNEL);

  const showDropdown =
    analyticsData?.metrics &&
    analyticsData.metrics.hasOwnProperty('conversion_funnel_logged_in') &&
    analyticsData.metrics.hasOwnProperty('conversion_funnel_logged_out');

  useEffect(() => {
    if (get(analyticsData, `metrics[${funnelType}]`))
      setData(buildFunnelChartData(analyticsData.metrics[funnelType]));
  }, [analyticsData?.metrics, funnelType]);

  const handleFunnelChange = useCallback(
    (e) => {
      setFunnelType(FUNNEL_TYPES[e?.target?.value]);
    },
    [setFunnelType],
  );

  return (
    <GenericPanel
      className="analytics-panel chart-item conversion-chart"
      isLoading={isFetching}
      hasNoData={data && !Object.keys(data)?.length}
    >
      <PanelTopbar>
        <div className="panel-info">
          <div className="panel-topbar-heading">
            <ConversionFunnelHeaderWrapper>
              <div>Conversion Funnel</div>
              {showDropdown && (
                <Input.Select
                  name="conversionFunnelFilter"
                  options={FUNNEL_FILTER}
                  value={FUNNEL_TYPES[funnelType]}
                  onChange={handleFunnelChange}
                  className="conversion-funnel-dropdown"
                  data-testid="conversion-funnel-dropdown"
                />
              )}
            </ConversionFunnelHeaderWrapper>
          </div>
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
