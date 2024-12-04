import * as React from 'react';
import {
  Heading,
  Table,
  Box,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
  Text,
  Button,
  PlusIcon,
  type TableData,
} from '@razorpay/blade/components';

import { ACOD_TABLE } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/constants';

import type { Rule, RuleType } from 'merchant/reducers/magicCheckout/magicxACODRules/types';

type ACODTableProps = {
  type: RuleType;
  rules: Rule[];
  createRule: () => void;
  deleteRule: (rule: Rule) => void;
  editRule: (rule: Rule) => void;
};
export const AdvancedCODTable: React.FC<ACODTableProps> = ({
  type,
  rules,
  createRule,
  deleteRule,
  editRule,
}) => {
  const data: TableData<Rule<typeof type>> = { nodes: rules };
  const hasRows = data.nodes.length > 0;

  return (
    <Box display="flex" flexDirection="column" gap="spacing.5" width="100%">
      <Box
        display="flex"
        alignItems="center"
        justifyContent="space-between"
        minHeight="36px"
        width="100%"
      >
        <Heading size="medium" weight="semibold" color="surface.text.gray.normal">
          {ACOD_TABLE[type].title}
        </Heading>
        <Button
          icon={PlusIcon}
          iconPosition="left"
          onClick={createRule}
          accessibilityLabel={ACOD_TABLE[type].newRuleCTAAccessibilityLabel}
        >
          {ACOD_TABLE[type].newRuleCTA}
        </Button>
      </Box>
      <Table data={data} gridTemplateColumns="1fr 2fr 3fr 1fr" isHeaderSticky isFirstColumnSticky>
        {(tableData) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                {ACOD_TABLE[type].tableHeaderCells.map((cell) => (
                  <TableHeaderCell key={cell}>
                    <Text weight="semibold" marginRight="spacing.3">
                      {cell}
                    </Text>
                  </TableHeaderCell>
                ))}
              </TableHeaderRow>
            </TableHeader>
            {hasRows && (
              <TableBody>
                {tableData.map((tableItem, rowIndex) => (
                  <TableRow key={rowIndex} item={tableItem}>
                    {ACOD_TABLE[type].tableRowCells.map((cell, cellIndex) => {
                      return (
                        <TableCell key={`${rowIndex}${cellIndex}`}>
                          {cell.value(tableItem, { deleteRule, editRule })}
                        </TableCell>
                      );
                    })}
                  </TableRow>
                ))}
              </TableBody>
            )}
          </>
        )}
      </Table>
      {!hasRows && (
        <Box
          display="flex"
          alignItems="center"
          justifyContent="center"
          backgroundColor="surface.background.sea.subtle"
          height="68px"
        >
          <Text color="surface.text.gray.muted" size="medium" weight="regular">
            {ACOD_TABLE[type].noRuleText}
          </Text>
        </Box>
      )}
    </Box>
  );
};
