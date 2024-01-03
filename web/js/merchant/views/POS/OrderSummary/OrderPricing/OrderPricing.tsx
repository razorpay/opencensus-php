import React from 'react';
import { Alert, Amount, Box, Divider, Heading, Text } from '@razorpay/blade/components';

import { PLAN_NAME_MAPPINGS } from 'merchant/views/POS/constants';
import { OrderPricing as OrderPricingType } from 'merchant/views/POS/types';

import PricingRow from './PricingRow';

type OrderPricingProps = {
  pricing: OrderPricingType | null;
};

const OrderPricing = ({ pricing }: OrderPricingProps): JSX.Element => {
  const totalAmountDetailedPricing = (pricing?.orderedDevices ?? []).map(
    ({ productDescription, deviceTotal, plan, quantity }) => ({
      title: `${productDescription.productTitle} | ${PLAN_NAME_MAPPINGS[plan]} (Qty: ${quantity})`,
      value: deviceTotal ?? 0,
    }),
  );

  const totalRentalAmountDetailedPricing = (pricing?.rentalDevices ?? []).map(
    ({ productDescription, rentalAmount, plan, quantity }) => ({
      title: `${productDescription.productTitle} | ${PLAN_NAME_MAPPINGS[plan]} (Qty: ${quantity})`,
      value: rentalAmount ?? 0,
    }),
  );

  return (
    <Box width="100%" maxWidth="450px">
      <Heading marginBottom="spacing.5">Payment Details</Heading>
      <PricingRow
        id="device-charges-container"
        title="Device charges"
        value={pricing?.deviceCharges}
        rows={totalAmountDetailedPricing}
      />
      <PricingRow title="GST @18%" value={pricing?.gstDevice} />
      <PricingRow title="Shipping" value={<Text marginX="spacing.2">{pricing?.shipping}</Text>} />
      <PricingRow
        title={<Heading>Total Order Price</Heading>}
        value={
          <Amount
            value={pricing?.total ?? 0}
            isAffixSubtle={false}
            suffix="none"
            size="heading-small-bold"
          />
        }
      />
      {pricing?.refund && pricing?.refund?.amount && pricing?.refund?.refId ? (
        <React.Fragment>
          <PricingRow
            title={<Heading color="feedback.text.positive.lowContrast">Total Refund</Heading>}
            value={
              <Amount
                value={pricing?.refund.amount ?? 0}
                isAffixSubtle={false}
                suffix="none"
                size="heading-small-bold"
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
      {(pricing?.rentalDevices || []).length > 0 && !pricing?.refund ? (
        <React.Fragment>
          <Divider marginBottom="spacing.4" />
          <PricingRow
            id="rental-charges-container"
            title="Rental charges"
            value={pricing?.rentalCharges}
            rows={totalRentalAmountDetailedPricing}
          />
          <PricingRow title="GST @18%" value={pricing?.gstRental} />
          <PricingRow title="Renewal" value={<Text marginX="spacing.2">Every Month</Text>} />
          <Box display="flex" flexDirection="column" alignItems="center" marginBottom="spacing.5">
            <Box maxWidth="400px">
              <Text size="small" textAlign="center" type="subtle">
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
