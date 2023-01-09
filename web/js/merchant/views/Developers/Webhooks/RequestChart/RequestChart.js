import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import * as WebhookStatsActions from 'merchant/reducers/developers/webhookStats';
import Footer from 'merchant/views/Developers/components/RequestChartFooter';
import RequestChartBody from './RequestChartBody';
import RequestChartToolbar, { aggregations } from './RequestChartToolbar';

function RequestChart({ selectedFilters, fetchStats, loading, data, error, webhookId }) {
  const { duration } = selectedFilters;
  const [selectedAggregation, setSelectedAggregation] = useState(aggregations[0]);
  const [filteredStatusCodeList, setFilteredStatusCodeList] = useState([]);
  const isDataNotAvailable = !data || !data?.stats?.length || error;

  useEffect(() => {
    fetchStats({ ...selectedFilters, webhookId });
    const aggregation = aggregations.find((aggr) =>
      aggr.isEnabled(selectedFilters.duration.from, selectedFilters.duration.to),
    );
    if (aggregation) {
      setSelectedAggregation(aggregation);
    }
  }, [selectedFilters.eventType, selectedFilters.duration.from, selectedFilters.duration.to]);

  const handleAggregateChange = (aggregation) => {
    const newlySelectedAggregation = aggregations.find((agg) => agg.value === aggregation);
    if (newlySelectedAggregation) {
      setSelectedAggregation(newlySelectedAggregation);
    }
  };

  const handleFilteredStatusCodeChange = (statusCode) => {
    let newFilteredStatusCodeList = [];
    const filteredCodeIndex = filteredStatusCodeList.findIndex(
      (filteredCode) => filteredCode === statusCode,
    );
    if (filteredCodeIndex !== -1) {
      newFilteredStatusCodeList = filteredStatusCodeList.filter(
        (filteredCode) => filteredCode !== statusCode,
      );
    } else {
      newFilteredStatusCodeList = [...filteredStatusCodeList, statusCode];
    }
    setFilteredStatusCodeList(newFilteredStatusCodeList);
  };

  return (
    <div className="request-chart">
      <RequestChartToolbar
        selectedFilters={selectedFilters}
        selectedAggregation={selectedAggregation}
        handleAggregateChange={handleAggregateChange}
        handleFilteredStatusCodeChange={handleFilteredStatusCodeChange}
        filteredStatusCodeList={filteredStatusCodeList}
        isDataNotAvailable={isDataNotAvailable}
      />
      <RequestChartBody
        data={{
          loading,
          data,
          error,
        }}
        selectedAggregation={selectedAggregation}
        duration={duration}
        filteredStatusCodeList={filteredStatusCodeList}
      />
      <div className="request-chart-footer">
        <Footer updatedAt={Number(duration.to)} />
      </div>
    </div>
  );
}

export default compose(
  connect(
    (state) => ({
      ...state.webhookStats,
    }),
    {
      ...WebhookStatsActions,
    },
  ),
)(RequestChart);
