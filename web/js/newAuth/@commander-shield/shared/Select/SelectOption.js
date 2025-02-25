import React, { useCallback } from 'react';
import PropTypes from 'prop-types';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import isEmpty from '@razorpay/universe-utils/isEmpty';
import Check from '@razorpay/blade-old/src/icons/Check';
import { useSelectContext } from './SelectContext';

const isChecked = ({ context, value }) => {
  return context && !isEmpty(context.value) && context.value === value;
};

const styles = {
  backgroundColor({ theme, checked }) {
    if (checked) {
      return theme.colors.tone[940];
    }
    return '';
  },
  hoverColor({ theme, checked }) {
    if (checked) {
      return theme.colors.tone[940];
    }
    return theme.colors.tone[930];
  },
};

const OptionBox = styled(View)`
  background-color: ${styles.backgroundColor};
  cursor: pointer;
  &:hover {
    background-color: ${styles.hoverColor};
  }
`;

const SelectOption = ({ value, testID, children }) => {
  const context = useSelectContext();
  const checked = isChecked({ context, value });

  const handleClick = useCallback(() => {
    if (context.onChange) {
      context.onChange(value);
    }
  }, [context, value]);

  return (
    <Space padding={[1.25]}>
      <Flex justifyContent="space-between" flexDirection="row">
        <OptionBox checked={checked} onClick={handleClick} data-testid={testID}>
          <Flex flexGrow={1}>
            <Text size="medium" color={checked ? 'shade.980' : 'shade.970'}>
              {children}
            </Text>
          </Flex>
          <Flex alignItems="center">
            <Space margin={[0, 0, 0, 1.25]}>
              <View>
                <Check fill={checked ? 'primary.900' : 'shade.930'} size="small" />
              </View>
            </Space>
          </Flex>
        </OptionBox>
      </Flex>
    </Space>
  );
};

SelectOption.propTypes = {
  value: PropTypes.string.isRequired,
  testID: PropTypes.string,
  children: PropTypes.string,
};

SelectOption.defaultProps = {
  testID: 'ds-select-option',
};

export default SelectOption;
