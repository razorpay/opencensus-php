import React, { useState, useCallback, useMemo } from 'react';
import GenericPanel, { PanelTopbar, PanelBody } from 'merchant/components/Home/GenericPanel';
import Input from 'common/new-ui/Input';
// eslint-disable-next-line import/no-cycle
import {
  NO_GRAPH_DATA,
  UTM_TABLE_COLUMN_MAP,
  UTM_OPTIONS,
  LIMIT,
} from 'merchant/views/MagicCheckout/OrderAnalytics/constants';
import {
  aggregateUTMData,
  getOrderPercentage,
} from 'merchant/views/MagicCheckout/OrderAnalytics/utils';
import DataTable from 'common/ui/Table/DataTable';
import Pager from 'merchant/views/MagicCheckout/RTOAnalytics/common/Pager';

const TrafficByUTM = ({ isFetching, data }) => {
  const [selectedOption, setSelectedOption] = useState(UTM_OPTIONS[0].name);
  const [currPage, setCurrPage] = useState(1);
  const tableData = useMemo(() => {
    if (data?.utm_source) {
      const rawData = aggregateUTMData(data.utm_source);
      const sortedData = {};
      Object.keys(rawData).forEach((key) => {
        sortedData[key] = rawData[key].sort(
          (first, second) => getOrderPercentage(second) - getOrderPercentage(first),
        );
      });
      return sortedData;
    }
    return {};
  }, [data]);
  // get total pages for currently selected medium
  const totalPages = Math.ceil(tableData?.[selectedOption]?.length / LIMIT);
  // get rows that would be visible for currently selected medium & page
  const currentRows = tableData?.[selectedOption]?.slice(LIMIT * (currPage - 1), LIMIT * currPage);

  const onPrev = () => {
    setCurrPage((prevPageNumber) => prevPageNumber - 1);
  };

  const onNext = () => {
    setCurrPage((prevPageNumber) => prevPageNumber + 1);
  };

  const changeTableView = useCallback(
    (e) => {
      setSelectedOption(e?.target?.value);
      setCurrPage(1);
    },
    [setSelectedOption],
  );

  return (
    <GenericPanel
      className="analytics-panel chart-item"
      isLoading={isFetching}
      hasNoData={!data?.utm_source?.length}
    >
      <PanelTopbar className="magic-utm-panel-header">
        <div className="panel-info">
          <p className="panel-topbar-heading">{data?.title}</p>
          <p className="panel-heading-subtext">
            Sources, mediums, campaings to track you data better
          </p>
        </div>
        <div className="panel-select">
          <p className="select-text-label">Select</p>
          <Input.Select
            name="tableView"
            options={UTM_OPTIONS}
            value={selectedOption}
            onChange={changeTableView}
            className="magic-utm-table-select"
          />
        </div>
      </PanelTopbar>
      <PanelBody
        customTitle={NO_GRAPH_DATA.customTitle}
        customSubtitle={NO_GRAPH_DATA.customSubtitle}
      >
        {tableData?.[selectedOption] && !isFetching ? (
          <div className="order-analytics-table-wrapper">
            <DataTable
              title="UTM Data"
              customClass="order-analytics-table"
              columns={UTM_TABLE_COLUMN_MAP[selectedOption]}
              items={currentRows}
            />
            <Pager
              prevLabel="Previous"
              nextLabel="Next"
              current={currPage}
              totalPages={totalPages}
              onNext={onNext}
              onPrev={onPrev}
            />
          </div>
        ) : null}
      </PanelBody>
    </GenericPanel>
  );
};

export default TrafficByUTM;
