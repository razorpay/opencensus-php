import React from 'react';
import PropTypes from 'prop-types';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';

const styles = {
  color({ disabled }) {
    if (disabled) {
      return 'shade.930';
    } else {
      return 'shade.950';
    }
  },
};

const CharacterCount = ({ maxLength, currentLength, disabled }) => {
  return (
    <Space padding={[0.5, 0, 0, 1]}>
      <Flex flex={0}>
        <View>
          <Text disabled={disabled} color={styles.color({ disabled })} size="xsmall">
            {`${currentLength}/${maxLength}`}
          </Text>
        </View>
      </Flex>
    </Space>
  );
};

CharacterCount.propTypes = {
  maxLength: PropTypes.number,
  currentLength: PropTypes.number,
  disabled: PropTypes.bool,
};

CharacterCount.defaultProps = {
  maxLength: 10,
  currentLength: 0,
  disabled: false,
};

export default CharacterCount;
