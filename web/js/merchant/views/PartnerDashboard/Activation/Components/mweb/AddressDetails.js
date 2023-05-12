import React, { useState, useEffect } from 'react';
import { Formik } from 'formik';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import Input, { Label } from 'common/new-ui/Input';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Checkbox from '@razorpay/blade-old/src/atoms/Checkbox';
import FileUpload from 'merchant/components/File/Upload';
import { Select, Option } from 'common/components/Select';
import { FormSection, Field } from 'merchant/views/onboarding/mobile/Form';
import GetTouchedFields from 'merchant/views/PartnerDashboard/Activation/utils/GetTouchedFields';
import {
  useActivationFormState,
  isTabComplete,
} from 'merchant/views/PartnerDashboard/Activation/Hooks/store';
import useActivation, {
  getRequestData,
  uploadFileData,
  deleteFileData,
  fetchActivationData,
} from 'merchant/views/PartnerDashboard/Activation/Hooks/useActivation';
import {
  addressProofOptions,
  stateOptions,
} from 'merchant/views/PartnerDashboard/Activation/Components/AddressDetails';
import {
  getPincodeDetails,
  addressDetailsSchema,
} from 'merchant/views/PartnerDashboard/Activation/utils/ActivationUtils';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import activationFormatter from 'merchant/views/PartnerDashboard/Activation/utils/ActivationFormatter';

const getAddressLabel = (proofType) => {
  if (proofType && proofType.length > 0)
    return {
      front: `${proofType}_front`,
      back: `${proofType}_back`,
    };
  return { front: '', back: '' };
};

const AddressDetails = ({ isFormLocked, partnerID, showNotification }) => {
  const { data, postData } = useActivation();
  const [updatedData, setUpdatedData] = useState(data);
  const [isBlurCalled, setIsBlurCalled] = useState(false);
  const [isOpAddressSameAsRegAddress, setIsOpAddressSameAsRegAddress] = useState(true);
  const [uploadDocsData, setUploadDocsData] = useState({});
  // const forceUpdate = useForceUpdate();
  const addressDetails = updatedData
    ? updatedData?.address_details
    : data
    ? data.addressDetails
    : {};
  const commonLockedFields = data?.lock_common_fields || [];

  const setAddressDetailsCompleted = useActivationFormState(
    (state) => state.setAddressDetailsCompleted,
  );

  const selectedAddressProof =
    uploadDocsData?.selectedAddressProof || addressDetails?.address_proof_type;

  const labelObject = getAddressLabel(selectedAddressProof);
  const addressProofFrontLabel = labelObject.front;
  const addressProofBackLabel = labelObject.back;
  useEffect(() => {
    const selectedAddressProof = addressDetails?.address_proof_type || '';
    const labelObject = getAddressLabel(selectedAddressProof);
    const addressProofFrontLabel = labelObject.front;
    const addressProofBackLabel = labelObject.back;
    const addressFrontValue = addressDetails[addressProofFrontLabel];
    const addressBackValue = addressDetails[addressProofBackLabel];

    const frontAddressFileName = addressFrontValue && addressFrontValue[0]?.metadata?.file_name;
    const backAddressFileName = addressBackValue && addressBackValue[0]?.metadata?.file_name;
    if (addressFrontValue && addressFrontValue[0]) addressFrontValue[0].name = frontAddressFileName;
    if (addressBackValue && addressBackValue[0]) addressBackValue[0].name = backAddressFileName;

    setUploadDocsData({
      selectedAddressProof,
      frontDocument: addressFrontValue,
      backDocument: addressBackValue,
    });
  }, [addressDetails]);

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

  const handleFileUpload = (file, field) => {
    if (file) {
      const formData = new FormData();
      formData.append('document_type', field);
      formData.append('file', file);
      formData.append('is_partner_kyc', '1');
      uploadFileData(formData)
        .then((response) => {
          if (response.success) {
            setUpdatedData(activationFormatter(response.data));
            showNotification({
              type: 'success',
              message: 'File uploaded successfully',
            });
          }
          return response;
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
    }
  };

  const handleFileDelete = (label) => {
    const fileId = uploadDocsData[label] ? uploadDocsData[label][0]?.id : null;
    if (fileId) {
      deleteFileData(fileId)
        .then((res) => {
          if (res.success) {
            fetchActivationData().then((data) => {
              setUpdatedData(activationFormatter(data));
              showNotification({
                type: 'success',
                message: 'File deleted successfully',
              });
            });
          }
        })
        .catch(() => {
          showNotification({
            type: 'error',
            message: 'File Not Found!',
          });
        });
    }
  };

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
        address_proof_type: addressDetails?.address_proof_type?.value,
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

            <Field>
              <Select
                label="Address Proof"
                name="address_proof_type"
                onChange={(val) => {
                  formikProps.setFieldValue('address_proof_type', val);
                  setUploadDocsData({ ...uploadDocsData, selectedAddressProof: val });
                }}
                errorText={
                  formikProps.touched.address_proof_type && formikProps.errors.address_proof_type
                }
                value={
                  (formikProps.values.address_proof_type &&
                    formikProps.values.address_proof_type.toUpperCase()) ||
                  uploadDocsData?.selectedAddressProof
                }
                disabled={isFormLocked || commonLockedFields.includes('address_proof_type')}
                bottomSheetHeaderText="SELECT ADDRESS PROOF"
              >
                {addressProofOptions?.map((item, index) => {
                  return (
                    <React.Fragment key={index}>
                      <Option key={index} value={item.name} label={item.label}>
                        {item.label}
                      </Option>
                    </React.Fragment>
                  );
                })}
              </Select>
            </Field>

            <div className="Input">
              <Label text="Address Proof Front" />
              <div className="Dropzone-cavity-partner">
                <FileUpload
                  name={addressProofFrontLabel}
                  accept={['jpg', 'png', 'pdf']}
                  files={uploadDocsData.frontDocument}
                  maxSize={4194304} // 4MB
                  showCloseBtn
                  showFileSize={true}
                  showAcceptInfo={false}
                  customClassName="transactionlimit-fileupload Input-content"
                  onFileChange={(file) => handleFileUpload(file, addressProofFrontLabel)}
                  onCloseClick={() => handleFileDelete('frontDocument')}
                />
              </div>
            </div>

            <div className="Input">
              <Label text="Address Proof Back" />
              <div className="Dropzone-cavity-partner">
                <FileUpload
                  name={addressProofBackLabel}
                  accept={['jpg', 'png', 'pdf']}
                  files={uploadDocsData.backDocument}
                  maxSize={4194304} // 4MB
                  showCloseBtn
                  showFileSize={true}
                  showAcceptInfo={false}
                  customClassName="transactionlimit-fileupload Input-content"
                  onFileChange={(file) => handleFileUpload(file, addressProofBackLabel)}
                  onCloseClick={() => handleFileDelete('backDocument')}
                />
              </div>
            </div>
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
