import React, { useState } from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import Select, { SelectPropsT } from './Select';
import Option from './Option';
import GrpOption from './GrpOption';

function debounce(cb, time) {
  let timeout;
  return (...args) => {
    clearTimeout(timeout);
    timeout = setTimeout(cb, time, ...args);
  };
}

export default {
  title: 'Select',
  component: Select,
} as Meta;

const Template: Story<SelectPropsT> = (args) => <Select {...args} />;

export const SelectWithControls = Template.bind({});

SelectWithControls.args = {
  label: 'Fruits',
  children: (
    <>
      <Option value="apple" label="apple">
        apple
      </Option>
      <Option value="orange" label="orange">
        orange
      </Option>
      <Option value="mango" label="mango">
        mango
      </Option>
      <Option value="papaya" label="papaya">
        papaya
      </Option>
      <Option value="pear" label="pear">
        pear
      </Option>
    </>
  ),
  placeholder: 'Select Fruits',
};

export const GroupedSelect = Template.bind({});

GroupedSelect.args = {
  label: 'Fruits',
  children: (
    <>
      <GrpOption label="grouped1">
        <Option value="apple" label="apple">
          apple
        </Option>
        <Option value="orange" label="orange">
          orange
        </Option>
        <Option value="mango" label="mango">
          mango
        </Option>
        <Option value="papaya" label="papaya">
          papaya
        </Option>
        <Option value="pear" label="pear">
          pear
        </Option>
      </GrpOption>
      <GrpOption label="grouped2">
        <Option value="apple2" label="apple">
          apple
        </Option>
        <Option value="orange2" label="orange">
          orange
        </Option>
        <Option value="mango2" label="mango">
          mango
        </Option>
        <Option value="papaya2" label="papaya">
          papaya
        </Option>
        <Option value="pear2" label="pear">
          pear
        </Option>
      </GrpOption>
      <GrpOption label="grouped3">
        <Option value="apple3" label="apple">
          apple
        </Option>
        <Option value="orange3" label="orange">
          orange
        </Option>
        <Option value="mango3" label="mango">
          mango
        </Option>
        <Option value="papaya3" label="papaya">
          papaya
        </Option>
        <Option value="pear3" label="pear">
          pear
        </Option>
      </GrpOption>
    </>
  ),
};

export const AsyncSelect: React.FC = () => {
  const Options = ['apple', 'oranges', 'papaya', 'mango', 'pear'];
  const [results, setResults] = useState(Options);
  const [isLoading, setLoading] = useState(false);
  const [value, setValue] = useState('oranges');
  const onSelect = (val: string) => {
    setValue(val);
  };
  const onInputChange = (val: string) => {
    setLoading(true);
    setTimeout(() => {
      setResults(Options.filter((option) => option.includes(val)));
      setLoading(false);
    }, 1000);
  };
  const debouncedInputChange = debounce(onInputChange, 200);
  return (
    <Select
      label="Fruits"
      searchable={true}
      value={value}
      onChange={onSelect}
      loading={isLoading}
      onInputChange={debouncedInputChange}
    >
      {results.map((item, index) => {
        return (
          <Option key={index} value={item} label={item}>
            {item}
          </Option>
        );
      })}
    </Select>
  );
};
