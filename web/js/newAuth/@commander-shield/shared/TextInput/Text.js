import React from 'react';
import PropTypes from 'prop-types';
import isEmpty from '@razorpay/blade-old/src/_helpers/isEmpty';
import AtomText from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';

const styles = {
  color({ disabled, errorText, successText, rightAlignComponent }) {
    if (disabled && !rightAlignComponent) {
      return 'shade.930';
    } else if (!isEmpty(errorText)) {
      return 'negative.900';
    } else if (!isEmpty(successText)) {
      return 'positive.900';
    } else {
      return 'shade.950';
    }
  },
};

const Text = ({ helpText, errorText, disabled, successText, rightAlignComponent }) => {
  let displayText;
  if (!isEmpty(errorText)) {
    displayText = errorText;
  } else if (!isEmpty(successText)) {
    displayText = successText;
  } else {
    displayText = helpText;
  }

  return (
    <Space padding={[0.5, 0, 0, 0]}>
      <Flex flex={1}>
        <View>
          <AtomText
            disabled={disabled}
            color={styles.color({ disabled, errorText, successText, rightAlignComponent })}
            size="xsmall"
          >
            {displayText}
          </AtomText>
        </View>
      </Flex>
    </Space>
  );
};

Text.propTypes = {
  helpText: PropTypes.node,
  successText: PropTypes.node,
  errorText: PropTypes.string,
  disabled: PropTypes.bool,
  rightAlignComponent: PropTypes.node,
};

Text.defaultProps = {
  errorText: '',
  helpText: '',
  successText: '',
  disabled: false,
  rightAlignComponent: '',
};

export default Text;
