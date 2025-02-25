import React, { useState, useMemo, useCallback, useEffect } from 'react';
import PropTypes from 'prop-types';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import SelectContext from './SelectContext';
import SelectOption from './SelectOption';

const SelectView = styled(View)`
  border: ${({ theme }) => `1px solid ${theme.colors.shade[930]}`};
  border-radius: 2px;
`;

const Select = ({ value, onChange, defaultValue, children }) => {
  const [selected, setSelected] = useState(value || defaultValue);

  useEffect(() => {
    setSelected(value);
  }, [value]);

  const onValueChange = useCallback(
    (newValue) => {
      setSelected(newValue);
      if (onChange) onChange(newValue);
    },
    [onChange],
  );

  const contextValue = useMemo(
    () => ({ value: selected, onChange: onValueChange }),
    [onValueChange, selected],
  );

  return (
    <SelectContext.Provider value={contextValue}>
      <SelectView>{children}</SelectView>
    </SelectContext.Provider>
  );
};

Select.propTypes = {
  value: PropTypes.string,
  defaultValue: PropTypes.string,
  onChange: PropTypes.func,
  children: PropTypes.node,
};

Select.defaultProps = {
  onChange: () => {},
};

Select.Option = SelectOption;

export default Select;
