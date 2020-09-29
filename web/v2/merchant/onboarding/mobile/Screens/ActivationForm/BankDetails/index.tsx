import React, { useState } from 'react';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import Checkbox from '@razorpay/blade/src/atoms/Checkbox';
import { FormSection, Field } from '../../../Form';

const BankDetails: React.FC = () => {
  const [hasGSTIN, setHasGSTIN] = useState(false);

  const onChange = (e) => {
    console.log(e.target.name, e.target.value);
  };

  const handleHasGSTIN = (checked) => {
    setHasGSTIN(checked);
  };

  return (
    <form onChange={onChange}>
      <FormSection
        title="Bank Details"
        subtitle="We will be depositing a small amount in this account to verify your bank details"
      >
        <Field>
          <TextInput width="auto" name="bank_account_name" label="Beneficiary Name" />
        </Field>
        <Field>
          <TextInput width="auto" name="bank_account_number" label="Account Number" />
        </Field>
        <Field last>
          <TextInput width="auto" name="bank_branch_ifsc" label="IFSC Code" />
        </Field>
      </FormSection>

      <FormSection title="Company Details" last>
        <Field>
          <TextInput width="auto" name="company_cin" label="Company Identification Number (CIN)" />
        </Field>
        <Field visible={!hasGSTIN} last>
          <TextInput
            width="auto"
            name="gstin"
            label="GST Identification Number (GSTIN)"
            helpText="Should match either of your registered address or operational address"
          />
        </Field>
        <Space margin={[1.75, 0, 0, 0]}>
          <View>
            <Checkbox title="I don't have a GSTIN" onChange={handleHasGSTIN} />
          </View>
        </Space>
      </FormSection>
    </form>
  );
};

export default BankDetails;
