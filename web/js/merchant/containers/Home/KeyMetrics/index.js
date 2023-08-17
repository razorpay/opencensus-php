import React, { Component } from 'react';
import { connect } from 'react-redux';
import Tabs, { Tab, TabPane } from 'common/ui/ReactTabs';
import {
  titleCase,
  isDefined,
  getPercentage,
  i18CurrencyConversionFromMinorUnitToCommonUnit,
  getFixedNumber,
  groupBy,
} from 'common/utils/rzp-utils';
import { i18HumanReadableNumerals, i18HumanReadableCurrency } from 'common/utils/numerals';
import Popover, { PopoverBody } from 'common/ui/Popover';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetch } from 'merchant/reducers/pokedex';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import {
  API_ERROR,
  API_INVALID_RESP,
  getPlatformColor,
  getPaymentMethodColor,
  platformsOrder,
  paymentMethodsOrder,
} from 'merchant/components/Home/data';
import { trackNoData, trackError } from 'merchant/containers/Home/ga';
import Tooltip from 'merchant/components/Home/Tooltip';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';

import {
  NUM_TRANSACTIONS,
  TRANSACTION_VOLUME,
  REFUNDS,
  SAVED_CARDS,
  PLATFORM,
  CUMULATIVE,
  METHOD,
  SAVED_CARD_PAYMENTS,
  tabsOrder,
  tabsMeta,
  getQuery,
  breakdownValsMap,
  getTimelineData,
} from './data';
import { trackTabClick, trackBreakdownChange, trackSavedCardsHidden } from './ga';
import Panel from './Panel';
import MiniChart from './TinyAreaChart';
import Mobile from './Mobile';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';

const csvDateFormat = 'DD-MM-YYYY';

const gutterBetweenTabs = 16; // 16px

const TabContent = ({
  value,
  percent,
  isCurrency,
  title,
  isLoading,
  error,
  histogram,
  isActive,
  helpText,
  user,
}) => {
  /*
   * Description:
   * Component responsible for rendering content in each Tab
   */

  let formattedValue = value;

  const merchantCurrency = user.merchant.currency;

  if (!isDefined(percent)) {
    if (isCurrency) {
      const convertedAmount = i18CurrencyConversionFromMinorUnitToCommonUnit(
        value,
        merchantCurrency,
      );
      formattedValue = i18HumanReadableCurrency(convertedAmount, merchantCurrency);
    } else {
      formattedValue = i18HumanReadableNumerals(value, merchantCurrency);
    }
  } else {
    formattedValue = `${getFixedNumber(percent)}%`;
  }

  const hasNoData = !histogram || histogram.datasets.length === 0;

  /*
   * checks if the current tab is showing currency values and renders
   * content in the tab
   *
   */
  return (
    <div className="card">
      <span>
        {!isLoading ? (
          <span>
            {title}
            {helpText && (
              <small className="help-content">
                <i class="i i-help" />
                <Popover align="top">
                  <PopoverBody>
                    <div>{helpText}</div>
                  </PopoverBody>
                </Popover>
              </small>
            )}
          </span>
        ) : (
          <PlaceholderLoader />
        )}
      </span>
      <h1>
        {!isLoading ? (
          <span>
            {error ? (
              '--'
            ) : (
              <span>
                {formattedValue}
                <Tooltip currency={user.merchant.currency} value={value} isCurrency={isCurrency} />
              </span>
            )}
          </span>
        ) : (
          <PlaceholderLoader />
        )}
      </h1>
      <div
        className={`mini-chart${!isLoading && hasNoData ? ' no-data' : ''}${
          isActive ? ' active' : ''
        }`}
      >
        <div className="min-chart-content">
          <MiniChart histogram={histogram} isActive={isActive} />
        </div>
      </div>
    </div>
  );
};

