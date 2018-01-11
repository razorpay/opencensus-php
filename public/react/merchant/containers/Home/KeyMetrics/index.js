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
import {
  humanReadableIndian,
  humanReadableIndianCurrency,
} from 'rzp/utils/numerals';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';

import { fetch } from 'merchant/modules/pokedex';
import {
  tabsOrder,
  tabsMeta,
  getQuery,
  breakdownVals,
  getTimelineData,
} from './data';
import Panel from './Panel';

import './styles.styl';

const csvDateFormat = 'DD-MM-YYYY';

const TabContent = ({ name, value, isCurrency, title, isLoading }) => {
  /*
   * Description:
   * Component responsible for rendering content in each Tab
   */

  let formattedTitle = isCurrency
      ? '₹' + getFormattedAmount(value)
      : getFormattedNumber(value),
    formattedValue = (value = isCurrency ? paiseToRupees(value) : value);

  formattedValue = (isCurrency
    ? humanReadableIndianCurrency
    : humanReadableIndian)(value);

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
        tabState.selectedGrouping = grouping[0];
      }

      tabState.selectedBreakdown = breakdownVals[0];

      tabState.data = {
        loading: false,

        // fetchData should be made true whenever the new data need to be
        // pulled
        fetchData: true,

        // timeline data will be stored here
        histogram: null,

        // main stat of the tab is stored here
        count: 0,

        // calculated legend info is stored here
        legendData: [],

        // trend data
        trend: {
          loading: true,
          previousCount: 0,
          currentCount: 0,
          startDate: null,
          endDate: null,
          show: true,
        },
      };
    });

    this.onGroupingChange = ::this.onGroupingChange;
    this.onBreakdownChange = ::this.onBreakdownChange;
    this.handleTabChange = ::this.handleTabChange;
    this.onScreenshot = ::this.onScreenshot;
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
      { selectedGrouping } = tabState,
      { startDate, endDate } = this.props;

    const query = getQuery({
      merchantId: '10000000000000',
      tabName: fetchAllCounts ? 'all' : selectedTab,
      breakdown: tabState.selectedBreakdown,
      startTime: startDate.unix(),
      endTime: endDate.unix(),
      groupBy: selectedGrouping ? selectedGrouping.value : '',
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

        if (mainStat) {
          tabState.data.count = mainStat.result[0]
            ? mainStat.result[0].value
            : 0;
        }

        // Timeline data
        const histogram = resp.data[`${tabName}Histogram`];
        if (histogram) {
          const { labels, datasets, aggregates, csv } = getTimelineData({
            data: histogram.result,
            groupByColumnName:
              tabMeta.groupByColumnName || selectedGrouping.value,
            startTime: startDate.unix(),
            endTime: endDate.unix(),
            breakdown: tabState.selectedBreakdown,
            groupTitleMap: tabMeta.groupTitleMap || { Mobile: 'mWeb' },
            valueTransformer: isCurrency && paiseToRupees,
          });

          const downloadFileName = `${title}, ${startDate.format(
            csvDateFormat
          )} to ${endDate.format(csvDateFormat)}, ${titleCase(
            selectedBreakdown
          )}${selectedGrouping ? ' ' + selectedGrouping.text : ''}(Razorpay)`;

          // display point only when there is only one point to plot
          if (labels.length === 1) {
            datasets.forEach(dataset => {
              dataset.pointRadius = 3;
              dataset.pointHoverRadius = 4;
            });
          }

          tabState.data.downloadFileName = downloadFileName;
          tabState.data.histogram = { labels, datasets };
          tabState.lastUpdatedAt = histogram.last_updated_at;
          tabState.data.legendData = aggregates;
          tabState.data.csv = {
            name: `${downloadFileName}.csv`,
            url: csv,
          };
          tabState.data.png = {
            name: `${downloadFileName}.png`,
            url: '',
          };
        }
      });

      if (isInitialLoad) {
        this.state.loading = false;
      }

      tabState.data.loading = false;
      tabState.data.fetchData = false;

      this.setState(this.state, () => {
        const { tabsState, selectedTab } = this.state,
          tabState = tabsState[selectedTab];

        this.setState(this.state);
      });
    });
  }

  onScreenshot(tabName, url, cb) {
    const tabState = this.state.tabsState[tabName];

    tabState.data.png = {
      name: `${tabState.data.downloadFileName}.png`,
      url: url,
    };

    this.setState(this.state, cb);
  }

  fetchPrevData(fetchAllReq, oldestTransactionDate) {
    const { tabsState } = this.state;

    oldestTransactionDate =
      oldestTransactionDate || this.props.oldestTransactionDate;

    const { startDate, endDate, value } = oldestTransactionDate;

    if (
      oldestTransactionDate.loading ||
      !oldestTransactionDate.value ||
      startDate.unix() < oldestTransactionDate.value
    ) {
      tabsOrder.forEach(tabName => {
        tabsState[tabName].data.trend.show = false;
      });

      this.setState({
        tabsState: { ...tabsState },
      });

      return Promise.resolve();
    }

    tabsOrder.forEach(tabName => {
      const { trend } = tabsState[tabName].data;

      trend.loading = true;
      trend.startDate = startDate;
      trend.endDate = endDate;
      trend.show = true;
    });

    this.setState({ tabsState: { ...tabsState } });

    const query = getQuery({
      merchantId: '10000000000000',
      tabName: 'all',
      startTime: startDate.unix(),
      endTime: endDate.unix(),
      countsOnly: true,
    });

    return fetch(query)
      .then(data => {
        if (!data.success) {
          return { error: ' ' };
        }

        return data.data;
      })
      .catch(() => {
        return { error: ' ' };
      })
      .then(data => {
        if (!data.error) {
          fetchAllReq.then(() => {
            tabsOrder.forEach(tabName => {
              const tabState = tabsState[tabName],
                { trend } = tabState.data,
                previousCount = data[tabName].result[0]
                  ? data[tabName].result[0].value
                  : 0,
                currentCount = tabState.data.count;

              trend.loading = false;
              trend.previousCount = previousCount;
              trend.currentCount = currentCount;
            });

            this.setState({
              tabsState: { ...tabsState },
            });
          });

          return;
        }

        tabsOrder.forEach(tabName => {
          tabsState[tabName].trend.loading = false;
        });

        this.setState({
          tabsState: { ...tabsState },
        });
      });
  }

  componentWillMount() {
    const fetchAllReq = (this.fetchAllReq = this.fetchData(true));

    if (this.props.oldestTransactionDate.value) {
      this.fetchPrevData(fetchReq);
    }
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
    const { startDate, endDate, oldestTransactionDate } = nextProps;

    if (
      startDate.toDate() - this.props.startDate.toDate() !== 0 ||
      endDate.toDate() - this.props.endDate.toDate() !== 0
    ) {
      const { tabsState } = this.state;

      // when switched tabs, new data should be fetched as the global
      // daterange changed
      this.clearCache(tabsState);

      this.setState({ tabsState }, () => {
        const fetchAllReq = this.fetchData(true);

        this.fetchPrevData(fetchAllReq, oldestTransactionDate);
      });
    } else if (
      oldestTransactionDate.value !== this.props.oldestTransactionDate.value
    ) {
      this.fetchPrevData(this.fetchAllReq, oldestTransactionDate);
    }
  }

  render() {
    const { tabsState, loading } = this.state,
      { startDate, endDate } = this.props;

    return (
      <Tabs className="keymetrics">
        <TabList
          className="nav nav-tabs nav-justified"
          activeTabClassName="active"
          disabledTabClassName="disabled"
        >
          {tabsOrder.map((tabName, index) => {
            const tabData = tabsState[tabName].data,
              { isCurrency, title } = tabsMeta[tabName];

            return (
              <Tab
                key={index}
                onClick={() => this.handleTabChange(tabName)}
                style={{ width: 100 / tabsOrder.length + '%' }}
              >
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
                onScreenshot={this.onScreenshot}
                externalUrl={`/#/app/${tabsMeta[tabName].index}`}
              />
            </TabPanel>
          );
        })}
      </Tabs>
    );
  }
}

export default KeyMetricsContainer;
