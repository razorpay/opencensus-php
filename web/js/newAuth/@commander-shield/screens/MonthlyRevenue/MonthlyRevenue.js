import React, { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Formik } from 'formik';
import * as yup from 'yup';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import { screenMap, goToScreen, monthlyRevenueOptions } from '../../screens/screenHelpers';
import Button from '../../shared/Button';
import Select from '../../shared/Select';
import Screen from '../../shared/Screen/';
import useUserContext from '../../user/useUserContext';
import useLocationQuery from '../../shared/useLocationQuery';
import useProgressBar from '../../shared/ProgressBar/useProgressBar';
import signUpEvents from '../SignUp/signUpEvents';
import monthlyRevenueEvents from './monthlyRevenueEvents';

const MonthlyRevenue = () => {
  const navigate = useNavigate();
  const { state, actions } = useUserContext();
  const locationQuery = useLocationQuery();
  const { setPercent } = useProgressBar();

  useEffect(() => setPercent(40), [setPercent]);

  const handleBack = () => {
    signUpEvents.trackBack(state.user, 'Business Type');
    goToScreen({ screen: screenMap.businessType, navigate, locationQuery });
  };

  const handleNext = (values) => {
    actions.updateUserBusinessDetails({
      monthlyRevenue: values.monthlyRevenue,
    });
    goToScreen({ screen: screenMap.contactDetails, navigate, locationQuery });
  };

  return (
    <Formik
      initialValues={{ monthlyRevenue: state.user.businessDetails.monthlyRevenue }}
      onSubmit={handleNext}
      validationSchema={yup.object().shape({
        monthlyRevenue: yup.string().required(),
      })}
      validateOnMount
    >
      {(formikProps) => {
        return (
          <Size height="100%">
            <form onSubmit={formikProps.handleSubmit}>
              <Screen>
                <Screen.Content>
                  <Space padding={[4.75, 0, 4, 0]}>
                    <View>
                      <Space margin={[0, 0, 1, 0]}>
                        <View>
                          <Heading size="xlarge">
                            What’s your estimated monthly revenue? (₹)
                          </Heading>
                        </View>
                      </Space>
                      <Text size="medium" color="shade.960">
                        We will use this detail to help you choose the best product from our payment
                        suite
                      </Text>
                    </View>
                  </Space>
                  <Select
                    value={formikProps.values.monthlyRevenue}
                    onChange={(newValue) => {
                      formikProps.setFieldValue('monthlyRevenue', newValue);
                      formikProps.validateForm().then(() => {
                        formikProps.submitForm();
                      });
                      monthlyRevenueEvents.trackMonthlyRevenueSelect(state.user, newValue);
                    }}
                  >
                    {monthlyRevenueOptions.map((item) => {
                      return (
                        <Select.Option key={item.value} value={item.value}>
                          {item.title}
                        </Select.Option>
                      );
                    })}
                  </Select>
                </Screen.Content>
                <Screen.Footer>
                  <Flex justifyContent="space-between">
                    <View>
                      <Button onClick={handleBack} variant="secondary" block>
                        Back
                      </Button>
                      <Space margin={[0, 0, 0, 2]}>
                        <Button
                          disabled={!formikProps.isValid}
                          block
                          onClick={() => {
                            monthlyRevenueEvents.trackMonthlyRevenueNext();
                            formikProps.submitForm();
                          }}
                        >
                          Next
                        </Button>
                      </Space>
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

export default MonthlyRevenue;
