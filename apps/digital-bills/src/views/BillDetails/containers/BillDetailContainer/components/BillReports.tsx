import React, { useMemo } from 'react';
import { Divider, TabItem, TabList, TabPanel, Tabs, Box } from '@razorpay/blade/components';
import moment from 'moment';

import BillVisitTable from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/BillVisitTable';
import ChannelStatusTableContainer from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/ChannelStatusTable';

import type {
  Bill,
  ChannelReport,
  DeliveryReport,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

type BillReportsProp = {
  visits: Bill['visits'];
  deliveryReport: DeliveryReport;
  timestamp: string;
};

const BillReports = (props: BillReportsProp): React.ReactElement => {
  const { visits = [], deliveryReport = {}, timestamp } = props;
  const mostRecentDeliveryReports = useMemo<DeliveryReport>(
    () =>
      Object.entries<ChannelReport[]>(deliveryReport).reduce(
        (acc, [key, value]) => {
          acc[key] = [...value].sort((reportA, reportB) =>
            moment(reportB.createdAt).diff(moment(reportA.createdAt)),
          );
          return acc;
        },
        { sms: [], email: [], whatsapp: [] },
      ),
    [deliveryReport],
  );

  return (
    <Tabs variant="borderless" isLazy data-analytics-name="bill-report-tabs">
      <TabList marginX="spacing.6">
        <TabItem value="channelStatusReport">Channel Status Report</TabItem>
        <TabItem value="billReadReceipt">Bill Read Receipt</TabItem>
      </TabList>
      <Divider />
      <Box paddingTop="spacing.4">
        <TabPanel value="channelStatusReport">
          <ChannelStatusTableContainer
            deliveryReports={mostRecentDeliveryReports}
            timestamp={timestamp}
          />
        </TabPanel>
        <TabPanel value="billReadReceipt">
          <BillVisitTable visits={visits} />
        </TabPanel>
      </Box>
    </Tabs>
  );
};

export default React.memo(BillReports);
