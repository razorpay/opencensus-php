import React from 'react';
import {
  Box,
  Heading,
  Text,
  Radio,
  RadioGroup,
  TextInput,
  Link,
  Checkbox,
} from '@razorpay/blade/components';

import { B2B_EXPORTS_TNC_LINK, NOT_APPLICABLE, VERIFIED } from './constants';
import { useIecCode } from './states';

const IecCode = () => {
  const {
    iecCode,
    iecCodeOption,
    acceptTnc,
    isReadOnly,
    validationState,
    acceptNotApplicableTnc,
    handleAcceptTnc,
    handleSelectOption,
    handleValueChange,
    handleAcceptNotApplicableTnc,
  } = useIecCode();

  return (
    <Box>
      <Heading size="large" marginBottom="spacing.2">
        Importer - Exporter code
      </Heading>
      <Text>
        An Importer-Exporter Code (IEC) is a key business identification number which has been
        mandated by the Directorate General of Foreign Trade (DGFT) for all businesses that export
        from India or import to India.
      </Text>

      <Box
        paddingTop="spacing.9"
        paddingX="spacing.1"
        maxHeight="300px"
        overflowY="auto"
        display="flex"
        gap="spacing.3"
        flexDirection="column"
      >
        <RadioGroup
          label="Do you have an Import Export Code (IEC)?"
          size="medium"
          value={iecCodeOption}
          onChange={handleSelectOption}
          validationState={validationState.iecCodeOption.state}
          errorText={validationState.iecCodeOption.errorText}
          necessityIndicator="required"
          isDisabled={isReadOnly}
        >
          <Radio value="yes" marginBottom="spacing.4">
            Yes
          </Radio>
          <Radio
            value={NOT_APPLICABLE}
            helpText="Know more about IEC exemptions here"
            marginBottom="spacing.4"
          >
            No, because it is not applicable for me
          </Radio>
        </RadioGroup>

        {iecCodeOption === 'yes' && (
          <Box marginTop="spacing.5" maxWidth="400px">
            <TextInput
              label="Importer Exporter Code"
              value={iecCode}
              placeholder="A1234567890"
              helpText="Available on the DGFT portal under IEC details in the Services tab"
              autoCapitalize="characters"
              onChange={handleValueChange}
              isDisabled={isReadOnly}
              validationState={validationState.iecCode.state}
              errorText={validationState.iecCode.errorText}
              necessityIndicator="required"
            />
          </Box>
        )}

        {iecCodeOption === NOT_APPLICABLE && (
          <Box marginTop="spacing.8">
            <Checkbox
              isChecked={acceptNotApplicableTnc === 'yes'}
              onChange={handleAcceptNotApplicableTnc}
              validationState={validationState.acceptNotApplicableTnc.state}
              errorText={validationState.acceptNotApplicableTnc.errorText}
              testID="acceptNotApplicableTnc"
            >
              I hereby declare that the IEC code &quot;Not applicable&quot; here specifies specific
              exemption granted to me as per Foreign Trade Policy
            </Checkbox>
          </Box>
        )}

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
    </Box>
  );
};

export default IecCode;
