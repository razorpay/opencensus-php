import React, { useState, useEffect, useCallback } from 'react';
import {
  Heading,
  Card,
  CardBody,
  Text,
  Box,
  Tabs,
  TabList,
  TabPanel,
  Button,
  ArrowLeftIcon,
  Link,
  UploadIcon,
  InfoIcon,
} from '@razorpay/blade/components';
import { useNavigate, useParams, useMatch } from 'react-router-dom';

import { useSplitzService } from 'common/splitz';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { merchantFetch } from 'merchant/utils/ajax';
import { ReconScreens } from 'merchant/views/Reconciliations/const';
import { useCalendarRange } from 'merchant/views/Reconciliations/hooks';
import { checkSplitScreenEnabled } from 'merchant/views/Reconciliations/utils';

import ProcessCharts from './ProcessCharts';
import ProcessOverview from './ProcessOverview';
import ProcessTransactions from './ProcessTransactions';
import ProcessRunsList from './RunsList';
import { TabItemRouterLink } from './TabItemRouterLink';
import { ProcessTabs, RECON_PROCESS_BASEURL } from './constants';

const ProcessStats = () => {
  const { abExperiments } = useSplitzService();

  const [processesActiveTab, setProcessesActiveTab] = useState(ProcessTabs.OVERVIEW);
  const [stats, setStats] = useState({});
  const [activeProcess, setActiveProcess] = useState({});
  const { dateRange, handleRangeChange } = useCalendarRange();
  const [error, setError] = useState(false);

  const navigate = useNavigate();
  const processesMatch = useMatch(`${RECON_PROCESS_BASEURL}/:processId/*`);
  const { processId: activeProcessId } = useParams();

  const enableSplitScreen = checkSplitScreenEnabled({ abExperiments });

  const triggerRun = () => {
    analyticsTrackWithUserInfo({
      screen: ReconScreens.ProcessOverview,
      objectName: 'recon new reconciliation',
      actionName: 'click',
      properties: {
        activeProcessId,
        activeProcessName: activeProcess?.name,
        activeProcessType: activeProcess?.type,
      },
    });
    navigate('/reconciliations/new-run', { state: activeProcess });
  };

  const isNewReconAllowed = () => {
    const filesConfigs = activeProcess?.merchant_sources;
    return Array.isArray(filesConfigs) && filesConfigs.some((file) => file.allow_upload);
  };

  const fetchReconProcessDetails = async () => {
    try {
      const res = await merchantFetch({
        url: `recon-saas/recon_process/${activeProcessId}`,
        mode: 'live',
        method: 'get',
      });
      if (res?.status_code === 200) {
        setActiveProcess(res.data);
      } else {
        setError(true);
        setActiveProcess({});
      }
    } catch (error) {
      setError(true);
    }
  };

  const fetchStats = useCallback(async () => {
    try {
      setError(false);
      if (!dateRange.startDate || !dateRange.endDate) {
        return;
      }
      setStats({});
      const from = dateRange.startDate.unix();
      const to = dateRange.endDate.unix();
      const res = await merchantFetch({
        url: `recon-saas/recon_process/stats/${activeProcessId}?from_date=${from}&to_date=${to}`,
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
  }, [activeProcessId, dateRange]);

  const changeTab = (tab) => {
    if (tab === ProcessTabs.OVERVIEW && processesActiveTab !== ProcessTabs.OVERVIEW) {
      setStats({});
      fetchStats();
    }
    setProcessesActiveTab(tab);
  };

  const handleBackLinkButton = (e) => {
    e.preventDefault();
    navigate(-1);
  };

  useEffect(() => {
    fetchReconProcessDetails();
  }, []);

  useEffect(() => {
    let activeTab = '';

    if (processesMatch) {
      const lastSegment = processesMatch.params['*'];

      switch (lastSegment) {
        case ProcessTabs.OVERVIEW:
          activeTab = ProcessTabs.OVERVIEW;
          break;
        case ProcessTabs.RUNS:
          activeTab = ProcessTabs.RUNS;
          break;
        case ProcessTabs.TRANSACTIONS:
          activeTab = ProcessTabs.TRANSACTIONS;
          break;
        case ProcessTabs.SPLIT:
          activeTab = ProcessTabs.SPLIT;
          break;
        default:
          break;
      }
    }

    if (activeTab) {
      setProcessesActiveTab(activeTab);
    }
  }, [processesMatch]);

  useEffect(() => {
    fetchStats();
  }, [dateRange, fetchStats]);

  return (
    <Box padding="spacing.6">
      <Link icon={ArrowLeftIcon} iconPosition="left" onClick={(e) => handleBackLinkButton(e)}>
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
            defaultValue={ProcessTabs.OVERVIEW}
            value={processesActiveTab}
            onChange={changeTab}
            isLazy
          >
            <TabList>
              <TabItemRouterLink
                value={ProcessTabs.OVERVIEW}
                to={`${RECON_PROCESS_BASEURL}/${activeProcessId}/${ProcessTabs.OVERVIEW}`}
              >
                Overview
              </TabItemRouterLink>
              {!enableSplitScreen ? (
                <TabItemRouterLink
                  value={ProcessTabs.RUNS}
                  to={`${RECON_PROCESS_BASEURL}/${activeProcessId}/${ProcessTabs.RUNS}`}
                >
                  Runs
                </TabItemRouterLink>
              ) : null}
              <TabItemRouterLink
                value={ProcessTabs.TRANSACTIONS}
                to={`${RECON_PROCESS_BASEURL}/${activeProcessId}/${ProcessTabs.TRANSACTIONS}`}
              >
                Transactions
              </TabItemRouterLink>
              {enableSplitScreen ? (
                <TabItemRouterLink
                  value={ProcessTabs.SPLIT}
                  to={`${RECON_PROCESS_BASEURL}/${activeProcessId}/${ProcessTabs.SPLIT}`}
                >
                  Split Screen
                </TabItemRouterLink>
              ) : null}
            </TabList>
            <TabPanel value={ProcessTabs.OVERVIEW}>
              <ProcessOverview
                dateRange={dateRange}
                handleRangeChange={handleRangeChange}
                activeProcess={activeProcess}
                stats={stats}
                error={error}
              />
            </TabPanel>
            {!enableSplitScreen ? (
              <TabPanel value={ProcessTabs.RUNS}>
                <ProcessRunsList activeProcess={activeProcess} />
              </TabPanel>
            ) : null}
            <TabPanel value={ProcessTabs.TRANSACTIONS}>
              <ProcessTransactions activeProcess={activeProcess} />
            </TabPanel>
            {enableSplitScreen ? (
              <TabPanel value={ProcessTabs.SPLIT}>
                <ProcessRunsList activeProcess={activeProcess} />
              </TabPanel>
            ) : null}
          </Tabs>
        </CardBody>
      </Card>
      {processesActiveTab === ProcessTabs.OVERVIEW ? (
        <ProcessCharts error={error} stats={stats} />
      ) : null}
    </Box>
  );
};

export default ProcessStats;
