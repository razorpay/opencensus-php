import React, { useState } from 'react';
import {
  Box,
  ChevronDownIcon,
  ChevronUpIcon,
  Divider,
  Heading,
  Link,
  Text,
} from '@razorpay/blade/components';

import { TechnicalSpecification } from 'apps/pos/src/app/views/SelfServe/types';

import { PosDeviceCollapsedContent } from './styles';
import { useScrollObserver } from 'apps/pos/src/app/views/SelfServe/utils/ScrollObserver';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

type ProductTechnicalSpecs = {
  technicalSpecifications: TechnicalSpecification[];
  collapsibleIndex: number;
  productTitle: string;
};

type SpecItem = TechnicalSpecification;

const SpecItem = ({ category, value }: SpecItem) => (
  <Box
    key={category}
    display="flex"
    backgroundColor="surface.background.gray.moderate"
    width="100%"
    padding="spacing.4"
    marginTop="spacing.2"
    borderRadius="medium"
  >
    <Box flex={{ base: '0 0 35%', xl: ' 0 0  15%' }} paddingX="spacing.4">
      <Text size="medium" weight="semibold" color="surface.text.gray.subtle">
        {category}
      </Text>
    </Box>
    <Divider orientation="vertical" />
    <Box paddingX="spacing.4" marginLeft="spacing.3">
      <Text size="medium" color="surface.text.gray.subtle">
        {value}
      </Text>
    </Box>
  </Box>
);

const ProductTechnicalSpecs = ({
  technicalSpecifications,
  collapsibleIndex,
  productTitle,
}: ProductTechnicalSpecs): JSX.Element => {
  const [isExpanded, setIsExapanded] = useState<boolean>(false);
  const nonCollapsibleItems = technicalSpecifications.slice(0, collapsibleIndex);
  const collapsibleItems = technicalSpecifications.slice(collapsibleIndex);
  const foldRef = React.useRef<HTMLDivElement>(null);

  useScrollObserver(foldRef, () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.pageSectionViewed, {
      sectionNumber: 4,
      section: 'Product Technical Specifications',
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'POS Product Description',
    });
  });

  return (
    <Box marginY="spacing.8" display="flex" justifyContent="center" ref={foldRef}>
      <Box width="100%" maxWidth={{ base: '100%', xl: '1100px' }}>
        <Heading textAlign="center" size="xlarge">
          Technical Specifications
        </Heading>
        <Box
          display="flex"
          backgroundColor="surface.background.gray.moderate"
          width="100%"
          padding="spacing.4"
          marginTop="spacing.6"
          borderRadius="medium"
        >
          <Box flex={{ base: '0 0 35%', xl: ' 0 0  20%' }} paddingX="spacing.4">
            <Heading size="small" color="surface.text.gray.subtle">
              Categories
            </Heading>
          </Box>
          <Box paddingX="spacing.4">
            <Heading marginLeft="spacing.3" size="small" color="surface.text.gray.subtle">
              Specifications
            </Heading>
          </Box>
        </Box>
        <Box marginTop="spacing.4">
          {nonCollapsibleItems.map(({ category, value }) => (
            <SpecItem key={category} category={category} value={value} />
          ))}
          <PosDeviceCollapsedContent isOpen={isExpanded}>
            {collapsibleItems.map(({ category, value }) => (
              <SpecItem key={category} category={category} value={value} />
            ))}
          </PosDeviceCollapsedContent>
          <Box
            padding="spacing.4"
            display="flex"
            alignItems="center"
            justifyContent="center"
            backgroundColor="surface.background.gray.moderate"
            marginTop="spacing.2"
          >
            <Link
              variant="button"
              onClick={() => {
                analytics.track_EXPERIMENTAL(SignUpEvents.linkClicked, {
                  label: isExpanded ? 'Show Less' : 'Show More',
                  whatsAppUpdates: 'No',
                  section: 'Technical Specifications',
                  subSection: productTitle,
                  l1FunnelStage: 'Device Exploration',
                  l2FunnelStage: 'POS Product Description',
                });
                setIsExapanded((isExpanded) => !isExpanded);
              }}
              icon={isExpanded ? ChevronUpIcon : ChevronDownIcon}
              iconPosition="right"
              testID="technical-spec-show-more-btn"
            >
              Show {isExpanded ? 'Less' : 'More'}
            </Link>
          </Box>
        </Box>
      </Box>
    </Box>
  );
};

export default ProductTechnicalSpecs;
