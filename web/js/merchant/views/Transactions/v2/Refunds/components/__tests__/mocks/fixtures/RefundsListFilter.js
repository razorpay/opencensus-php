import React from 'react';
import mockMoment from 'moment';
import { render } from 'test-utils';

import * as useMobile from 'common/hooks/useMobile/useMobile';
import RefundsListFilter from 'merchant/views/Transactions/v2/Refunds/components/RefundsListFilter';

jest.mock('common/ui/Forms/DateRangePickerField', () => ({ onDatesChange }) => {
  const onChangeDate = () => onDatesChange({ from: mockMoment(), to: mockMoment().add(2, 'days') });
  return (
    <div>
      Date Range Picker
      <button onClick={onChangeDate}>Change Date</button>
    </div>
  );
});
jest.setTimeout(35000);

export const useMobileSpy = jest.spyOn(useMobile, 'useMobile');

export const defaultProps = {
  onSubmit: jest.fn(),
};

export const renderApp = () => render(<RefundsListFilter {...defaultProps} />);
