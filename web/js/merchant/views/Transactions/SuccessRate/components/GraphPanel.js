import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import GenericPanel, { PanelTopbar, PanelBody } from 'merchant/components/Home/GenericPanel';
import TagGroup from './TagGroup';
import GraphIntervals from './GraphIntervals';
import ChartArea from './ChartArea';

import { fetchBreakdownIntervals, updateSelectedTags } from 'merchant/reducers/successRate';
import { breakdownInterval, chartStyle, defaultChartStyle } from '../constants';
import { queryFilters, generateDatasets, getIntervals } from '../helper';
import { methodIntervalClick, methodTagsClick, trackSuccessRateEvents } from '../trackEvents';

const GraphPanel = (props) => {
  const chartReference = React.useRef(null);
  const { isLoading, activeTab, startDate, endDate, tab = {} } = props;
  const { selectedTags, tags, selectedInterval, histogram, data, error, group_by } = tab;
  const { datasets } = histogram;
  const hasNoData = !histogram || datasets?.length === 0;

  const updateDatasets = (tag, position) => {
    if (chartReference?.current) {
      const chart = chartReference.current.chartInstance;
      const tagIndex = tags.indexOf(tag);
      const intervals = getIntervals({
        tag,
        data,
        group_by,
        activeTab,
      });

      if (position > -1 && selectedTags.length > 1) {
        chart.data.datasets.splice(position, 1);
      } else if (position < 0) {
        const newDataset = {
          label: tag,
          data: generateDatasets(intervals),
          ...(chartStyle[tagIndex] ?? defaultChartStyle),
        };
        chart.data.datasets.push(newDataset);
      }
      chart.update();
    }
  };

  const handleTags = (tag) => {
    const tagsClone = [...selectedTags];
    const tagIndex = tagsClone.indexOf(tag);
    if (tagIndex > -1 && tagsClone.length > 1) tagsClone.splice(tagIndex, 1);
    else if (tagIndex < 0) tagsClone.push(tag);
    props.updateSelectedTags(tagsClone);
    updateDatasets(tag, tagIndex);
    trackSuccessRateEvents(methodTagsClick({ selectedTags: tagsClone }));
  };

  const handleBreakdown = (breakdown) => {
    if (breakdown === selectedInterval) return;
    const payload = queryFilters();
    payload.interval = breakdownInterval[breakdown];
    props.fetchBreakdownIntervals(breakdown, payload);
    trackSuccessRateEvents(methodIntervalClick({ breakdown }));
  };

  return (
    <GenericPanel
      className="box-widget graph-panel"
      isLoading={isLoading}
      hasNoData={hasNoData}
      error={error}
    >
      <PanelTopbar className="graph-panel__topbar">
        <TagGroup
          isLoading={isLoading}
          tags={tags}
          selectedTags={selectedTags}
          groupBy={group_by}
          onSelect={handleTags}
          activeTab={activeTab}
        />
        <div className="panel-actions">
          <GraphIntervals
            selected={selectedInterval}
            onChange={handleBreakdown}
            activeTab={activeTab}
            startDate={startDate}
            endDate={endDate}
          />
        </div>
      </PanelTopbar>
      <PanelBody>
        <div className="panel-body-content">
          <div className="chart-container">
            <ErrorBoundary
              FallbackComponent={() => (
                <button disabled className="Button">
                  There was an issue, please try later!
                </button>
              )}
              resetOnProps
            >
              <ChartArea ref={chartReference} interval={selectedInterval} histogram={histogram} />
            </ErrorBoundary>
          </div>
        </div>
      </PanelBody>
    </GenericPanel>
  );
};

const mapStateToProps = ({ successRate }) => {
  const { isLoading, tabLoading, graphLoading, activeTab, filters, tabs } = successRate;
  return {
    isLoading: isLoading || tabLoading || graphLoading,
    activeTab,
    startDate: filters?.startDate,
    endDate: filters?.endDate,
    tab: tabs[activeTab],
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ fetchBreakdownIntervals, updateSelectedTags }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(GraphPanel);
