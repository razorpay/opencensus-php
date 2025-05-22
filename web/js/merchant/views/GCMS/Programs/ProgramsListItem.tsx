import React, { memo } from 'react';
import { Box, Card, CardBody, Divider, Text, Skeleton } from '@razorpay/blade/components';
import { truncatedString } from 'common/utils/rzp-utils';
import { Program } from 'merchant/views/GCMS/Programs/types';
import { displayExpiryValidity, getProgramDenomination } from 'merchant/views/GCMS/shared/utils';
import { DEFAULT_IMAGE } from '../shared/constants';
import { displayPriceDenominations } from './constants';

type Props = {
  program: Program;
  onClick: () => void;
};

const ProgramsListItem: React.FC<Props> = ({
  program,
  onClick,
  programImages,
  isProgramImagesLoading,
}) => {
  return (
    <Box
      paddingX="spacing.0"
      paddingY="spacing.0"
      width="210px"
      height="257px"
      borderRadius="medium"
      borderColor="surface.border.gray.muted"
      overflow="hidden"
    >
      <Card
        onClick={onClick}
        accessibilityLabel="GCMS Programs Card"
        elevation="midRaised"
        padding="16px"
        width="100%"
        height="100%"
        testID="program-card"
      >
        <CardBody>
          <Box
            width="100%"
            height="117px"
            display="flex"
            justifyContent="center"
            alignItems="center"
          >
            {isProgramImagesLoading ? (
              <Skeleton borderRadius="medium" height="100%" width="100%" />
            ) : (
              <img
                src={(programImages && programImages[program.id]) || DEFAULT_IMAGE}
                height="100%"
                width="100%"
                style={{ 'object-fit': 'cover', 'border-radius': '5px' }}
                alt={program.name}
                className="card-image"
              />
            )}
          </Box>
          <Box>
            <Box marginTop="12px" height="32px" marginBottom="8px">
              <Text weight="semibold" size="small" truncateAfterLines={2}>
                {program.name}
              </Text>
            </Box>
            <Box>
              <Divider />
            </Box>
            <Box paddingTop="12px" display="flex" flexDirection="column" gap="8px">
              <Box display="flex" flexDirection="row" justifyContent="space-between">
                <Text color="surface.text.gray.muted" size="xsmall">
                  Validity
                </Text>
                <Text size="xsmall">{displayExpiryValidity(program)}</Text>
              </Box>
              <Box
                paddingTop="spacing.2"
                display="flex"
                flexDirection="row"
                justifyContent="space-between"
              >
                <Text color="surface.text.gray.muted" size="xsmall">
                  Denomination
                </Text>
                <Text truncateAfterLines={1} size="xsmall">
                  {displayPriceDenominations(
                    program.policies.gift_card_price_type,
                    program.policies.gift_card_price_denominations,
                    program.policies.gift_card_minimum_price,
                    program.policies.gift_card_maximum_price,
                  )}
                </Text>
              </Box>
            </Box>
          </Box>
        </CardBody>
      </Card>
    </Box>
  );
};

export default memo(ProgramsListItem);
