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

  componentWillMount() {
    const { tabsState, selectedTab } = this.state;

    getData().then(resp => {
      tabsOrder.forEach(tabName => {
        tabsState[tabName].data.count = resp[tabName];
      });

      const tabState = tabsState[selectedTab];

      tabState.data.diff = resp.diff;
      tabState.data.histogram = resp.histogram.map(item => [
        item.timestamp,
        item.value,
      ]);

      this.setState({ tabsState });
    });
  }

  handleTabChange(tabName) {
    this.setState({
      selectedTab: tabName,
    });
  }

  onGroupingChange(tabName, selectedGrouping) {
    const { tabsState } = this.state,
      tabState = tabsState[tabName];

    tabState.selectedGrouping = selectedGrouping;

    this.setState({ tabsState });
  }

  render() {
    const tabsState = this.state.tabsState;

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
              />
            </TabPanel>
          );
        })}
      </Tabs>
    );
  }
}

export default KeyMetricsContainer;
