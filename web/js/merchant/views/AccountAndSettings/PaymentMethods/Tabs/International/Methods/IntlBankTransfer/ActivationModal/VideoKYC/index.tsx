import React from 'react';
import {
  Box,
  Heading,
  Text,
  Radio,
  RadioGroup,
  TextInput,
  Alert,
  CopyIcon,
  Link,
} from '@razorpay/blade/components';

import { VIDEO_KYC_STEPS } from './constants';
import { useVideoKyc } from './states';

const IecCode = () => {
  const {
    owner,
    webLink,
    isSavingForm,
    promoterPanName,
    validationState,
    handleCopyLink,
    handleSelectOption,
  } = useVideoKyc();

  return (
    <Box>
      <Heading size="large" marginBottom="spacing.2">
        Confirmation of authorised signatory
      </Heading>
      <Text>Video KYC needs to be done for the Authorised Signatory : {promoterPanName}</Text>

      <Box
        paddingTop="spacing.7"
        paddingX="spacing.1"
        maxHeight="350px"
        overflowY="auto"
        display="flex"
        gap="spacing.3"
        flexDirection="column"
      >
        <RadioGroup
          label=""
          size="medium"
          value={owner}
          onChange={handleSelectOption}
          validationState={validationState.owner.state}
          errorText={validationState.owner.errorText}
          isDisabled={isSavingForm}
          name="owner"
        >
          <Radio value="yes" marginBottom="spacing.4">
            Yes, I am {promoterPanName}
          </Radio>
          <Radio value="no" marginBottom="spacing.4">
            No, I am not {promoterPanName}
          </Radio>
        </RadioGroup>

        {owner === 'yes' ? (
          <Box>
            <Heading size="medium" marginBottom="spacing.2">
              Start Video KYC
            </Heading>
            <Text>
              You&apos;re one-step away from account activation. Complete video KYC and start
              accepting payments
            </Text>
            <Alert
              color="information"
              title="Video KYC must be done within 72 hours"
              description=""
              isDismissible={false}
              isFullWidth
              marginTop="spacing.7"
              marginBottom="spacing.3"
            />
            <Box
              display="grid"
              gridTemplateColumns={{
                base: '1fr',
                l: '1fr 1fr',
              }}
              gap="spacing.3"
            >
              {VIDEO_KYC_STEPS.map(({ title, subtitle, Icon }) => (
                <Box
                  key={title}
                  display="flex"
                  flexDirection="column"
                  gap="spacing.3"
                  padding={['spacing.5', 'spacing.7']}
                  backgroundColor="surface.background.gray.moderate"
                  borderRadius="medium"
                >
                  <Icon size="medium" />
                  <Text>
                    {title}{' '}
                    <Text as="span" weight="semibold">
                      {subtitle}
                    </Text>
                  </Text>
                </Box>
              ))}
            </Box>
          </Box>
        ) : null}

        {owner === 'no' && webLink ? (
          <Box>
            <Heading size="medium" marginBottom="spacing.2">
              Share link for Video KYC
            </Heading>
            <Text>
              Copy and share generated link with declared Authorised Signatory{' '}
              <Text as="span" weight="semibold">
                {promoterPanName}
              </Text>
              . Video KYC will fail if done by anyone else
            </Text>

            <Box marginTop="spacing.5">
              <TextInput
                label="Video KYC link"
                value={webLink}
                onChange={() => {}}
                trailingButton={
                  <Link
                    variant="button"
                    icon={CopyIcon}
                    onClick={handleCopyLink}
                    accessibilityLabel="copy link"
                  />
                }
              />
            </Box>

            <Alert
              color="information"
              title="We have slots from 10am-8pm, Mon-Fri"
              description="Please return on the next working day if outside these hours"
              isDismissible={false}
              isFullWidth
              marginTop="spacing.5"
              marginBottom="spacing.2"
            />
          </Box>
        ) : null}
      </Box>
    </Box>
  );
};

export default IecCode;
