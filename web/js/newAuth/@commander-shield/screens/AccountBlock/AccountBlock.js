import React, { useEffect } from 'react';
import PropTypes from 'prop-types';
import View from '@razorpay/blade-old/src/atoms/View';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Screen from '../../shared/Screen/';
import useUserContext from '../../user/useUserContext';
import { BANK_NAMES } from '../../shared/constants';
import AccountBlockImage from './AccountBlockImage';
import accountBlockEvents from './accountBlockEvents';

const AccountBlock = ({ orgName }) => {
  const { state } = useUserContext();
  const isAxisOrg = orgName === BANK_NAMES.AXIS;

  useEffect(() => {
    accountBlockEvents.trackSuccess({
      email: state.user.email,
      method: state.authMethod,
    });
  }, [state.authMethod, state.user.email]);

  return (
    <Size height="100%">
      <Screen>
        <Screen.Content>
          <Space padding={[2.5, 0, 5, 0]}>
            <View>
              <Heading weight="bold" size="xlarge" color="shade.980">
                Account Blocked
              </Heading>
            </View>
          </Space>
          <Flex justifyContent="center" flexDirection="column" alignItems="center">
            <View>
              <AccountBlockImage />
            </View>
          </Flex>
          <View>
            <Space padding={[5, 0, 5, 0]}>
              <View>
                {!state.user.isOwner ? (
                  <Heading weight="bold" size="medium" color="shade.980">
                    Contact Your Owner
                  </Heading>
                ) : null}
                <Space padding={[1.25, 0, 0]}>
                  <View>
                    <Text size="xsmall" color="shade.980">
                      Your account has been blocked due to too many wrong OTP attempts.{' '}
                      {!state.user.isOwner
                        ? 'Please contact your owner to unblock your account'
                        : null}
                    </Text>
                  </View>
                </Space>
                {isAxisOrg ? (
                  <Space padding={[1.25, 0, 0]}>
                    <Text size="xsmall" color="shade.980">
                      A cooling off period of{' '}
                      <Text size="xsmall" weight="bold" style={{ display: 'inline' }}>
                        15 minutes
                      </Text>{' '}
                      exists post your account is unlocked, so please try logging in post that.
                    </Text>
                  </Space>
                ) : null}
              </View>
            </Space>
          </View>
        </Screen.Content>
      </Screen>
    </Size>
  );
};

AccountBlock.propTypes = {
  orgName: PropTypes.string,
};

export default AccountBlock;
