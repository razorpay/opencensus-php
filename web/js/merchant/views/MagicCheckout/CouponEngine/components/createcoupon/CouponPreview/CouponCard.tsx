import React, { useContext } from 'react';
import CouponIcon from 'assets/coupons/coupon-icon.svg';
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';
import { Box, Text, CheckIcon } from '@razorpay/blade/components';
import { makeSpace, makeSize } from '@razorpay/blade/utils';

const CouponCard = () => {
  const { widgetsData } = useContext(ModalContext);
  return (
    <Box
      display="flex"
      flexDirection="column"
      height={makeSize(130)}
      borderRadius="large"
      backgroundColor="surface.background.gray.intense"
    >
      <Box
        display="flex"
        alignItems="center"
        paddingX="spacing.6"
        paddingBottom="spacing.2"
        paddingTop="spacing.5"
        borderRadius="large"
        borderWidth="thinner"
        borderStyle="solid"
        borderColor="surface.border.gray.subtle"
        borderBottomLeftRadius="none"
        borderBottomRightRadius="none"
        borderBottomWidth="none"
      >
        <img src={CouponIcon} alt="coupon icon" />
        <Text marginLeft="spacing.6">
          {' '}
          {widgetsData.couponDetails.description === ''
            ? 'Coupon Description'
            : widgetsData.couponDetails.description}
        </Text>
      </Box>
      <Box position="relative" minHeight="spacing.6" width="100%" overflow="hidden">
        <Box height="50%" backgroundColor="surface.background.gray.intense" />
        <Box height="50%" backgroundColor="surface.background.gray.moderate" />

        <Box
          position="absolute"
          top="spacing.0"
          width="spacing.6"
          borderRadius="xlarge"
          borderWidth="thinner"
          borderColor="surface.border.gray.subtle"
          height="100%"
          backgroundColor="surface.background.gray.moderate"
          left={makeSize(-10)}
        />
        <Box
          position="absolute"
          top="spacing.0"
          width="spacing.6"
          borderRadius="xlarge"
          borderWidth="thinner"
          borderColor="surface.border.gray.subtle"
          height="100%"
          backgroundColor="surface.background.gray.moderate"
          right={makeSpace(-10)}
        />

        <Box
          position="absolute"
          marginY="auto"
          marginX="spacing.5"
          display="flex"
          alignItems="center"
          width="89%"
          top="spacing.0"
          height="100%"
          justifyContent="space-between"
          backgroundColor="transparent"
        >
          {Array.from({ length: 16 }).map((_, idx) => (
            <Box
              height={makeSize(10)}
              width="spacing.4"
              borderRadius="round"
              backgroundColor="surface.background.gray.intense"
              key={idx}
            />
          ))}
        </Box>
      </Box>
      <Box
        width="100%"
        height="100%"
        backgroundColor="surface.background.gray.moderate"
        display="flex"
        alignItems="center"
        justifyContent="space-between"
        borderBottomLeftRadius="medium"
        borderBottomRightRadius="medium"
        paddingY="spacing.0"
        borderWidth="thinner"
        paddingX="spacing.6"
        borderTopWidth="none"
        borderColor="surface.border.gray.subtle"
      >
        <Text as="span" color="surface.text.gray.subtle">
          T&C Applicable • T&Cs
        </Text>

        <Box
          backgroundColor="surface.background.gray.intense"
          paddingY="spacing.2"
          paddingX="spacing.3"
          height="spacing.7"
          alignItems="center"
          position="relative"
          display="flex"
          borderRadius="medium"
          gap="spacing.1"
        >
          <CheckIcon color="surface.icon.onSea.onSubtle" size="small" />
          <Text
            as="p"
            truncateAfterLines={20}
            color="surface.text.gray.subtle"
            size="small"
            wordBreak="break-all"
            weight="semibold"
          >
            {widgetsData.couponDetails.code === '' ? 'Coupon Code' : widgetsData.couponDetails.code}
          </Text>
          <Box
            position="absolute"
            width={makeSize(14)}
            height="spacing.3"
            top={makeSpace(9)}
            borderRadius="large"
            backgroundColor="surface.background.gray.moderate"
            left={makeSpace(-10)}
          ></Box>
          <Box
            position="absolute"
            width={makeSize(14)}
            height="spacing.3"
            top={makeSpace(9)}
            borderRadius="large"
            backgroundColor="surface.background.gray.moderate"
            right={makeSpace(-10)}
          ></Box>
        </Box>
      </Box>
    </Box>
  );
};
export default CouponCard;
