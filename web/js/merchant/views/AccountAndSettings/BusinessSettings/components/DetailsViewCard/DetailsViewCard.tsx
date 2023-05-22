import { Box, Heading, Link, Text } from '@razorpay/blade/components';
import { DetailsViewCardProps } from 'merchant/views/AccountAndSettings/BusinessSettings/typings';
import React from 'react';
import { StyledDetailListing, StyledDivider } from './styled';

const DetailsViewCard = ({ title, info, handleAction }: DetailsViewCardProps): JSX.Element => {
  return (
    <Box
      display="flex"
      flexDirection="column"
      backgroundColor="surface.background.level3.lowContrast"
      padding={{
        base: ['spacing.4', 'spacing.5', 'spacing.6'],
        m: ['spacing.6', 'spacing.7', 'spacing.7'],
      }}
      gap="spacing.5"
    >
      <Heading>{title}</Heading>
      <StyledDivider />
      <Box display="flex" flexDirection="column" gap="spacing.7">
        {info.map((item, index) => (
          <Box key={index} display="flex" flexDirection="column">
            <Text type="subtle">{item.label}</Text>
            <StyledDetailListing>
              <Box
                display="flex"
                gap={{ base: 'spacing.3', m: 'spacing.2' }}
                justifyContent={{ base: 'space-between', m: 'initial' }}
                alignItems="center"
              >
                <Text weight="bold">{item.value || '----'}</Text>
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
