import React from 'react';
import mockMoment from 'moment';
import * as useMobile from '@dashboard/shared-ui/hooks/useMobile';
import { render } from 'apps/self-serve/src/services/test/test-utils';

import DisputeListFilter from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputeListFilter';

jest.mock(
  'common/ui/Forms/DateRangePickerField',
  () =>
    function DateRangePickerField({ onDatesChange }) {
      const onChangeDate = () =>
        onDatesChange({
          from: mockMoment(),
          to: mockMoment().add(2, 'days'),
        });
      return (
        <div>
          Date Range Picker
          <button onClick={onChangeDate}>Change Date</button>
        </div>
      );
    },
);

export const useMobileSpy = jest.spyOn(useMobile, 'useMobile');

jest.setTimeout(35000);

export const defaultProps = {
  onSubmit: jest.fn(),
  loading: false,
};

export const renderApp = () => render(<DisputeListFilter {...defaultProps} />);
