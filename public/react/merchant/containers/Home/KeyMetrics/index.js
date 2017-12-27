import React, { Component } from 'react';
import { connect } from 'react-redux';
import numeral from 'numeral';
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs';
import moment from 'moment';

import Amount from 'rzp/ui/Amount';
import {
  paiseToRupees,
  titleCase,
  shortenText,
  getFormattedAmount,
  getFormattedNumber,
} from 'rzp/utils/rzp-utils';
import { getTimelineData } from 'rzp/utils/chart/transformers';
import {
  humanReadableIndian,
  humanReadableIndianCurrency,
} from 'rzp/utils/numerals';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';

import { fetch } from 'merchant/modules/pokedex';
import { tabsOrder, tabsMeta, getQuery, breakdownVals } from './data';
import Panel from './Panel';

const csvDateFormat = 'DD-MM-YYYY';

const TabContent = ({ name, value, isCurrency, title, isLoading }) => {
  /*
   * Description:
   * Component responsible for rendering content in each Tab
   */

  let formattedTitle =
      '₹ ' +
      (isCurrency ? getFormattedAmount(value) : getFormattedNumber(value)),
    formattedValue = (value = isCurrency ? paiseToRupees(value) : value);

  if (value >= 1000) {
    // formatting number, eg. 1200 as 1.2 k , decimal part is optional
    formattedValue = (isCurrency
      ? humanReadableIndianCurrency
      : humanReadableIndian)(value);
  }

  /*
   * checks if the current tab is showing currency values and renders
   * content in the tab
   *
   */
  return (
    <a title={formattedTitle}>
      <div>
        <h1>{!isLoading ? formattedValue : <PlaceholderLoader />}</h1>
        <span>{!isLoading ? title : <PlaceholderLoader />}</span>
      </div>
    </a>
  );
};

@connect(state => {
  return {
    ...state.session,
  };
})
class KeyMetricsContainer extends Component {
  constructor(props) {
    super(props);

    // TODO: move state to redux
    this.state = {
      selectedTab: tabsOrder[0],
      tabsState: {},

      // indicated nothing is loaded including the tab content
      // this will be false after initial fetch
      loading: true,
    };

    // Populating default value
    tabsOrder.forEach(tabName => {
      const grouping = tabsMeta[tabName].grouping,
        // assigment on R.H.S is intentional, puts value and declares
        // variable at the same time
        tabState = (this.state.tabsState[tabName] = {});

      if (grouping.length > 0) {
        // default grouping selected in each tab
        tabState.selectedGrouping = grouping[0].value;
      }

      tabState.selectedBreakdown = breakdownVals[0];

      tabState.data = {
        loading: false,

        // fetchData should be made true whenever the new data need to be
        // pulled
        fetchData: true,

        // timeline data will be stored here
        histogram: [],

        // main stat of the tab is stored here
        count: 0,

        // calculated legend info is stored here
        legendData: [],
      };
    });

    this.onGroupingChange = ::this.onGroupingChange;
    this.onBreakdownChange = ::this.onBreakdownChange;
    this.handleTabChange = ::this.handleTabChange;
  }

