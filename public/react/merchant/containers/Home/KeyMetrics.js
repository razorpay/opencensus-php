import React from 'react';
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs';

import Highcharts from 'rzp/ui/Highcharts';

export default props => {
  return (
    <Tabs>
      <TabList
        className="nav nav-tabs nav-justified"
        activeTabClassName="active"
        disabledTabClassName="disabled"
      >
        <Tab>
          <a className="text-left">
            <h1>Chart 1</h1>
            <small>some description</small>
          </a>
        </Tab>
        <Tab>
          <a>Chart 2</a>
        </Tab>
        <Tab>
          <a>Chart 3</a>
        </Tab>
        <Tab>
          <a>Chart 4</a>
        </Tab>
      </TabList>
      <TabPanel>
        <Highcharts />
      </TabPanel>
      <TabPanel>
        <Highcharts />
      </TabPanel>
      <TabPanel>
        <Highcharts />
      </TabPanel>
      <TabPanel>
        <Highcharts />
      </TabPanel>
    </Tabs>
  );
};
