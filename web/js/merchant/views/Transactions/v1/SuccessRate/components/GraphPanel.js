import React, { useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { useSplitzService } from 'common/splitz';
import GenericTooltip from 'common/ui/Tooltip';
import fileDownload from 'common/utils/file-download';
import { arrayObjToCsv } from 'common/utils/rzp-utils';
import GenericPanel, { PanelTopbar, PanelBody } from 'merchant/components/Home/GenericPanel';
import { fetchBreakdownIntervals, updateSelectedTags } from 'merchant/reducers/successRate';
import {
  breakdownInterval,
  chartStyle,
  defaultChartStyle,
  SR_X,
  SR_Y,
  DOWNTIME_X,
  DOWNTIME_Y,
} from 'merchant/views/Transactions/v1/SuccessRate/constants';
import {
  queryFilters,
  generateDatasets,
  getIntervals,
  getTagLabelWithOverallTag,
  generateDowntimeDataSets,
  getNoDataTitle,
  getNoDataSubTitle,
  reportSR,
} from 'merchant/views/Transactions/v1/SuccessRate/helper';
import {
  trackSuccessRateEvents,
  methodIntervalClick,
  methodTagsClick,
  downloadSRGraphReport,
} from 'merchant/views/Transactions/v1/SuccessRate/trackEvents';

import ChartArea from './ChartArea';
import GraphIntervals from './GraphIntervals';
import TagGroup from './TagGroup';

const GraphPanel = (props) => {
  const chartReference = React.useRef(null);
  const splitz = useSplitzService();
  const { isLoading, activeTab, startDate, endDate, tab = {} } = props;
  const {
    selectedTags,
    tags,
    selectedInterval,
    histogram,
    data,
    error,
    group_by,
    downtimes: { resolved, ongoing },
  } = tab;
  const { datasets } = histogram;
  const hasNoData = !histogram || datasets?.length === 0;

  /**
   * @param 'tag' is the tag name string not tag object
   * @param 'position' is the index of "checked tag" from selected tag list
   */

  const updateDatasets = (tag, position) => {
    if (chartReference?.current) {
      const chart = chartReference.current.chartInstance;
      // 'index' of actual tags list
      const tagIndex = tags.findIndex(({ name }) => name === tag);

      if (position > -1 && selectedTags.length > 1) {
        // Filter out the dataset having tagname that is unselected from the ui.
        const updatedDatasets = chart?.data?.datasets?.filter(({ tagName }) => tagName !== tag);

        chart.data.datasets = updatedDatasets;
      } else if (position < 0) {
        const intervals = getIntervals({
          tag,
          data,
          group_by,
          activeTab,
        });
        const filterOngoingDowntimes = ongoing.filter(
          ({ method }) => method === activeTab.toLowerCase(),
        );

        const newDataset = [
          {
            label: getTagLabelWithOverallTag({ tag, activeTab, groupBy: group_by }),
            data: generateDatasets(intervals),
            ...(chartStyle[tagIndex] ?? defaultChartStyle),
            xAxisID: SR_X,
            yAxisID: SR_Y,
            tagName: tag,
          },
          {
            label: getTagLabelWithOverallTag({ tag, activeTab, groupBy: group_by }),
            data: generateDowntimeDataSets({
              intervals: [...resolved, ...filterOngoingDowntimes],
              tag: tags[tagIndex],
              activeTab,
              startTime: +startDate.format('X'),
              endTime: +endDate.format('X'),
              groupBy: group_by,
            }),
            ...(chartStyle[tagIndex] ?? defaultChartStyle),
            xAxisID: DOWNTIME_X,
            yAxisID: DOWNTIME_Y,
            tagName: tag,
            type: 'scatter',
          },
        ];

        chart.data.datasets.push(...newDataset);
      }
      chart.update();
    }
  };

  /**
   * @param 'tag' is the tag name string not tag object
   */

  const handleTags = (tag) => {
    const tagsClone = [...selectedTags];
    const tagIndex = tagsClone.findIndex(({ name }) => name === tag);
    if (tagIndex > -1 && tagsClone.length > 1) tagsClone.splice(tagIndex, 1);
    else if (tagIndex < 0) {
      const newTag = tags.find(({ name }) => name === tag);
      tagsClone.push(newTag);
    }
    props.updateSelectedTags(tagsClone);
    updateDatasets(tag, tagIndex);
    trackSuccessRateEvents(methodTagsClick({ selectedTags: tagsClone }), splitz);
  };

  const handleBreakdown = (breakdown) => {
    if (breakdown === selectedInterval) return;
    const updateDropdownOptions = activeTab !== 'Overall';
    const payload = queryFilters(updateDropdownOptions);
    payload.interval = breakdownInterval[breakdown];
    props.fetchBreakdownIntervals(breakdown, payload);
    trackSuccessRateEvents(methodIntervalClick({ breakdown }), splitz);
  };

  const handleDownload = useCallback(() => {
    if (!hasNoData) {
      const res = reportSR(tab?.histogram?.datasets);
      const csvData = arrayObjToCsv(res);
      fileDownload(csvData, `SR_${tab?.name}_Report.csv`);
      trackSuccessRateEvents(
        downloadSRGraphReport({ fileName: `SR_${tab?.name}_Report.csv` }),
        splitz,
      );
    }
  }, [hasNoData, tab?.name, tab?.histogram?.datasets]);

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
          <button
            className="btn btn-default download-btn"
            onClick={handleDownload}
            disabled={isLoading || hasNoData}
            type="button"
          >
            <i className="i i-download" />
            <GenericTooltip align="top">Download the SR Report</GenericTooltip>
          </button>
        </div>
      </PanelTopbar>
      <PanelBody customTitle={getNoDataTitle(tab)} customSubtitle={getNoDataSubTitle(tab)}>
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
