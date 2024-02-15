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
        backgroundColor={'surface.background.level2.lowContrast'}
        borderColor="brand.gray.400.lowContrast"
        borderWidth={'thin'}
        maxWidth={'50%'}
        borderRadius={'medium'}
      >
        <Box marginTop={'spacing.4'} marginX={'spacing.6'} justifyContent={'space-between'}>
          <Text weight={'bold'} color="surface.text.subtle.lowContrast">
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
            <Text color="surface.text.subtle.lowContrast">Reseller Business PAN</Text>
            <Text marginTop={'spacing.5'} color="surface.text.subtle.lowContrast">
              Business Name
            </Text>
            <Text marginTop={'spacing.5'} color="surface.text.subtle.lowContrast">
              Business Label
            </Text>
            <Text marginTop={'spacing.5'} color="surface.text.subtle.lowContrast">
              Authorized Signatory PAN
            </Text>
            <Text marginTop={'spacing.5'} color="surface.text.subtle.lowContrast">
              CINGSTIN
            </Text>
            <Text marginTop={'spacing.5'} color="surface.text.subtle.lowContrast">
              GSTIN
            </Text>
          </Box>
          <Box marginLeft={'spacing.11'}>
            <Text weight={'bold'} color="surface.text.subtle.lowContrast">
              {resellerPan}
            </Text>
            <Text marginTop={'spacing.5'} weight={'bold'} color="surface.text.subtle.lowContrast">
              {businessName}
            </Text>
            <Text marginTop={'spacing.5'} weight={'bold'} color="surface.text.subtle.lowContrast">
              {billingLabel}
            </Text>
            <Text marginTop={'spacing.5'} weight={'bold'} color="surface.text.subtle.lowContrast">
              {authorizedSignatoryPan}
            </Text>
            <Text marginTop={'spacing.5'} weight={'bold'} color="surface.text.subtle.lowContrast">
              {cinGstin}
            </Text>
            <Text marginTop={'spacing.5'} weight={'bold'} color="surface.text.subtle.lowContrast">
              {gstin}
            </Text>
          </Box>
        </Box>
      </Box>
    </div>
  );
};

export default KycDetails;
