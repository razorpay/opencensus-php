import React, { useState } from 'react';
import { Formik } from 'formik';
import * as Yup from 'yup';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import Radio from '@razorpay/blade/src/atoms/Radio';
import { FormSection, Field } from '../Form';
import { useActivationFormState, isTabComplete } from '../context/store';
import useActivation from '../hooks/useActivation';

const businessOverviewSchema = Yup.object().shape({
  business_type: Yup.string().required('Business Type is a required field'),
  business_dba: Yup.string().required('Billing Label is a required field'),
});

const BusinessOverview: React.FC = () => {
  const { data, postData } = useActivation();
  const businessOverview = data.business_overview;
  const setBusinessOverviewCompleted = useActivationFormState(
    (state) => state.setBusinessOverviewCompleted,
  );
  const [websiteOption, setWebsiteOption] = useState('1');

  const handleBlur = (e, formikProps) => {
    const updatedBusinessOverview = {
      business_type: {
        value: formikProps.values.business_type,
        error: formikProps.errors.business_type,
      },
      business_dba: {
        value: formikProps.values.business_dba,
        error: formikProps.errors.business_dba,
      },
      business_website: {
        value: formikProps.values.business_website,
        error: formikProps.errors.business_website,
      },
    };
    const isComplete = isTabComplete(
      { ...data, business_overview: updatedBusinessOverview },
      'business_overview',
    );
    setBusinessOverviewCompleted(isComplete);
    if (isComplete) {
      postData(updatedBusinessOverview);
    }
    formikProps.handleBlur(e);
  };

  return (
    <Formik
      initialValues={{
        business_type: businessOverview.business_type.value,
        business_dba: businessOverview.business_dba.value,
        business_website: businessOverview.business_website.value,
      }}
      initialErrors={{
        business_type: businessOverview.business_type.error,
        business_dba: businessOverview.business_dba.error,
        business_website: businessOverview.business_website.error,
      }}
      validationSchema={businessOverviewSchema}
      validateOnMount={true}
      onSubmit={() => {
        console.log('onSubmit');
      }}
    >
      {(formikProps) => (
        <form onChange={formikProps.handleChange} onBlur={(e) => handleBlur(e, formikProps)}>
          <FormSection title="About Your Business">
            <Field>
              <TextInput
                width="auto"
                name="business_type"
                label="Business Type"
                value={formikProps.values.business_type}
                errorText={formikProps.errors.business_type}
              />
            </Field>
            <Field last>
              <TextInput
                width="auto"
                name="business_dba"
                label="Billing Label"
                helpText="Something that your customers are familiar with"
                value={formikProps.values.business_dba}
                errorText={formikProps.errors.business_dba}
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
                        name="business_website"
                        label="Website/App URL"
                        helpText="Click the help icon to view the mandatory sections required in your website/app for quick verification"
                        value={formikProps.values.business_website}
                        errorText={formikProps.errors.business_website}
                      />
                    </View>
                  </Space>
                ) : null}
                <Space margin={[1, 0]}>
                  <View>
                    <Radio.Option value="1" title="I don't have a website/app" />
                  </View>
                </Space>
              </Radio>
            </Field>
          </FormSection>
        </form>
      )}
    </Formik>
  );
};

export default BusinessOverview;
