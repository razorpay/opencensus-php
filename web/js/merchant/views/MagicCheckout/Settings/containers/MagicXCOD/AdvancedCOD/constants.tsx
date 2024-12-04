import * as React from 'react';
import { Box, Link, EditIcon, TrashIcon, PackageIcon, CashIcon } from '@razorpay/blade/components';

import type { Rule } from 'merchant/reducers/magicCheckout/magicxACODRules/types';

// Get Started
export const GetStartedCards = {
  payment: {
    title: 'Payment Rule',
    description:
      'Set advanced rules for customising payment methods shown on checkout, based on AND, OR conditions',
    icon: <CashIcon size="xlarge" color="interactive.icon.positive.normal" />,
    accessibilityLabel: 'Create a payment rule',
  },
  shipping: {
    title: 'Shipping Rule',
    description:
      'Set advanced rules for customising shipping methods shown on checkout, based on AND, OR conditions',
    icon: <PackageIcon size="xlarge" color="interactive.icon.positive.normal" />,
    accessibilityLabel: 'Create a shipping rule',
  },
};

// Tables
const tableHeaderCells = ['Name', 'Description', 'Rule', 'Action'];
const tableRowCells: Array<{
  value: (
    rule: Rule,
    actions: {
      deleteRule: (rule: Rule) => void;
      editRule: (rule: Rule) => void;
    },
  ) => React.ReactNode;
}> = [
  {
    value: (rule) => rule.name,
  },
  {
    value: (rule) => rule.description,
  },
  {
    value: () => '-', // TODO: must be calculated using rule actions
  },
  {
    value: (rule, actions) => (
      <Box display="flex" gap="spacing.5">
        <Link
          variant="button"
          icon={TrashIcon}
          accessibilityLabel="Delete rule"
          onClick={() => {
            actions.deleteRule(rule);
          }}
        />
        <Link
          variant="button"
          icon={EditIcon}
          accessibilityLabel="Edit rule"
          onClick={() => {
            actions.editRule(rule);
          }}
        />
      </Box>
    ),
  },
];
export const ACOD_TABLE = {
  payment: {
    tableHeaderCells,
    tableRowCells,
    title: 'Payment Rule',
    newRuleCTA: 'New Payment Rule',
    newRuleCTAAccessibilityLabel: 'Create new payment rule',
    noRuleText: 'No rule created',
  },
  shipping: {
    tableHeaderCells,
    tableRowCells,
    title: 'Shipping Rule',
    newRuleCTA: 'New Shipping Rule',
    newRuleCTAAccessibilityLabel: 'Create new shipping rule',
    noRuleText: 'No rule created',
  },
};
