import React, { useState } from 'react';
import { Formik } from 'formik';
import * as Yup from 'yup';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import Radio from '@razorpay/blade/src/atoms/Radio';
import { FormSection, Field, GetTouchedFields } from '../Form';
import { useActivationFormState, isTabComplete } from '../context/store';
import useActivation, { getRequestData } from '../hooks/useActivation';

const BusinessOverview: React.FC = () => {
  const { data, postData } = useActivation();
  const businessOverview = data.business_overview;
  const setBusinessOverviewCompleted = useActivationFormState(
    (state) => state.setBusinessOverviewCompleted,
  );
  const hasWebsite = useActivationFormState((state) => state.has_website);
  const setHasWebsite = useActivationFormState((state) => state.setHasWebsite);
  const [isBlurCalled, setIsBlurCalled] = useState(false);

  const handleBlur = (e, formikProps) => {
    formikProps.handleBlur(e);
    setIsBlurCalled(true);
  };

  const handleSubmit = (updatedDetails) => {
    const isComplete = isTabComplete(
      { ...data, business_overview: { ...businessOverview, ...updatedDetails }, hasWebsite },
      'business_overview',
    );
    setBusinessOverviewCompleted(isComplete);
    const reqData = getRequestData(businessOverview, updatedDetails);
    if (Object.keys(reqData).length) {
      postData(reqData);
    }
  };

  return (
    <Formik
      initialValues={{
        business_type: businessOverview.business_type.value,
        business_dba: businessOverview.business_dba.value,
        business_website: businessOverview.business_website.value,
        business_category: businessOverview.business_category.value,
      }}
      initialErrors={{
        business_type: businessOverview.business_type.error,
        business_dba: businessOverview.business_dba.error,
        business_website: businessOverview.business_website.error,
        business_category: businessOverview.business_category.error,
      }}
      validationSchema={() => {
        const _schema = Yup.object().shape({
          business_type: Yup.string()
            .nullable()
            .required('Business Type is a required field')
            .nullable(),
          business_dba: Yup.string()
            .nullable()
            .required('Billing Label is a required field')
            .nullable(),
          business_category: Yup.string()
            .nullable()
            .required('Please select your business category')
            .nullable(),
          business_website: Yup.lazy(() => {
            if (hasWebsite) {
              return Yup.string().required('Please provide website').nullable();
            }
            return Yup.string().nullable();
          }),
        });
        return _schema;
      }}
      validateOnMount={false}
      onSubmit={() => {
        console.log('onSubmit');
      }}
    >
      {(formikProps) => (
        <form
          onSubmit={formikProps.handleSubmit}
          onChange={formikProps.handleChange}
          onBlur={(e) => handleBlur(e, formikProps)}
        >
          <FormSection title="About Your Business">
            <Field>
              <TextInput
                width="auto"
                name="business_type"
                label="Business Type"
                value={formikProps.values.business_type}
                errorText={formikProps.touched.business_type && formikProps.errors.business_type}
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="business_category"
                label="Business Category"
                value={formikProps.values.business_category}
                disabled
                errorText={
                  formikProps.touched.business_category && formikProps.errors.business_category
                }
              />
            </Field>
            <Field last>
              <TextInput
                width="auto"
                name="business_dba"
                label="Billing Label"
                helpText="Something that your customers are familiar with"
                value={formikProps.values.business_dba}
                errorText={formikProps.touched.business_dba && formikProps.errors.business_dba}
              />
            </Field>
          </FormSection>

          <FormSection title="Website Details" last>
            <Field last>
              <Radio
                defaultValue={hasWebsite ? '0' : '1'}
                size="medium"
                onChange={(val) => {
                  const _hasWebsite = val === '0';
                  setHasWebsite(_hasWebsite);
                  if (!_hasWebsite) {
                    formikProps.setFieldValue('business_website', '');
                  }
                  setIsBlurCalled(true);
                }}
              >
                <Radio.Option value="0" title="I have a live website/app" />
                {hasWebsite ? (
                  <Space margin={[3.75, 0, 0, 3.5]}>
                    <View>
                      <TextInput
                        width="auto"
                        name="business_website"
                        label="Website/App URL"
                        helpText="Click the help icon to view the mandatory sections required in your website/app for quick verification"
                        value={formikProps.values.business_website}
                        errorText={
                          formikProps.touched.business_website &&
                          formikProps.errors.business_website
                        }
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
          <GetTouchedFields
            handleSubmit={handleSubmit}
            isBlurCalled={isBlurCalled}
            setIsBlurCalled={setIsBlurCalled}
          />
        </form>
      )}
    </Formik>
  );
};

export default BusinessOverview;
