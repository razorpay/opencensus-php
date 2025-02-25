import React from 'react';
import PropTypes from 'prop-types';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import isEmpty from '@razorpay/universe-utils/isEmpty';
import LinkButton from '../../shared/LinkButton';
import useUserContext from '../../user/useUserContext';
import OTPInput from '../../shared/OTPInput';
import { SIGNUP } from '../screenHelpers';

const CustomText = styled(Text)`
  word-break: break-word;
`;

const SignUpOtp = ({ handleChangeOnClick, email, autoReadOtpSignup }) => {
  const { state } = useUserContext();

  return (
    <>
      <Flex flexDirection="row" alignItem="center">
        <View>
          <CustomText size="medium" weight="bold">
            {!isEmpty(email) ? email : state?.user?.contact}
          </CustomText>
          <Space margin={[0.125, 0, 0, 0.5]}>
            <View>
              <LinkButton onClick={handleChangeOnClick} size="xsmall" type="button">
                Change
              </LinkButton>
            </View>
          </Space>
        </View>
      </Flex>
      <Space padding={[0, 0, 0.5, 0]}>
        <View>
          <OTPInput context={SIGNUP} email={email} autoReadOtpSignup={autoReadOtpSignup} />
        </View>
      </Space>
    </>
  );
};

SignUpOtp.propTypes = {
  handleChangeOnClick: PropTypes.func,
  email: PropTypes.string,
  autoReadOtpSignup: PropTypes.bool,
};

export default SignUpOtp;
