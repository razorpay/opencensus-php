import React, { useState } from 'react';
import { ArrowRightIcon, Badge, Box, Link, Switch, Text } from '@razorpay/blade/components';
import { ListItemCard } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/ListItemCard';
import { getInstrumentLogo } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/getInstrumentLogo';
import _track from './track';

export default function CardIssuer({
  updatedCardModalConfig,
  setUpdatedCardModalConfig,
  cardIssuer,
  org,
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
        Card Issuer
      </Text>
      <Link
        href={`https://${org.business_name?.toLowerCase() || "razorpay"}.com/docs/payments/payment-gateway/web-integration/standard/configure-payment-methods/supported-methods/#supported-banks`}
        display="flex"
        icon={ArrowRightIcon}
        target='_blank'
        iconPosition="right"
      >
        See documentation
      </Link>
      <Box gap="spacing.5" display="flex" flexDirection="column" marginTop="spacing.5">
        {cardIssuer.map((item, index) => (
          <ListItemCard
            elevation="lowRaised"
            key={index}
            Title={
              <Text variant="body" weight="medium" size="medium" color={'surface.text.gray.normal'}>
                {item.name}
              </Text>
            }
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
                  src={getInstrumentLogo('card', item.code)}
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
            accessibilityLabel={item.name}
            RightComponent={
              <Box display="flex" gap="spacing.5">
                <Badge color="positive" size="large">
                  Activated
                </Badge>
                <Switch
                  accessibilityLabel={`${item.name} switch`}
                  isChecked={updatedCardModalConfig?.issuers?.includes(item.code)}
                  onChange={({ isChecked }) => {
                    setUpdatedCardModalConfig((prev) => {
                      const updatedIssuers = isChecked
                        ? [...prev.issuers, item.code]
                        : prev.issuers.filter((prevItem) => prevItem !== item.code);

                      return {
                        ...prev,
                        issuers: updatedIssuers,
                      };
                    });
                    _track.cardIssuerToggled(item.code, isChecked);
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
