import React, { useState, useEffect } from 'react';
import {
  Heading,
  Card,
  CardBody,
  Text,
  Box,
  Tabs,
  TabList,
  TabItem,
  TabPanel,
  Button,
  ArrowLeftIcon,
  Link,
  UploadIcon,
  InfoIcon,
} from '@razorpay/blade/components';
import moment from 'moment';
import { useNavigate } from 'react-router-dom';

import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { merchantFetch } from 'merchant/utils/ajax';
import { ReconScreens } from 'merchant/views/Reconciliations/const';

import ProcessCharts from './ProcessCharts';
import ProcessOverview from './ProcessOverview';
import ProcessRunsDetail from './ProcessRunsDetail';
import ProcessTransactions from './ProcessTransactions';
import { ProcessTabs } from './constants';

const ProcessStats = ({ activeProcess, closeDetail, openRunDetail }) => {
  const [activeTab, setActiveTab] = useState(ProcessTabs.OVERVIEW);
  const [stats, setStats] = useState({});
  const [startEndDates, setStartEndDates] = useState({
    startDate: moment().subtract(7, 'days').startOf('day'),
    endDate: moment().endOf('day'),
  });
  const [error, setError] = useState(false);

  const navigate = useNavigate();

  const triggerRun = () => {
    analyticsTrackWithUserInfo({
      screen: ReconScreens.ProcessOverview,
      objectName: 'recon new reconciliation',
      actionName: 'click',
      properties: {
        processId: activeProcess?.id,
        processName: activeProcess?.name,
        processType: activeProcess?.type,
      },
    });
    navigate('/reconciliations/new-run', { state: activeProcess });
  };

  const isNewReconAllowed = () => {
    const filesConfigs = activeProcess?.merchant_sources;
    return Array.isArray(filesConfigs) && filesConfigs.some((file) => file.allow_upload);
  };

  const fetchStats = async () => {
    try {
      setError(false);
      const from = startEndDates.startDate.unix();
      const to = startEndDates.endDate.unix();
      const res = await merchantFetch({
        url: `recon-saas/recon_process/stats/${activeProcess?.id}?from_date=${from}&to_date=${to}`,
        mode: 'live',
        method: 'get',
      });
      if (res?.status_code === 200) {
        setStats(res.data);
      } else {
        setError(true);
      }
    } catch (error) {
      setError(true);
    }
  };

  const changeTab = (tab) => {
    if (tab === ProcessTabs.OVERVIEW && activeTab !== ProcessTabs.OVERVIEW) {
      setStats({});
      fetchStats();
    }
    setActiveTab(tab);
  };

  useEffect(() => {
    fetchStats();
  }, [startEndDates]);

  return (
    <Box padding="spacing.6">
      <Link icon={ArrowLeftIcon} iconPosition="left" onClick={() => closeDetail({})}>
        Go Back
      </Link>
      <Card marginTop="spacing.6">
        <CardBody>
          <Box
            display="flex"
            justifyContent="space-between"
            alignItems="center"
            marginBottom="spacing.6"
          >
            <Box>
              <Box display="flex" alignItems="center">
                <Heading size="large">{activeProcess?.name}</Heading>
                <InfoIcon marginX="spacing.3" />
              </Box>
              <Text size="small" color="surface.text.gray.muted">
                <Box display="flex" alignItems="center">
                  Product:
                  <Text size="small" marginX="spacing.2" color="surface.text.gray.subtle">
                    {activeProcess?.product_name}
                  </Text>
                  | Type:
                  <Text size="small" marginX="spacing.2" color="surface.text.gray.subtle">
                    {activeProcess?.type}
                  </Text>
                </Box>
              </Text>
            </Box>
            <Box>
              {isNewReconAllowed() ? (
                <Button icon={UploadIcon} onClick={triggerRun}>
                  New Reconciliation
                </Button>
              ) : null}
            </Box>
          </Box>
          <Tabs
            variant="bordered"
            orientation="horizontal"
            value={activeTab}
            onChange={changeTab}
            isLazy
          >
            <TabList>
              <TabItem value={ProcessTabs.OVERVIEW}>Overview</TabItem>
              <TabItem value={ProcessTabs.RUNS}>Runs</TabItem>
              <TabItem value={ProcessTabs.TRANSACTIONS}>Transactions</TabItem>
            </TabList>

            <TabPanel value={ProcessTabs.OVERVIEW}>
              <ProcessOverview
                dateRange={startEndDates}
                setDates={setStartEndDates}
                activeProcess={activeProcess}
                stats={stats}
                error={error}
              />
            </TabPanel>
            <TabPanel value={ProcessTabs.RUNS}>
              <ProcessRunsDetail activeProcess={activeProcess} openRunDetail={openRunDetail} />
            </TabPanel>
            <TabPanel value={ProcessTabs.TRANSACTIONS}>
              <ProcessTransactions activeProcess={activeProcess} />
            </TabPanel>
          </Tabs>
        </CardBody>
      </Card>
      {activeTab === ProcessTabs.OVERVIEW ? <ProcessCharts error={error} stats={stats} /> : null}
    </Box>
  );
};

export default ProcessStats;
