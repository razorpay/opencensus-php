import React from 'react';
import startCase from 'lodash/startCase';
import { ArrowRightIcon, Box, Link, Switch, Text } from '@razorpay/blade/components';
import { ListItemCard } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/ListItemCard';
import _track from './track';

export default function CardType({
  updatedCardModalConfig,
  setUpdatedCardModalConfig,
  cardType,
  org,
}) {
  return (
    <Box
      backgroundColor="surface.background.gray.moderate"
      paddingY="spacing.5"
      paddingX="spacing.4"
      borderRadius="large"
    >
      <Text weight="semibold" size="medium">
        Card Type
      </Text>
      <Link
        href={`https://${org.business_name?.toLowerCase() || "razorpay"}.com/docs/payments/payment-gateway/web-integration/standard/configure-payment-methods/supported-methods/#supported-cards`}
        icon={ArrowRightIcon}
        size='small'
        target="_blank"
        iconPosition="right"
      >
        See documentation
      </Link>
      <Box gap="spacing.5" display="flex" flexDirection="column" marginTop="spacing.5">
        {cardType.map((item, index) => (
          <ListItemCard
            elevation="lowRaised"
            key={index}
            Title={
              <Text
                variant="body"
                weight="medium"
                size="medium"
                color={item.isDisabled ? 'surface.text.gray.muted' : 'surface.text.gray.normal'}
              >
                {`${startCase(item.title)} Cards`}
              </Text>
            }
            accessibilityLabel={item.title}
            RightComponent={
              <Switch
                accessibilityLabel={`${item.title} switch`}
                isChecked={updatedCardModalConfig?.types?.includes(item.title)}
                onChange={({ isChecked }) => {
                  setUpdatedCardModalConfig((prev) => {
                    const updatedTypes = isChecked
                      ? [...prev.types, item.title]
                      : prev.types.filter((prevItem) => prevItem !== item.title);

                    return {
                      ...prev,
                      types: updatedTypes,
                    };
                  });
                  _track.cardTypeToggled(item.title, isChecked);
                }}
              />
            }
          />
        ))}
      </Box>
    </Box>
  );
}
