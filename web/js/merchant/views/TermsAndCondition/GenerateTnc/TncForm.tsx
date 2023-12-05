import React from 'react';
import { useMutation } from '@tanstack/react-query';
import * as Yup from 'yup';
import { Formik, Form } from 'formik';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Checkbox from '@razorpay/blade-old/src/atoms/Checkbox';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import Radio from '@razorpay/blade-old/src/atoms/Radio';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import Button from '@razorpay/blade-old/src/atoms/Button';
import { Select, Option } from 'common/components/Select';
import Link from 'common/components/Link';
import { FormSection, Field } from 'merchant/views/onboarding/mobile/Form';
import { fetch } from 'common/services/rest/rest-fetch';
import { StyledFooter } from './Styled';
import { analyticsTrack } from 'common/services/tracking/segment';
import { useApp } from 'common/context/App';

interface FormScreenPropsT {
  setApiResponse: (data: any) => void;
}

const FIELD_OPTIONS = {
  shipping_period: [
    { label: '0-2 days', name: '0-2 days' },
    { label: '3-5 days', name: '3-5 days' },
    { label: '6-8 days', name: '6-8 days' },
    { label: 'More than 8 days', name: '8 days' },
  ],
  refund_period: [
    { label: '1-2 days', name: '1-2 days' },
    { label: '3-5 days', name: '3-5 days' },
    { label: '6-8 days', name: '6-8 days' },
    { label: '9-15 days', name: '9-15 days' },
    { label: '16-30 days', name: '16-30 days' },
  ],
  warranty: [
    { label: 'Upto 3 months', name: '3 months' },
    { label: 'Upto 6 months', name: '6 months' },
    { label: 'Upto 1 years', name: '1 years' },
    { label: 'Upto 2 years', name: '2 years' },
    { label: 'NA', name: 'NA' },
  ],
};

const generateTnc = async (payload) => {
  const respose = await fetch<any>({
    url: 'merchant/tnc',
    method: 'POST',
    mode: 'live',
    data: payload,
  });
  return respose;
};

const tncValidattionSchema = Yup.object().shape({
  support_email: Yup.string()
    .email('Please enter a valid email id.')
    .required('Support email is a required field')
    .nullable(),
  shipping_period: Yup.string().required('Shipping time is a required field').nullable(),
  refund_request_period: Yup.string()
    .required('Refund request time is a required field')
    .nullable(),
  refund_process_period: Yup.string()
    .required('Refund process time is a required field')
    .nullable(),
  warranty_period: Yup.string().required('Warranty time is a required field').nullable(),
});

