import * as React from 'react';
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
  type TableData,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { api } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/api';
import { ACOD_TABLE } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/constants';
import { useConfirm } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/common/components/ConfirmationModal';

import type { Rule, RuleType } from 'merchant/reducers/magicCheckout/magicxACODRules/types';

type ACODTableProps = {
  type: RuleType;
  rules: Rule[];
  createRule: () => void;
  deleteRule: (rule: Rule) => void;
  editRule: (rule: Rule) => void;
  ruleLimit: number;
  merchantId: string;
};
const AdvancedCODTable: React.FC<ACODTableProps> = ({
  type,
  rules,
  ruleLimit,
  createRule,
  deleteRule,
  editRule,
  merchantId,
}) => {
  const confirm = useConfirm();
  const data: TableData<Rule<typeof type>> = { nodes: rules };
  const hasRows = data.nodes.length > 0;
  const isRuleLimitMaxedOut = rules.length >= ruleLimit;

  const handleDelete = async (rule: Rule) => {
    await confirm.promise(() => api.deleteRule(rule, merchantId), {
      title: `Delete ${rule.name}`,
      description: `Are you sure you want to delete this ${rule.type} rule?`,
      confirmText: 'Delete',
      confirmColor: 'negative',
      dismissText: 'Cancel',
      onSuccess: (res: any) => {
        if (res?.success === true) {
          deleteRule(rule);
        }
      },
    });
  };

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
                          {cell.value(tableItem, { deleteRule: handleDelete, editRule })}
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

const mapStateToProps = (state) => ({
  merchantId: state.config?.config?.id || '',
});

export default connect(mapStateToProps)(AdvancedCODTable);