  fetchData(fetchAllCounts) {
    /*
	 * Fetches data , if `fetchAllCounts` is true, fetches all tabs stats
	 * and the selected tab's graph data, when ever the tab is
	 * switched, latest data including stat for the selected tab is fetched
	 */

    const isInitialLoad = this.state.loading;

    const { tabsState, selectedTab } = this.state,
      tabState = tabsState[selectedTab],
      { startDate, endDate } = this.props;

    const query = getQuery({
      merchantId: '10000000000000',
      tabName: fetchAllCounts ? 'all' : selectedTab,
      breakdown: tabState.selectedBreakdown,
      startTime: startDate.unix(),
      endTime: endDate.unix(),
      groupBy: tabState.selectedGrouping,
      fetchHistogramForTab: selectedTab,
    });

    tabState.data.loading = true;

    this.setState({ tabsState });

    return fetch(query).then(resp => {
      tabsOrder.forEach(tabName => {
        const tabState = tabsState[tabName],
          { selectedBreakdown } = tabState,
          tabMeta = tabsMeta[tabName],
          { isCurrency, title } = tabMeta;

        // Main stat showin in the tab
        const mainStat = resp.data[tabName];

        if (mainStat && mainStat.result[0]) {
          const value = mainStat.result[0].value;

          tabState.data.count = mainStat.result[0].value;
        }

        // Timeline data
        const histogram = resp.data[`${tabName}Histogram`];
        if (histogram) {
          const { labels, datasets, aggregates, csv } = getTimelineData({
            data: histogram.result,
            groupByColumnName: tabState.selectedGrouping,
            groupTitleMap: {},
            valueTransformer: isCurrency && paiseToRupees,
          });

          // \u05C0 is pipe like character, as pipes are being converted
          // into underscores by browser
          const downloadFileName = titleCase(
            shortenText(
              `${selectedBreakdown} ${title} \u05C0 ${startDate.format(
                csvDateFormat
              )} - ${endDate.format(
                csvDateFormat
              )} \u05C0 ${tabMeta.getGroupTitle(tabState.selectedGrouping)}`
            )
          );

          tabState.data.histogram = { labels, datasets };
          tabState.lastUpdatedAt = histogram.last_updated_at;
          tabState.data.legendData = aggregates;
          tabState.data.csv = {
            name: `${downloadFileName}.csv`,
            url: csv,
          };
        }
      });

      if (isInitialLoad) {
        this.state.loading = false;
      }

      tabState.data.loading = false;
      tabState.data.fetchData = false;

      this.setState(this.state);
    });
  }

  componentWillMount() {
    this.fetchData(true);
  }

  handleTabChange(tabName) {
    this.setState(
      {
        selectedTab: tabName,
      },
      () => {
        const { data } = this.state.tabsState[tabName];

        // fetchData depends on state, so calling it after state update,
        // this func gets new data only when the tab data is not loading and
        // fetchData is true
        return !data.loading && data.fetchData && this.fetchData();
      }
    );
  }

  onGroupingChange(tabName, selectedGrouping) {
    const { tabsState } = this.state,
      tabState = tabsState[tabName];

    tabState.selectedGrouping = selectedGrouping;

    this.setState({ tabsState }, () => {
      this.fetchData();
    });
  }

  clearCache(tabsState) {
    // hint to fetch new data
    tabsOrder.forEach(tabName => {
      tabsState[tabName].data.fetchData = true;
    });
  }

  onBreakdownChange(tabName, selectedBreakdown) {
    const { tabsState } = this.state;

    tabsState[tabName].selectedBreakdown = selectedBreakdown;

    this.setState({ tabsState }, () => {
      this.fetchData();
    });
  }

  componentWillReceiveProps(nextProps) {
    const { startDate, endDate } = nextProps;

    if (
      startDate.toDate() !== this.props.startDate.toDate() ||
      endDate.toDate() !== this.props.endDate.toDate
    ) {
      const { tabsState } = this.state;

      // when switched tabs, new data should be fetched as the global
      // daterange changed
      this.clearCache(tabsState);

      this.setState({ tabsState }, () => {
        return this.fetchData(true);
      });
    }
  }

  render() {
    const { tabsState, loading } = this.state,
      { startDate, endDate } = this.props;

    return (
      <Tabs>
        <TabList
          className="nav nav-tabs nav-justified"
          activeTabClassName="active"
          disabledTabClassName="disabled"
        >
          {tabsOrder.map((tabName, index) => {
            const tabData = tabsState[tabName].data,
              { isCurrency, title } = tabsMeta[tabName];

            return (
              <Tab key={index} onClick={() => this.handleTabChange(tabName)}>
                <TabContent
                  value={tabData.count}
                  name={tabName}
                  isCurrency={isCurrency}
                  title={title}
                  isLoading={loading}
                />
              </Tab>
            );
          })}
        </TabList>

        {tabsOrder.map((tabName, index) => {
          const tabState = tabsState[tabName],
            { isCurrency } = tabsMeta[tabName];

          return (
            <TabPanel key={index}>
              <Panel
                tabName={tabName}
                selectedBreakdown={tabState.selectedBreakdown}
                onBreakdownChange={this.onBreakdownChange}
                selectedGrouping={tabState.selectedGrouping}
                onGroupingChange={this.onGroupingChange}
                data={tabsState[tabName].data}
                startDate={startDate}
                endDate={endDate}
                lastUpdatedAt={tabsState[tabName].lastUpdatedAt}
                isCurrency={isCurrency}
              />
            </TabPanel>
          );
        })}
      </Tabs>
    );
  }
}

export default KeyMetricsContainer;
