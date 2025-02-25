import React, { useState, useEffect, useRef } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import * as yup from 'yup';
import { Formik } from 'formik';
import styled from 'styled-components';
import isEmpty from '@razorpay/universe-utils/isEmpty';
import Size from '@razorpay/blade-old/src/atoms/Size';
import View from '@razorpay/blade-old/src/atoms/View';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import hasKey from '@razorpay/universe-utils/hasKey';
import Checkbox from '@razorpay/blade-old/src/atoms/Checkbox';
import captureException, { sentryFlows } from '../../shared/captureException';
import Link from '../../shared/Link';
import {
  screenMap,
  goToScreen,
  goToDashboard,
  setSignupExpData,
  authMethods,
  UNREGISTERED_BUSINESS_ID,
} from '../screenHelpers';
import Button from '../../shared/Button';
import TextInput from '../../shared/TextInput';
import Screen from '../../shared/Screen/';
import useLocationQuery from '../../shared/useLocationQuery';
import useProgressBar from '../../shared/ProgressBar/useProgressBar';
import useUserContext from '../../user/useUserContext';
import useSnackbar from '../../shared/Snackbar/useSnackbar';
import { REMOVE_PRESIGNUP_FUNCTIONALITY } from '../../shared/Experiments/Experiments';
import signUpEvents from '../SignUp/signUpEvents';
import setCookie from '../../utils/setCookie';
import getExpStatus from '../../utils/getExperimentStatus';
import { handleEventForInputError } from '../../js/signUpAnalytics';
import WhatsAppIcon from '../../assets/WhatsAppIcon';
import { setIsRemovePreSignUpExperimentEnabled } from '../../js/analytics';
import { MOBILE_NUMBER_VERIFY_REGEX } from '../../utils/regex';
import contactDetailsEvents from './contactDetailsEvents';
import ConfirmationModal from './ConfirmationModal';

const StyledButton = styled(Button)`
  &&& {
    padding: 0 8px;
    &:hover {
      background-color: transparent;
    }
    &:focus {
      background-color: transparent;
    }
    &:active {
      background-color: transparent;
    }
  }
`;

