import React, { useState } from 'react';
import { Amount, Box, ChevronDownIcon, ChevronUpIcon, Text } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import { DetailedPricingContent, DetailedPricingHeader, PricingRowOfferTag } from './styles';

type PricingProps = {
  id?: string;
  title: string | JSX.Element;
  value?: number | JSX.Element;
  rows?: {
    title: string;
    value: number;
  }[];
  offerRows?: {
    title: string;
    value: number;
    subTitle?: string;
    prevValue?: number | null;
    isRenderValuePlanText?: boolean;
  }[];
  isPartnerPricing?: boolean;
};

const PricingRow = ({
  id,
  rows,
  title,
  value = 0,
  offerRows,
  isPartnerPricing,
}: PricingProps): JSX.Element => {
  const [isExpanded, setIsExpanded] = useState<boolean>(false);
  const isCollapsibleHeader = (rows || []).length > 0 || (offerRows || []).length > 0;

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
              <Text weight={isCollapsibleHeader ? 'semibold' : 'regular'}>{title} </Text>
            ) : (
              title
            )}
            {isCollapsibleHeader ? (
              <React.Fragment>
                {isExpanded ? (
                  <ChevronUpIcon
                    size="medium"
                    color="interactive.icon.gray.muted"
                    marginTop="spacing.1"
                    marginX="spacing.2"
                  />
                ) : (
                  <ChevronDownIcon
                    size="medium"
                    color="interactive.icon.gray.muted"
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
        {isExpanded && (offerRows ?? []).length > 0 ? (
          <Box
            backgroundColor="surface.background.gray.subtle"
            marginBottom="spacing.5"
            padding="spacing.4"
          >
            <PricingRowOfferTag isPartnerPricing={isPartnerPricing}>
              <Text
                size="small"
                weight="semibold"
                color={
                  isPartnerPricing ? 'feedback.text.notice.intense' : 'surface.text.primary.normal'
                }
              >
                {isPartnerPricing ? 'Partner Offer Applied' : 'Offer Applied'}
              </Text>
            </PricingRowOfferTag>
            {offerRows?.map(
              (
                { title, value, prevValue = null, subTitle, isRenderValuePlanText = false },
                index,
              ) => (
                <Box
                  key={`${title}-${index}`}
                  display="flex"
                  alignItems="center"
                  justifyContent="space-between"
                  width="100%"
                  borderRadius="medium"
                  marginBottom="spacing.3"
                >
                  <Box>
                    <Text>{title} </Text>
                    <Text size="small">{subTitle} </Text>
                  </Box>
                  <Box>
                    {prevValue !== null ? (
                      <Amount
                        value={prevValue}
                        isAffixSubtle={false}
                        suffix="none"
                        marginRight="spacing.2"
                        isStrikethrough={true}
                        color="surface.text.gray.muted"
                        type="body"
                        size="small"
                        weight="semibold"
                      />
                    ) : null}
                    {!isRenderValuePlanText ? (
                      <Amount value={value} isAffixSubtle={false} suffix="none" />
                    ) : (
                      <Text>{value}</Text>
                    )}
                  </Box>
                </Box>
              ),
            )}
          </Box>
        ) : null}
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
                <Box>
                  <Text>{title} </Text>
                </Box>
                <Amount value={value} isAffixSubtle={false} suffix="none" />
              </Box>
            ))
          : null}
      </DetailedPricingContent>
    </Box>
  );
};

export default PricingRow;
