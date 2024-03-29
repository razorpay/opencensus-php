import React from 'react';
import { Box, Text, Divider } from '@razorpay/blade/components';

interface VirtualAccountDetailsProps {
  resellerPan: string;
  businessName: string;
  billingLabel: string;
  authorizedSignatoryPan: string;
  cinGstin: string;
  gstin: string;
}

const KycDetails = ({
  resellerPan,
  businessName,
  billingLabel,
  authorizedSignatoryPan,
  cinGstin,
  gstin,
}: VirtualAccountDetailsProps) => {
  return (
    <div>
      <Box
        marginTop={'spacing.7'}
        marginBottom={'spacing.10'}
        marginLeft={'spacing.6'}
        backgroundColor={'surface.background.gray.intense'}
        borderColor="surface.border.gray.muted"
        borderWidth={'thin'}
        maxWidth={'50%'}
        borderRadius={'medium'}
      >
        <Box marginTop={'spacing.4'} marginX={'spacing.6'} justifyContent={'space-between'}>
          <Text weight="semibold" color="surface.text.gray.subtle">
            KYC Details
          </Text>
        </Box>

        <Divider marginY="spacing.4" />

        <Box
          flexDirection={{
            base: 'column',
            m: 'row',
          }}
          display="flex"
          marginX={'spacing.6'}
          marginBottom={'spacing.6'}
        >
          <Box justifyContent="space-between">
            <Text color="surface.text.gray.subtle">Reseller Business PAN</Text>
            <Text marginTop={'spacing.5'} color="surface.text.gray.subtle">
              Business Name
            </Text>
            <Text marginTop={'spacing.5'} color="surface.text.gray.subtle">
              Business Label
            </Text>
            <Text marginTop={'spacing.5'} color="surface.text.gray.subtle">
              Authorized Signatory PAN
            </Text>
            <Text marginTop={'spacing.5'} color="surface.text.gray.subtle">
              CINGSTIN
            </Text>
            <Text marginTop={'spacing.5'} color="surface.text.gray.subtle">
              GSTIN
            </Text>
          </Box>
          <Box marginLeft={'spacing.11'}>
            <Text weight="semibold" color="surface.text.gray.subtle">
              {resellerPan}
            </Text>
            <Text marginTop={'spacing.5'} weight="semibold" color="surface.text.gray.subtle">
              {businessName}
            </Text>
            <Text marginTop={'spacing.5'} weight="semibold" color="surface.text.gray.subtle">
              {billingLabel}
            </Text>
            <Text marginTop={'spacing.5'} weight="semibold" color="surface.text.gray.subtle">
              {authorizedSignatoryPan}
            </Text>
            <Text marginTop={'spacing.5'} weight="semibold" color="surface.text.gray.subtle">
              {cinGstin}
            </Text>
            <Text marginTop={'spacing.5'} weight="semibold" color="surface.text.gray.subtle">
              {gstin}
            </Text>
          </Box>
        </Box>
      </Box>
    </div>
  );
};

export default KycDetails;
