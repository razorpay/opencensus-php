import React from 'react';
import {
  Box,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
  Text,
  InfoIcon,
} from '@razorpay/blade/components';

import { TITLES } from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/ChannelStatusTable/constants';

import type { DeliveryReport } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

type ChannelStatusTableProps = {
  deliveryReports: DeliveryReport;
  timestamp: string;
};

const ChannelStatusTable = ({
  deliveryReports,
  timestamp,
}: ChannelStatusTableProps): React.ReactElement => {
  const mostRecentSms = deliveryReports?.sms?.[0];
  const mostRecentEmail = deliveryReports?.email?.[0];
  const mostRecentWhatsapp = deliveryReports?.whatsapp?.[0];

  return (
    <Table
      data={{
        nodes: Object.entries(TITLES).map(([id, { cellHeader }]) => ({ id, cellHeader })),
      }}
      data-analytics-name="channel-status-report-table"
    >
      {(tableData): React.ReactElement => {
        return (
          <>
            <TableHeader>
              <TableHeaderRow>
                <TableHeaderCell key="title">
                  <Box whiteSpace="normal">
                    <Text>Title</Text>
                  </Box>
                </TableHeaderCell>
                <TableHeaderCell key="sms">
                  <Box whiteSpace="normal">
                    <Text>SMS</Text>
                  </Box>
                </TableHeaderCell>
                <TableHeaderCell key="email">
                  <Box whiteSpace="normal">
                    <Text>E-mail</Text>
                  </Box>
                </TableHeaderCell>
                <TableHeaderCell key="whatsapp">
                  <Box whiteSpace="normal">
                    <Text>WhatsApp</Text>
                  </Box>
                </TableHeaderCell>
              </TableHeaderRow>
            </TableHeader>
            {tableData.length ? (
              <TableBody>
                {tableData.map(({ id, cellHeader }) => (
                  <TableRow key={id} item={{ id }}>
                    <TableCell>
                      <Box whiteSpace="normal">
                        <Text wordBreak="break-all">{cellHeader}</Text>
                      </Box>
                    </TableCell>
                    {TITLES[id as keyof typeof TITLES]
                      .cellValue({
                        smsDeliveryReport: mostRecentSms,
                        emailDeliveryReport: mostRecentEmail,
                        whatsAppDeliveryReport: mostRecentWhatsapp,
                        timestamp,
                      })
                      .map((cellValue: React.ReactElement | string, index: number) => (
                        <TableCell key={`${index}`}>
                          <Box whiteSpace="normal">
                            <Text wordBreak="break-all">{cellValue}</Text>
                          </Box>
                        </TableCell>
                      ))}
                  </TableRow>
                ))}
              </TableBody>
            ) : (
              // Grid column end value is given in accordance to number of table columns + 1
              <Box gridColumn="1/5" padding="spacing.8">
                <Box gap="spacing.2" display="flex" justifyContent="center" alignItems="center">
                  <InfoIcon size="xlarge" />{' '}
                  <Text weight="semibold" variant="body">
                    No data found
                  </Text>
                </Box>
              </Box>
            )}
          </>
        );
      }}
    </Table>
  );
};
export default ChannelStatusTable;
