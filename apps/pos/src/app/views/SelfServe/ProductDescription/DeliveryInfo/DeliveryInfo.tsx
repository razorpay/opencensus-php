import React, { useRef, useState } from 'react';
import { Box, Link, Text, TextInput } from '@razorpay/blade/components';
import analytics, { SignUpEvents, StatusT } from '@razorpay/universe-utils/analytics';

import { useSplitzService } from 'common/splitz';
import {
  DELIVERY_AVAILABLE_TEXT,
  DELIVERY_UNAVAILABLE_TEXT,
} from 'apps/pos/src/app/views/SelfServe/constants';
import { getPincodeInfo } from 'apps/pos/src/app/views/SelfServe/services';

import { TextInputWrapper } from './styles';
import { checkIfPanIndiaLive } from 'apps/pos/src/app/views/SelfServe/helpers';

type ErrorMessage = {
  text: string;
  type: 'error' | 'success' | 'none';
};

const DeliveryInfo = ({ productTitle }: { productTitle: string }): JSX.Element => {
  const [pincode, setPincode] = useState<string>('');
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const formFieldFillInititated = useRef(false);
  const [message, setMessage] = useState<ErrorMessage>({
    text: '',
    type: 'none',
  });

  const { abExperiments } = useSplitzService();
  const { omniChannelGtm } = abExperiments ?? {};
  const gtmCities = omniChannelGtm?.variables?.cities;
  const availableCities = typeof gtmCities === 'string' ? gtmCities.split(',') : [];

  const handleOnChange = (inputObj) => {
    const { value } = inputObj;
    setPincode(value);
    setMessage({
      type: 'none',
      text: '',
    });
  };

  const trackResponseReceived = ({
    status,
    errorMessage = '',
  }: {
    status: StatusT;
    errorMessage?: string;
  }) => {
    analytics.track_EXPERIMENTAL(SignUpEvents.formPageResponseReceived, {
      formName: 'Delivery Availability Check',
      fieldName: 'Pincode',
      fieldType: 'Text Box',
      status,
      errorMessage,
      section: 'Device',
      subSection: productTitle,
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'Delivery Availability Check',
    });
  };

  const handleOnCheck = async () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.linkClicked, {
      label: 'Check',
      section: 'Device',
      whatsAppUpdates: 'No',
      subSection: productTitle,
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'Delivery Availability Check',
    });

    setIsLoading(true);
    try {
      const { data } = await getPincodeInfo(pincode);
      if (
        (data && availableCities.includes(data?.city)) ||
        checkIfPanIndiaLive({ abExperiments })
      ) {
        trackResponseReceived({
          status: 'Success',
          errorMessage: '',
        });
        return setMessage({
          type: 'success',
          text: DELIVERY_AVAILABLE_TEXT,
        });
      }
      trackResponseReceived({
        status: 'Failure',
        errorMessage: DELIVERY_UNAVAILABLE_TEXT,
      });
      return setMessage({
        type: 'error',
        text: DELIVERY_UNAVAILABLE_TEXT,
      });
    } catch {
      trackResponseReceived({
        status: 'Failure',
        errorMessage: 'Pincode entered is not valid',
      });
      return setMessage({
        type: 'error',
        text: 'Pincode entered is not valid',
      });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Box marginBottom="spacing.4">
      <Text size="large">
        Delivery Info &nbsp;
        <i className="i i-delivery-truck" />
      </Text>
      <Box
        display="flex"
        gap="spacing.4"
        marginTop="spacing.4"
        alignItems="flex-start"
        minWidth="230px"
        width={{ base: '100%', l: '50%' }}
      >
        <TextInputWrapper>
          <TextInput
            name="pin-code"
            type="number"
            label="Check availability in your city"
            placeholder="Enter PIN Code"
            onChange={handleOnChange}
            value={pincode}
            validationState={message.type}
            successText={message.type === 'success' ? message?.text : ''}
            errorText={message.type === 'error' ? message?.text : ''}
            isLoading={isLoading}
            maxCharacters={6}
            onBlur={() => {
              if (!formFieldFillInititated.current) {
                formFieldFillInititated.current = true;
                analytics.track_EXPERIMENTAL(SignUpEvents.formFieldFillInitiated, {
                  formName: 'Delivery Availability Check',
                  fieldName: 'Pincode',
                  fieldType: 'Text Box',
                  section: 'Device',
                  subSection: productTitle,
                  l1FunnelStage: 'Device Exploration',
                  l2FunnelStage: 'Delivery Availability Check',
                });
              }
            }}
          />
        </TextInputWrapper>
        <Link variant="button" marginTop="spacing.8" onClick={handleOnCheck} isDisabled={!pincode}>
          Check
        </Link>
      </Box>
    </Box>
  );
};

export default DeliveryInfo;
