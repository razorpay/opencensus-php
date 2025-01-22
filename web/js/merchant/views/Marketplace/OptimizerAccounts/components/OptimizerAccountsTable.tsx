import React from 'react';
import { Link } from 'react-router-dom';
import {
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
  Indicator,
  Link as BladeLink,
} from '@razorpay/blade/components';

import { OPTIMIZER_ACCOUNTS_TABLE_HEADERS, ACCOUNT_STATUS_MAP } from '../constants';
import { OptimizerAccount } from '../types';

export const OptimizerAccountsTable = ({
  optimizerAccounts,
  isLoading,
  isRefreshing,
}: {
  optimizerAccounts: OptimizerAccount[];
  isLoading: boolean;
  isRefreshing: boolean;
}): JSX.Element => {
  return (
    <Table
      data={{ nodes: optimizerAccounts }}
      isHeaderSticky
      isLoading={isLoading}
      isRefreshing={isRefreshing}
    >
      {(tableData) => (
        <>
          <TableHeader>
            <TableHeaderRow>
              {OPTIMIZER_ACCOUNTS_TABLE_HEADERS.map((header) => (
                <TableHeaderCell key={header}>{header}</TableHeaderCell>
              ))}
            </TableHeaderRow>
          </TableHeader>
          <TableBody>
            {tableData.map((tableItem) => (
              <TableRow key={tableItem.id} item={tableItem}>
                <TableCell>
                  <Link to={`/route/optimizer/accounts/${tableItem.id}`}>
                    <BladeLink>{tableItem.id}</BladeLink>
                  </Link>
                </TableCell>
                <TableCell>{tableItem.account_name}</TableCell>
                <TableCell>
                  <Indicator color="positive">
                    {ACCOUNT_STATUS_MAP[tableItem.account_status]}
                  </Indicator>
                </TableCell>
                <TableCell>
                  {tableItem.accounts_map.map((account) => account.provider_name).join(', ')
                    .length > 30
                    ? `${tableItem.accounts_map
                        .map((account) => account.provider_name)
                        .join(', ')
                        .substring(0, 28)}...`
                    : tableItem.accounts_map.map((account) => account.provider_name).join(', ')}
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </>
      )}
    </Table>
  );
};
