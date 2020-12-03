import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade/src/atoms/View';
import Flex from '@razorpay/blade/src/atoms/Flex';
import Space from '@razorpay/blade/src/atoms/Space';
import Text from '@razorpay/blade/src/atoms/Text';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import Icon from '@razorpay/blade/src/atoms/Icon';
import Link from '@commander/shield/src/shared/Link';
import Card from '../../../../components/Card';
import { FormSection, Field } from '../Form';

const StyledSeparator = styled(View)`
  height: 1px;
  width: 100%;
  background-color: rgba(22, 47, 86, 0.05);
`;

const DocumentUpload: React.FC = () => {
  const onChange = (e) => {
    console.log(e.target.name, e.target.value);
  };

  return (
    <>
      <Card padding={[2]} margin={[0, 0, 2, 0]}>
        <Flex>
          <View>
            <Space margin={[0, 1, 0, 0]}>
              <View>
                <Icon name="info" fill="shade.800" size="small" />
              </View>
            </Space>
            <Text size="small" color="shade.940">
              JPG, PNG or PDF of max size 2 MB. Make sure to upload all the pages of the documents
            </Text>
          </View>
        </Flex>
      </Card>

      <form onChange={onChange}>
        <FormSection title="Authorised Signatory's Address Proof">
          <Field>
            <TextInput width="auto" name="bank_account_name" label="Choose Proof type" />
          </Field>
          <Field last>
            <TextInput width="auto" name="bank_account_number" label="Account Number" />
          </Field>
        </FormSection>

        <FormSection title="Company Details" last>
          <Field last>
            <TextInput
              width="auto"
              name="company_cin"
              label="Company Identification Number (CIN)"
            />
          </Field>
        </FormSection>
      </form>

      <Space margin={[2, 0, 1.5, 0]}>
        <StyledSeparator />
      </Space>

      <Text size="xsmall" align="center">
        By submitting these details you agree to our <Link size="xsmall">terms and conditions</Link>
      </Text>
    </>
  );
};

export default DocumentUpload;
