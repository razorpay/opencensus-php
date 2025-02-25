import React from 'react';
import styled from 'styled-components';
import Size from '@razorpay/blade-old/src/atoms/Size';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import Text from '@razorpay/blade-old/src/atoms/Text';
import PropTypes from 'prop-types';
import { useFormikContext } from 'formik';

import Screen from '../Screen';
import LinkButton from '../LinkButton';

export const ScreenForm = ({ children }) => {
  const formikProps = useFormikContext();
  return (
    <Size height="100%">
      <form onSubmit={formikProps.handleSubmit}>
        <Screen>
          <Screen.Content>{children}</Screen.Content>
        </Screen>
      </form>
    </Size>
  );
};

ScreenForm.propTypes = {
  children: PropTypes.node,
};

export const ScreenHeading = ({ children }) => (
  <Space padding={[5, 0, 3, 0]}>
    <View>
      <Heading weight="bold" size="xlarge">
        {children}
      </Heading>
    </View>
  </Space>
);

ScreenHeading.propTypes = {
  children: PropTypes.node,
};

const TextBreakWord = styled(Text)`
  word-break: break-word;
`;

export const EmailNumberChangeView = ({ onChangeClick, children }) => (
  <Flex flexDirection="row" alignItem="center">
    <View>
      <TextBreakWord size="medium" weight="bold">
        {children}
      </TextBreakWord>
      <Space margin={[0.25, 0, 0, 0.5]}>
        <View>
          <LinkButton onClick={onChangeClick} size="xsmall" type="button">
            Change
          </LinkButton>
        </View>
      </Space>
    </View>
  </Flex>
);

EmailNumberChangeView.propTypes = {
  children: PropTypes.string,
  onChangeClick: PropTypes.func,
};

export const SpaceView = (props) => {
  const { children, ...rest } = props;
  return (
    <Space {...rest}>
      <View>{children}</View>
    </Space>
  );
};

SpaceView.propTypes = {
  children: PropTypes.node,
};

export const CenteredView = (props) => {
  const { children, ...rest } = props;

  return (
    <Flex justifyContent="center">
      <SpaceView {...rest}>{children}</SpaceView>
    </Flex>
  );
};
CenteredView.propTypes = {
  children: PropTypes.node,
};

export const ScreenInfoText = (props) => {
  const { children, ...rest } = props;
  return (
    <Space margin={[1, 0, 1, 0]} {...rest}>
      <Text size="xsmall" color="shade.980">
        {children}
      </Text>
    </Space>
  );
};

ScreenInfoText.propTypes = {
  children: PropTypes.string,
};
