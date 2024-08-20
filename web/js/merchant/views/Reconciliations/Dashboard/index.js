import React, { useState, useEffect } from 'react';
import { Card, CardBody, Box, Tabs, TabList, TabPanel } from '@razorpay/blade/components';
import { useMatch } from 'react-router-dom';

import Processes from './Processes';
import Runs from './Runs';
import { TabItemRouterLink } from './TabItemRouterLink';
import { DashboardTabs } from './constants';

const ReconDashboard = () => {
  const [dashboardActiveTab, setDashboardActiveTab] = useState(DashboardTabs.PROCESSES);

  const dashboardMatch = useMatch('/reconciliations/dashboard/*');

  useEffect(() => {
    let activeTab = '';

    if (dashboardMatch) {
      const lastSegment = dashboardMatch.params['*'];

      switch (lastSegment) {
        case DashboardTabs.OVERVIEW:
          activeTab = DashboardTabs.OVERVIEW;
          break;
        case DashboardTabs.RUNS:
          activeTab = DashboardTabs.RUNS;
          break;
        default:
          break;
      }
    }

    if (activeTab) {
      setDashboardActiveTab(activeTab);
    }
  }, []);

  return (
    <Box paddingTop="spacing.1">
      <Card margin="spacing.6">
        <CardBody>
          <Tabs
            variant="bordered"
            orientation="horizontal"
            isLazy
            defaultValue={DashboardTabs.PROCESSES}
            value={dashboardActiveTab}
            onChange={(tab) => setDashboardActiveTab(tab)}
          >
            <TabList>
              <TabItemRouterLink
                value={DashboardTabs.PROCESSES}
                to={`/reconciliations/dashboard/${DashboardTabs.PROCESSES}`}
              >
                Processes
              </TabItemRouterLink>
              <TabItemRouterLink
                value={DashboardTabs.RUNS}
                to={`/reconciliations/dashboard/${DashboardTabs.RUNS}`}
              >
                Runs
              </TabItemRouterLink>
            </TabList>
            <Box paddingTop="spacing.4">
              <TabPanel value={DashboardTabs.PROCESSES}>
                <Processes />
              </TabPanel>
              <TabPanel value={DashboardTabs.RUNS}>
                <Runs />
              </TabPanel>
            </Box>
          </Tabs>
        </CardBody>
      </Card>
    </Box>
  );
};

export default ReconDashboard;
