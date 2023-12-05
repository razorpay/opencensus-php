import React, { useState, useEffect } from 'react';
import styled from 'styled-components';
import * as Yup from 'yup';
import { Formik } from 'formik';
import { useQuery } from '@tanstack/react-query';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import TextArea from '@razorpay/blade-old/src/atoms/TextArea';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import Checkbox from '@razorpay/blade-old/src/atoms/Checkbox';
import { Select, Option } from 'common/components/Select';
import { FormSection, Field, GetTouchedFields } from 'merchant/views/onboarding/mobile/Form';
import {
  useActivationFormState,
  isVisible,
  isTabComplete,
} from 'merchant/views/onboarding/mobile/context/store';
import useActivation, {
  getRequestData,
} from 'merchant/views/onboarding/mobile/hooks/useActivation';
import useGstin from 'merchant/views/onboarding/mobile/hooks/useGstin';
import {
  getLabel,
  isUnregisteredBusiness,
  getHelpText,
  getPanError,
  getPanNameError,
  isVerificationValid,
  getGstinFiledError,
  getCinFieldError,
} from 'merchant/views/onboarding/mobile/services/utils';
import {
  states,
  CIN_BusinessTypes,
  LLPIN_BusinessTypes,
  PROPRIETORSHIP,
} from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import { analyticsTrack } from 'common/services/tracking/segment';
import { useApp } from 'common/context/App';
import { useSnackbar } from 'common/components/SnackBar/SnackbarContext';
import { fetch } from 'common/services/rest/rest-fetch';
import BusinessName from 'merchant/views/onboarding/mobile/Fields/BusinessName';
import GstinAutoPopulate from 'merchant/views/onboarding/mobile/Fields/GstinAutoPopulate';
import usePartnerActivation from 'merchant/views/onboarding/mobile/hooks/usePartnerActivation';
import useTrackEvents from 'merchant/hooks/useTrackEvents';

const Container = styled(View)`
  position: relative;
`;
const IconContainer = styled.span`
  position: absolute;
  right: 12px;
  top: 4px;
`;
const businessDetailsSchema = ({ hasGSTIN, businessOverviewDetails }) =>
  Yup.object().shape({
    company_pan: Yup.string()
      .trim()
      .length(10, 'PAN card must be 10 characters')
      .matches(/^[a-zA-z]{5}\d{4}[a-zA-Z]{1}$/, {
        message: 'Invalid PAN Card',
        excludeEmptyString: true,
      })
      .test('companypan', 'Invalid PAN format.', (value) => {
        if (!value || value.length <= 3) {
          return true;
        }
        return ['C', 'H', 'F', 'A', 'T', 'B', 'J', 'G', 'L'].indexOf(value[3].toUpperCase()) !== -1;
      })
      .required('Company PAN is a required field')
      .nullable(),
    business_name: Yup.string().required('Business Name is a required field').nullable(),
    promoter_pan: Yup.string()
      .trim()
      .length(10, 'PAN must be 10 characters')
      .matches(/^[a-zA-z]{5}\d{4}[a-zA-Z]{1}$/, {
        message: 'Invalid PAN Card.',
        excludeEmptyString: true,
      })
      .test('promoter_pan', 'Invalid PAN Card', (value) => {
        if (!value || value.length <= 3) {
          return true;
        }
        return value[3].toLowerCase() === 'p';
      })
      .required('Promoter PAN is a required field')
      .nullable(),
    promoter_pan_name: Yup.string()
      .matches(/^[a-zA-Z ]+$/, {
        message: 'PAN Name should not have any numbers or special characters',
        excludeEmptyString: true,
      })
      .required('Promoter PAN Name is a required field')
      .nullable(),
    business_dba: Yup.string()
      .nullable()
      .min(3, 'Please enter billing label with at least 3 characters')
      .required('Billing Label is a required field')
      .nullable(),
    business_registered_address: Yup.string()
      .required('Registered address is a required field')
      .nullable(),
    business_registered_state: Yup.string()
      .required('Registered state is a required field')
      .nullable(),
    business_registered_city: Yup.string()
      .required('Registered city is a required field')
      .nullable(),
    business_registered_pin: Yup.string()
      .trim()
      .length(6, 'Please enter a 6 digit pincode')
      .required('Registered PIN is a required field')
      .nullable(),
    business_operation_address: Yup.string()
      .required('Operation Address is a required field')
      .nullable(),
    business_operation_state: Yup.string()
      .required('Operation State is a required field')
      .nullable(),
    business_operation_city: Yup.string().required('Operation City is a required field').nullable(),
    business_operation_pin: Yup.string()
      .trim()
      .length(6, 'Please enter a 6 digit pincode')
      .required('Operation PIN is a required field')
      .nullable(),
    gstin: Yup.string().when('hasGstin', {
      is: hasGSTIN,
      then: Yup.string()
        .trim()
        .length(15, 'Please provide valid GSTIN')
        .matches(/^[0123][0-9][a-z]{5}[0-9]{4}[a-z][0-9][a-z0-9][a-z0-9]$/gi, {
          message: 'Invalid GSTIN',
          excludeEmptyString: true,
        })
        .required('GSTIN is a required field')
        .nullable(),
      otherwise: Yup.string().trim().nullable(),
    }),
    company_cin: Yup.lazy(() => {
      if (CIN_BusinessTypes.includes(Number(businessOverviewDetails.business_type.value))) {
        return Yup.string()
          .trim()
          .length(21, 'CIN must be 21 characters')
          .matches(/^([a-z]{3}-\d{4}|[ul]\d{5}[a-z]{2}\d{4}[a-z]{3}\d{6})$/i, {
            message: 'Invalid Format',
            excludeEmptyString: true,
          })
          .required('Company CIN is a required field')
          .nullable();
      }
      return Yup.string()
        .trim()
        .matches(/^([a-z]{3}-\d{4}|[ul]\d{5}[a-z]{2}\d{4}[a-z]{3}\d{6})$/i, {
          message: 'Invalid Format',
          excludeEmptyString: true,
        })
        .required('LLPIN is a required field')
        .nullable();
    }),
  });

