import React from 'react';
import { Alert, Amount, Box, Divider, Text } from '@razorpay/blade/components';

import { PLAN_NAME_MAPPINGS } from 'apps/pos/src/app/views/SelfServe/constants';
import { isValidFee } from 'apps/pos/src/app/views/SelfServe/helpers';
import { OrderPricing as OrderPricingType } from 'apps/pos/src/app/views/SelfServe/types';

import PricingRow from './PricingRow';

type OrderPricingProps = {
  pricing: OrderPricingType | null;
};

const OrderPricing = ({ pricing }: OrderPricingProps): JSX.Element => {
  const totalAmountDetailedPricingWithOffers = (pricing?.orderedDevicesWithOffer ?? []).map(
    ({ productDescription, deviceTotal, plan, quantity, prevDeviceTotal }) => ({
      title: `${productDescription?.productTitle}`,
      value: deviceTotal ?? 0,
      prevValue: prevDeviceTotal,
      subTitle: ` ${PLAN_NAME_MAPPINGS[plan]} | (Qty: ${quantity})`,
    }),
  );

  const totalAmountDetailedPricing = (pricing?.orderedDevices ?? []).map(
    ({ productDescription, deviceTotal, plan, quantity }) => ({
      title: PLAN_NAME_MAPPINGS[plan]
        ? `${productDescription?.productTitle} | ${PLAN_NAME_MAPPINGS[plan]} (Qty: ${quantity})`
        : `${productDescription?.productTitle} (Qty: ${quantity})`,
      value: deviceTotal ?? 0,
    }),
  );

  const totalRentalAmountDetailedPricingWithOffers = (pricing?.rentalDevicesWithOffer ?? []).map(
    ({ productDescription, plan, quantity }) => ({
      title: ` ${PLAN_NAME_MAPPINGS[plan]} - ${productDescription?.productTitle} X ${quantity}`,
      value: 0,
      subTitle: `first ${productDescription?.rentalDiscountPeriod} ${
        productDescription?.rentalDiscountPeriod > 1 ? 'months' : 'month'
      }`,
    }),
  );

  const totalRentalAmountDetailedPricingPostOffer = (pricing?.rentalDevicesWithOffer ?? [])
    .filter(({ nextRentalAmount }) => isValidFee(nextRentalAmount))
    .map(({ productDescription, nextRentalAmount, plan, quantity, prevRetalAmount }) => ({
      title: ` ${PLAN_NAME_MAPPINGS[plan]} - ${productDescription?.productTitle} X ${quantity}`,
      value: nextRentalAmount as number,
      prevValue: prevRetalAmount,
      subTitle: `post ${productDescription?.rentalDiscountPeriod} ${
        productDescription?.rentalDiscountPeriod > 1 ? 'months' : 'month'
      }`,
    }));

  const totalRentalAmountDetailedPricing = (pricing?.rentalDevices ?? []).map(
    ({ productDescription, rentalAmount, plan, quantity }) => ({
      title: `${productDescription?.productTitle} | ${PLAN_NAME_MAPPINGS[plan]} (Qty: ${quantity})`,
      value: rentalAmount ?? 0,
    }),
  );

  const rentalOfferItems = [
    ...totalRentalAmountDetailedPricingWithOffers,
    ...totalRentalAmountDetailedPricingPostOffer,
  ];

  const MDR_PRICING_ROW = {
    title: 'MDR (%)',
    value: 0,
    isRenderValuePlanText: true,
  };

  return (
    <Box width="100%" maxWidth="450px">
      <Text marginBottom="spacing.5" size="large">
        Payment Details
      </Text>
      <PricingRow
        id="device-charges-container"
        title="Device charges"
        value={pricing?.deviceCharges}
        rows={totalAmountDetailedPricing}
        offerRows={totalAmountDetailedPricingWithOffers}
        isPartnerPricing={pricing?.isPartnerPricing}
      />
      <PricingRow title="GST @18%" value={pricing?.gstDevice} />
      <PricingRow title="Shipping" value={<Text marginX="spacing.2">{pricing?.shipping}</Text>} />
      <PricingRow
        title={<Text size="large">Total Order Price</Text>}
        value={
          <Amount
            value={pricing?.total ?? 0}
            isAffixSubtle={false}
            suffix="none"
            type="body"
            size="large"
            weight="semibold"
            testID="total-order-price"
          />
        }
      />
      {pricing?.refund && pricing?.refund?.amount && pricing?.refund?.refId ? (
        <React.Fragment>
          <PricingRow
            title={
              <Text color="feedback.text.positive.intense" size="large">
                Total Refund
              </Text>
            }
            value={
              <Amount
                value={pricing?.refund.amount ?? 0}
                isAffixSubtle={false}
                suffix="none"
                type="body"
                size="large"
                weight="semibold"
              />
            }
          />
          <Alert
            color="positive"
            title="Your refund has successfully been processed!"
            description={`Contact your bank with refund transaction reference number ${pricing.refund.refId}`}
            isDismissible={false}
          />
        </React.Fragment>
      ) : null}
      {((pricing?.rentalDevices || []).length > 0 ||
        (pricing?.rentalDevicesWithOffer || []).length > 0) &&
      !pricing?.refund ? (
        <React.Fragment>
          <Divider marginBottom="spacing.4" />
          <PricingRow
            id="rental-charges-container"
            title="Rental charges"
            value={pricing?.rentalCharges}
            rows={totalRentalAmountDetailedPricing}
            offerRows={rentalOfferItems?.length > 0 ? [...rentalOfferItems, MDR_PRICING_ROW] : []}
            isPartnerPricing={pricing?.isPartnerPricing}
          />
          <PricingRow title="GST @18%" value={pricing?.gstRental} />
          <PricingRow title="Renewal" value={<Text marginX="spacing.2">Every Month</Text>} />
          <Box display="flex" flexDirection="column" alignItems="center" marginBottom="spacing.5">
            <Box maxWidth="400px">
              <Text size="small" textAlign="center" color="surface.text.gray.subtle">
                Once your device is delivered, monthly rental charges will automatically be debited
                from your account.
              </Text>
            </Box>
          </Box>
        </React.Fragment>
      ) : null}
    </Box>
  );
};

export default OrderPricing;