const ContactDetails = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const locationQuery = useLocationQuery();
  const { state, actions } = useUserContext();
  const snackbar = useSnackbar();
  const { setPercent } = useProgressBar();
  const isUnregisteredBusiness = state.user.businessDetails.type === UNREGISTERED_BUSINESS_ID;
  const [showCouponCodeView, setShowCouponCodeView] = useState(false);
  const [isCouponCodeApplied, setIsCouponCodeApplied] = useState(false);
  const [couponCodeSuccessText, setCouponCodeSuccessText] = useState('');
  const [couponCodeErrorText, setCouponCodeErrorText] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [isPreSignUpEnabled, setIsPreSignUpEnabled] = useState(true);
  const formikRef = useRef();
  const isMobileNumSignUpEnabled = state.isMobileNumberSignupEnabled;
  const showEmailField = false; // disabling email field for phase-1, would be reqd in phase-2 of signup with otp
  const signUpMethod = state.user.isSignupViaEmail ? authMethods.EMAIL : authMethods.PHONE_NUMBER;

  useEffect(() => setPercent(60), [setPercent]);

  useEffect(() => {
    if (getExpStatus(state.user, REMOVE_PRESIGNUP_FUNCTIONALITY)) {
      setIsPreSignUpEnabled(false);
      setIsRemovePreSignUpExperimentEnabled(true); // For analytics
    }
  }, [state.user.experiments]);

  const validateCouponCode = async (formikProps, couponCode) => {
    try {
      await actions.validateCouponCode(couponCode);
      actions.updateUser({
        previousValidatedCoupon: {
          code: couponCode,
        },
      });
      setIsCouponCodeApplied(true);
      contactDetailsEvents.trackCouponCodeSuccess(state.user, couponCode);
    } catch (error) {
      actions.updateUser({
        coupon: {
          code: '',
          credit: '',
          expiry: '',
        },
      });

      if (error?.message !== 'Coupon code not found') {
        formikProps.setFieldValue('isCouponCodeChecked', false);
      }

      captureException(error, {
        flow: sentryFlows.CONTACT_DETAILS,
      });
      setCouponCodeErrorText(error?.message);
      contactDetailsEvents.trackCouponCodeError(couponCode, error?.message);
    }
  };

  const handleBack = (formikProps) => {
    formikProps.setSubmitting(true);

    actions.updateUser({
      name: formikProps.values.name,
      contact: formikProps.values.contact,
      coupon: {
        code: formikProps.values.couponCode,
      },
    });
    const previousScreen = Object.keys(screenMap)[location.state.previousScreenIndex];

    signUpEvents.trackBack(
      state.user,
      screenMap[previousScreen] === screenMap.monthlyRevenue
        ? 'Estimated Monthly Revenue'
        : 'Business Type',
    );

    goToScreen({ screen: screenMap[previousScreen], navigate, locationQuery });
  };

  const handleCouponCodeAction = (formikProps) => {
    // remove button clicked
    if (isCouponCodeApplied) {
      setShowModal(true);
    } else if (!formikProps.values?.couponCode) {
      setCouponCodeErrorText('Please enter a valid coupon code.');
    } else {
      // apply button clicked
      formikProps.setFieldValue('isCouponCodeChecked', true);
      contactDetailsEvents.trackCouponCodeInitiate(state.user, formikProps.values.couponCode);
      validateCouponCode(formikProps, formikProps.values.couponCode);
    }
  };

  const sendPreSignUpData = async (values) => {
    contactDetailsEvents.trackSubmitInitiate(state.user, signUpMethod);
    const contactInfo = {
      name: values.name,
    };

    if (signUpMethod === authMethods.EMAIL) {
      contactInfo.contact = values.contact;
    }
    try {
      await actions.sendPreSignUpData(contactInfo, {
        email: values.email,
        isPreSignUpEnabled,
      });

      contactDetailsEvents.trackSubmitSuccess(
        {
          ...state.user,
          name: values.name,
          contact: values.contact,
          email: values.email,
        },
        signUpMethod,
      );

      const response = await actions.getUserDetails();
      setCookie('midExists', !!response.user.mid);
      if (response.user.mid && state.user.coupon.code)
        setCookie(`coupon_code--${response.user.mid}`, state.user.coupon.code, 90);

      if (response.user.isConfirmed || response.user.isMobileVerified) {
        actions.showLoader();
        setSignupExpData();
        goToDashboard(state.signUpSource);
      } else {
        goToScreen({ screen: screenMap.verifyEmail, navigate, locationQuery });
      }
    } catch (error) {
      captureException(error, {
        flow: sentryFlows.CONTACT_DETAILS,
      });
      contactDetailsEvents.trackSubmitError({
        user: state.user,
        error: error?.message,
        method: signUpMethod,
      });
      snackbar.error(error?.message);
    }
  };

  const onSubmit = (values) => {
    sendPreSignUpData(values);
  };

  const handleCouponCodeShow = () => {
    setShowCouponCodeView(true);
    contactDetailsEvents.trackGotCouponCodeClick(state.user);
  };

  const handleOnChange = (formikProps, value) => {
    formikProps.setFieldValue('couponCode', value.toUpperCase());
    setCouponCodeErrorText('');
  };

  const handleModalClose = () => {
    setShowModal(false);
  };

  const handleModalConfirm = () => {
    setShowModal(false);
    actions.updateUser({
      coupon: {
        code: '',
        credit: '',
        expiry: '',
      },
    });
    setCouponCodeErrorText('');
    setCouponCodeSuccessText('');
    formikRef.current.setFieldValue('couponCode', '');
    setIsCouponCodeApplied(false);
  };

  const handleWhatsAppOptIn = () => {
    actions.updateUser({ isWhatsAppOptIn: !state.user.isWhatsAppOptIn });
  };

  useEffect(() => {
    if (state.user.coupon.code) {
      validateCouponCode(formikRef.current, state.user.coupon.code);
      setShowCouponCodeView(true);
    }
  }, []);

  useEffect(() => {
    if (state.user.coupon.credit) {
      const expiryDate = new Date(
        new Date().setDate(new Date().getDate() + state.user.coupon.expiry),
      ).toDateString();

      const successMessage = `Transactions worth amount ₹ ${
        state.user.coupon.credit
      } /- will be free of charge${state.user.coupon.expiry ? ` till ${expiryDate}.` : `.`}`;

      setCouponCodeSuccessText(successMessage);
    }
  }, [state.user.coupon.credit, state.user.coupon.expiry]);

  const couponCodeView = (formikProps) => {
    if (showCouponCodeView) {
      return (
        <Space padding={[4, 0, 0, 0]}>
          <View>
            <TextInput
              name="couponCode"
              type="text"
              variant="filled"
              width="auto"
              label="Coupon code"
              placeholder="Enter coupon code"
              value={formikProps.values.couponCode}
              errorText={
                formikProps.errors.couponCode && hasKey(formikProps.touched, 'couponCode')
                  ? formikProps.errors.couponCode
                  : couponCodeErrorText
              }
              successText={couponCodeSuccessText}
              onChange={(value) => {
                formikProps.setFieldValue('isCouponCodeChecked', false);
                handleOnChange(formikProps, value);
              }}
              onBlur={(value) => {
                formikProps.setFieldTouched('couponCode', value);
                handleEventForInputError(formikProps, 'couponCode', contactDetailsEvents);
              }}
              rightAlignComponent={
                <StyledButton
                  onClick={() => handleCouponCodeAction(formikProps)}
                  size="medium"
                  variant="tertiary"
                  variantColor="primary"
                >
                  {isCouponCodeApplied ? 'Remove' : 'Apply'}
                </StyledButton>
              }
              disabled={isCouponCodeApplied}
            />
            {isCouponCodeApplied && isUnregisteredBusiness ? (
              <Space padding={[1, 0, 0, 0]}>
                <Text size="xsmall" color="shade.950">
                  *This offer is not applicable for credit card transactions
                </Text>
              </Space>
            ) : null}
          </View>
        </Space>
      );
    } else {
      return <Link onClick={handleCouponCodeShow}>Got a coupon code?</Link>;
    }
  };

  const contactDetailsView = (formikProps) => {
    if (signUpMethod !== authMethods.EMAIL) {
      if (showEmailField) {
        return (
          <TextInput
            type="email"
            name="email"
            value={formikProps.values.email}
            width="auto"
            label="Email (Optional)"
            placeholder="example@xyz.com"
            errorText={
              formikProps.errors.email && hasKey(formikProps.touched, 'email')
                ? formikProps.errors.email
                : undefined
            }
            variant="filled"
            onChange={(value) => formikProps.setFieldValue('email', value)}
            onBlur={(value) => {
              formikProps.setFieldTouched('email', value);
              handleEventForInputError(formikProps, 'email', contactDetailsEvents);
            }}
          />
        );
      }
    } else {
      return (
        <TextInput
          type="text"
          name="contact"
          value={formikProps.values.contact}
          width="auto"
          label="Contact number"
          placeholder="Mobile number"
          errorText={
            formikProps.errors.contact && hasKey(formikProps.touched, 'contact')
              ? formikProps.errors.contact
              : undefined
          }
          variant="filled"
          onChange={(value) => formikProps.setFieldValue('contact', value)}
          onBlur={(value) => {
            formikProps.setFieldTouched('contact', value);
            handleEventForInputError(formikProps, 'contact', contactDetailsEvents);
          }}
        />
      );
    }
    return null;
  };

  return (
    <>
      <Formik
        innerRef={formikRef}
        initialValues={{
          name: state.user.name,
          contact: state.user.contact,
          couponCode: state.user.coupon.code,
          isCouponCodeChecked: true, // internal flag for checking if coupon code is checked (tried to validate)
          isWhatsAppOptIn: state.user.isWhatsAppOptIn,
          email: '',
        }}
        onSubmit={onSubmit}
        validationSchema={yup.lazy((values) =>
          yup.object().shape({
            name: yup
              .string()
              .trim()
              .min(4, 'Name should have at least 4 characters.')
              .matches(/^[a-zA-Z\s]+$/, {
                message: 'Name may only contain alphabets and spaces.',
                excludeEmptyString: true,
              })
              .required('Please enter your name.'),
            contact: yup
              .string()
              .when('condition', {
                is: () => signUpMethod === authMethods.EMAIL,
                then: yup.string().required('Please enter your mobile number to continue.'),
                otherwise: yup.string(),
              })
              .matches(MOBILE_NUMBER_VERIFY_REGEX, {
                message: 'Please provide a valid mobile number.',
                excludeEmptyString: true,
              }),
            email: yup.string().when('condition', {
              is: () => showEmailField && isMobileNumSignUpEnabled,
              then: yup.string().email('Please enter a valid email'),
              otherwise: yup.string(),
            }),
            couponCode: yup
              .mixed()
              .test(
                'coupon-code-applied-click',
                couponCodeErrorText ? couponCodeErrorText : 'Please apply coupon code.',
                (couponCodeValue) => {
                  if (showCouponCodeView && !isEmpty(couponCodeValue)) {
                    return values.isCouponCodeChecked;
                  }
                  return true;
                },
              ),
          }),
        )}
        validateOnMount
      >
        {(formikProps) => {
          if (!formikProps.isSubmitting) {
            if (formikProps.isValid) {
              setPercent(80);
            } else if (formikProps.errors.name && formikProps.errors.contact) {
              setPercent(60);
            } else {
              setPercent(70);
            }
          }

          return (
            <Size height="100%">
              <form onSubmit={formikProps.handleSubmit}>
                <Screen>
                  <Screen.Content>
                    <Space padding={[4.75, 0, 4, 0]}>
                      <View>
                        <Heading weight="bold" size="xlarge">
                          Your contact details
                        </Heading>
                      </View>
                    </Space>
                    <Space padding={[0.5, 0, 2.5, 0]}>
                      <View>
                        <TextInput
                          name="name"
                          width="auto"
                          value={formikProps.values.name}
                          label="Your name"
                          placeholder="Your full name"
                          errorText={
                            formikProps.errors.name && hasKey(formikProps.touched, 'name')
                              ? formikProps.errors.name
                              : undefined
                          }
                          variant="filled"
                          type="text"
                          onChange={(value) => formikProps.setFieldValue('name', value.trim())}
                          onBlur={(value) => {
                            formikProps.setFieldTouched('name', value.trim());
                            handleEventForInputError(formikProps, 'name', contactDetailsEvents);
                          }}
                        />
                      </View>
                    </Space>
                    <Space padding={[0.5, 0, 1, 0]}>
                      <View>{contactDetailsView(formikProps)}</View>
                    </Space>
                    <Space padding={[0, 0, 4, 0]}>
                      <Flex alignItems="center">
                        <View>
                          <Checkbox
                            onChange={handleWhatsAppOptIn}
                            title="Get account updates on WhatsApp"
                            size="small"
                            checked={state.user.isWhatsAppOptIn}
                          />
                          <Size height="15px" width="15px">
                            <Space margin={[0, 0.5]}>
                              <WhatsAppIcon />
                            </Space>
                          </Size>
                        </View>
                      </Flex>
                    </Space>
                    {couponCodeView(formikProps)}
                  </Screen.Content>
                  <Screen.Footer>
                    {isPreSignUpEnabled ? (
                      <Flex justifyContent="space-between">
                        <View>
                          <Button onClick={() => handleBack(formikProps)} variant="secondary" block>
                            Back
                          </Button>
                          <Space margin={[0, 0, 0, 2]}>
                            <Button type="submit" block disabled={!formikProps.isValid}>
                              Next
                            </Button>
                          </Space>
                        </View>
                      </Flex>
                    ) : (
                      <Button type="submit" block disabled={!formikProps.isValid}>
                        Next
                      </Button>
                    )}
                  </Screen.Footer>
                </Screen>
              </form>
            </Size>
          );
        }}
      </Formik>
      {showModal ? (
        <ConfirmationModal
          couponCode={formikRef.current.values.couponCode}
          closeModal={handleModalClose}
          confirmModal={handleModalConfirm}
        />
      ) : null}
    </>
  );
};

export default ContactDetails;
