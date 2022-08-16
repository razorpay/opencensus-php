import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import moment from 'moment';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import GenericPanel, { PanelTopbar, PanelBody } from 'merchant/components/Home/GenericPanel';
import TagGroup from './TagGroup';
import GraphIntervals from './GraphIntervals';
import ChartArea from './ChartArea';

import { updateGraphInterval } from 'merchant/reducers/successRate';
import { chartStyle, defaultLineStyle } from '../constants';

const GraphPanel = (props) => {
  const chartReference = React.useRef(null);
  const [tagsList, setTagsList] = React.useState(['Overall']);
  const { isLoading, activeTab, startDate, endDate, tab } = props;
  const { tags, selectedInterval, histogram, data, error, group_by } = tab;
  const { datasets } = histogram;
  const hasNoData = !histogram || datasets?.length === 0;

  const updateDatasets = (tag, position) => {
    if (chartReference?.current) {
      const chart = chartReference.current.chartInstance;
      const tagIndex = tags.indexOf(tag);
      const intervals =
        tag === 'Overall'
          ? data?.intervals
          : data?.groups[group_by].find((obj) => obj.name === tag)?.intervals;

      if (position > -1 && tagsList.length > 1) {
        chart.data.datasets.splice(position, 1);
      } else if (position < 0) {
        const newDataset = {
          label: tag,
          data: intervals.map((obj) => ({
            x: +moment.unix(obj.to).format('x'),
            y: obj.sr,
          })),
          ...(chartStyle[tagIndex] ?? defaultLineStyle),
        };
        chart.data.datasets.push(newDataset);
      }
      chart.update();
    }
  };

  const handleTags = (tag) => {
    setTagsList((prevState) => {
      const tagsClone = [...prevState];
      const tagIndex = tagsClone.indexOf(tag);
      if (tagIndex > -1 && tagsClone.length > 1) tagsClone.splice(tagIndex, 1);
      else if (tagIndex < 0) tagsClone.push(tag);
      updateDatasets(tag, tagIndex);
      return tagsClone;
    });
  };

  return (
    <GenericPanel
      className="box-widget graph-panel"
      isLoading={isLoading}
      hasNoData={hasNoData}
      error={error}
    >
      <PanelTopbar className="graph-panel__topbar">
        <TagGroup isLoading={isLoading} tags={tags} selectedTags={tagsList} onSelect={handleTags} />
        <div className="panel-actions">
          <GraphIntervals
            selected={selectedInterval}
            onChange={props.updateGraphInterval}
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
              <ChartArea
                ref={chartReference}
                startDate={startDate}
                interval={selectedInterval}
                histogram={histogram}
              />
            </ErrorBoundary>
          </div>
        </div>
      </PanelBody>
    </GenericPanel>
  );
};

const mapStateToProps = ({ successRate }) => {
  const { activeTab, filters, tabs } = successRate;
  return {
    activeTab,
    startDate: filters?.startDate,
    endDate: filters?.endDate,
    tab: tabs[activeTab],
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ updateGraphInterval }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(GraphPanel);
