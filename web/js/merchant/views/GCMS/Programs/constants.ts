import { Program } from 'merchant/views/GCMS/Programs/types';
import { daysToMonths, getProgramDenomination } from 'merchant/views/GCMS/shared/utils';

export const getProgramHeaderSections = (program: Program) => {
  return [
    {
      name: 'Categories',
      value: program.policies?.program_category,
    },
    {
      name: 'Validity',
      value: program.policies?.gift_card_validity_in_days
        ? program.policies?.gift_card_validity_in_days > 30
          ? `${daysToMonths(program.policies?.gift_card_validity_in_days)} months`
          : `${program.policies?.gift_card_validity_in_days} days`
        : '-',
    },
    {
      name: 'Denomination',
      value: getProgramDenomination({ policy: program.policies }),
    },
  ];
};

export const getProgramContentSections = (program: Program) => {
  const minDiscount = program.policies?.min_discount_percent || 0;
  const maxDiscount = program.policies?.max_discount_percent || 0;
  return [
    {
      name: 'Program Name',
      value: program.name || '-',
    },
    {
      name: 'Issuer Details',
      value: 'Razorpay',
    },
    {
      name: 'Program Category',
      value: program.policies?.program_category || '-',
    },
    {
      name: 'Program Type',
      value: program.type || '-',
    },
    {
      name: 'Program Description',
      value: program.policies?.program_desc || '-',
    },
    // {
    //   name: 'Date Range',
    //   value: program.policies.,
    // },
    {
      name: 'Discount Range (%)',
      value: `${minDiscount / 100 || '-'}% to ${maxDiscount / 100 || '-'}%`,
    },
    {
      name: 'Program Distribution',
      value: program.policies?.program_distribution || '-',
    },
    // {
    //   name: 'Program Visiblity',
    //   value: program.policies.,
    // },
  ];
};

export const getProgramDenominationSections = (program: Program) => {
  const sortedDenominations = Array.isArray(program.policies?.gift_card_price_denominations)
    ? program.policies?.gift_card_price_denominations.sort((a, b) => a - b)
    : [];
  return [
    {
      name: 'Type',
      value: program.type || '-',
    },
    {
      name: 'Denomination',
      value: sortedDenominations.length > 0 ? sortedDenominations : undefined,
    },
  ];
};
