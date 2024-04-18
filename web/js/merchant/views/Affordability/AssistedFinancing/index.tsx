import React, { useEffect, useState } from 'react';
import {
  Box,
  Card,
  CardHeader,
  CardHeaderLeading,
  CardBody,
  TextInput,
  Button,
  Heading,
  Text,
  ChevronDownIcon,
  Divider,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { isMobileDevice } from 'merchant/components/Home/data';
import { setActivePageName } from 'merchant/reducers/app';
import fetchPaymentMethods from 'merchant/utils/fetchPaymentMethods';

import AvailableEmiOption from './availableEmiOptions';
import { eligibilityPhoneNumberValidityRegex } from './constants';
import { fetchEligibilityApi } from './queries';
import SendPaymentLinkModal from './sendPaymentlinkModal';
import {
  generateEligibilityErrorPayload,
  generateEligibilityTrackPayload,
  getCardEmiArray,
  getCardlessEmiArray,
} from './utils';

import type { PaymentConfig, PaymentOptions, PaymentOption, EnabledOptionsArray } from './type';
import { convertToMinorUnit } from '@razorpay/i18nify-js/currency';
import { getDialCodeByCountryCode } from '@razorpay/i18nify-js/phoneNumber';
import { getUser } from 'merchant/store';

const user = getUser();

const CheckEmiOptionMobileComponent = ({ title }) => {
  return (
    <Box display="flex" alignItems="center" flex={1} flexDirection="row">
      <Box
        height="6px"
        width="6px"
        borderRadius="round"
        backgroundColor="surface.background.primary.intense"
        marginRight="spacing.4"
      />
      <Text size="small">{title}</Text>
    </Box>
  );
};

const AssistedFinancing = ({ setActivePageName }) => {
  const [mobileNumber, setMobileNumber] = useState('');
  const [orderAmount, setOrderAmount] = useState('');
  const [isPaymentMethodsLoading, setPaymentMethodsLoading] = useState(false);
  const [isEmiOptionsVisible, setIsEmiOptionsVisible] = useState(false);
  const [merchantPaymentMethods, setMerchantPaymentMethods] = useState<PaymentOptions>([]);
  const [isPaymentLinkModalVisible, setPaymentLinkModalVisible] = useState(false);
  const [paymentLinkData, setPaymentLinkData] = useState<null | PaymentOption>(null);
  const [isCheckEmiDropdownVisible, setIsCheckEmiDropdownVisible] = useState(false);
  const [paymentMethods, setPaymentMethods] = useState<PaymentConfig>({
    emi_options: {},
    cardless_emi: {},
  });

  const addMobileNumber = (e) => {
    setMobileNumber(e.value);
  };

  const addOrderAmount = (e) => {
    setOrderAmount(e.value);
  };

  useEffect(() => {
    const fetchMethods = async () => {
      const paymentMethods = await fetchPaymentMethods();
      setPaymentMethods(paymentMethods);
    };
    fetchMethods();
    setActivePageName('Assisted Financing');
  }, []);

  const onCheckEmiOptionsClicked = async () => {
    const formatedOrderAmount = convertToMinorUnit(parseFloat(orderAmount), {
      currency: user.merchant.currency,
    });

    analyticsTrack({
      objectName: 'EMI availability',
      actionName: 'checked',
      screen: 'Assisted Financing',
      properties: {
        customer_mobile_number: mobileNumber,
        order_amount: formatedOrderAmount,
        source: 'sales_assisted',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    setIsEmiOptionsVisible(false);
    setPaymentMethodsLoading(true);
    setPaymentLinkData(null);
    const eligibilityData = await fetchEligibilityApi(formatedOrderAmount, mobileNumber);

    const { cardless_emi = {}, emi_options = {} } = paymentMethods;

    const cardlessEmiArray = getCardlessEmiArray(cardless_emi, eligibilityData);

    const cardEmiArray = getCardEmiArray(emi_options, eligibilityData, orderAmount);

    const updatedMerchantPaymentMethods = [...cardlessEmiArray, ...cardEmiArray].sort((a, b) => {
      return a.notEligible === b.notEligible ? 0 : a.notEligible ? 1 : -1;
    });

    const enabledOptionsArray = [...cardlessEmiArray, ...cardEmiArray]
      .filter((item) => !item.notEligible)
      .map(({ method, provider, type }: EnabledOptionsArray) => ({
        method,
        provider,
        type,
      }));

    if (eligibilityData?.errors) {
      analyticsTrack({
        objectName: 'EMI availability',
        actionName: 'response',
        screen: 'Assisted Financing',
        properties: {
          eligibility_response: generateEligibilityErrorPayload(eligibilityData),
          enabled_options: enabledOptionsArray,
          source: 'sales_assisted',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    } else {
      analyticsTrack({
        objectName: 'EMI availability',
        actionName: 'response',
        screen: 'Assisted Financing',
        properties: {
          eligibility_response: generateEligibilityTrackPayload(eligibilityData?.instruments),
          enabled_options: enabledOptionsArray,
          source: 'sales_assisted',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }

    setMerchantPaymentMethods(updatedMerchantPaymentMethods);

    setIsEmiOptionsVisible(true);
    setPaymentMethodsLoading(false);
  };

  const isMobileNumberValid =
    eligibilityPhoneNumberValidityRegex.test(mobileNumber) && !!orderAmount;

  return (
    <Box
      display="flex"
      gap="spacing.5"
      margin={isMobileDevice() ? 'spacing.5' : 'spacing.8'}
      paddingTop="spacing.8"
      flexDirection="column"
    >
      <Card
        backgroundColor="surface.background.gray.moderate"
        width="100%"
        padding={isMobileDevice() ? 'spacing.5' : 'spacing.7'}
      >
        <CardHeader>
          <CardHeaderLeading title="Assisted Financing" />
        </CardHeader>
        <CardBody>
          <Card
            backgroundColor="surface.background.gray.moderate"
            width={{
              m: '400px',
              s: '100%',
            }}
            padding={isMobileDevice() ? 'spacing.5' : 'spacing.7'}
          >
            <CardBody>
              <Box display="flex" flexDirection="row" justifyContent="space-between">
                <Box display="flex" flexDirection="column">
                  <Heading>Check EMI Options</Heading>
                  <Text marginBottom="spacing.6">Enter Customer mobile number & Order Amount</Text>
                </Box>
                {isMobileDevice() && isEmiOptionsVisible && (
                  <div
                    onClick={() => setIsCheckEmiDropdownVisible((value) => !value)}
                    style={{ paddingTop: '10px' }}
                  >
                    <ChevronDownIcon
                      size="large"
                      color={
                        isCheckEmiDropdownVisible
                          ? 'interactive.icon.gray.normal'
                          : 'interactive.icon.primary.normal'
                      }
                    />
                  </div>
                )}
              </Box>
              {isMobileDevice() && isCheckEmiDropdownVisible && isEmiOptionsVisible ? (
                <>
                  <Divider orientation="horizontal" marginBottom="spacing.5" />
                  <Box
                    display="flex"
                    gap="spacing.3"
                    alignItems="center"
                    justifyContent="space-between"
                    padding={['spacing.3', 'spacing.4']}
                    flex="1"
                    backgroundColor="surface.background.gray.moderate"
                  >
                    <CheckEmiOptionMobileComponent
                      title={`${getDialCodeByCountryCode(
                        user.merchant.country_code,
                      )} - ${mobileNumber}`}
                    />
                    <Divider orientation="vertical" />
                    <CheckEmiOptionMobileComponent title={`₹ ${orderAmount}`} />
                  </Box>
                </>
              ) : (
                <>
                  <TextInput
                    value={mobileNumber}
                    onChange={addMobileNumber}
                    label="Mobile Number"
                    type="number"
                    placeholder="9999999999"
                    prefix="+ 91"
                  />
                  <TextInput
                    value={orderAmount}
                    onChange={addOrderAmount}
                    label="Order Amount"
                    type="number"
                    placeholder="0"
                    marginTop="spacing.6"
                    prefix="₹"
                    testID="order-amount"
                  />
                  <Button
                    color="primary"
                    isFullWidth
                    onClick={onCheckEmiOptionsClicked}
                    size="medium"
                    type="button"
                    variant="primary"
                    marginTop="spacing.8"
                    isDisabled={!isMobileNumberValid}
                    isLoading={isPaymentMethodsLoading}
                  >
                    Check Available EMI Options
                  </Button>
                </>
              )}
            </CardBody>
          </Card>
        </CardBody>
      </Card>

      <SendPaymentLinkModal
        showPaymentLinkModal={isPaymentLinkModalVisible}
        setShowPaymentLinkModal={setPaymentLinkModalVisible}
        orderAmount={orderAmount}
        mobileNumber={mobileNumber}
        paymentLinkData={paymentLinkData}
      />

      <AvailableEmiOption
        shouldShowEmiMethods={isEmiOptionsVisible}
        merchantPaymentMethods={merchantPaymentMethods}
        showPaymentLinkModal={() => {
          setPaymentLinkModalVisible(true);
        }}
        paymentLinkData={paymentLinkData}
        setPaymentLinkData={setPaymentLinkData}
      />
    </Box>
  );
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      setActivePageName,
    },
    dispatch,
  );
};
export default connect(null, mapDispatchToProps)(AssistedFinancing);
