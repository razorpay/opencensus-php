import React from 'react';

import MultiSelectSlot from '@apps/digital-bills/src/common/components/StoreFilterModal/MultiSelectSlot';
import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { userEvent } from '@apps/digital-bills/src/services/test/test-utils';

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
    const { getByText, getAllByRole } = renderWithWrappers(
      <MultiSelectSlot name="State" title="States" options={OPTIONS} onChange={onChange} />,
    );
    expect(getByText('States')).toBeInTheDocument();
    let options = getAllByRole('checkbox');
    expect(options[0]).toBeInTheDocument();

    // onChange validation
    await userEvent.click(options[0]);
    expect(onChange).toHaveBeenLastCalledWith({ name: 'State', values: ['state1'] });

    // 'View All' collapse validation
    const defaultDisplayedItems = 3;
    expect(options).toHaveLength(defaultDisplayedItems);
    await userEvent.click(getByText('View All'));
    options = getAllByRole('checkbox');
    expect(options).toHaveLength(OPTIONS.length);
  });

  test('should render placeholder message when no options are available', () => {
    const { getByText } = renderWithWrappers(
      <MultiSelectSlot name="State" title="States" options={[]} />,
    );
    expect(getByText('No States found')).toBeInTheDocument();
  });
});
