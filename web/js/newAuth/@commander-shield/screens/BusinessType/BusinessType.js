import React, { useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { Formik } from 'formik';
import isEmpty from '@razorpay/universe-utils/isEmpty';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import findIndex from '@razorpay/universe-utils/findIndex';
import Button from '../../shared/Button';
import Select from '../../shared/Select';
import Screen from '../../shared/Screen/';
import { screenMap, goToScreen } from '../screenHelpers';
import useUserContext from '../../user/useUserContext';
import useLocationQuery from '../../shared/useLocationQuery';
import useProgressBar from '../../shared/ProgressBar/useProgressBar';
import readCookie from '../../utils/readCookie';
import businessTypeEvents from './businessTypeEvents';

const BusinessType = () => {
  const navigate = useNavigate();
  const locationQuery = useLocationQuery();
  const { state, actions } = useUserContext();
  const { setPercent } = useProgressBar();
  const businessType = state.user.businessDetails.type;
  const { registered, unregistered } = state.user.businessTypes;
  const formRef = useRef();

  useEffect(() => setPercent(20), [setPercent]);

  useEffect(() => {
    if (isEmpty(registered) && isEmpty(unregistered)) {
      actions.getActiveBusinessTypes();
    }
  }, []);

  const formikInitialValues = {
    registered:
      findIndex(registered, (item) => item.id === businessType) !== -1 ? businessType : '',
    unregistered:
      findIndex(unregistered, (item) => item.id === businessType) !== -1 ? businessType : '',
  };

  const goNext = (values) => {
    let screen;
    if (
      values.unregistered ||
      state.user.partnerIntent ||
      // skipping revenue screen for SG users
      readCookie('rzp_user_merchant_region') === 'SG'
    ) {
      screen = screenMap.contactDetails;
    } else {
      screen = screenMap.monthlyRevenue;
    }

    goToScreen({ screen, navigate, locationQuery });
  };

  const handleNext = (values) => {
    const value = values.unregistered ? values.unregistered : values.registered;
    actions.updateUserBusinessDetails({
      type: value,
    });
    goNext(values);
  };

  const handleValidation = (values) => {
    if (isEmpty(values.registered) && isEmpty(values.unregistered)) {
      return 'invalid';
    }
    return '';
  };

  const onChangeUnregisteredBusiness = (formikProps, newValue) => {
    formikProps.setFieldValue('registered', null);
    formikProps.setFieldValue('unregistered', newValue);
    formikProps.validateForm().then(() => {
      formikProps.submitForm();
    });

    businessTypeEvents.trackBusinessTypeSelect(state.user, newValue);
  };

  const onChangeRegisteredBusiness = (formikProps, newValue) => {
    formikProps.setFieldValue('unregistered', null);
    formikProps.setFieldValue('registered', newValue);
    formikProps.validateForm().then(() => {
      formikProps.submitForm();
    });

    businessTypeEvents.trackBusinessTypeSelect(state.user, newValue);
  };

  return (
    <Formik
      initialValues={formikInitialValues}
      onSubmit={handleNext}
      validate={handleValidation}
      validateOnMount
      innerRef={formRef}
    >
      {(formikProps) => {
        return (
          <Size height="100%">
            <form onSubmit={formikProps.handleSubmit}>
              <Screen>
                <Screen.Content>
                  <Space padding={[4.75, 0, 4, 0]}>
                    <View>
                      <Heading size="xlarge">What’s your business type?</Heading>
                    </View>
                  </Space>
                  <Space margin={[0, 0, 4, 0]}>
                    <View>
                      <Space margin={[0, 0, 1, 0]}>
                        <View>
                          <Text size="medium" weight="bold">
                            Individual business
                          </Text>
                        </View>
                      </Space>
                      <Select
                        value={formikProps.values.unregistered}
                        onChange={(value) => onChangeUnregisteredBusiness(formikProps, value)}
                      >
                        {unregistered?.map((item) => (
                          <React.Fragment key={item.id}>
                            {item.status === 'active' && item.label !== 'Individual' ? (
                              <Select.Option key={item.id} value={item.id}>
                                {item.label === 'Not Yet Registered'
                                  ? item.label?.replace('Not Yet Registered', 'Individual')
                                  : item.label}
                              </Select.Option>
                            ) : null}
                          </React.Fragment>
                        ))}
                      </Select>
                    </View>
                  </Space>
                  <View>
                    <Space margin={[0, 0, 1, 0]}>
                      <View>
                        <Text size="medium" weight="bold">
                          Registered business
                        </Text>
                      </View>
                    </Space>
                    <Select
                      value={formikProps.values.registered}
                      onChange={(value) => onChangeRegisteredBusiness(formikProps, value)}
                    >
                      {registered?.map((item) => (
                        <React.Fragment key={item.id}>
                          {item.status === 'active' ? (
                            <Select.Option key={item.id} value={item.id}>
                              {item.label}
                            </Select.Option>
                          ) : null}
                        </React.Fragment>
                      ))}
                    </Select>
                  </View>
                </Screen.Content>
                <Screen.Footer>
                  <Flex justifyContent="flex-end">
                    <View>
                      <Flex flexBasis="50%">
                        <Button
                          disabled={!formikProps.isValid || formikProps.isSubmitting}
                          onClick={() => {
                            businessTypeEvents.trackBusinessTypeNext();
                            formikProps.submitForm();
                          }}
                        >
                          Next
                        </Button>
                      </Flex>
                    </View>
                  </Flex>
                </Screen.Footer>
              </Screen>
            </form>
          </Size>
        );
      }}
    </Formik>
  );
};

export default BusinessType;
