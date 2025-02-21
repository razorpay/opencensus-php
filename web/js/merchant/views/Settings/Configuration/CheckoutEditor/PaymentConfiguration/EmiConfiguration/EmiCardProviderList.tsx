import React from 'react';
import { Badge, Box, Switch, Text } from '@razorpay/blade/components';
import { ListItemCard } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/ListItemCard';
import { getInstrumentLogo } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/getInstrumentLogo';

export default function EmiCardProviderList({
  finalCardConfigurationObj,
  setFinalCardConfigurationObj,
  cardProvider,
}) {
  const [isLoading, setIsLoading] = React.useState(false);
  return (
    <Box borderRadius="large">
      <Box gap="16px" display="flex" flexDirection="column">
        {cardProvider.map((item, index) => (
          <ListItemCard
            elevation="lowRaised"
            key={index}
            Title={
              <Text variant="body" weight="medium" size="medium" color={'surface.text.gray.normal'}>
                {item.name}
              </Text>
            }
            accessibilityLabel={item.code}
            LeftIcon={
              <Box
                display="flex"
                alignItems="center"
                width="spacing.8"
                height="spacing.8"
                padding="spacing.2"
                borderRadius="max"
                borderBottomStyle="solid"
                borderWidth="thin"
                borderColor="surface.border.gray.muted"
              >
                <img
                  src={getInstrumentLogo('card', item.name)}
                  alt={item.name}
                  width="22.15px"
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
                  isChecked={finalCardConfigurationObj?.networks?.includes(item.name)}
                  onChange={({ isChecked }) => {
                    setFinalCardConfigurationObj((prev) => {
                      const updatedNetworks = isChecked
                        ? [...prev.networks, item.name]
                        : prev.networks.filter((prevItem) => prevItem !== item.name);
                      return {
                        ...prev,
                        networks: updatedNetworks,
                      };
                    });
                  }}
                />
              </Box>
            }
          />
        ))}
      </Box>
    </Box>
  );
}
