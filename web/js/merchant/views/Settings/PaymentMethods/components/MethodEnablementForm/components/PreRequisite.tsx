import React, { useState } from 'react';
import {
  Box,
  Collapsible,
  CollapsibleBody,
  CollapsibleLink,
  Heading,
  Text,
} from '@razorpay/blade/components';

import { BulletPointsProps } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/types';

const BulletPoints = ({ title, description }: BulletPointsProps) => (
  <Box>
    <Text marginBottom="spacing.3" weight="semibold" size="medium">
      {title}
    </Text>
    <Text>{description}</Text>
  </Box>
);

const PreRequisiteTab = (): React.ReactElement => {
  const [shouldShowAlternateWays, setShouldShowAlternateWays] = useState(false);

  const onExpandChange = ({ isExpanded }) => {
    setShouldShowAlternateWays(isExpanded);
  };

  return (
    <Box display="flex" flexDirection="row">
      <Box
        display="flex"
        flexDirection="column"
        flex="1"
        marginRight={{ base: 'spacing.0', m: 'spacing.11' }}
      >
        <Box marginBottom={{ base: 'spacing.5', m: 'spacing.8' }}>
          <Heading size="small">Pre-requisite Information</Heading>
        </Box>
        <Text marginBottom={{ base: 'spacing.5', m: 'spacing.8' }}>
          One or more documents to be uploaded in the next step would require digital signature of
          the issuing authority or self attestation
        </Text>
        <BulletPoints
          title="Self-Attestation"
          description="If uploading a scanned copy, please self attest the first and last pages of the document by adding a signature of the authorised signatory"
        />
        <Collapsible
          marginTop={{ base: 'spacing.5', m: 'spacing.7' }}
          onExpandChange={onExpandChange}
        >
          <CollapsibleLink>Know alternate ways of document verification</CollapsibleLink>
          <CollapsibleBody>
            <Box marginTop={{ base: 'spacing.5', m: 'spacing.9' }}>
              <BulletPoints
                title="Digital Signature of the Issuing Authority"
                description="Check if the documents already have the digital signature of respective issuing authority. Please refer to the sample document on the right to verify the same"
              />
            </Box>
          </CollapsibleBody>
        </Collapsible>
      </Box>
      <Box width="160px" height="auto" display={{ base: 'none', m: 'flex' }} flexDirection="column">
        <Box>
          <img
            width="100%"
            height="auto"
            src={require('assets/cross-border/self-attest-sample1.png')}
            alt="self-attest-sample"
          />
        </Box>
        {shouldShowAlternateWays && (
          <Box marginTop="auto">
            <img
              width="100%"
              height="auto"
              src={require('assets/cross-border/self-attest-sample2.png')}
              alt="self-attest-sample"
            />
          </Box>
        )}
      </Box>
    </Box>
  );
};

export default PreRequisiteTab;
