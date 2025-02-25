import React from 'react';
import styled from 'styled-components';
import PropTypes from 'prop-types';
import { getColor } from '@razorpay/blade-old/src/_helpers/theme';
import isDefined from '@razorpay/blade-old/src/_helpers/isDefined';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Link from '../Link';

const styles = {
  container: {
    height({ variant }) {
      if (variant === 'outlined') {
        return '10px';
      } else {
        return '20px';
      }
    },
  },
  text: {
    color({ theme, isFocused, hasError, disabled, variant, value }) {
      if (variant === 'outlined') {
        if (isFocused && !hasError) {
          return getColor(theme, 'primary.800');
        } else if (isDefined(value)) {
          return getColor(theme, 'shade.950');
        }
      }
      if (disabled) {
        return getColor(theme, 'shade.940');
      }
      return getColor(theme, 'shade.960');
    },
    fontSize({ theme }) {
      return theme.fonts.size.xsmall;
    },
    lineHeight({ theme }) {
      return theme.fonts.lineHeight.small;
    },
    fontFamily({ theme }) {
      return theme.fonts.family.lato.regular;
    },
  },
  label: {
    padding({ iconLeft, prefix, position, variant, isFocused, value }) {
      if (variant !== 'filled' && (iconLeft || prefix) && !isFocused && !isDefined(value)) {
        return [0, 0, 0, 3];
      } else if (position === 'left') {
        return [1, 0.75, 1, 0.75];
      }
      return [0, 0, 0, 0];
    },
    top({ isFocused, variant, value }) {
      if (isDefined(value) || isFocused || variant === 'filled') {
        return '-4px';
      }
      return '14px';
    },
  },
};

const FloatView = styled(View)`
  position: absolute;
`;

const StyledText = styled(Text)`
  font-family: ${styles.text.fontFamily};
  font-style: normal;
  font-weight: normal;
  font-size: ${styles.text.fontSize};
  line-height: ${styles.text.lineHeight};
  color: ${styles.text.color};
  top: ${styles.label.top};
  transition: top 100ms linear;
`;

const StyledLabelLinkView = styled(View)`
  position: relative;
  top: -4px;
`;

const Label = ({
  children,
  position,
  disabled,
  iconLeft: IconLeft,
  prefix,
  animated,
  isFocused,
  hasError,
  variant,
  value,
  labelLink,
  labelLinkHandler,
}) => {
  return (
    <Space
      padding={styles.label.padding({
        position,
        isFocused,
        iconLeft: IconLeft,
        prefix,
        variant,
        value,
      })}
    >
      {animated ? (
        <FloatView>
          <StyledText
            as="label"
            htmlFor={children}
            size="medium"
            isFocused={isFocused}
            hasError={hasError}
            disabled={disabled}
            variant={variant}
            value={value}
          >
            {children}
          </StyledText>
        </FloatView>
      ) : (
        <Flex flexDirection="row">
          <View>
            <StyledText
              as="label"
              size="medium"
              htmlFor={children}
              hasError={hasError}
              disabled={disabled}
              variant={variant}
              value={value}
            >
              {children}
            </StyledText>
            {labelLink && variant === 'filled' ? (
              <Space margin={[0, 0, 0, 0.5]}>
                <StyledLabelLinkView>
                  <Link onClick={labelLinkHandler} size="xsmall">
                    {labelLink}
                  </Link>
                </StyledLabelLinkView>
              </Space>
            ) : null}
          </View>
        </Flex>
      )}
    </Space>
  );
};

Label.propTypes = {
  children: PropTypes.string,
  position: PropTypes.oneOf(['top', 'left']).isRequired,
  disabled: PropTypes.bool,
  animated: PropTypes.bool,
  isFocused: PropTypes.bool,
  variant: PropTypes.oneOf(['outlined', 'filled']).isRequired,
  iconLeft: PropTypes.elementType,
  prefix: PropTypes.string,
  hasError: PropTypes.bool,
  value: PropTypes.string,
  labelLink: PropTypes.string,
  labelLinkHandler: PropTypes.func,
};

Label.defaultProps = {
  children: 'Label',
  disabled: false,
  animated: false,
  value: undefined,
};

export default Label;
