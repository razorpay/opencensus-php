import { Badge, Box, Switch, Text } from '@razorpay/blade/components';
import { ListItemCard } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/ListItemCard';
import React, { useState } from 'react';
import { getInstrumentLogo } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/getInstrumentLogo';

export default function WalletListItem({ item, activeBanks, setActiveBanks }) {
  const [isLoading, setIsLoading] = useState(false);

  return (
    <ListItemCard
      backgroundColor="surface.background.gray.moderate"
      Title={
        <Text variant="body" weight="medium" size="medium" color="surface.text.gray.normal">
          {item.name}
        </Text>
      }
      accessibilityLabel={item.name}
      LeftIcon={
        <Box
          display="flex"
          alignItems="center"
          padding="spacing.3"
          width="spacing.8"
          height="spacing.8"
          borderRadius="max"
          borderBottomStyle="solid"
          borderWidth="thin"
          borderColor="surface.border.gray.muted"
        >
          <img
            src={getInstrumentLogo('wallet', item.code)}
            alt={item.name}
            width={'100%'}
            onLoad={() => {
              setIsLoading(true);
            }}
            style={{
              display: `${isLoading ? 'initial' : 'none'}`,
            }}
          />
        </Box>
      }
      RightComponent={
        <Box display="flex" gap="16px">
          <Badge color="positive" size="large">
            Activated
          </Badge>
          <Switch
            accessibilityLabel={`${item.name} switch`}
            isChecked={activeBanks?.wallets?.includes(item.code)}
            onChange={({ isChecked }) => {
              setActiveBanks((prev) => {
                const updatedBanks = isChecked
                  ? { ...prev, wallets: [...prev.wallets, item.code] }
                  : {
                      ...prev,
                      wallets: prev.wallets.filter((prevItem) => prevItem !== item.code),
                    };
                return updatedBanks;
              });
            }}
          />
        </Box>
      }
    />
  );
}
