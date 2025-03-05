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

import { browserIcons } from '@apps/digital-bills/src/assets/icons/browser';
import { osIcons } from '@apps/digital-bills/src/assets/icons/os';
import imagePlaceholder from '@apps/digital-bills/src/assets/icons/image-placeholder.svg';
import parseUserAgent from '@apps/digital-bills/src/utils/helpers/parseUserAgent';

import type { Bill } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

type BillVisitTableProps = {
  visits: Bill['visits'];
};

const BillVisitTable = ({ visits = [] }: BillVisitTableProps): React.ReactElement => {
  return (
    <Table
      data={{
        nodes: visits.map((visit, index) => ({
          ...parseUserAgent(visit.userAgent),
          id: `${index}`,
          ip: visit.ip,
        })),
      }}
    >
      {(tableData): React.ReactElement => {
        return (
          <>
            <TableHeader>
              <TableHeaderRow>
                <TableHeaderCell key="device">
                  <Box whiteSpace="normal">
                    <Text>Device Name</Text>
                  </Box>
                </TableHeaderCell>
                <TableHeaderCell key="browser">
                  <Box whiteSpace="normal">
                    <Text>Browser</Text>
                  </Box>
                </TableHeaderCell>
                <TableHeaderCell key="os">
                  <Box whiteSpace="normal">
                    <Text>Operating System</Text>
                  </Box>
                </TableHeaderCell>
                <TableHeaderCell key="ip">
                  <Box whiteSpace="normal">
                    <Text>IP Address</Text>
                  </Box>
                </TableHeaderCell>
              </TableHeaderRow>
            </TableHeader>
            {tableData.length ? (
              <TableBody>
                {tableData.map(({ id, device, browser, os, ip }) => (
                  <TableRow key={id} item={{ id, device, browser, os, ip }}>
                    <TableCell>
                      <Box whiteSpace="normal">
                        <Text wordBreak="break-all">{device || '-'}</Text>
                      </Box>
                    </TableCell>
                    <TableCell>
                      <Box whiteSpace="normal" display="flex" alignItems="center" gap="spacing.3">
                        <img
                          src={browserIcons[browser] || imagePlaceholder}
                          alt={`${browser} icon`}
                          width="32px"
                          height="32px"
                        />
                        <Text wordBreak="break-all">{browser || '-'}</Text>
                      </Box>
                    </TableCell>
                    <TableCell>
                      <Box whiteSpace="normal" display="flex" alignItems="center" gap="spacing.3">
                        <img
                          src={osIcons[os] || imagePlaceholder}
                          alt={`${os} icon`}
                          width="32px"
                          height="32px"
                        />
                        <Text wordBreak="break-all">{os || '-'}</Text>
                      </Box>
                    </TableCell>
                    <TableCell>
                      <Box whiteSpace="normal">
                        <Text wordBreak="break-all">{ip || '-'}</Text>
                      </Box>
                    </TableCell>
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
export default BillVisitTable;
