import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import moment from 'moment';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { analyticsTrack } from 'common/utils/analytics';
import GenericPanel, { PanelTopbar, PanelBody } from 'merchant/components/Home/GenericPanel';
import TagGroup from './TagGroup';
import GraphIntervals from './GraphIntervals';
import ChartArea from './ChartArea';

import { updateGraphInterval, fetchBreakdownIntervals } from 'merchant/reducers/successRate';
import { breakdownInterval, chartStyle, defaultChartStyle } from '../constants';
import { queryFilters } from '../helper';
import { methodIntervalClick, methodTagsClick } from '../ga';

const initTagList = ['Overall'];

const GraphPanel = (props) => {
  const chartReference = React.useRef(null);
  const [tagList, setTagList] = React.useState(initTagList);
  const { isLoading, activeTab, startDate, endDate, tab = {} } = props;
  const { tags, selectedInterval, histogram, data, error, group_by } = tab;
  const { datasets } = histogram;
  const hasNoData = !histogram || datasets?.length === 0;

  useEffect(() => {
    return () => {
      setTagList(initTagList);
    };
  }, [isLoading]);

  const updateDatasets = (tag, position) => {
    if (chartReference?.current) {
      const chart = chartReference.current.chartInstance;
      const tagIndex = tags.indexOf(tag);
      const intervals =
        (tag === 'Overall'
          ? data?.intervals
          : data?.groups[group_by]?.find((obj) => obj.name === tag)?.intervals) ?? [];

      if (position > -1 && tagList.length > 1) {
        chart.data.datasets.splice(position, 1);
      } else if (position < 0) {
        const newDataset = {
          label: tag,
          data: intervals?.map((obj) => ({
            x: +moment.unix(obj?.from).format('x'),
            y: obj.sr,
          })),
          ...(chartStyle[tagIndex] ?? defaultChartStyle),
        };
        chart.data.datasets.push(newDataset);
      }
      chart.update();
    }
  };

  const handleTags = (tag) => {
    const tagsClone = [...tagList];
    const tagIndex = tagsClone.indexOf(tag);
    if (tagIndex > -1 && tagsClone.length > 1) tagsClone.splice(tagIndex, 1);
    else if (tagIndex < 0) tagsClone.push(tag);
    updateDatasets(tag, tagIndex);
    setTagList(tagsClone);

    analyticsTrack(methodTagsClick({ selectedTags: tagsClone }));
  };

  const handleBreakdown = (breakdown) => {
    if (breakdown === selectedInterval) return;
    const payload = queryFilters();
    payload.interval = breakdownInterval[breakdown];
    props.fetchBreakdownIntervals(breakdown, payload);
    setTagList(initTagList);

    analyticsTrack(methodIntervalClick({ breakdown }));
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
          selectedTags={tagList}
          groupBy={group_by}
          onSelect={handleTags}
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
  return bindActionCreators({ updateGraphInterval, fetchBreakdownIntervals }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(GraphPanel);
