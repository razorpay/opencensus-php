import React from 'react';
import PropTypes from 'prop-types';
import Space from '@razorpay/blade-old/src/atoms/Space';
import View from '@razorpay/blade-old/src/atoms/View';
import Info from '@razorpay/blade-old/src/icons/Info';

const styles = {
  color({ disabled, hasError }) {
    if (hasError) {
      return 'negative.900';
    } else if (disabled) {
      return 'shade.930';
    } else {
      return 'shade.950';
    }
  },
  padding({ variant }) {
    if (variant === 'filled') {
      return [0, 1, 0, 1];
    } else {
      return [1, 1, 0, 0];
    }
  },
};

const AccessoryIcon = ({ icon: Icon, disabled, hasError, variant }) => {
  return (
    <Space padding={styles.padding({ variant })}>
      <View>
        <Icon size="small" fill={styles.color({ disabled, hasError })} />
      </View>
    </Space>
  );
};

AccessoryIcon.propTypes = {
  icon: PropTypes.elementType,
  disabled: PropTypes.bool,
  hasError: PropTypes.bool,
  variant: PropTypes.oneOf(['filled', 'outlined']).isRequired,
};

AccessoryIcon.defaultProps = {
  icon: Info,
  disabled: false,
  hasError: false,
};

export default AccessoryIcon;
