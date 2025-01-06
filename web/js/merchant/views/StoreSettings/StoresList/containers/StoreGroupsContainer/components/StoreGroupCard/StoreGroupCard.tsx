import React from 'react';
import { Card, CardBody, Box, Text } from '@razorpay/blade/components';

import type { StoreGroupForListing } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/types';

type StoreGroupCardProps = {
  storeGroupInfo: StoreGroupForListing;
  activeStoreGroup: boolean;
  onCardSelect: () => void;
};

const StoreGroupCard = ({
  storeGroupInfo,
  activeStoreGroup,
  onCardSelect,
}: StoreGroupCardProps): React.ReactElement => {
  const { name, description, storesCount } = storeGroupInfo;
  return (
    <Card
      accessibilityLabel={`${name} store group card`}
      padding="spacing.3"
      minHeight="65px"
      backgroundColor={
        activeStoreGroup ? 'surface.background.gray.subtle' : 'surface.background.gray.intense'
      }
      elevation="none"
      onClick={onCardSelect}
    >
      <CardBody>
        <Box
          display="flex"
          alignItems={{ base: 'flex-start', m: 'center' }}
          justifyContent="space-between"
          flexDirection={{ base: 'column', m: 'row' }}
          gap="spacing.2"
          marginBottom="spacing.2"
        >
          <Text
            size="large"
            weight="semibold"
            color="surface.text.gray.subtle"
            wordBreak="break-word"
          >
            {name}
          </Text>
          <Box minWidth="80px" textAlign={{ base: 'left', m: 'right' }}>
            <Text size="medium" weight="medium" color="surface.text.gray.muted">
              {storesCount} stores
            </Text>
          </Box>
        </Box>
        <Text size="small" weight="medium" color="surface.text.gray.subtle" wordBreak="break-word">
          {description}
        </Text>
      </CardBody>
    </Card>
  );
};

export default StoreGroupCard;
