import React from 'react';
import mockMoment from 'moment';
import * as sharedUtils from '@libs/shared-utils';
import { render } from 'apps/self-serve/src/services/test/test-utils';

import PaymentsListFilter from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsListFilter';

const defaultInitialState = {
  session: {
    user: {
      isOmniChannelMerchant: false,
    },
  },
};
jest.mock(
  '@libs/web-nexus/common/ui/Forms/DateRangePickerField',
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
jest.setTimeout(35000);

export const useMobileSpy = jest.spyOn(sharedUtils, 'useMobile');

export const defaultProps = {
  onSubmit: jest.fn(),
};

export const renderApp = (props, initialState = defaultInitialState) => {
  return render(<PaymentsListFilter {...defaultProps} {...props} />, {
    initialState,
  });
};
