import React from 'react';
import { Box, Link, Text } from '@razorpay/blade/components';

import { DetailsViewCardProps } from 'merchant/views/AccountAndSettings/BusinessSettings/typings';

import { StyledDetailListing, StyledDivider } from './styled';
import { getI18FormattedPhoneNumber } from 'merchant/components/Mask/Contact';

const DetailsViewCard = ({ title, info, handleAction }: DetailsViewCardProps): JSX.Element => {
  return (
    <Box
      display="flex"
      flexDirection="column"
      backgroundColor="surface.background.gray.moderate"
      padding={{
        base: ['spacing.4', 'spacing.5', 'spacing.6'],
        m: ['spacing.6', 'spacing.7', 'spacing.7'],
      }}
      gap="spacing.5"
    >
      <Text size="large">{title}</Text>
      <StyledDivider />
      <Box display="flex" flexDirection="column" gap="spacing.7">
        {info.map((item, index) => (
          <Box key={index} display="flex" flexDirection="column">
            <Text color="surface.text.gray.subtle">{item.label}</Text>
            <StyledDetailListing>
              <Box
                display="flex"
                gap={{ base: 'spacing.3', m: 'spacing.2' }}
                justifyContent={{ base: 'space-between', m: 'initial' }}
                alignItems="center"
              >
                <Text weight="semibold">
                  {item.type === 'phone_number'
                    ? getI18FormattedPhoneNumber(item.value)
                    : item.value || '----'}
                </Text>

                {item.isEditEnable || item.showDisabledCTA ? (
                  <Link
                    variant="button"
                    size="small"
                    onClick={handleAction?.bind(null, item)}
                    isDisabled={!item.isEditEnable}
                    testID={item.type}
                  >
                    {item.value ? 'Edit' : 'Add'}
                  </Link>
                ) : null}
              </Box>
            </StyledDetailListing>
          </Box>
        ))}
      </Box>
    </Box>
  );
};

export default DetailsViewCard;
