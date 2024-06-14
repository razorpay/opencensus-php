import React from 'react';
import {
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
  Badge,
  TablePagination,
  TableData,
  Link,
  ChevronRightIcon,
  Text,
  Box,
} from '@razorpay/blade/components';
import { DeviceType, PosActivationStatus, TableItem } from '../../types';
import useDevice from '../../hooks/useDevice';

type TableComponentPropType = {
  merchantsKYC: TableItem[];
};

const DashboardTable: React.FC<TableComponentPropType> = ({ merchantsKYC }) => {
  const isMobile = useDevice(DeviceType.MOBILE);

  const nodes: TableItem[] = merchantsKYC;
  const data: TableData<TableItem> = {
    nodes,
  };

  return (
    <Table
      data={data}
      isHeaderSticky
      height="600px"
      gridTemplateColumns={
        isMobile ? 'repeat(2,minmax(100px, 1fr))' : 'repeat(8,minmax(100px, 1fr))'
      }
      pagination={
        <TablePagination
          onPageChange={console.log}
          defaultPageSize={10}
          onPageSizeChange={console.log}
          showPageSizePicker
          showPageNumberSelector
        />
      }
    >
      {(tableData) => (
        <>
          <TableHeader>
            <TableHeaderRow>
              {!isMobile ? <TableHeaderCell>Initiated On</TableHeaderCell> : null}
              <TableHeaderCell>MID</TableHeaderCell>
              {!isMobile ? (
                <>
                  <TableHeaderCell>Merchant Name</TableHeaderCell>
                  <TableHeaderCell>Mobile Number</TableHeaderCell>
                  <TableHeaderCell>Business Model</TableHeaderCell>
                  <TableHeaderCell>Pricing</TableHeaderCell>
                </>
              ) : null}
              <TableHeaderCell>Status</TableHeaderCell>
              {!isMobile ? <TableHeaderCell>{''}</TableHeaderCell> : null}
            </TableHeaderRow>
          </TableHeader>
          <TableBody>
            {tableData.map((tableItem, index) => (
              <TableRow key={index} item={tableItem}>
                {!isMobile ? (
                  <TableCell>
                    {tableItem.initiatedOn.toLocaleDateString('en-IN', {
                      year: 'numeric',
                      month: '2-digit',
                      day: '2-digit',
                    })}
                  </TableCell>
                ) : null}
                <TableCell>
                  <Box display="flex" flexDirection="column">
                    <Text>{tableItem.mId}</Text>
                    {isMobile ? (
                      <Box display="flex" flexDirection="column">
                        <Text color="surface.text.gray.subtle">
                          {tableItem.merchantName} &#8226; {tableItem.mobileNumber}
                        </Text>
                        <Text color="surface.text.gray.subtle">
                          {tableItem.initiatedOn.toLocaleDateString('en-IN', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                          })}{' '}
                          &#8226; {tableItem.businessModel} &#8226; {tableItem.pricing}
                        </Text>
                      </Box>
                    ) : null}
                  </Box>
                </TableCell>
                {!isMobile ? (
                  <>
                    <TableCell>{tableItem.merchantName}</TableCell>
                    <TableCell>{tableItem.mobileNumber}</TableCell>
                    <TableCell>{tableItem.businessModel}</TableCell>
                    <TableCell>{tableItem.pricing}</TableCell>
                  </>
                ) : null}
                <TableCell>
                  <Box display="flex" flexDirection="column">
                    <Badge
                      size="medium"
                      color={
                        tableItem.status === PosActivationStatus.ACTIVATED
                          ? 'positive'
                          : tableItem.status === PosActivationStatus.UNDER_REVIEW
                          ? 'notice'
                          : tableItem.status === PosActivationStatus.REJECTED
                          ? 'negative'
                          : 'neutral'
                      }
                    >
                      {tableItem.status}
                    </Badge>
                    {isMobile ? (
                      <Link
                        marginTop="spacing.5"
                        alignSelf="left"
                        iconPosition="right"
                        icon={ChevronRightIcon}
                        variant="anchor"
                        size="medium"
                      >
                        {''}
                      </Link>
                    ) : null}
                  </Box>
                </TableCell>
                {!isMobile ? (
                  <TableCell>
                    <Link
                      margin="0px"
                      alignSelf="center"
                      iconPosition="right"
                      icon={ChevronRightIcon}
                      variant="anchor"
                      size="medium"
                    >
                      Details
                    </Link>
                  </TableCell>
                ) : null}
              </TableRow>
            ))}
          </TableBody>
        </>
      )}
    </Table>
  );
};

export default DashboardTable;
