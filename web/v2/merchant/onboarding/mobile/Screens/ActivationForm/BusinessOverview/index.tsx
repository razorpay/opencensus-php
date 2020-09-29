import React, { useState } from 'react';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import Radio from '@razorpay/blade/src/atoms/Radio';
import { FormSection, Field } from '../../../Form/';

const BusinessOverview: React.FC = () => {
  const [websiteOption, setWebsiteOption] = useState('0');

  const onChange = (e) => {
    console.log(e.target.name, e.target.value);
  };

  return (
    <form onChange={onChange}>
      <FormSection title="About Your Business">
        <Field>
          <TextInput width="auto" name="business_type" label="Business Type" />
        </Field>
        <Field last>
          <TextInput
            width="auto"
            name="billing_label"
            label="Billing Label"
            helpText="Something that your customers are familiar with"
          />
        </Field>
      </FormSection>

      <FormSection title="Website Details" last>
        <Field last>
          <Radio
            defaultValue={websiteOption}
            size="medium"
            onChange={(val) => {
              setWebsiteOption(val);
            }}
          >
            <Radio.Option value="0" title="I have a live website/app" />
            {websiteOption === '0' ? (
              <Space margin={[3.75, 0, 0, 3.5]}>
                <View>
                  <TextInput
                    width="auto"
                    name="website"
                    label="Website/App URL"
                    helpText="Click the help icon to view the mandatory sections required in your website/app for quick verification"
                  />
                </View>
              </Space>
            ) : null}
            <Space margin={[1, 0]}>
              <View>
                <Radio.Option value="1" title="I have a website/app but it isn't live yet" />
              </View>
            </Space>
            <Radio.Option value="2" title="I don't have a website/app" />
          </Radio>
        </Field>
      </FormSection>
    </form>
  );
};

export default BusinessOverview;
