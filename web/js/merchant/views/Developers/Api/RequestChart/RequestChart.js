import React from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import * as ApiStatsActions from 'merchant/reducers/developers/apiStats';
import Footer from '../../components/RequestChartFooter';
import RequestChartBody from './RequestChartBody';
import RequestChartToolbar, { aggregations } from './RequestChartToolbar';
import { trackApiChartDurationChanged, trackApiChartStatusChanged } from '../events';

function RequestChart(props) {
  const { selectedFilters, fetchStats, loading, data, error } = props;
  const { duration } = selectedFilters;
  const [selectedAggregation, setSelectedAggregation] = React.useState(aggregations[0]);
  const [filteredStatusCodeList, setFilteredStatusCodeList] = React.useState([]);
  // if we didn't get data or we got error inside data then no chart data is available
  const isDataNotAvailable = !data || !data?.stats?.length || error;

  React.useEffect(() => {
    fetchStats(selectedFilters);
    const aggregation = aggregations.find((aggr) =>
      aggr.isEnabled(selectedFilters.duration.from, selectedFilters.duration.to),
    );
    if (aggregation) {
      setSelectedAggregation(aggregation);
    }
  }, [selectedFilters.duration.from, selectedFilters.duration.to]);

  const handleAggregateChange = (aggregation) => {
    const newlySelectedAggregation = aggregations.find((agg) => agg.value === aggregation);
    if (newlySelectedAggregation) {
      setSelectedAggregation(newlySelectedAggregation);
    }
    trackApiChartDurationChanged(aggregation);
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
    trackApiChartStatusChanged();
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
      ...state.apiStats,
    }),
    {
      ...ApiStatsActions,
    },
  ),
)(RequestChart);
