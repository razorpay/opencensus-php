import { Program } from 'merchant/views/GCMS/Programs/types';
import { displayExpiryValidity, getProgramDenomination } from 'merchant/views/GCMS/shared/utils';
import { getFormattedAmount } from 'newAuth/utils';
import { DENOMINATION_TYPE_ENUM } from 'merchant/views/GCMS/shared/constants';

export const getProgramHeaderSections = (program: Program) => {
  return [
    {
      name: 'Categories',
      value: program.policies?.program_category,
    },
    {
      name: 'Validity',
      value:
        (program.policies?.gift_card_validity_in_days &&
          `${program.policies?.gift_card_validity_in_days} days`) ||
        '-',
    },
    {
      name: 'Denomination',
      value: getProgramDenomination({ policy: program.policies }),
    },
  ];
};

export function displayPriceDenominations(
  giftCardPriceType,
  giftCardDenominations,
  minPrice,
  maxPrice,
) {
  switch (giftCardPriceType) {
    case DENOMINATION_TYPE_ENUM.FIXED: {
      if (giftCardDenominations.length === 0) {
        return '-';
      } else if (giftCardDenominations.length === 1) {
        return getFormattedAmount(giftCardDenominations[0] * 100, true);
      } else {
        return (
          giftCardDenominations
            .slice(0, giftCardDenominations.length - 1)
            .map((val) => getFormattedAmount(val * 100, true))
            .join(', ') +
          ' & ' +
          getFormattedAmount(giftCardDenominations[giftCardDenominations.length - 1] * 100, true)
        );
      }
    }
    case DENOMINATION_TYPE_ENUM.RANGE: {
      return `${getFormattedAmount(minPrice * 100, true)} to ${getFormattedAmount(
        maxPrice * 100,
        true,
      )}`;
    }
    default:
      return '-';
  }
}

export const getProgramContentSections = (program: Program) => {
  const minDiscount = program.policies?.min_discount_percent || 0;
  return [
    {
      name: 'Program Name',
      value: program.name || '-',
    },
    {
      name: 'Program Description',
      value: program.policies?.program_desc || '-',
    },
    {
      name: 'Discount (%)',
      value: `${minDiscount || '-'}%`,
    },
  ];
};

export const getProgramGiftCardDetails = (program: Program) => {
  const {
    policies: { gift_card_maximum_price, gift_card_minimum_price },
  } = program;
  return [
    {
      name: 'Denomination Type',
      value:
        program.policies.gift_card_price_type === 'fixed'
          ? 'Fixed Denomination'
          : 'Customizable Denomination',
    },
    {
      name: 'Denomination Range',
      value: displayPriceDenominations(
        program.policies.gift_card_price_type,
        program.policies.gift_card_price_denominations,
        gift_card_minimum_price,
        gift_card_maximum_price,
      ),
    },
    {
      name: 'Card Expiry',
      value: displayExpiryValidity(program),
    },
    {
      name: 'Steps to Redeem',
      value: program.policies.steps_to_redeem || '-',
    },
    {
      name: 'Terms & Conditions',
      value: program.policies.tnc || '-',
    },
  ];
};

export const getProgramDenominationSections = (program: Program) => {
  const sortedDenominations = Array.isArray(program.policies?.gift_card_price_denominations)
    ? program.policies?.gift_card_price_denominations.sort((a, b) => a - b)
    : [];
  return [
    {
      name: '',
      value: sortedDenominations?.length > 0 ? sortedDenominations : undefined,
    },
  ];
};
