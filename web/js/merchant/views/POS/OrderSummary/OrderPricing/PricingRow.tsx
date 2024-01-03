import React, { useState } from 'react';
import { Amount, Box, ChevronDownIcon, ChevronUpIcon, Text } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import { DetailedPricingContent, DetailedPricingHeader } from './styles';

type PricingProps = {
  id?: string;
  title: string | JSX.Element;
  value?: number | JSX.Element;
  rows?: {
    title: string;
    value: number;
  }[];
};

const PricingRow = ({ id, rows, title, value = 0 }: PricingProps): JSX.Element => {
  const [isExpanded, setIsExpanded] = useState<boolean>(false);
  const isCollapsibleHeader = (rows || []).length > 0;

  return (
    <Box testID={id ?? ''}>
      <Box
        display="flex"
        alignItems="center"
        justifyContent="space-between"
        width="100%"
        marginBottom="spacing.4"
      >
        <DetailedPricingHeader
          onClick={() => {
            analytics.track_EXPERIMENTAL(SignUpEvents.iconClicked, {
              type: isExpanded ? 'Shrink Icon' : 'Expand Icon',
              l1FunnelStage: 'Purchase Intention',
              l2FunnelStage: 'Pre-checkout',
              section: 'Pre-checkout',
              subSection: id === 'device-charges-container' ? 'Device Charges' : 'Rental Charges',
            });

            setIsExpanded((isExpanded) => !isExpanded);
          }}
        >
          <Box display="flex" alignItems="center">
            {typeof title === 'string' ? (
              <Text weight={isCollapsibleHeader ? 'bold' : 'regular'}>{title} </Text>
            ) : (
              title
            )}
            {isCollapsibleHeader ? (
              <React.Fragment>
                {isExpanded ? (
                  <ChevronUpIcon
                    size="medium"
                    color="surface.text.subdued.lowContrast"
                    marginTop="spacing.1"
                    marginX="spacing.2"
                  />
                ) : (
                  <ChevronDownIcon
                    size="medium"
                    color="surface.text.subdued.lowContrast"
                    marginTop="spacing.1"
                    marginX="spacing.2"
                  />
                )}
              </React.Fragment>
            ) : null}
          </Box>
        </DetailedPricingHeader>

        {typeof value === 'number' ? (
          <Amount value={value} isAffixSubtle={false} suffix="none" />
        ) : (
          value
        )}
      </Box>
      <DetailedPricingContent isExpanded={isExpanded}>
        {isExpanded
          ? rows?.map(({ title, value }, index) => (
              <Box
                key={`${title}-${index}`}
                display="flex"
                alignItems="center"
                justifyContent="space-between"
                width="100%"
                marginBottom="spacing.3"
              >
                <Text>{title} </Text>
                <Amount value={value} isAffixSubtle={false} suffix="none" />
              </Box>
            ))
          : null}
      </DetailedPricingContent>
    </Box>
  );
};

export default PricingRow;
