import React from 'react';
import GenericPanel, { PanelTopbar, PanelBody } from 'merchant/components/Home/GenericPanel';
// eslint-disable-next-line import/no-cycle
import { NO_GRAPH_DATA } from 'merchant/views/MagicCheckout/OrderAnalytics/constants';
import DataTable from 'common/ui/Table/DataTable';
import {
  total_gmv,
  product_name,
  product_qty,
} from 'merchant/views/MagicCheckout/OrderAnalytics/common/cellItem';

const TopSellingProducts = ({ isFetching, data }) => {
  const COLUMNS = [product_name, product_qty, total_gmv];
  return (
    <GenericPanel
      className="analytics-panel chart-item"
      isLoading={isFetching}
      hasNoData={!data?.values?.length}
    >
      <PanelTopbar className="magic-utm-panel-header">
        <div className="panel-info">
          <p className="panel-topbar-heading">{data?.title}</p>
          <p className="panel-heading-subtext">Products with highest GMV</p>
        </div>
      </PanelTopbar>
      <PanelBody
        customTitle={NO_GRAPH_DATA.customTitle}
        customSubtitle={NO_GRAPH_DATA.customSubtitle}
      >
        {data?.values?.length > 0 && !isFetching ? (
          <DataTable
            title="Top selling products"
            customClass="order-analytics-table"
            columns={COLUMNS}
            items={data.values}
          />
        ) : null}
      </PanelBody>
    </GenericPanel>
  );
};

export default TopSellingProducts;
