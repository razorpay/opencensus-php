import React, { useState } from 'react';
import { ArrowRightIcon, Badge, Box, Link, Switch, Text } from '@razorpay/blade/components';
import { ListItemCard } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/ListItemCard';
import { getInstrumentLogo } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/getInstrumentLogo';
import _track from './track';

export default function CardProvider({
  finalCardConfigurationObj,
  setFinalCardConfigurationObj,
  cardProvider,
}) {
  const [isLoading, setIsLoading] = useState(false);
  return (
    <Box
      backgroundColor="surface.background.gray.moderate"
      paddingY="spacing.5"
      paddingX="spacing.4"
      borderRadius="large"
    >
      <Text weight="semibold" size="large">
        Card Provider
      </Text>
      <Link
        href="https://razorpay.com/docs/payments/payment-gateway/web-integration/standard/configure-payment-methods/supported-methods/#supported-card-networks"
        display="flex"
        icon={ArrowRightIcon}
        iconPosition="right"
      >
        See documentation
      </Link>
      <Box gap="16px" display="flex" flexDirection="column" margin="16px 0px 0px 0px">
        {cardProvider.map((item, index) => (
          <ListItemCard
            elevation="lowRaised"
            key={index}
            Title={
              <Text variant="body" weight="medium" size="medium" color={'surface.text.gray.normal'}>
                {item.name}
              </Text>
            }
            accessibilityLabel={item.name}
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
                    _track.cardProviderToggled(item.name, isChecked);
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