const TncForm: React.FC<FormScreenPropsT> = ({ setApiResponse }) => {
  const { user } = useApp();

  const { mutate: postData } = useMutation({
    mutationFn: generateTnc,
    onSuccess: (response) => {
      setApiResponse(response);
      analyticsTrack({
        objectName: 'Act',
        actionName: 'tnc link',
        screen: 'tnc form',
        eventAction: 'success',
        user,
      });
    },
    onError: (err: any) => {
      analyticsTrack({
        objectName: 'Act',
        actionName: 'tnc link',
        screen: 'tnc form',
        properties: { error_msg: err?.response?.errors[0] },
        eventAction: 'failed',
        user,
      });
    },
  });

  const handleSubmit = (formikProps) => {
    const isServiceType = formikProps.values.product_type === 'services';

    analyticsTrack({
      objectName: 'Act',
      actionName: 'genrate tnc page',
      screen: 'tnc form',
      eventAction: 'clicked',
      user,
    });

    if (
      (!isServiceType && Object.keys(formikProps.errors).length) ||
      Object.keys(formikProps.errors).length > 2
    ) {
      return null;
    }
    const commonReqBody = {
      deliverable_type: isServiceType ? 'services' : 'goods',
      support_email: formikProps.values.support_email,
      refund_process_period: formikProps.values.refund_process_period,
      refund_request_period: formikProps.values.refund_request_period,
    };
    let reqData;
    if (isServiceType) {
      reqData = { ...commonReqBody };
    } else {
      reqData = {
        ...commonReqBody,
        warranty_period: formikProps.values.warranty_period,
        shipping_period: formikProps.values.shipping_period,
      };
    }
    if (Object.keys(reqData).length) {
      postData(reqData);
    }
    return null;
  };

  return (
    <Formik
      initialValues={{
        support_email: user.contact_email,
        shipping_period: '',
        refund_request_period: '',
        refund_process_period: '',
        warranty_period: '',
        product_type: 'goods',
        isConsentCheck: false,
      }}
      validationSchema={tncValidattionSchema}
      onSubmit={() => {}}
    >
      {(formikProps) => (
        <Form>
          <FormSection title="Do you provide goods or services?">
            <Field last>
              <Radio
                defaultValue="0"
                size="medium"
                onChange={(val) => {
                  if (val === '0') {
                    formikProps.setFieldValue('product_type', 'goods');
                  } else {
                    formikProps.setFieldValue('product_type', 'services');
                  }
                  analyticsTrack({
                    objectName: 'Act',
                    actionName: 'deliverable type',
                    screen: 'tnc form',
                    properties: { type_of_delivery: formikProps.values.product_type },
                    eventAction: 'clicked',
                    user,
                  });
                }}
              >
                <Radio.Option value="0" title="Goods" />
                <Radio.Option value="1" title="Services" />
              </Radio>
              <Flex alignItems="center">
                <View>
                  <Link
                    href={`https://${user.isOrgAxis ? 'axis' : 'tnc'}.razorpay.com/tnc/${
                      formikProps.values.product_type === 'goods'
                        ? '000000000goods'
                        : '000000services'
                    }`}
                    onClick={() => {
                      analyticsTrack({
                        objectName: 'Act',
                        actionName: 'sample tnc page',
                        screen: 'tnc form',
                        eventAction: 'clicked',
                        user,
                      });
                    }}
                    target="_blank"
                    rel="noreferrer noopener"
                  >
                    View Sample Page
                  </Link>
                  <Space margin={[0, 0, 0, 0.5]}>
                    <Flex>
                      <View>
                        <Icon name="link" size="small" fill="primary.800" />{' '}
                      </View>
                    </Flex>
                  </Space>
                </View>
              </Flex>
            </Field>
          </FormSection>
          <FormSection title="Other Information">
            <Field>
              <TextInput
                width="auto"
                name="support_email"
                type="text"
                label="Support Email Id"
                helpText="Mail on which customers can reach out to you for any queries"
                value={formikProps.values.support_email}
                onChange={(val) => formikProps.setFieldValue('support_email', val)}
                errorText={formikProps.touched.support_email && formikProps.errors.support_email}
              />
            </Field>
            <Field visible={formikProps.values.product_type === 'goods'}>
              <Select
                label="Time taken to ship orders"
                placeholder="Select"
                value={formikProps.values.shipping_period}
                onChange={(val) => {
                  formikProps.setFieldTouched('shipping_period');
                  formikProps.setFieldValue('shipping_period', val);
                  analyticsTrack({
                    objectName: 'Act',
                    actionName: 'shipping period',
                    screen: 'tnc form',
                    properties: { selected_option: val },
                    eventAction: 'initiated',
                    user,
                  });
                }}
                errorText={
                  formikProps.touched.shipping_period && formikProps.errors.shipping_period
                }
              >
                {FIELD_OPTIONS.shipping_period.map(({ label, name }) => (
                  <Option key={name} value={name} label={label}>
                    {label}
                  </Option>
                ))}
              </Select>
            </Field>
            <Field>
              <Select
                label="Refund request time"
                placeholder="Select"
                helpText="Until how many days from order delivery can your customers raise a refund request"
                value={formikProps.values.refund_request_period}
                onChange={(val) => {
                  formikProps.setFieldTouched('refund_request_period');
                  formikProps.setFieldValue('refund_request_period', val);
                  analyticsTrack({
                    objectName: 'Act',
                    actionName: 'refund request period',
                    screen: 'tnc form',
                    properties: { selected_option: val },
                    eventAction: 'initiated',
                    user,
                  });
                }}
                errorText={
                  formikProps.touched.refund_request_period &&
                  formikProps.errors.refund_request_period
                }
              >
                {FIELD_OPTIONS.refund_period.map(({ label, name }) => (
                  <Option key={name} value={name} label={label}>
                    {label}
                  </Option>
                ))}
              </Select>
            </Field>
            <Field>
              <Select
                label="Time taken to process refunds"
                placeholder="Select"
                value={formikProps.values.refund_process_period}
                onChange={(val) => {
                  formikProps.setFieldTouched('refund_process_period');
                  formikProps.setFieldValue('refund_process_period', val);
                  analyticsTrack({
                    objectName: 'Act',
                    actionName: 'refund process period',
                    screen: 'tnc form',
                    properties: { selected_option: val },
                    eventAction: 'initiated',
                    user,
                  });
                }}
                errorText={
                  formikProps.touched.refund_process_period &&
                  formikProps.errors.refund_process_period
                }
              >
                {FIELD_OPTIONS.refund_period.map(({ label, name }) => (
                  <Option key={name} value={name} label={label}>
                    {label}
                  </Option>
                ))}
              </Select>
            </Field>
            <Field visible={formikProps.values.product_type === 'goods'} last>
              <Select
                label="Warranty period of products"
                placeholder="Select"
                helpText="In case you sell multiple products with different warranty periods, Choose the range relevant for most of the products"
                value={formikProps.values.warranty_period}
                onChange={(val) => {
                  formikProps.setFieldTouched('warranty_period');
                  formikProps.setFieldValue('warranty_period', val);
                  analyticsTrack({
                    objectName: 'Act',
                    actionName: 'warranty period',
                    screen: 'tnc form',
                    properties: { selected_option: val },
                    eventAction: 'initiated',
                    user,
                  });
                }}
                errorText={
                  formikProps.touched.warranty_period && formikProps.errors.warranty_period
                }
              >
                {FIELD_OPTIONS.warranty.map(({ label, name }) => (
                  <Option key={name} value={name} label={label}>
                    {label}
                  </Option>
                ))}
              </Select>
            </Field>
          </FormSection>
          <FormSection title="Consent" last>
            <Field last>
              <Flex>
                <View>
                  <Checkbox
                    name="consent"
                    title=""
                    defaultChecked={formikProps.values.isConsentCheck}
                    onChange={(val) => {
                      formikProps.setFieldValue('isConsentCheck', val);
                      analyticsTrack({
                        objectName: 'Act',
                        actionName: 'tnc consent',
                        screen: 'tnc form',
                        properties: { checked: val },
                        eventAction: 'clicked',
                        user,
                      });
                    }}
                  />
                  <Space padding={[0, 0, 0, 0.75]}>
                    <View>
                      {user.isOrgAxis ? (
                        <>
                          I/We have reviewed and accepted the contents of URL LINK which covers the
                          Terms and Conditions as applicable for the Goods & Service being purchased
                          by the customers from me/our Company. I agree and confirm that, these
                          Terms and Conditions are prepared for me/our Company by [Razorpay] basis
                          my/our request and requirement and same shall not be considered as
                          substitute for independent legal advice, in any manner whatsoever. I/We
                          will use my/our independent discretion to determine the suitability of
                          these Terms and Conditions for my/our Company’s businesses purposes. I/We
                          hereby confirm that, neither the [Bank] nor [Razorpay] shall be held
                          liable or responsible in any manner whatsoever in respect of any dispute
                          arising out of or in connection with these Terms and Conditions.
                        </>
                      ) : (
                        <>
                          I / we understand and acknowledge that the use of these draft terms and
                          conditions for my website/ payment page is at my sole discretion and risk.
                          I understand that the provision of these draft terms and conditions by
                          Razorpay is not a substitute for independent legal advice, and I/ we will
                          use our independent discretion to determine the suitability of these for
                          my/ our business purposes and will absolve Razorpay of all liability in
                          this regard.
                        </>
                      )}
                    </View>
                  </Space>
                </View>
              </Flex>
            </Field>
          </FormSection>
          <Flex justifyContent="space-between">
            <StyledFooter>
              <Button
                type="submit"
                size="large"
                onClick={() => handleSubmit(formikProps)}
                disabled={!formikProps.values.isConsentCheck}
                block
              >
                Generate Page
              </Button>
            </StyledFooter>
          </Flex>
        </Form>
      )}
    </Formik>
  );
};

export default TncForm;
