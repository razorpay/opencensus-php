/* eslint-disable i18n-rules/no-hardcoded-i18n-types */
/* eslint-disable jsx-a11y/no-static-element-interactions */
import React from 'react';
import {
  Box,
  Amount,
  Divider,
  Text,
  ChevronUpIcon,
  ChevronDownIcon,
} from '@razorpay/blade/components';
import { DeviceCharges, DeviceOrderSummaryItem } from 'apps/pos/src/app/types/modular';

interface CollapsedContent {
  title: string;
  value: number | string | null | undefined;
  content: JSX.Element;
}

interface PricingItemProps {
  title: string;
  value: number | string | null | undefined;
  titleTrailing?: JSX.Element;
  size?: 'small' | 'medium' | 'large';
  color?: 'surface.text.gray.muted' | 'surface.text.gray.normal';
}

interface DeviceOrderPricingProps {
  addedDevices: DeviceOrderSummaryItem[];
  orderSummary: DeviceCharges;
}

const PricingItem = ({
  title,
  value,
  titleTrailing,
  size,
  color = 'surface.text.gray.normal',
}: PricingItemProps) => {
  return (
    <Box display="flex" alignItems="center" justifyContent="space-between" marginBottom="spacing.4">
      <Box display="flex" alignItems="center">
        <Text size={size} color={color} marginRight="spacing.3">
          {title}
        </Text>
        {titleTrailing}
      </Box>
      {typeof value == 'string' ? (
        <Text size={size} color={color}>
          {value}
        </Text>
      ) : (
        <Amount
          value={value ?? 0}
          isAffixSubtle={false}
          suffix="none"
          weight="semibold"
          size={size}
          color={color}
        />
      )}
    </Box>
  );
};

const CollapsedContent = ({ title, value, content }: CollapsedContent): JSX.Element => {
  const [isExpanded, setIsExpanded] = React.useState(false);
  return (
    <Box>
      <div onClick={() => setIsExpanded(!isExpanded)} onKeyUp={() => null}>
        <PricingItem
          title={title}
          value={value}
          titleTrailing={
            isExpanded ? (
              <ChevronUpIcon size="large" color="interactive.icon.gray.normal" />
            ) : (
              <ChevronDownIcon size="large" color="interactive.icon.gray.normal" />
            )
          }
        />
      </div>
      {isExpanded ? <Box>{content}</Box> : null}
    </Box>
  );
};

const DeviceOrderPricing = ({
  addedDevices,
  orderSummary,
}: DeviceOrderPricingProps): JSX.Element | null => {
  return (
    <React.Fragment>
      <CollapsedContent
        title="Device Charges"
        value={orderSummary?.deviceCharge}
        content={
          <Box>
            {addedDevices?.map((device) => (
              <PricingItem
                key={device.itemId}
                title={device.deviceName}
                value={device.setupCharge}
                size="small"
                color="surface.text.gray.muted"
              />
            ))}
          </Box>
        }
      />
      <PricingItem title="Advance Rental Charges" value={orderSummary?.advanceRentalCharge} />
      <PricingItem title="Paper roll" value={orderSummary?.paperRollCharge} />
      <PricingItem title="Shipping" value={orderSummary?.shippingCharge} />
      <PricingItem title="GST @18%" value={orderSummary?.gst} />
      <PricingItem title="Total Order Price" value={orderSummary?.totalOrderCharge} size="large" />
      <Divider orientation="horizontal" margin="spacing.5" />
      <CollapsedContent
        title="Rental Charges"
        value={orderSummary?.totalRentalCharge}
        content={
          <Box>
            {orderSummary?.rentalCharge?.map((rental) => (
              <React.Fragment key={rental?.deviceName}>
                <PricingItem
                  title={rental?.deviceName as string}
                  value={rental?.fee}
                  color="surface.text.gray.muted"
                  size="small"
                />
                <PricingItem title="GST @18%" value={rental?.gst} color="surface.text.gray.muted" />
                <PricingItem
                  title="Renewal"
                  value={rental?.renewal}
                  color="surface.text.gray.muted"
                />
              </React.Fragment>
            ))}
          </Box>
        }
      />
      <PricingItem title="Rental Due" value={orderSummary?.totalRentalCharge} />
    </React.Fragment>
  );
};

export default DeviceOrderPricing;