interface IBusinessDetailsProps {
  isFormLocked?: boolean;
  startPolling?: () => void;
}

const BusinessDetails = ({
  isFormLocked,
  startPolling = () => {},
}: IBusinessDetailsProps): React.ReactElement => {
  const { data, postData } = useActivation();
  const trackEvents = useTrackEvents();
  const {
    user,
    experiments: {
      canSkipPoiValidation,
      isSyncExperimentEnabled,
      isGstinAutoPopulate,
      isInstantActivationEnabled,
      isGstinSyncFlowEnabled,
      isLlpinSyncFlowEnabled,
      isCinSyncFlowEnabled,
      isGstinLLpinCinSyncFlowEnabled,
    },
  } = useApp();
  const { gstinDetails } = useGstin();
  const snackbar = useSnackbar();
  const [pinCode, setPinCodeValue] = useState<string>('');
  const [isRegisteredPin, setIsRegisteredPin] = useState<boolean>(true);
  const [addressFormikValue, setAddressFormikValue] = useState({});
  const [businessDetailsCardTitle, setBusinessDetailsCardTitle] = useState('');

  const { business_type: businessType } = data;
  const businessDetails = data.business_details;
  const businessOverviewDetails = data.business_overview;

  const hasGSTIN = useActivationFormState((state) => state.has_gstin);
  const setHasGSTIN = useActivationFormState((state) => state.setHasGSTIN);

  const setBusinessDetailsCompleted = useActivationFormState(
    (state) => state.setBusinessDetailsCompleted,
  );
  const setIsOpen = useActivationFormState((state) => state.setIsFAQOpen);
  const setFAQSection = useActivationFormState((state) => state.setFAQSection);
  const setSameAddress = useActivationFormState((state) => state.setSameAddress);
  const hasSameAdress = useActivationFormState((state) => state.same_address);
  const [isBlurCalled, setIsBlurCalled] = useState<boolean>(false);
  const { getFieldStatus } = usePartnerActivation();

  const autoFillCityState = (context) => {
    let reqData = {};
    if (hasSameAdress && isRegisteredPin) {
      reqData = {
        business_registered_city: context.city || '',
        business_registered_state: context.state_code || '',
        business_operation_city: context.city || '',
        business_operation_state: context.state_code || '',
      };
    } else if (isRegisteredPin && !hasSameAdress) {
      reqData = {
        business_registered_city: context.city || '',
        business_registered_state: context.state_code || '',
      };
    } else if (!isRegisteredPin && !hasSameAdress) {
      reqData = {
        business_operation_city: context.city || '',
        business_operation_state: context.state_code || '',
      };
    }
    setAddressFormikValue(reqData);
    postData(reqData);
  };

  const { refetch } = useQuery({
    queryKey: ['pincode', pinCode],
    queryFn: async () => {
      const fetchData = await fetch<any>({
        url: `pincodes/${pinCode}`,
      });
      return fetchData;
    },
    enabled: false,
    retry: false,
    refetchOnWindowFocus: false,
    onSuccess: (res) => {
      autoFillCityState(res);
    },
    onError: () => {
      autoFillCityState({});
      snackbar.error('invalid pin code');
    },
  });

  const hasPoiStatus = isVerificationValid(data?.poi_verification_status);

  const shouldShowPoiError: boolean =
    !data.submitted && hasPoiStatus && (!canSkipPoiValidation || isSyncExperimentEnabled);

  const isCompanyPanInvalid: boolean =
    isVisible('company_pan', data) &&
    !data.submitted &&
    isSyncExperimentEnabled &&
    isVerificationValid(data?.company_pan_verification_status);

  useEffect(() => {
    if (hasPoiStatus) {
      analyticsTrack({
        objectName: 'SignUp',
        actionName: 'bank account',
        screen: 'home page',
        eventAction: 'failed',
        user,
      });
    }
  }, [hasPoiStatus]);

  useEffect(() => {
    trackEvents({
      objectName: 'Page',
      actionName: 'Viewed',
      screen: 'home page',
      properties: {
        pageTitle: 'Business Overview',
      },
    });
    if (isGstinSyncFlowEnabled || isLlpinSyncFlowEnabled || isCinSyncFlowEnabled) {
      trackEvents({
        objectName: 'BVS in sync mode',
        actionName: 'qualified',
        screen: 'home page',
        properties: {
          pageTitle: 'Business Overview',
          isBvsInSsync: isGstinLLpinCinSyncFlowEnabled,
          isGstinSync: isGstinSyncFlowEnabled,
          isLlpinSync: isLlpinSyncFlowEnabled,
          isCinSync: isCinSyncFlowEnabled,
        },
      });
    }
  }, []);

  const copySameAddress = (reqData, updatedDetails) => {
    let _reqData = { ...reqData };
    const addressFieldKeys = [
      'business_registered_address',
      'business_registered_state',
      'business_registered_city',
      'business_registered_pin',
    ];
    addressFieldKeys.forEach((key) => {
      const operationAddressKey = key.replace('registered', 'operation');
      if (updatedDetails[key]) {
        _reqData = {
          ..._reqData,
          [operationAddressKey]: {
            value: updatedDetails[key].value,
            error: updatedDetails[key].error,
          },
        };
      } else {
        _reqData = {
          ..._reqData,
          [operationAddressKey]: {
            value: businessDetails[key].value,
            error: businessDetails[key].error,
          },
        };
      }
    });
    return _reqData;
  };

  const handleSameAddress = (checked) => {
    setSameAddress(checked);
    setIsBlurCalled(true);
  };

  const handleBlur = (e, formikProps) => {
    formikProps.handleBlur(e);
    setIsBlurCalled(true);
  };

  const handleSubmit = (updatedDetails) => {
    let reqData;
    const isComplete = isTabComplete(
      {
        ...data,
        business_details: { ...businessDetails, ...updatedDetails },
        hasSameAdress,
        hasGSTIN,
        isInstantActivationEnabled,
      },
      'business_details',
    );
    setBusinessDetailsCompleted(isComplete);
    if (hasSameAdress) {
      reqData = copySameAddress(reqData, updatedDetails);
      updatedDetails = { ...reqData, ...updatedDetails };
    }
    reqData = getRequestData(businessDetails, updatedDetails);
    if (Object.keys(reqData).length) {
      postData(reqData, {
        onSuccess: (res) => {
          if (isGstinSyncFlowEnabled || isLlpinSyncFlowEnabled || isCinSyncFlowEnabled) {
            const { gstin_verification_status, cin_verification_status } = res ?? {};
            //if anyone of these status got updated and status is initiated then start polling.
            const canStartPolling =
              [gstin_verification_status, cin_verification_status].indexOf('initiated') !== -1;

            if (canStartPolling) {
              startPolling();
            }
          }
        },
      });
    }
  };

  useEffect(() => {
    const hasSamePinCode = [
      businessDetails.business_registered_pin.value,
      businessDetails.business_operation_pin.value,
    ].includes(pinCode);

    if (hasSamePinCode && pinCode.length === 6) {
      refetch();
    }
  }, [businessDetails, pinCode]);

  useEffect(() => {
    if (isCompanyPanInvalid) {
      analyticsTrack({
        objectName: 'Company PAN',
        actionName: 'mismatch error',
        screen: 'Business Detail Tab',
        eventAction: 'thrown',
        user,
        isLJReqiuired: false,
      });
    }
    if (shouldShowPoiError) {
      analyticsTrack({
        objectName: 'PAN',
        actionName: 'mismatch error',
        screen: 'Business Detail Tab',
        eventAction: 'thrown',
        user,
        isLJReqiuired: false,
      });
    }
  }, [isCompanyPanInvalid, shouldShowPoiError]);

  const isPanVerified: boolean =
    data.poi_verification_status === 'verified' && isSyncExperimentEnabled;
  const isCompanyPanVerified: boolean =
    data.company_pan_verification_status === 'verified' && isSyncExperimentEnabled;
  const isGstinVerificationFailed =
    isGstinSyncFlowEnabled && isVerificationValid(data?.gstin_verification_status);
  const isCinVerificationFailed =
    (isLlpinSyncFlowEnabled || isCinSyncFlowEnabled) &&
    isVerificationValid(data?.cin_verification_status);
  const CIN_TYPE = LLPIN_BusinessTypes.includes(Number(businessType)) ? 'LLPIN' : 'CIN';

  return (
    <Formik
      initialValues={{
        gstin: businessDetails.gstin.value,
        company_cin: businessDetails.company_cin.value,
        company_pan: businessDetails.company_pan.value,
        business_name: businessDetails.business_name.value,
        promoter_pan: businessDetails.promoter_pan.value,
        promoter_pan_name: businessDetails.promoter_pan_name.value,
        business_dba: businessDetails.business_dba.value,
        business_registered_address: businessDetails.business_registered_address.value,
        business_registered_state: businessDetails.business_registered_state.value || '',
        business_registered_city: businessDetails.business_registered_city.value || '',
        business_registered_pin: businessDetails.business_registered_pin.value,
        business_operation_address: businessDetails.business_operation_address.value,
        business_operation_state: businessDetails.business_operation_state.value || '',
        business_operation_city: businessDetails.business_operation_city.value || '',
        business_operation_pin: businessDetails.business_operation_pin.value,
        ...addressFormikValue,
      }}
      validationSchema={businessDetailsSchema.bind(null, { hasGSTIN, businessOverviewDetails })}
      enableReinitialize
      onSubmit={() => console.log('onSubmit')}
    >
      {(formikProps) => (
        <form
          onChange={formikProps.handleChange}
          onBlur={(e) => {
            handleBlur(e, formikProps);
          }}
        >
          <FormSection
            title="PAN Details"
            subtitle={
              shouldShowPoiError || isCompanyPanInvalid
                ? 'PAN Verification failed. Please review your details and submit again'
                : 'These details will be verified with the government database'
            }
            hasError={shouldShowPoiError || isCompanyPanInvalid}
          >
            <Field visible={isVisible('company_pan', data)}>
              <TextInput
                width="auto"
                name="company_pan"
                label="Business PAN"
                value={
                  formikProps.values.company_pan && formikProps.values.company_pan.toUpperCase()
                }
                errorText={getPanError(
                  formikProps.touched.company_pan,
                  formikProps.errors.company_pan,
                  isCompanyPanInvalid,
                )}
                disabled={
                  isFormLocked || isCompanyPanVerified || getFieldStatus('company_pan').isDisabled
                }
                helpText={getFieldStatus('company_pan').description || 'PAN of the Company'}
                autoCapitalize="characters"
                onBlur={() => setBusinessDetailsCardTitle('PAN Details')}
              />
            </Field>
            <Field visible={isVisible('business_name', data)}>
              {CIN_BusinessTypes.includes(Number(businessType)) ||
              LLPIN_BusinessTypes.includes(Number(businessType)) ? (
                <Space margin={[0, 0, 0, 0]}>
                  <View>
                    <BusinessName
                      businessNameValue={formikProps.values.business_name}
                      errorText={getPanNameError(
                        formikProps.touched.business_name,
                        formikProps.errors.business_name,
                        isCompanyPanInvalid,
                      )}
                      updateBusinessName={({
                        company_name = '',
                        identity_number,
                        identity_type,
                      }) => {
                        formikProps.setFieldTouched('business_name');
                        formikProps.setFieldValue('business_name', company_name);
                        if (identity_type) {
                          const isCin = CIN_BusinessTypes.includes(Number(businessType));
                          const isLlpin = LLPIN_BusinessTypes.includes(Number(businessType));
                          if (
                            (isCin && identity_type === 'cin') ||
                            (isLlpin && identity_type === 'llpin')
                          ) {
                            formikProps.setFieldTouched('company_cin');
                            formikProps.setFieldValue('company_cin', identity_number);
                          }
                        }
                        if (!data.business_dba) {
                          formikProps.setFieldTouched('business_dba');
                          formikProps.setFieldValue('business_dba', company_name);
                        }
                        setBusinessDetailsCardTitle('PAN Details');
                        setIsBlurCalled(true);
                      }}
                      onInputBlur={(value) => {
                        formikProps.setFieldTouched('business_name');
                        formikProps.setFieldValue('business_name', value);
                        if (!data.business_dba) {
                          formikProps.setFieldTouched('business_dba');
                          formikProps.setFieldValue('business_dba', value);
                        }
                        setIsBlurCalled(true);
                      }}
                      disabled={
                        isFormLocked ||
                        isCompanyPanVerified ||
                        getFieldStatus('business_name').isDisabled
                      }
                    />
                  </View>
                </Space>
              ) : (
                <TextInput
                  width="auto"
                  name="business_name"
                  label="Business Name"
                  value={formikProps.values.business_name}
                  errorText={getPanNameError(
                    formikProps.touched.business_name,
                    formikProps.errors.business_name,
                    isCompanyPanInvalid,
                  )}
                  disabled={
                    isFormLocked ||
                    isCompanyPanVerified ||
                    getFieldStatus('business_name').isDisabled
                  }
                  onChange={(value: string) => {
                    if (!businessDetails.business_dba.value) {
                      formikProps.setFieldTouched('business_dba');
                      formikProps.setFieldValue('business_dba', value);
                    }
                  }}
                  helpText={
                    getFieldStatus('business_name').description || 'As mentioned in the PAN'
                  }
                />
              )}
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="promoter_pan"
                label={getLabel('promoter_pan', data)}
                value={
                  formikProps.values.promoter_pan && formikProps.values.promoter_pan.toUpperCase()
                }
                errorText={getPanError(
                  formikProps.touched.promoter_pan,
                  formikProps.errors.promoter_pan,
                  shouldShowPoiError,
                )}
                disabled={
                  isFormLocked || isPanVerified || getFieldStatus('promoter_pan').isDisabled
                }
                helpText={
                  getFieldStatus('promoter_pan').description || getHelpText('promoter_pan', data)
                }
                onBlur={() => {
                  setBusinessDetailsCardTitle('PAN Details');
                }}
                autoCapitalize="characters"
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="promoter_pan_name"
                label={getLabel('promoter_pan_name', data)}
                value={formikProps.values.promoter_pan_name}
                errorText={getPanNameError(
                  formikProps.touched.promoter_pan_name,
                  formikProps.errors.promoter_pan_name,
                  shouldShowPoiError,
                )}
                disabled={
                  isFormLocked || isPanVerified || getFieldStatus('promoter_pan_name').isDisabled
                }
                onChange={(value: string) => {
                  if (
                    isUnregisteredBusiness(businessOverviewDetails.business_type.value) &&
                    !businessDetails.business_dba.value
                  ) {
                    formikProps.setFieldTouched('business_dba');
                    formikProps.setFieldValue('business_dba', value);
                  }
                }}
                helpText={
                  getFieldStatus('promoter_pan_name').description || 'As mentioned in the PAN'
                }
                onBlur={() => setBusinessDetailsCardTitle('PAN Details')}
              />
            </Field>
            <Field visible={isVisible('company_cin', data)}>
              <TextInput
                width="auto"
                name="company_cin"
                label={getLabel('company_cin', data)}
                value={formikProps.values.company_cin}
                errorText={getCinFieldError(
                  formikProps.touched.company_cin,
                  formikProps.errors.company_cin,
                  isCinVerificationFailed,
                  CIN_TYPE,
                  data?.cin_verification_status,
                )}
                onBlur={() => {
                  analyticsTrack({
                    objectName: 'SignUp',
                    actionName: 'Company Cin',
                    screen: 'home page',
                    eventAction: 'initiated',
                    user,
                  });
                  setBusinessDetailsCardTitle('PAN Details');
                }}
                disabled={isFormLocked}
              />
            </Field>
            <Field last>
              <Container>
                <TextInput
                  width="auto"
                  name="business_dba"
                  label="Billing Label"
                  helpText="Your brand name that your customers are familiar with"
                  value={formikProps.values.business_dba}
                  errorText={formikProps.touched.business_dba && formikProps.errors.business_dba}
                  disabled={isFormLocked}
                  onChange={(value: string) => {
                    formikProps.setFieldValue('business_dba', value);
                  }}
                />
                <IconContainer
                  onClick={() => {
                    if (isFormLocked) {
                      return;
                    }
                    analyticsTrack({
                      objectName: 'SignUp',
                      actionName: 'faq',
                      screen: 'home page',
                      user,
                      eventAction: 'initiated',
                      properties: {
                        clickSource: 'billing label',
                      },
                    });
                    setFAQSection('Q1');
                    setIsOpen(true);
                  }}
                >
                  <Icon
                    name="helpCircle"
                    size="small"
                    fill={isFormLocked ? 'shade.930' : 'primary.800'}
                  />
                </IconContainer>
              </Container>
            </Field>
          </FormSection>

          <FormSection
            title="Address Details"
            subtitle="These details will be verified with the government database"
            disabled={isFormLocked}
          >
            <Field>
              <TextArea
                width="auto"
                name="business_registered_address"
                label="Enter Address"
                value={formikProps.values.business_registered_address}
                errorText={
                  formikProps.touched.business_registered_address &&
                  formikProps.errors.business_registered_address
                }
                disabled={isFormLocked}
                onBlur={() => setBusinessDetailsCardTitle('Address Details')}
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="business_registered_pin"
                label="Pincode"
                value={formikProps.values.business_registered_pin}
                errorText={
                  formikProps.touched.business_registered_pin &&
                  formikProps.errors.business_registered_pin
                }
                onChange={(value: string) => {
                  if (value.length === 6) {
                    setPinCodeValue(value);
                  }
                  setIsRegisteredPin(true);
                }}
                disabled={isFormLocked}
                onBlur={() => setBusinessDetailsCardTitle('Address Details')}
              />
            </Field>
            <Field>
              <TextInput
                width="auto"
                name="business_registered_city"
                label="City"
                value={formikProps.values.business_registered_city}
                errorText={
                  formikProps.touched.business_registered_city &&
                  formikProps.errors.business_registered_city
                }
                disabled={isFormLocked}
                onBlur={(value) => {
                  setBusinessDetailsCardTitle('Address Details');
                  formikProps.setFieldTouched('business_registered_city');
                  formikProps.setFieldValue('business_registered_city', value);
                }}
              />
            </Field>
            <Field>
              <Select
                label="Select State"
                bottomSheetHeaderText="SELECT STATE"
                placeholder=""
                inputPlaceholder="Search State"
                searchable={true}
                errorText={
                  formikProps.touched.business_registered_state &&
                  formikProps.errors.business_registered_state
                }
                value={formikProps.values.business_registered_state}
                onChange={(value) => {
                  formikProps.setFieldTouched('business_registered_state');
                  formikProps.setFieldValue('business_registered_state', value);
                  setIsBlurCalled(true);
                  trackEvents({
                    objectName: 'Bottom sheet',
                    actionName: 'Closed',
                    screen: 'home page',
                    properties: {
                      'Modal Label': 'Select state',
                    },
                  });
                }}
                onInputBlur={(value) => {
                  setBusinessDetailsCardTitle('Address Details');
                  formikProps.setFieldTouched('business_registered_state');
                  formikProps.setFieldValue('business_registered_state', value);
                  setIsBlurCalled(true);
                  analyticsTrack({
                    objectName: 'SignUp',
                    actionName: 'bank account',
                    screen: 'home page',
                    eventAction: 'failed',
                    user,
                  });
                }}
                disabled={isFormLocked}
              >
                {Object.keys(states).map((state_code) => (
                  <Option key={state_code} value={state_code} label={states[state_code]}>
                    {states[state_code]}
                  </Option>
                ))}
              </Select>
            </Field>
            <Field visible={isVisible('business_operation_address', data)} last>
              <Checkbox
                name="same_address"
                title="Operational address is the same as above"
                helpText="Physical verification may take place"
                defaultChecked={hasSameAdress}
                onChange={(value) => {
                  handleSameAddress(value);
                  setBusinessDetailsCardTitle('Address Details');
                  trackEvents({
                    objectName: 'Checkbox',
                    actionName: 'Clicked',
                    screen: 'home page',
                    properties: {
                      'Checkbox Label': 'Physical verification may take place',
                      'Option Selected': 'Physical verification may take place',
                      'Element Type': 'Form',
                      Mandatory: 'Yes',
                    },
                  });
                }}
              />
            </Field>
          </FormSection>

          {!hasSameAdress ? (
            <FormSection title="Business Operational Address" last>
              <Field>
                <TextArea
                  width="auto"
                  name="business_operation_address"
                  label="Enter Address"
                  value={formikProps.values.business_operation_address}
                  errorText={
                    formikProps.touched.business_operation_address &&
                    formikProps.errors.business_operation_address
                  }
                  disabled={isFormLocked}
                  onBlur={() => setBusinessDetailsCardTitle('Business Operational Address')}
                />
              </Field>
              <Field>
                <TextInput
                  width="auto"
                  name="business_operation_pin"
                  label="Pincode"
                  value={formikProps.values.business_operation_pin}
                  errorText={
                    formikProps.touched.business_operation_pin &&
                    formikProps.errors.business_operation_pin
                  }
                  disabled={isFormLocked}
                  onBlur={(value) => {
                    setBusinessDetailsCardTitle('Business Operational Address');
                    formikProps.setFieldTouched('business_operation_pin');
                    formikProps.setFieldValue('business_operation_pin', value);
                  }}
                  onChange={(value: string) => {
                    if (value.length === 6) {
                      setPinCodeValue(value);
                    }
                    setIsRegisteredPin(false);
                  }}
                />
              </Field>
              <Field>
                <TextInput
                  width="auto"
                  name="business_operation_city"
                  label="City"
                  value={formikProps.values.business_operation_city}
                  errorText={
                    formikProps.touched.business_operation_city &&
                    formikProps.errors.business_operation_city
                  }
                  onBlur={() => setBusinessDetailsCardTitle('Business Operational Address')}
                  disabled={isFormLocked}
                />
              </Field>
              <Field last>
                <Select
                  label="Select State"
                  placeholder=""
                  bottomSheetHeaderText="SELECT STATE"
                  inputPlaceholder="Search State"
                  searchable={true}
                  errorText={
                    formikProps.touched.business_operation_state &&
                    formikProps.errors.business_operation_state
                  }
                  value={formikProps.values.business_operation_state}
                  onChange={(value) => {
                    formikProps.setFieldTouched('business_operation_state');
                    formikProps.setFieldValue('business_operation_state', value);
                    setIsBlurCalled(true);
                  }}
                  onInputBlur={() => setBusinessDetailsCardTitle('Business Operational Address')}
                  disabled={isFormLocked}
                >
                  {Object.keys(states).map((state_code) => (
                    <Option key={state_code} value={state_code} label={states[state_code]}>
                      {states[state_code]}
                    </Option>
                  ))}
                </Select>
              </Field>
            </FormSection>
          ) : null}

          {isVisible('gstin', {
            ...data,
            isInstantActivationEnabled,
          }) ? (
            <FormSection title="Company Details" last disabled={isFormLocked}>
              <Field last>
                {isGstinAutoPopulate && gstinDetails?.gstinList && data.gstin !== '' ? (
                  <GstinAutoPopulate
                    gstin={formikProps.values.gstin}
                    gstinDetails={gstinDetails}
                    errorText={getGstinFiledError(
                      formikProps.touched.gstin,
                      formikProps.errors.gstin,
                      isGstinVerificationFailed,
                      data?.gstin_verification_status,
                    )}
                    updateGstin={(value) => {
                      formikProps.setFieldTouched('gstin');
                      formikProps.setFieldValue('gstin', value);
                      setBusinessDetailsCardTitle('Company Details');
                      setIsBlurCalled(true);
                    }}
                    hasGSTIN={hasGSTIN}
                    disabled={isFormLocked || hasGSTIN || getFieldStatus('gstin').isDisabled}
                    location="Business Details Tab"
                  />
                ) : (
                  <TextInput
                    width="auto"
                    name="gstin"
                    label="GST Identification Number (GSTIN)"
                    value={formikProps.values.gstin}
                    errorText={getGstinFiledError(
                      formikProps.touched.gstin,
                      formikProps.errors.gstin,
                      isGstinVerificationFailed,
                      data?.gstin_verification_status,
                    )}
                    onBlur={() => {
                      setBusinessDetailsCardTitle('Company Details');
                      analyticsTrack({
                        objectName: 'SignUp',
                        actionName: 'Gst Identification Number',
                        screen: 'home page',
                        eventAction: 'initiated',
                        user,
                      });
                      setBusinessDetailsCardTitle('Company Details');
                    }}
                    disabled={isFormLocked || hasGSTIN || getFieldStatus('gstin').isDisabled}
                    helpText={
                      getFieldStatus('gstin').description ||
                      'Enter GSTIN & get reviewed faster. Should match your business address.'
                    }
                  />
                )}
              </Field>
              {!isUnregisteredBusiness(businessOverviewDetails.business_type.value) ? (
                <>
                  <Space margin={[1.75, 0, 0, 0]}>
                    <View>
                      <Checkbox
                        name="no_gstin"
                        title="I don't have a GSTIN"
                        defaultChecked={hasGSTIN}
                        onChange={(value) => {
                          setHasGSTIN(value);
                          if (value) {
                            formikProps.setFieldTouched('gstin');
                            formikProps.setFieldValue('gstin', '');
                            setIsBlurCalled(true);
                          } else {
                            isTabComplete(
                              { ...data, hasGSTIN: !value },
                              'bank_and_company_details',
                            );
                            setBusinessDetailsCompleted(value);
                          }
                          setBusinessDetailsCardTitle('Company Details');
                          trackEvents({
                            objectName: 'Checkbox',
                            actionName: 'Clicked',
                            screen: 'home page',
                            properties: {
                              'Checkbox Label': 'I dont have a GSTIN',
                              'Option Selected': 'I dont have a GSTIN',
                              'Element Type': 'Form',
                            },
                          });
                          analyticsTrack({
                            objectName: 'SignUp',
                            actionName: "I don't have a GSTIN checkbox",
                            screen: 'home page',
                            eventAction: 'initiated',
                            user,
                          });
                        }}
                      />
                    </View>
                  </Space>
                  {hasGSTIN && Number(data.business_type) === PROPRIETORSHIP && (
                    <Text size="xsmall" color="negative.900">
                      Please note that skipping GSTIN might lead to delay in your account review by
                      upto two weeks, usually it takes 3-4 days
                    </Text>
                  )}
                </>
              ) : null}
            </FormSection>
          ) : null}

          <GetTouchedFields
            handleSubmit={handleSubmit}
            isBlurCalled={isBlurCalled}
            setIsBlurCalled={setIsBlurCalled}
            tabName="Business Details"
            cardTitle={businessDetailsCardTitle}
          />
        </form>
      )}
    </Formik>
  );
};

export default BusinessDetails;
