import React, { Component } from 'react';
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs';

import Highcharts from 'rzp/ui/Highcharts';

import { tabsOrder, tabsMeta } from './data';
import Panel from './Panel';

const date = {
  loading: false,
  tabs: {
    transactionVolume: {
      total: 242460,
      diff: 8686,
      histogram: [],
    },
    numTransactions: {
      total: 12460,
      diff: 123,
      histogram: [],
    },
    refunds: {
      total: 738,
      diff: 1245,
      histogram: [],
    },
    savedCards: {
      total: 3697,
      diff: 121,
      histogram: [],
    },
  },
};

class KeyMetricsContainer extends Component {
  constructor(props) {
    super(props);

    this.state = {
      selectedTab: tabsOrder[0],
      tabsState: {},
    };

    tabsOrder.forEach(tabName => {
      var grouping = (tabsMeta[
        tabName
      ].grouping.tabState = this.state.tabsState[tabName] = {});

      if (grouping.length > 0) {
        tabsState[tabName] = {
          selectedGrouping: grouping[0].value,
        };
      }
    });

    this.onGroupingChange = this.onGroupingChange.bind(this);
    this.handleTabChange = this.handleTabChange.bind(this);
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
            return (
              <Tab key={index} onClick={() => this.handleTabChange(tabName)}>
                <a>
                  <h1>Loading..</h1>
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
              />
            </TabPanel>
          );
        })}
      </Tabs>
    );
  }
}

export default KeyMetricsContainer;
