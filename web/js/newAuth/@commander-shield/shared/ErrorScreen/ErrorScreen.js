import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Screen from '../Screen';
import Button from '../Button';
import GenericErrorImage from '../../assets/genericError.svg';

const Image = styled.img`
  width: 100%;
`;

const ErrorScreen = () => {
  return (
    <Size height="100%">
      <Screen>
        <Screen.Content>
          <Flex justifyContent="center" flexDirection="column" flexGrow={1}>
            <Space padding={[4.75, 0, 0, 0]}>
              <View>
                <Image src={GenericErrorImage} alt="Something went wrong" />
              </View>
            </Space>
          </Flex>
          <View>
            <Space padding={[0, 0, 5, 0]}>
              <View>
                <Heading weight="bold" size="xlarge">
                  Oops!
                </Heading>
                <Heading weight="bold" size="xlarge">
                  Something went wrong
                </Heading>
                <Space padding={[1.25, 0, 0]}>
                  <View>
                    <Text size="medium" color="shade.960">
                      Try reloading this page or starting over
                    </Text>
                  </View>
                </Space>
              </View>
            </Space>
          </View>
        </Screen.Content>

        <Screen.Footer>
          <Button
            type="submit"
            size="medium"
            variant="primary"
            block
            onClick={() => location.reload()}
          >
            Reload page
          </Button>
        </Screen.Footer>
      </Screen>
    </Size>
  );
};

export default ErrorScreen;
