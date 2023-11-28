import React from 'react';
import { Accordion, AccordionItem } from '@razorpay/blade/components';
import styled from 'styled-components';

import DataTable from 'common/ui/Table/DataTable';

import { PAYMENT_METHODS_MAP } from './constants';
import { PartnerPricingPlans, SubmerchantPaymentMethod } from './types';

const StyledPricingPlansAccordion = styled.div(
  ({ theme }) => `
  div[data-blade-component='accordion'] > div {
    max-width: 100%;
  }
  .table {
    margin-bottom: 0px;
    thead > tr {
      height: ${theme.spacing[8]}px;
    }
    tr {
      height: ${theme.spacing[11]}px;
    }
    th {
      min-width: 80px;
      background-color: ${theme.colors.brand.gray[300].lowContrast};
    }
  }
`,
);

// Ref: https://github.com/razorpay/admin-dashboard/blob/39a5d8a3c232d1d0aa72f96d4031fd5032f49b82/js/admin/plans/NewPricing/PricingPreview.js#L17
const getRange = (item) => {
  if (item.amt_range?.[0] !== 'custom' && item.amt_range?.[0]?.length > 1) {
    const min = item.amt_range?.[0]?.split('-')[0] / 100 || 0;
    const max = item.amt_range?.[0]?.split('-')[1] / 100 || '';
    if (!isNaN(min) && !isNaN(max as number)) {
      if (min > 0 && max === '') {
        return `${min}+`;
      } else {
        return `${min}-${max}`;
      }
    } else {
      return '-';
    }
  }
  if (item.amt_range?.[0] === 'custom' && (item.min_amount_range || item.max_amount_range)) {
    return `${item.min_amount_range / 100}-${item.max_amount_range / 100}`;
  }
  if (item.amount_range_min === '' && item.amount_range_max === '') {
    return '';
  } else if (item.min_amount_range === '' && item.max_amount_range === '') {
    return '';
  }
  if (
    item.amount_range_min !== undefined &&
    item.amount_range_min !== null &&
    item.amount_range_max !== undefined &&
    item.amount_range_max !== null
  ) {
    const min = item.amount_range_min / 100;
    const max = item.amount_range_max / 100;
    return `${min}-${max}`;
  }
  return '-';
};

const staticFields = [
  ['Fee Model', (item) => item.fee_model],
  ['Amount range (₹)', getRange],
  ['Min Fee(₹)', (item) => item.min_fee / 100 || '-'],
  [
    'Rate (%)',
    (item) =>
      item.percent_rate_scale_factor && item.percent_rate
        ? `${item.percent_rate / item.percent_rate_scale_factor}%`
        : '-',
  ],
  ['Method Type', (item) => item.payment_method_type],
  ['Network', (item) => item.payment_network || '-'],
].map(([title, value]) => ({
  title,
  value,
}));

type PartnerPricingTableProps = {
  rules: PartnerPricingPlans['rules'];
  paymentMethod: SubmerchantPaymentMethod;
};
const PricingRulesTable = ({ rules, paymentMethod }: PartnerPricingTableProps): JSX.Element => {
  return (
    <StyledPricingPlansAccordion>
      <Accordion>
        <AccordionItem title={`Method: ${PAYMENT_METHODS_MAP[paymentMethod] || paymentMethod}`}>
          <DataTable title="Pricing Plans" columns={staticFields} items={rules} />
        </AccordionItem>
      </Accordion>
    </StyledPricingPlansAccordion>
  );
};
export default PricingRulesTable;
