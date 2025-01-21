import * as React from 'react';
import { Box, Link, EditIcon, TrashIcon, PackageIcon, CashIcon } from '@razorpay/blade/components';

import type { Rule } from 'merchant/reducers/magicCheckout/magicxACODRules/types';
import type { PropFact } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const DOCUMENTATION_LINK =
  'https://razorpay.com/docs/payments/cod-checkout360/shopify/configure-cod/#advanced';

export const Facts: PropFact[] = [
  { name: 'quantity', label: 'Quantity', type: 'number' },
  { name: 'subtotal', label: 'Cart Total Before Discount', type: 'number' },
  { name: 'weight', label: 'Weight', type: 'number' },
  { name: 'discountPercentage', label: 'Discount Percentage', type: 'number' },
  { name: 'orderAmount', label: 'Cart Total', type: 'number' },
  {
    name: 'customerEmail',
    label: 'Email',
    type: 'string',
    placeholder: 'Enter comma separated values e.g. abc@gmail.com,xyz@gmail.com',
  },
  {
    name: 'phone',
    label: 'Phone',
    type: 'string',
    placeholder: 'Enter comma separated values e.g. +919988776655,+919999888866',
  },
  {
    name: 'zipcode',
    label: 'Zipcode',
    type: 'string',
    placeholder: 'Enter comma separated values e.g. 560021,400101',
  },
  { name: 'cartContainsDiscount', label: 'Cart Contains Discount', type: 'boolean' },
];

// Get Started
const SHOPIFY_APP_NAME = 'Razorpay COD & Magic Checkout App';
export const GetStartedCards = {
  appUpdateNotice: `Note: Please update the ${SHOPIFY_APP_NAME} on Shopify to create Payment and Shipping rules.`,
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
    rulesLimitMaxedOut: (limit: number) =>
      `Shopify allows a maximum of ${limit} Payment Rules can be created on your store.`,
  },
  shipping: {
    tableHeaderCells,
    tableRowCells,
    title: 'Shipping Rule',
    newRuleCTA: 'New Shipping Rule',
    newRuleCTAAccessibilityLabel: 'Create new shipping rule',
    noRuleText: 'No rule created',
    rulesLimitMaxedOut: (limit: number) =>
      `Shopify allows a maximum of ${limit} Shipping Rules can be created on your store.`,
  },
};
