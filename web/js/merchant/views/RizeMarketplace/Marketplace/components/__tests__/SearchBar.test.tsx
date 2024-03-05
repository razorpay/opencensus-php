import React from 'react';

import SearchBar from 'merchant/views/RizeMarketplace/Marketplace/components/SearchBar';
import { SearchBarProps } from 'merchant/views/RizeMarketplace/Marketplace/components/SearchBar/types';
import { render, screen, userEvent } from 'test-utils';

const renderSearchBar = (props: SearchBarProps) => render(<SearchBar {...props} />);

const getSearchInput = () => screen.getByRole('textbox', { name: /search marketplace/i });

const getClearButton = () =>
  screen.getByRole('button', {
    name: /clear input content/i,
  });

describe('SearchBar', () => {
  test('should not trigger search when user is typing', async () => {
    const triggerSearch = jest.fn();
    renderSearchBar({ onChange: triggerSearch });

    const searchInput = getSearchInput();

    await userEvent.type(searchInput, 'test');
    expect(triggerSearch).not.toHaveBeenCalled();
  });

  test('should trigger search when user presses enter key', async () => {
    const triggerSearch = jest.fn();
    render(<SearchBar onChange={triggerSearch} />);

    const searchInput = getSearchInput();

    await userEvent.type(searchInput, 'test{enter}');
    expect(triggerSearch).toHaveBeenCalledWith('test');

    await userEvent.clear(searchInput);
    await userEvent.type(searchInput, '{enter}');
    expect(triggerSearch).toHaveBeenCalledWith('');
  });

  test('should trigger search when input becomes empty', async () => {
    const triggerSearch = jest.fn();
    render(<SearchBar onChange={triggerSearch} />);

    const searchInput = getSearchInput();

    await userEvent.type(searchInput, 'test');
    await userEvent.clear(searchInput);
    expect(triggerSearch).toHaveBeenCalledWith('');
  });

  test('should trigger search when user clicks on clear button', async () => {
    const triggerSearch = jest.fn();
    render(<SearchBar onChange={triggerSearch} />);

    const searchInput = getSearchInput();
    await userEvent.type(searchInput, 'test');
    const clearButton = getClearButton();

    await userEvent.click(clearButton);
    expect(triggerSearch).toHaveBeenCalledWith('');
  });
});
