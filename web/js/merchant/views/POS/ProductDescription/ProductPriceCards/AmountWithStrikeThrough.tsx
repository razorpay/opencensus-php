import React from 'react';
import { Box, Amount } from '@razorpay/blade/components';

import { OfferAmountComponentWrapper } from './styles';

type AmountWithStrikeThroughProps = {
  value: number;
  size?: 'heading-small-bold' | 'body-medium-bold' | 'heading-large-bold' | 'body-small-bold';
  testID?: string;
};

const AmountWithStrikeThrough = ({
  value = 0,
  size = 'body-medium-bold',
  testID,
}: AmountWithStrikeThroughProps): JSX.Element => {
  return (
    <OfferAmountComponentWrapper data-testid={testID}>
      <Amount
        value={value}
        isAffixSubtle={false}
        suffix="none"
        marginRight="spacing.2"
        size={size}
      />
      {/*TODO: migrate to isStrikeThrough prop in v11 */}
      <Box
        borderBottomColor="brand.gray.700.lowContrast"
        borderBottomWidth="thin"
        position="absolute"
        width="100%"
        top="50%"
        left="0px"
        right="0px"
      />
    </OfferAmountComponentWrapper>
  );
};

export default AmountWithStrikeThrough;
