import React from 'react';
import {
  Box,
  Tabs,
  TabList,
  TabItem,
  TabPanel,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
} from '@razorpay/blade/components';

import {
  getCardCoverageData,
  getCardCoverageColumns,
  getUPICoverageData,
  getUPICoverageColumns,
  getNetbankingCoverageData,
  getNetbankingCoverageColumns,
  getWalletCoverageData,
  getWalletCoverageColumns,
  getOtherMethodsCoverageData,
  getOtherMethodsCoverageColumns,
} from './utils';

export const InstrumentCoverage = ({ tabs, razorpayCoverage, gateway, gatewayCoverage }) => {
  const TableContent = ({ method }) => {
    let data = [];
    let columns = [];
    if (method === 'card') {
      data = getCardCoverageData(gatewayCoverage, razorpayCoverage);
      columns = getCardCoverageColumns(gateway);
    } else if (method === 'upi') {
      data = getUPICoverageData(gatewayCoverage, razorpayCoverage);
      columns = getUPICoverageColumns(gateway);
    } else if (method === 'netbanking') {
      data = getNetbankingCoverageData(gatewayCoverage, razorpayCoverage);
      columns = getNetbankingCoverageColumns(gateway);
    } else if (method === 'wallet') {
      data = getWalletCoverageData(gatewayCoverage, razorpayCoverage);
      columns = getWalletCoverageColumns(gateway);
    } else if (method === 'others') {
      data = getOtherMethodsCoverageData(gatewayCoverage, razorpayCoverage);
      columns = getOtherMethodsCoverageColumns(gateway);
    }
    return (
      <Box width="42rem" marginTop="spacing.3">
        <Table
          data={{
            nodes: data,
          }}
        >
          {(items) => {
            return (
              <>
                <TableHeader>
                  <TableHeaderRow>
                    {columns.map(({ label }, index) => (
                      <TableHeaderCell key={index}>{label}</TableHeaderCell>
                    ))}
                  </TableHeaderRow>
                </TableHeader>
                <TableBody>
                  {items.map((item, index) => (
                    <TableRow key={index} item={item}>
                      {columns.map(({ value }, index) => (
                        <TableCell key={index}>{value(item)}</TableCell>
                      ))}
                    </TableRow>
                  ))}
                </TableBody>
              </>
            );
          }}
        </Table>
      </Box>
    );
  };

  return (
    <Tabs variant="bordered" orientation="horizontal">
      <TabList>
        {tabs?.map(({ label, value }) => (
          <TabItem key={value} value={value}>
            {label}
          </TabItem>
        ))}
      </TabList>
      <TabPanel value="card">
        <TableContent method="card" />
      </TabPanel>
      <TabPanel value="upi">
        <TableContent method="upi" />
      </TabPanel>
      <TabPanel value="netbanking">
        <TableContent method="netbanking" />
      </TabPanel>
      <TabPanel value="wallet">
        <TableContent method="wallet" />
      </TabPanel>
      <TabPanel value="others">
        <TableContent method="others" />
      </TabPanel>
    </Tabs>
  );
};
