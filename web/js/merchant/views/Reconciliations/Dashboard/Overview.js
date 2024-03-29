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
  CheckIcon,
  Badge,
} from '@razorpay/blade/components';
import moment from 'moment';
import { useNavigate } from 'react-router-dom';

import { merchantFetch } from 'merchant/utils/ajax';

import ProcessCharts from './ProcessCharts';
import ProcessOverview from './ProcessOverview';
import ProcessRunsDetail from './ProcessRunsDetail';
import ProcessTransactions from './ProcessTransactions';

const ProcessStats = ({ activeProcess, closeDetail, openRunDetail }) => {
  const [activeTab, setActiveTab] = useState('overview');
  const [stats, setStats] = useState({});
  const [startEndDates, setStartEndDates] = useState({
    startDate: moment().subtract(7, 'days').startOf('day'),
    endDate: moment().endOf('day'),
  });

  const navigate = useNavigate();
  const triggerRun = () => {
    navigate('/reconciliations/new-run', { state: activeProcess });
  };

  const isNewReconAllowed = () => {
    const filesConfigs = activeProcess?.merchant_sources;
    return Array.isArray(filesConfigs) && filesConfigs.some((file) => file.allow_upload);
  };

  const fetchStats = async () => {
    const from = startEndDates.startDate.unix();
    const to = startEndDates.endDate.unix();
    const res = await merchantFetch({
      url: `recon-saas/recon_process/stats/${activeProcess?.id}?from_date=${from}&to_date=${to}`,
      mode: 'live',
      method: 'get',
    });
    if (res?.status_code === 200) {
      setStats(res.data);
    }
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
                <Heading marginRight="spacing.4" size="large">
                  {activeProcess?.name}
                </Heading>
                <InfoIcon marginX="spacing.3" />
                <Badge size="large" color="positive" icon={CheckIcon}>
                  Completed
                </Badge>
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
            onChange={setActiveTab}
            isLazy
          >
            <TabList>
              <TabItem value="overview">Overview</TabItem>
              <TabItem value="runs">Runs</TabItem>
              <TabItem value="transactions">Transactions</TabItem>
            </TabList>

            <TabPanel value="overview">
              <ProcessOverview
                dateRange={startEndDates}
                setDates={setStartEndDates}
                activeProcess={activeProcess}
                stats={stats}
              />
            </TabPanel>
            <TabPanel value="runs">
              <ProcessRunsDetail activeProcess={activeProcess} openRunDetail={openRunDetail} />
            </TabPanel>
            <TabPanel value="transactions">
              <ProcessTransactions activeProcess={activeProcess} />
            </TabPanel>
          </Tabs>
        </CardBody>
      </Card>
      {activeTab === 'overview' ? <ProcessCharts stats={stats} /> : null}
    </Box>
  );
};

export default ProcessStats;