// eslint-disable-next-line react/no-unsafe
@connect(
  (state) => {
    return {
      ...state.session,
    };
  },
  {
    showNotification,
  },
)
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

    this.requestId = 0;
    this.trendRequestID = 0;
    this.otherTabsReqId = 0;

    // Populating default value
    tabsOrder.forEach((tabName) => {
      const { grouping, filters } = tabsMeta[tabName];
      // assigment on R.H.S is intentional, puts value and declares
      // variable at the same time
      // eslint-disable-next-line no-multi-assign, react/no-direct-mutation-state
      const tabState = (this.state.tabsState[tabName] = {});

      tabState.name = tabName;

      if (grouping.length > 0) {
        // default grouping selected in each tab
        tabState.selectedGrouping = grouping[0];
      }

      if (filters && filters.length > 0) {
        tabState.selectedFilters = filters.reduce((result, filter) => {
          const filterName = filter.name;
          const firstFilter = filter.values[0];

          result[filterName] = firstFilter;

          return result;
        }, {});
      }

      tabState.selectedBreakdown = breakdownValsMap.daily.value;

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

        // data for small overview graphs shown in tabs
        tinyGraphData: null,

        // trend data
        trend: {
          loading: true,
          previousCount: 0,
          currentCount: 0,
          startDate: null,
          endDate: null,
          show: true,
          error: '',
        },

        showTab: props.isAdmin || tabName !== SAVED_CARDS,

        error: '',
      };
    });

    this.state.tabWidth = `${100 / tabsOrder.length}%`;

    this.node = null;

    this.onGroupingChange = this.onGroupingChange.bind(this);
    this.onFilterChange = this.onFilterChange.bind(this);
    this.onBreakdownChange = this.onBreakdownChange.bind(this);
    this.handleTabChange = this.handleTabChange.bind(this);
    this.onScreenshot = this.onScreenshot.bind(this);
    this.setTabWidth = this.setTabWidth.bind(this);
    this.getVisibleTabs = this.getVisibleTabs.bind(this);
  }

  setTabWidth() {
    if (!this.node) {
      return;
    }

    const nodeWidth = this.node.clientWidth;
    const numVisibleTabs = this.getVisibleTabs().length;

    if (!numVisibleTabs) {
      return;
    }

    const tabWidth = (nodeWidth - gutterBetweenTabs * (numVisibleTabs - 1)) / numVisibleTabs;

    this.setState({
      tabWidth: `${tabWidth}px`,
    });
  }

  getVisibleTabs() {
    const { tabsState } = this.state;

    return tabsOrder.filter((tabName) => {
      if (tabName === 'refunds') {
        return (
          tabsState[tabName].data.showTab &&
          showWhenUtil({
            additionalCondition: (user) =>
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Refunds),
          })
        );
      }

      return tabsState[tabName].data.showTab;
    });
  }

  tabStateMixin({ tabState, histogram, refreshTinyGraphs }) {
    /*
     * Given response from PQL( `histogram` ) , prepares timeline data
     * required for chart.js using `getTimelineData`, Prepares CSV and Screenshot
     *
     * This function will be called whenever the user interacts with
     * datepicker/breakdown group, or grouping dropdown
     *
     * refreshTinyGraphs will be true only when someone changes the dates
     */

    const { selectedGrouping, selectedBreakdown, name: tabName } = tabState;
    const tabMeta = tabsMeta[tabName];
    const {
      title,
      isCurrency,
      noGrouping,
      valueKey = 'value',
      groupTitleMap = { Mobile: 'mWeb' },
    } = tabMeta;
    const groupByColumnName = !isDefined(tabMeta.groupByColumnName)
      ? selectedGrouping && selectedGrouping.value
      : tabMeta.groupByColumnName;
    const { startDate, endDate, sectionTitle } = this.props;

    /*
     * Preparing options for `getTimelineData`
     */
    const options = {
      data: histogram.result,
      groupByColumnName,
      startTime: startDate.unix(),
      endTime: endDate.unix(),
      breakdown: selectedBreakdown,
      groupTitleMap,
      isCurrency,
      valueKey,
      noGrouping: isDefined(noGrouping) ? noGrouping : groupByColumnName === CUMULATIVE,
      currency: this.props.user.merchant.currency,
    };

    /*
     * For Transaction Volume, Number of Transactions and Refunds, we
     * group by Payment Method (card , netbanking etc..) and
     * Platform (Desktop, Andorid , IOS etc..) , we can get color to be used
     * for a particular platform from `getPlatformColor`, similarly for
     * payment methods from `getPaymentMethodColor`
     */
    if ([NUM_TRANSACTIONS, TRANSACTION_VOLUME, REFUNDS].indexOf(tabName) >= 0) {
      options.getColor =
        selectedGrouping && selectedGrouping.value === PLATFORM
          ? getPlatformColor
          : getPaymentMethodColor;
    }

    if (options.groupByColumnName === METHOD) {
      options.groupOrder = paymentMethodsOrder;
    } else if (options.groupByColumnName === PLATFORM) {
      options.groupOrder = platformsOrder;
    }

    const { labels, datasets, aggregates, csv } = getTimelineData(options);

    // track in GA that no data found in this section for
    // given daterange
    if (labels.length === 0) {
      trackNoData(
        `${tabMeta.title} in ${sectionTitle} from ${startDate.format(
          csvDateFormat,
        )} to ${endDate.format(csvDateFormat)}`,
      );
    }

    // preparing csv and png
    const downloadFileName = `${title}, ${startDate.format(csvDateFormat)} to ${endDate.format(
      csvDateFormat,
    )}, ${titleCase(selectedBreakdown)}${
      selectedGrouping ? ` ${selectedGrouping.text}` : ''
    }(Razorpay)`;

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

    if (refreshTinyGraphs) {
      // eslint-disable-next-line no-multi-assign
      const data = (tabState.data.tinyGraphData = {
        labels,
        datasets: [],
      });

      if (datasets && datasets.length > 0) {
        // need to filter and show only "Saved Card Payments" in tiny chart,
        // as the tab has no cumulative like other tabs
        if (tabName === SAVED_CARDS) {
          const savedCardsDataset = datasets.filter((dataset) => {
            return dataset.label === SAVED_CARD_PAYMENTS;
          })[0];

          if (savedCardsDataset) {
            data.datasets.push({ ...savedCardsDataset });
          }
        } else {
          data.datasets.push({ ...datasets[0] });
        }
      }
    }

    return tabState;
  }

  makeQueryForTab(tabName, fetchAllCounts) {
    /*
     * gets the query to be made to Harvester,
     * if fetchAllCounts is true, gets counts and histgram for the first tab in
     * `tabsOrder` and only counts for the rest of the tabs.
     */

    const { selectedFilters, selectedGrouping, selectedBreakdown } = this.state.tabsState[tabName];
    const { startDate, endDate, isMobile } = this.props;

    let filterBy = null;

    if (selectedFilters) {
      filterBy = Object.keys(selectedFilters).reduce((result, filterName) => {
        result[filterName] = selectedFilters[filterName].value;

        return result;
      }, {});
    }

    return getQuery({
      tabName: fetchAllCounts ? 'all' : tabName,
      breakdown: selectedBreakdown,
      startTime: startDate.unix(),
      endTime: endDate.unix(),
      groupBy: !!selectedGrouping && selectedGrouping.value,
      filterBy,
      includeHistogramForTab: !!fetchAllCounts && !isMobile && tabName,
    });
  }

  fetchOtherTabsHistogram(refreshTinyGraphs) {
    /*
     * Makes query and prepares data for tabs other than the selected tab
     */

    const { selectedTab, tabsState } = this.state;
    const otherTabs = this.getVisibleTabs().filter((tabName) => tabName !== selectedTab);
    const { mode, analyticsFetch } = this.props;

    const query = otherTabs.reduce(
      (result, tabName) => {
        const tabQuery = this.makeQueryForTab(tabName);
        const tabState = tabsState[tabName];

        tabState.data.loading = true;
        tabState.data.error = '';

        result.filters = { ...result.filters, ...tabQuery.filters };

        result.aggregations = {
          ...result.aggregations,
          [`${tabName}Histogram`]: tabQuery.aggregations[`${tabName}Histogram`],
        };

        return result;
      },
      { filters: {}, aggregations: {} },
    );

    const requestId = ++this.otherTabsReqId;

    return (analyticsFetch || fetch)(query, mode)
      .then((resp) => {
        if (requestId !== this.otherTabsReqId) {
          return null;
        }

        if (!resp.data) {
          return API_INVALID_RESP;
        }

        return resp;
      })
      .catch((e) => {
        console.error(e);

        if (requestId !== this.otherTabsReqId) {
          return null;
        }

        return e;
      })
      .then((data) => {
        if (!data) {
          return;
        }

        if (data.error) {
          trackError(`Error while fetching data for Keymetrics - Remaining tabs data`);

          this.props.showNotification({
            type: 'error',
            message: data.error,
            hidePrevious: true,
          });
        }

        otherTabs.forEach((tabName) => {
          const tabState = tabsState[tabName];

          if (!data.error && data.data) {
            const histogram = data.data[`${tabName}Histogram`];
            if (histogram) {
              this.tabStateMixin({
                tabState: tabsState[tabName],
                histogram,
                refreshTinyGraphs,
              });
            }
          }

          tabState.data.loading = false;
          tabState.data.fetchData = false;
          tabState.data.error = data.error;
        });

        // eslint-disable-next-line react/no-access-state-in-setstate
        this.setState(this.state);
      });
  }

  fetchData(fetchAllCounts, initiatePoint) {
    /*
     * Fetches data , if `fetchAllCounts` is true, fetches all tabs stats
     * and the selected tab's graph data, when ever the tab is
     * switched, latest data including stat for the selected tab is fetched
     */
    const isInitialLoad = this.state.loading;

    const { tabsState, selectedTab } = this.state;
    const tabState = { ...tabsState[selectedTab] };
    const { mode, analyticsFetch } = this.props;

    const query = this.makeQueryForTab(selectedTab, fetchAllCounts);

    if (this.props.isMobile && fetchAllCounts) {
      tabsOrder.forEach((tabName) => {
        tabsState[tabName].data.loading = true;
      });
    } else {
      tabState.data.loading = true;
    }

    tabState.data.error = '';

    this.setState({ tabsState });

    const requestId = ++this.requestId;

    return (analyticsFetch || fetch)(query, mode)
      .then((resp) => {
        if (requestId !== this.requestId) {
          return null;
        }

        if (!resp.data) {
          return API_INVALID_RESP;
        }

        if (!isInitialLoad) {
          const selfServeSuccessData = {
            selfServeAction: 'Payment Details Fetched',
            page: 'Home',
            screen: 'Home',
            props: {},
          };
          if (initiatePoint) {
            selfServeSuccessData.props.initiatePoint = initiatePoint;
          }
          selfServeTrackSuccess(selfServeSuccessData);
        }
        tabsOrder.forEach((tabName) => {
          const tabMeta = tabsMeta[tabName];
          const { isPercent, valueKey = 'value' } = tabMeta;

          // Main stat showin in the tab
          const mainStat = resp.data[tabName];

          if (mainStat) {
            if (tabName === SAVED_CARDS) {
              const data = groupBy(mainStat.result, tabMeta.groupByColumnName);
              const savedCardsValue = data['1'] ? data['1'][0].value : 0;
              const otherCardsValue = data['0'] ? data['0'][0].value : 0;

              tabsState[tabName].data.count = savedCardsValue;

              tabsState[tabName].data.percent = getPercentage(
                savedCardsValue + otherCardsValue,
                savedCardsValue,
              );

              /*
               * For Saved Card Txns tab
               * 1) If not Admin
               * 2) If number of saved cards is less than 15%
               *    hide the tab for the merchant
               * 3) Decide to show the tab or not only on initial load
               */
              if (isInitialLoad) {
                tabsState[tabName].data.showTab =
                  tabsState[tabName].data.showTab || tabsState[tabName].data.percent > 15;

                if (!tabsState[tabName].data.showTab) {
                  trackSavedCardsHidden(tabsState[tabName].data.percent);
                }
              }
            } else {
              const value = mainStat.result[0] ? mainStat.result[0][valueKey] : 0;

              tabsState[tabName].data.count = value;

              if (isPercent) {
                tabsState[tabName].data.percent = value;
              }
            }
          }

          // Timeline data
          const histogram = resp.data[`${tabName}Histogram`];

          if (histogram) {
            this.tabStateMixin({
              tabState: tabsState[tabName],
              histogram,
              refreshTinyGraphs: fetchAllCounts,
            });
          }
        });

        if (isInitialLoad) {
          // eslint-disable-next-line react/no-direct-mutation-state
          this.state.loading = false;
        }

        if (!this.props.isMobile) {
          // eslint-disable-next-line react/no-access-state-in-setstate
          this.setState(this.state, () => {
            this.setTabWidth();

            if (fetchAllCounts) {
              this.fetchOtherTabsHistogram(fetchAllCounts);
            }
          });
        }

        return resp;
      })
      .catch((e) => {
        console.error(e);

        if (requestId !== this.requestId) {
          return null;
        }

        return API_ERROR;
      })
      .then((data) => {
        if (!data) {
          return;
        }

        if (data.error) {
          trackError(`Error while fetching data for Keymetrics - ${selectedTab}`);

          this.props.showNotification({
            type: 'error',
            message: data.error,
            hidePrevious: true,
          });
        }

        if (isInitialLoad) {
          // eslint-disable-next-line react/no-direct-mutation-state
          this.state.loading = false;
        }

        tabsOrder.forEach((tabName) => {
          tabsState[tabName].data.loading = false;
          tabsState[tabName].data.fetchData = false;
          tabsState[tabName].data.error = data.error;
        });

        // eslint-disable-next-line react/no-access-state-in-setstate
        this.setState(this.state);
      });
  }

  onScreenshot(tabName, url, cb) {
    const tabState = this.state.tabsState[tabName];

    tabState.data.png = {
      name: `${tabState.data.downloadFileName}.png`,
      url,
    };

    // eslint-disable-next-line react/no-access-state-in-setstate
    this.setState(this.state, cb);
  }

  fetchPrevData(fetchAllReq, oldestTransactionDate) {
    const { tabsState } = this.state;

    oldestTransactionDate = oldestTransactionDate || this.props.oldestTransactionDate;

    const { analyticsFetch } = this.props;
    const { startDate, endDate } = oldestTransactionDate;

    if (
      oldestTransactionDate.error ||
      !oldestTransactionDate.value ||
      startDate.unix() < oldestTransactionDate.value
    ) {
      tabsOrder.forEach((tabName) => {
        tabsState[tabName].data.trend.show = false;
      });

      this.setState({
        tabsState: { ...tabsState },
      });

      return Promise.resolve();
    }

    tabsOrder.forEach((tabName) => {
      const { trend } = tabsState[tabName].data;

      trend.loading = true;
      trend.startDate = startDate;
      trend.endDate = endDate;
      trend.show = true;
      trend.error = '';
    });

    this.setState({ tabsState: { ...tabsState } });

    const trendRequestID = ++this.trendRequestID;

    const query = getQuery({
      tabName: 'all',
      startTime: startDate.unix(),
      endTime: endDate.unix(),
    });

    return (analyticsFetch || fetch)(query, this.props.mode)
      .then((data) => {
        if (trendRequestID !== this.trendRequestID) {
          return null;
        }

        if (!data.success) {
          return API_ERROR;
        }

        if (!data.data) {
          return API_INVALID_RESP;
        }

        return data.data;
      })
      .catch((err) => {
        console.error(err);

        if (trendRequestID !== this.trendRequestID) {
          return null;
        }

        return API_ERROR;
      })
      .then((data) => {
        if (!data) {
          return;
        }

        if (!data.error) {
          fetchAllReq.then(() => {
            tabsOrder.forEach((tabName) => {
              const tabState = tabsState[tabName];
              const { trend } = tabState.data;

              let previousCount = data?.[tabName]?.result[0] ? data[tabName].result[0].value : 0;
              const currentCount = tabState.data.count;

              if (tabName === SAVED_CARDS) {
                const savedCardData = data[tabName].result.filter((item) => item.saved_card)[0];

                previousCount = savedCardData ? savedCardData.value : 0;
              }

              trend.loading = false;
              trend.previousCount = previousCount;
              trend.currentCount = currentCount;
            });

            this.setState({
              tabsState: { ...tabsState },
            });
          });

          return;
        } else {
          trackError(`Error while fetching prev data for all tabs`);

          this.props.showNotification({
            type: 'error',
            message: data.error,
            hidePrevious: true,
          });

          tabsOrder.forEach((tabName) => {
            const tabState = tabsState[tabName];

            tabState.data.trend.error = data.error;
          });
        }

        tabsOrder.forEach((tabName) => {
          tabsState[tabName].data.trend.loading = false;
        });

        this.setState({
          tabsState: { ...tabsState },
        });
      });
  }

  UNSAFE_componentWillMount() {
    // eslint-disable-next-line no-multi-assign
    const fetchAllReq = (this.fetchAllReq = this.fetchData(true));

    if (this.props.oldestTransactionDate.value) {
      this.fetchPrevData(fetchAllReq);
    }
  }

  componentDidMount() {
    this.setTabWidth();
    window.addEventListener('resize', this.setTabWidth);
  }

  componentWillUnmount() {
    window.removeEventListener('resize', this.setTabWidth);
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
        return !data.loading && data.fetchData && this.fetchData(null, 'Tab Change');
      },
    );
    selfServeTrackInitiate({
      selfServeAction: 'Payment Details Fetched',
      page: 'Home',
      screen: 'Home',
      props: {
        initiatePoint: 'Tab Change',
      },
    });
    trackTabClick(tabsMeta[tabName].title);
  }

  onFilterChange(tabName, selectedFilter) {
    const { tabsState } = this.state;
    const { onFilterChange } = this.props;
    const tabState = tabsState[tabName];

    tabState.selectedFilters = {
      ...tabState.selectedFilters,
      [selectedFilter.filterName]: selectedFilter,
    };

    // Hardcoding, can not import these values as the code will
    // not be present in merchant dashboard
    if (selectedFilter.filterName === 'paymentMethods') {
      tabState.selectedFilters.sources = {
        filterName: 'sources',
        text: 'All Sources',
        value: 'all',
      };
    }

    this.setState({ tabsState }, () => {
      this.fetchData();
    });

    return onFilterChange && onFilterChange(selectedFilter);
  }

  onGroupingChange(tabName, selectedGrouping) {
    selfServeTrackInitiate({
      selfServeAction: 'Payment Details Fetched',
      page: 'Home',
      screen: 'Home',
      props: {
        initiatePoint: 'Grouping change',
      },
    });
    const { tabsState } = this.state;
    const tabState = tabsState[tabName];

    tabState.selectedGrouping = selectedGrouping;

    this.setState({ tabsState }, () => {
      this.fetchData(false, 'Grouping change');
    });
  }

  clearCache(tabsState) {
    // hint to fetch new data
    tabsOrder.forEach((tabName) => {
      tabsState[tabName].data.fetchData = true;
    });
  }

  onBreakdownChange(tabName, selectedBreakdown) {
    const { tabsState } = this.state;

    tabsState[tabName].selectedBreakdown = selectedBreakdown;

    this.setState({ tabsState }, () => {
      this.fetchData(false, 'Breakdown Change');
    });
    selfServeTrackInitiate({
      selfServeAction: 'Payment Details Fetched',
      page: 'Home',
      screen: 'Home',
      props: {
        initiatePoint: 'Breakdown Change',
      },
    });
    trackBreakdownChange(selectedBreakdown);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    const { startDate, endDate, oldestTransactionDate } = nextProps;

    if (
      startDate.toDate() - this.props.startDate.toDate() !== 0 ||
      endDate.toDate() - this.props.endDate.toDate() !== 0
    ) {
      const { tabsState } = this.state;

      // when switched tabs, new data should be fetched as the global
      // daterange changed
      this.clearCache(tabsState);

      // check the daterange and correct the breakdown in each tab
      // if needed
      tabsOrder.forEach((tabName) => {
        const selectedBreakdown = tabsState[tabName].selectedBreakdown;
        const { hourly, weekly, monthly } = breakdownValsMap;

        if (selectedBreakdown !== 'daily') {
          const showHourly = hourly.isEnabled(startDate, endDate);
          const showWeekly = weekly.isEnabled(startDate, endDate);
          const showMonthly = monthly.isEnabled(startDate, endDate);

          if (
            (selectedBreakdown === 'hourly' && !showHourly) ||
            (selectedBreakdown === 'weekly' && !showWeekly) ||
            (selectedBreakdown === 'monthly' && !showMonthly)
          ) {
            /*
             * if the changed daterange doesn't fit for the
             * selected breakdown switch to daily
             */
            tabsState[tabName].selectedBreakdown = 'daily';
          }
        }
      });

      this.setState({ tabsState }, () => {
        const fetchAllReq = this.fetchData(true);

        this.fetchPrevData(fetchAllReq, oldestTransactionDate);
      });
    } else if (oldestTransactionDate.value !== this.props.oldestTransactionDate.value) {
      this.fetchPrevData(this.fetchAllReq, oldestTransactionDate);
    }
  }

  render() {
    const { tabsState, loading, tabWidth, selectedTab } = this.state;
    const { startDate, endDate, showGroupingByPtfm, sectionTitle } = this.props;
    const visibleTabs = this.getVisibleTabs();

    if (this.props.isMobile) {
      return (
        <Mobile
          tabsState={tabsState}
          getVisibleTabs={this.getVisibleTabs}
          startDate={startDate}
          endDate={endDate}
          loading={loading}
          sectionTitle={sectionTitle}
        />
      );
    }

    return (
      <div
        ref={(node) => (this.node = node)}
        className={`keymetrics-container ${loading ? 'loading' : ''}`}
      >
        <Tabs
          className="keymetrics"
          justified={true}
          tabsWrapperProps={{ id: 'analytics-keymetrics-section' }}
        >
          {visibleTabs.map((tabName, index) => {
            const tabData = tabsState[tabName].data;
            const { isCurrency, title, helpText } = tabsMeta[tabName];

            return (
              <Tab
                key={index}
                onClick={() => {
                  this.handleTabChange(tabName);
                }}
                style={{
                  width: tabWidth,
                  marginLeft: `${index === 0 ? 0 : gutterBetweenTabs}px`,
                  marginBottom: `${gutterBetweenTabs}px`,
                }}
              >
                <TabContent
                  user={this.props.user}
                  value={tabData.count}
                  name={tabName}
                  helpText={helpText}
                  isCurrency={isCurrency}
                  title={title}
                  isLoading={loading}
                  error={tabData.error}
                  percent={tabData.percent}
                  trend={tabData.trend}
                  histogram={tabData.tinyGraphData}
                  isActive={selectedTab === tabName}
                />
              </Tab>
            );
          })}

          {visibleTabs.map((tabName, index) => {
            const tabState = tabsState[tabName];
            const { isCurrency } = tabsMeta[tabName];

            return (
              <TabPane key={index}>
                <Panel
                  tabName={tabName}
                  selectedBreakdown={tabState.selectedBreakdown}
                  onBreakdownChange={this.onBreakdownChange}
                  selectedGrouping={tabState.selectedGrouping}
                  selectedFilters={tabState.selectedFilters}
                  onGroupingChange={this.onGroupingChange}
                  onFilterChange={this.onFilterChange}
                  data={{ ...tabsState[tabName].data }}
                  startDate={startDate}
                  endDate={endDate}
                  lastUpdatedAt={tabsState[tabName].lastUpdatedAt}
                  isCurrency={isCurrency}
                  onScreenshot={this.onScreenshot}
                  externalUrl={`/#/app/${tabsMeta[tabName].index}`}
                  showGroupingByPtfm={showGroupingByPtfm}
                  sectionTitle={sectionTitle}
                />
              </TabPane>
            );
          })}
        </Tabs>
      </div>
    );
  }
}

export default KeyMetricsContainer;
