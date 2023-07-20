import React, { useState, useEffect } from 'react';
import { Formik } from 'formik';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import Input from 'common/new-ui/Input';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Checkbox from '@razorpay/blade-old/src/atoms/Checkbox';
import { FormSection, Field } from 'merchant/views/onboarding/mobile/Form';
import GetTouchedFields from 'merchant/views/PartnerDashboard/Activation/utils/GetTouchedFields';
import {
  useActivationFormState,
  isTabComplete,
} from 'merchant/views/PartnerDashboard/Activation/Hooks/store';
import useActivation, {
  getRequestData,
} from 'merchant/views/PartnerDashboard/Activation/Hooks/useActivation';
import { stateOptions } from 'merchant/views/PartnerDashboard/Activation/Components/AddressDetails';
import {
  getPincodeDetails,
  addressDetailsSchema,
} from 'merchant/views/PartnerDashboard/Activation/utils/ActivationUtils';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const AddressDetails = ({ isFormLocked, partnerID, showNotification }) => {
  const { data, postData } = useActivation();
  const [isBlurCalled, setIsBlurCalled] = useState(false);
  const [isOpAddressSameAsRegAddress, setIsOpAddressSameAsRegAddress] = useState(true);
  const addressDetails = data?.addressDetails || {};
  const commonLockedFields = data?.lock_common_fields || [];

  const setAddressDetailsCompleted = useActivationFormState(
    (state) => state.setAddressDetailsCompleted,
  );

  const handleCityAndState = (e, formikProps) => {
    if (isOpAddressSameAsRegAddress) {
      const field = e.target?.name.replace('registered', 'operation');
      formikProps.setFieldValue(field, e.target?.value);
      formikProps.setFieldTouched(field, true);
    }
  };

  const handleSetAndTouch = (formikProps, field, value) => {
    formikProps.setFieldValue(field, value);
    formikProps.setFieldTouched(field, true);
  };

  const autoFillFromPinCode = (e, formikProps) => {
    const { name: field, value } = e.target;
    getPincodeDetails(value)
      .then((data) => {
        const cityField = `${field.slice(0, -3)}city`;
        const stateField = `${field.slice(0, -3)}state`;
        handleSetAndTouch(formikProps, cityField, data?.city);
        handleSetAndTouch(formikProps, stateField, data?.state_code);

        if (isOpAddressSameAsRegAddress && field.includes('registered')) {
          handleSetAndTouch(formikProps, cityField.replace('registered', 'operation'), data?.city);
          handleSetAndTouch(
            formikProps,
            stateField.replace('registered', 'operation'),
            data?.state_code,
          );
        }
        setIsBlurCalled(true);
      })
      .catch((err) => {
        if (err.errors.length && err.errors[0]) {
          showNotification({
            type: 'error',
            message: err.errors,
          });
        }
        return err;
      });
  };

  const handleBlur = (e, formikProps) => {
    const { name: field } = e.target;
    handleCityAndState(e, formikProps);
    if (field.includes('pin')) autoFillFromPinCode(e, formikProps);
    formikProps.handleBlur(e);
    if (!field.includes('pin')) setIsBlurCalled(true);
  };

  useEffect(() => {
    analyticsTrack({
      objectName: 'partnerships.partner_KYC',
      actionName: 'form_open',
      screen: 'Partner KYC Address Details',
      properties: {
        partnerID,
        section: 'Address Details',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }, []);

  const handleSubmit = (updatedDetails) => {
    const isComplete = isTabComplete(
      {
        ...data,
        address_details: { ...addressDetails, ...updatedDetails },
      },
      'address_details',
    );
    setAddressDetailsCompleted(isComplete);

    const reqData = getRequestData(addressDetails, updatedDetails);
    if (Object.keys(reqData).length) {
      postData(reqData);
    }
  };

  return (
    <Formik
      initialValues={{
        business_registered_address: addressDetails?.business_registered_address?.value,
        business_registered_pin: addressDetails?.business_registered_pin?.value,
        business_registered_city: addressDetails?.business_registered_city?.value,
        business_registered_state: addressDetails?.business_registered_state?.value,
        isOpAddressSameAsRegAddress,
        business_operation_address: addressDetails?.business_operation_address?.value,
        business_operation_pin: addressDetails?.business_operation_pin?.value,
        business_operation_city: addressDetails?.business_operation_city?.value,
        business_operation_state: addressDetails?.business_operation_state?.value,
      }}
      validationSchema={addressDetailsSchema()}
      validateOnMount={true}
    >
      {(formikProps) => (
        <form onChange={formikProps.handleChange} onBlur={(e) => handleBlur(e, formikProps)}>
          <FormSection title="Business Details" last>
            <Field>
              <TextInput
                width="auto"
                name="business_registered_address"
                label="Registered Business Address"
                value={
                  formikProps.values.business_registered_address &&
                  formikProps.values.business_registered_address.toUpperCase()
                }
                errorText={
                  formikProps.touched.business_registered_address &&
                  formikProps.errors.business_registered_address
                }
                disabled={
                  isFormLocked || commonLockedFields.includes('business_registered_address')
                }
                autoCapitalize="characters"
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="business_registered_pin"
                label="Registered Business Pincode"
                type="number"
                maxLength={6}
                value={
                  formikProps.values.business_registered_pin &&
                  formikProps.values.business_registered_pin.toUpperCase()
                }
                errorText={
                  formikProps.touched.business_registered_pin &&
                  formikProps.errors.business_registered_pin
                }
                disabled={isFormLocked || commonLockedFields.includes('business_registered_pin')}
                autoCapitalize="characters"
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="business_registered_city"
                label="Registered Business City"
                value={
                  formikProps.values.business_registered_city &&
                  formikProps.values.business_registered_city.toUpperCase()
                }
                errorText={
                  formikProps.touched.business_registered_city &&
                  formikProps.errors.business_registered_city
                }
                disabled={isFormLocked || commonLockedFields.includes('business_registered_city')}
                autoCapitalize="characters"
              />
            </Field>
            <Field>
              <Input.Select
                name="business_registered_state"
                label="Registered Business State"
                className="Input--vTop Input--space"
                size="small"
                options={stateOptions}
                required
                disabled={isFormLocked || commonLockedFields.includes('business_registered_state')}
                value={
                  formikProps.values.business_registered_state &&
                  formikProps.values.business_registered_state.toUpperCase()
                }
                errorText={
                  formikProps.touched.business_registered_state &&
                  formikProps.errors.business_registered_state
                }
              />
            </Field>

            <Space margin={[1.75, 0, 3.25, 0]}>
              <View>
                <Checkbox
                  name="isOpAddressSameAsRegAddress"
                  title="Is operational address same as registered address"
                  defaultChecked={isOpAddressSameAsRegAddress}
                  onChange={(value) => setIsOpAddressSameAsRegAddress(value)}
                />
              </View>
            </Space>

            <Field visible={!isOpAddressSameAsRegAddress}>
              <TextInput
                width="auto"
                name="business_operation_address"
                label="Operational Business Address"
                value={
                  formikProps.values.business_operation_address &&
                  formikProps.values.business_operation_address.toUpperCase()
                }
                errorText={
                  formikProps.touched.business_operation_address &&
                  formikProps.errors.business_operation_address
                }
                disabled={isFormLocked || commonLockedFields.includes('business_operation_address')}
                autoCapitalize="characters"
              />
            </Field>
            <Field visible={!isOpAddressSameAsRegAddress}>
              <TextInput
                width="auto"
                name="business_operation_pin"
                label="Operational Business Pincode"
                type="number"
                maxLength={6}
                value={
                  formikProps.values.business_operation_pin &&
                  formikProps.values.business_operation_pin.toUpperCase()
                }
                errorText={
                  formikProps.touched.business_operation_pin &&
                  formikProps.errors.business_operation_pin
                }
                disabled={isFormLocked || commonLockedFields.includes('business_operation_pin')}
                autoCapitalize="characters"
              />
            </Field>
            <Field visible={!isOpAddressSameAsRegAddress}>
              <TextInput
                width="auto"
                name="business_operation_city"
                label="Operational Business City"
                value={
                  formikProps.values.business_operation_city &&
                  formikProps.values.business_operation_city.toUpperCase()
                }
                errorText={
                  formikProps.touched.business_operation_city &&
                  formikProps.errors.business_operation_city
                }
                disabled={isFormLocked || commonLockedFields.includes('business_operation_city')}
                autoCapitalize="characters"
              />
            </Field>
            <Field visible={!isOpAddressSameAsRegAddress}>
              <Input.Select
                name="business_operation_state"
                label="Operational Business State"
                className="Input--vTop Input--space"
                size="small"
                options={stateOptions}
                required
                disabled={isFormLocked || commonLockedFields.includes('business_operation_state')}
                value={
                  formikProps.values.business_operation_state &&
                  formikProps.values.business_operation_state.toUpperCase()
                }
                errorText={
                  formikProps.touched.business_operation_state &&
                  formikProps.errors.business_operation_state
                }
              />
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

export default AddressDetails;
