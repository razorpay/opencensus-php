import React from 'react';
import { Box, Heading, Text, CheckCircle2Icon, Checkbox, Link } from '@razorpay/blade/components';

import { B2B_EXPORTS_TNC_LINK, NOT_APPLICABLE, VERIFIED } from './constants';
import { useInternationalDetails } from './states';

const InternationalDetails = () => {
  const { purposeCode, purposeCodeDesc, iecCode, acceptTnc, validationState, handleAcceptTnc } =
    useInternationalDetails();

  return (
    <Box>
      <Heading size="large" marginBottom="spacing.2">
        International details
      </Heading>
      <Text>We&apos;ve saved these important business details for you</Text>

      <Box
        marginTop="spacing.9"
        display="flex"
        gap="spacing.8"
        flexDirection="column"
        maxWidth="500px"
      >
        <Box display="flex" gap="spacing.3">
          <Box flex={1}>
            <Text color="surface.text.gray.normal" size="medium" weight="semibold">
              Purpose code
            </Text>
            <Heading size="large" marginBottom="spacing.3">
              {purposeCode}
            </Heading>
            {purposeCodeDesc && <Text>{purposeCodeDesc}</Text>}
          </Box>

          <CheckCircle2Icon color="feedback.icon.positive.intense" />
        </Box>

        {iecCode !== NOT_APPLICABLE && (
          <Box display="flex" gap="spacing.3">
            <Box flex={1}>
              <Text color="surface.text.gray.normal" size="medium" weight="semibold">
                Importer Exporter code
              </Text>
              <Heading size="large" marginBottom="spacing.3">
                {iecCode}
              </Heading>
              <Text>Available on the DGFT portal under IEC details in the Services tab</Text>
            </Box>

            <CheckCircle2Icon color="feedback.icon.positive.intense" />
          </Box>
        )}
      </Box>

      {acceptTnc !== VERIFIED && (
        <Box marginTop="spacing.8">
          <Checkbox
            isChecked={acceptTnc === 'yes'}
            onChange={handleAcceptTnc}
            validationState={validationState.acceptTnc.state}
            errorText={validationState.acceptTnc.errorText}
          >
            I accept the{' '}
            <Link href={B2B_EXPORTS_TNC_LINK} target="_blank" rel="noopener">
              Terms and Conditions
            </Link>
          </Checkbox>
        </Box>
      )}
    </Box>
  );
};

export default InternationalDetails;
