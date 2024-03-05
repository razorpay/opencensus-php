import React, { useRef } from 'react';
import { SearchIcon, TextInput } from '@razorpay/blade/components';

import { SearchBarProps } from './types';

const SearchBar = ({ defaultValue, value, onChange }: SearchBarProps): JSX.Element => {
  const formRef = useRef<HTMLFormElement>(null);

  const handleChange = (): void => {
    if (!formRef.current) return;
    const formData = new FormData(formRef.current);
    const searchInputValue = formData.get('search');
    if (typeof searchInputValue !== 'string') return;

    onChange(searchInputValue);
  };

  return (
    <form
      ref={formRef}
      onSubmit={(e): void => {
        e.preventDefault();
        handleChange();
      }}
    >
      <TextInput
        accessibilityLabel="Search marketplace"
        placeholder="Search marketplace"
        showClearButton
        icon={SearchIcon}
        defaultValue={defaultValue}
        value={value}
        name="search"
        type="search"
        onClearButtonClick={handleChange}
        onChange={({ value }): void => {
          if (value) return;
          handleChange();
        }}
      />
    </form>
  );
};

export default SearchBar;
