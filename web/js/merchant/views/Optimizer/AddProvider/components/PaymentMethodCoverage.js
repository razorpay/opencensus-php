import React from 'react';
import { Box, Title, Text, CheckIcon, CloseIcon, Button, Alert } from '@razorpay/blade/components';

import { titleCase } from 'common/utils/rzp-utils';
import { IconBackground } from 'merchant/views/Optimizer/AddProvider/components/styled';
import { areMandatoryMethodsCovered } from 'merchant/views/Optimizer/AddProvider/utils';

const CoverageIcon = ({ status }) => {
  const iconColor =
    status === 'positive'
      ? 'feedback.background.positive.highContrast'
      : 'feedback.background.negative.highContrast';
  const IconComp = status === 'positive' ? CheckIcon : CloseIcon;
  return (
    <IconBackground status={status}>
      <Box display="flex" alignItems="center" justifyContent="center" paddingLeft="spacing.1">
        <IconComp color={iconColor} size="medium" />
      </Box>
    </IconBackground>
  );
};

const ShowCoverage = ({ methods, coverage }) => {
  return methods?.map((method) => (
    <Box display="flex" flexDirection="column" gap="spacing.5" key={method}>
      <Text>
        <CoverageIcon status={coverage[method]?.supported ? 'positive' : 'negative'} />
        <Text as="span" marginLeft="spacing.3" weight="bold">
          {titleCase(method)}
        </Text>
        {` is ${coverage[method]?.supported ? 'Covered' : 'Not Covered'}`}
      </Text>
    </Box>
  ));
};

export const PaymentMethodCoverage = ({
  isFormEdit,
  selectedProvider,
  methods,
  mandatoryMethods,
  gatewayCoverage,
  razorpayCoverage,
  businessName,
}) => {
  return (
    <Box
      display="flex"
      flexDirection="column"
      padding="spacing.7"
      gap={isFormEdit ? 'spacing.9' : 'spacing.6'}
      backgroundColor="surface.background.level2.lowContrast"
    >
      <Box display="flex" justifyContent="space-between">
        <Box display="flex" flexDirection="column" gap="spacing.3">
          <Title color="surface.text.subtle.lowContrast">Payment Method Coverage</Title>
        </Box>
      </Box>

      <Box display="flex" flexDirection="column" gap="spacing.5">
        <Text>Method coverage on {selectedProvider}</Text>
        <ShowCoverage methods={methods} coverage={gatewayCoverage} />
      </Box>

      <Box display="flex" flexDirection="column" gap="spacing.3">
        {!areMandatoryMethodsCovered(mandatoryMethods, gatewayCoverage) ? (
          <Alert
            description={`One or more payment methods are not supported. Please reach out to your ${selectedProvider} account manager or ${selectedProvider} support team for help.`}
            isFullWidth={true}
            isDismissible={false}
            color="negative"
          />
        ) : null}
      </Box>

      <Box display="flex" flexDirection="column" gap="spacing.5">
        <Text>Method coverage on {businessName}</Text>
        <ShowCoverage methods={methods} coverage={razorpayCoverage} />
      </Box>

      <Box display="flex" flexDirection="column" gap="spacing.3">
        {!areMandatoryMethodsCovered(mandatoryMethods, razorpayCoverage) ? (
          <Alert
            description={`One or more payment methods are not supported. Please ensure that all necessary methods are enabled on your ${businessName} account.`}
            isFullWidth={true}
            isDismissible={false}
            color="negative"
            actions={{
              primary: {
                onClick: () => {
                  window.open(`${window.location.origin}/app/payment-methods/upi-qr`, '_blank');
                },
                text: 'Go to Account & Settings',
              },
            }}
          />
        ) : null}
      </Box>

      <Box display="flex" justifyContent="end" alignItems="center" gap="spacing.7">
        <Button isDisabled={true}>Test integration</Button>
      </Box>
    </Box>
  );
};
