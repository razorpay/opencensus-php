import React from 'react';
import { Card, CardBody, Box, Heading, Amount, Spinner } from '@razorpay/blade/components';

import RetryOnError from '@apps/digital-bills/src/common/components/RetryOnError';

type SalesCardProps = {
  isLoading: boolean;
  heading: string;
  value?: number;
  isGraphExpanded?: boolean;
  setSelectedOverviewCategory: () => void;
  expandGraph: () => void;
  isSelected?: boolean;
  hasError?: boolean;
  retryFn?: () => void;
};

const SalesCard = ({
  isLoading,
  heading,
  value = 0,
  expandGraph,
  setSelectedOverviewCategory,
  isSelected = false,
  hasError = false,
  retryFn,
}: SalesCardProps): React.ReactElement => {
  const onCardSelect = () => {
    setSelectedOverviewCategory();
    expandGraph();
  };

  const renderContent = () => {
    if (hasError)
      return (
        <Box display="flex" alignItems="center">
          <RetryOnError retryFn={retryFn} justifyContent="flex-start" />
        </Box>
      );
    return isLoading ? (
      <Spinner
        alignSelf="center"
        color="primary"
        label=""
        size="large"
        accessibilityLabel={`${heading} spinner`}
      />
    ) : (
      <Box
        display="flex"
        flexDirection="column"
        justifyContent="space-between"
        alignItems="flex-start"
        height="100%"
      >
        <Heading size="small" weight="regular">
          {heading}
        </Heading>
        <Amount type="heading" size="large" weight="semibold" suffix="humanize" value={value} />
      </Box>
    );
  };

  return (
    <Card
      height="125px"
      elevation="none"
      padding="spacing.5"
      isSelected={isSelected}
      accessibilityLabel={`${heading} Card`}
      onClick={onCardSelect}
    >
      <CardBody height="100%">
        <Box display="flex" justifyContent="center" height="100%">
          {renderContent()}
        </Box>
      </CardBody>
    </Card>
  );
};

export default SalesCard;
