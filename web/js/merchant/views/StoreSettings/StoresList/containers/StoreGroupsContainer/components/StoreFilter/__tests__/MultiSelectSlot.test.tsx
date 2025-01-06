import React from 'react';

import { render, screen, userEvent } from 'test-utils';

import MultiSelectSlot from '../MultiSelectSlot';

describe('MultiSelectSlot', () => {
  const onChange = jest.fn();
  const OPTIONS = [
    { label: 'State 1', value: 'state1' },
    { label: 'State 2', value: 'state2' },
    { label: 'State 3', value: 'state3' },
    { label: 'State 4', value: 'state4' },
    { label: 'State 5', value: 'state5' },
    { label: 'State 6', value: 'state6' },
  ];

  test('should render MultiSelectSlot', async () => {
    render(<MultiSelectSlot name="State" title="States" options={OPTIONS} onChange={onChange} />);
    expect(screen.getByText('States')).toBeInTheDocument();
    let options = screen.getAllByRole('checkbox');
    expect(options[0]).toBeInTheDocument();

    // onChange validation
    await userEvent.click(options[0]);
    expect(onChange).toHaveBeenLastCalledWith({ name: 'State', values: ['state1'] });

    // 'View All' collapse validation
    const defaultDisplayedItems = 3;
    expect(options).toHaveLength(defaultDisplayedItems);
    await userEvent.click(screen.getByText('View All'));
    options = screen.getAllByRole('checkbox');
    expect(options).toHaveLength(OPTIONS.length);
  });

  test('should render placeholder message when no options are available', () => {
    render(<MultiSelectSlot name="State" title="States" options={[]} />);
    expect(screen.getByText('No States found')).toBeInTheDocument();
  });
});
