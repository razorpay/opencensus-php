import React, { useState, useEffect } from 'react';
import { Card, CardBody, Box, Tabs, TabList, TabPanel } from '@razorpay/blade/components';
import { useMatch } from 'react-router-dom';

import { useSplitzService } from 'common/splitz';
import DownloadList from 'merchant/views/Reconciliations/Dashboard/DownloadList';
import Processes from 'merchant/views/Reconciliations/Dashboard/Processes';
import ReportList from 'merchant/views/Reconciliations/Dashboard/ReportList';
import RunsList from 'merchant/views/Reconciliations/Dashboard/RunsList';
import { TabItemRouterLink } from 'merchant/views/Reconciliations/Dashboard/TabItemRouterLink';
import { checkCustomReportingEnabled } from 'merchant/views/Reconciliations/utils';

import { DashboardTabs, RECON_DASHBOARD_BASEURL } from './constants';

const ReconDashboard = () => {
  const { abExperiments } = useSplitzService();

  const [dashboardActiveTab, setDashboardActiveTab] = useState(DashboardTabs.PROCESSES);

  const dashboardMatch = useMatch(`${RECON_DASHBOARD_BASEURL}/*`);

  const enableCustomReporting = checkCustomReportingEnabled({ abExperiments });

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
        case DashboardTabs.REPORTS:
          activeTab = DashboardTabs.REPORTS;
          break;
        case DashboardTabs.DOWNLOADS:
          activeTab = DashboardTabs.DOWNLOADS;
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
                to={`${RECON_DASHBOARD_BASEURL}/${DashboardTabs.PROCESSES}`}
              >
                Processes
              </TabItemRouterLink>
              <TabItemRouterLink
                value={DashboardTabs.RUNS}
                to={`${RECON_DASHBOARD_BASEURL}/${DashboardTabs.RUNS}`}
              >
                Runs
              </TabItemRouterLink>
              {enableCustomReporting ? (
                <>
                  <TabItemRouterLink
                    value={DashboardTabs.REPORTS}
                    to={`${RECON_DASHBOARD_BASEURL}/${DashboardTabs.REPORTS}`}
                  >
                    Reports
                  </TabItemRouterLink>
                  <TabItemRouterLink
                    value={DashboardTabs.DOWNLOADS}
                    to={`${RECON_DASHBOARD_BASEURL}/${DashboardTabs.DOWNLOADS}`}
                  >
                    Downloads
                  </TabItemRouterLink>
                </>
              ) : null}
            </TabList>
            <Box paddingTop="spacing.4">
              <TabPanel value={DashboardTabs.PROCESSES}>
                <Processes />
              </TabPanel>
              <TabPanel value={DashboardTabs.RUNS}>
                <RunsList />
              </TabPanel>
              {enableCustomReporting ? (
                <>
                  <TabPanel value={DashboardTabs.REPORTS}>
                    <ReportList />
                  </TabPanel>
                  <TabPanel value={DashboardTabs.DOWNLOADS}>
                    <DownloadList />
                  </TabPanel>
                </>
              ) : null}
            </Box>
          </Tabs>
        </CardBody>
      </Card>
    </Box>
  );
};

export default ReconDashboard;
