import React, { useState } from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import Text from '@razorpay/blade/src/atoms/Text';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import TextArea from '@razorpay/blade/src/atoms/TextArea';
import Checkbox from '@razorpay/blade/src/atoms/Checkbox';
import Link from '@commander/shield/src/shared/Link';
import { FormSection, Field } from '../../../Form/';

const StyledSeparator = styled(View)`
  height: 1px;
  width: 100%;
  background-color: rgba(22, 47, 86, 0.05);
`;

const BusinessDetails: React.FC = () => {
  const [hasSameAddress, setSameAddress] = useState(true);

  const handleSameAddress = (checked) => {
    setSameAddress(checked);
  };

  const onChange = (e) => {
    console.log(e.target.name, e.target.value);
  };

  return (
    <form onChange={onChange}>
      <FormSection
        title="PAN Details"
        subtitle="These details will be verified with the government database"
      >
        <Field>
          <TextInput width="auto" name="promoter_pan" label="Business Owner's PAN" />
        </Field>
        <Field last>
          <TextInput
            width="auto"
            name="promoter_pan_name"
            label="Business Owner's Name"
            helpText="As mentioned in the PAN"
          />
        </Field>
      </FormSection>

      <FormSection
        title="Address Details"
        subtitle="These details will be verified with the government database"
      >
        <Field>
          <TextArea width="auto" name="business_registered_address" label="Enter Address" />
        </Field>
        <Field>
          <TextInput width="auto" name="business_registered_pin" label="Pincode" />
        </Field>
        <Field>
          <TextInput width="auto" name="business_registered_city" label="City" />
        </Field>
        <Field>
          <TextInput width="auto" name="business_registered_state" label="Select State" />
        </Field>
        <Space margin={[1.75, 0, 0, 0]}>
          <View>
            <Checkbox
              title="Operational address is the same as above"
              helpText="Physical verification may take place"
              defaultChecked
              onChange={handleSameAddress}
            />
          </View>
        </Space>
      </FormSection>

      {!hasSameAddress ? (
        <FormSection title="Business Operational Address" last>
          <Field>
            <TextArea width="auto" name="business_operation_address" label="Enter Address" />
          </Field>
          <Field>
            <TextInput width="auto" name="business_operation_pin" label="Pincode" />
          </Field>
          <Field>
            <TextInput width="auto" name="business_operation_city" label="City" />
          </Field>
          <Field last>
            <TextInput width="auto" name="business_operation_state" label="Select State" />
          </Field>
        </FormSection>
      ) : null}

      <Space margin={[2, 0, 1.5, 0]}>
        <StyledSeparator />
      </Space>

      <Text size="xsmall" align="center">
        By submitting these details you agree to our <Link size="xsmall">terms and conditions</Link>
      </Text>
    </form>
  );
};

export default BusinessDetails;
