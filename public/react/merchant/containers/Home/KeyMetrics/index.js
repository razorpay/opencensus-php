import React, { Component } from 'react';
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs';

import Highcharts from 'rzp/ui/Highcharts';

import { tabsOrder, tabsMeta } from './data';
import Panel from './Panel';

import { getData } from 'merchant/models/HomeKeyMetricsMock';

class KeyMetricsContainer extends Component {
  constructor(props) {
    super(props);

    this.state = {
      selectedTab: tabsOrder[0],
      tabsState: {},
      loading: true,
    };

    tabsOrder.forEach(tabName => {
      const grouping = tabsMeta[tabName].grouping,
        tabState = (this.state.tabsState[tabName] = {});

      if (grouping.length > 0) {
        tabState.selectedGrouping = grouping[0].value;
      }

      tabState.data = {};
    });

    this.onGroupingChange = this.onGroupingChange.bind(this);
    this.handleTabChange = this.handleTabChange.bind(this);
  }

  fetchData(tabName, breakDown) {
    breakDown = breakDown || this.props.selectedBreakdown;

    const { tabsState, selectedTab } = this.state;

    getData(tabName, breakDown).then(resp => {
      if (!tabName) {
        tabsOrder.forEach(tabName => {
          tabsState[tabName].data.count = resp[tabName];
          tabsState[tabName].data.percentage = resp[`${tabName}Percentage`];
        });
      } else {
        tabsState[tabName].data.count = resp[tabName];
      }

      const tabState = tabsState[tabName || selectedTab];

      tabState.data.diff = resp.diff;

      tabState.data.histogram = [];

      Object.keys(resp).forEach(keyName => {
        if (keyName.indexOf('group') === 0) {
          tabState.data.histogram.push({
            name: keyName,
            data: resp[keyName].histogram.map(item => [
              item.timestamp,
              item.value,
            ]),
          });
        }
      });

      this.setState({ tabsState, loading: false });
    });
  }

  componentWillMount() {
    const { tabsState, selectedTab } = this.state;

    this.fetchData();
  }

  handleTabChange(tabName) {
    this.setState({
      selectedTab: tabName,
    });

    const { tabsState } = this.state;

    return !tabsState[tabName].data.histogram && this.fetchData(tabName);
  }

  onGroupingChange(tabName, selectedGrouping) {
    const { tabsState } = this.state,
      tabState = tabsState[tabName];

    tabState.selectedGrouping = selectedGrouping;

    delete tabState.data.diff;
    delete tabState.data.histogram;

    this.setState({ tabsState }, () => {
      this.fetchData(tabName);
    });
  }

  componentWillReceiveProps(nextProps) {
    const { startDate, endDate, selectedBreakdown } = nextProps;

    if (
      startDate.toDate() !== this.props.startDate.toDate() ||
      endDate.toDate() !== this.props.endDate.toDate ||
      selectedBreakdown !== this.props.selectedBreakdown
    ) {
      tabsOrder.forEach(tabName => {
        const tabState = this.state.tabsState[tabName];

        delete tabState.data.diff;
        delete tabState.data.histogram;
      });

      this.setState({ tabsState: this.state.tabsState });
      this.fetchData(this.state.selectedTab, selectedBreakdown);
    }
  }

  render() {
    const { tabsState, loading } = this.state,
      { startDate, endDate } = this.props;

    if (loading) {
      return <span>Loading...</span>;
    }

    return (
      <Tabs>
        <TabList
          className="nav nav-tabs nav-justified"
          activeTabClassName="active"
          disabledTabClassName="disabled"
        >
          {tabsOrder.map((tabName, index) => {
            const tabData = tabsState[tabName].data;

            return (
              <Tab key={index} onClick={() => this.handleTabChange(tabName)}>
                <a>
                  <h1>{tabData.count ? tabData.count.value : 'Loading...'}</h1>
                  {tabsMeta[tabName].title}
                </a>
              </Tab>
            );
          })}
        </TabList>

        {tabsOrder.map((tabName, index) => {
          return (
            <TabPanel key={index}>
              <Panel
                tabName={tabName}
                selectedGrouping={tabsState[tabName].selectedGrouping}
                onGroupingChange={this.onGroupingChange}
                data={tabsState[tabName].data}
                startDate={startDate}
                endDate={endDate}
              />
            </TabPanel>
          );
        })}
      </Tabs>
    );
  }
}

export default KeyMetricsContainer;
