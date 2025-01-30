import React from 'react';
import {
  Alert,
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
  InfoIcon,
} from '@razorpay/blade/components';

import { useACODTable } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/Table/hooks';
import { ACOD_TABLE } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/constants';

import type {
  Rule as ACODRule,
  RuleType as ACODRuleType,
} from 'merchant/reducers/magicCheckout/magicxACODRules/types';
import type { ACODTableData } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/Table/types';

type ACODTableProps = {
  type: ACODRuleType;
  rules: ACODRule[];
  ruleLimit: number;
};
const AdvancedCODTable: React.FC<ACODTableProps> = ({ type, rules, ruleLimit }) => {
  const { nodes, createRule, editRule, deleteRule } = useACODTable(type, rules);
  const data: ACODTableData<typeof type> = { nodes };
  const hasRows = data.nodes.length > 0;
  const isRuleLimitMaxedOut = rules.length >= ruleLimit;

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
          isDisabled={isRuleLimitMaxedOut}
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
      {isRuleLimitMaxedOut && (
        <Alert
          color="notice"
          isDismissible={false}
          description={<span>{ACOD_TABLE[type].rulesLimitMaxedOut(ruleLimit)}</span>}
          isFullWidth
          icon={() => <InfoIcon color="feedback.icon.notice.intense" />}
        />
      )}
    </Box>
  );
};

export default AdvancedCODTable;
