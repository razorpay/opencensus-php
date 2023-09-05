import React from 'react';
import cloneDeep from 'lodash/cloneDeep';
import moment from 'moment';

import * as reducers from 'merchant/reducers/successRate';
import store from 'merchant/store';
import SuccessRateFilter from 'merchant/views/Transactions/v1/SuccessRate/components/SuccessRateFilter';
import {
  DEFAULT_METHOD,
  CARD_GROUPING_DATA,
} from 'merchant/views/Transactions/v1/SuccessRate/constants';
import * as services from 'merchant/views/Transactions/v1/SuccessRate/service';
import { render, screen, userEvent, waitFor } from 'test-utils';

const state = store.getState();

const renderApp = (initialState = state) => {
  render(<SuccessRateFilter />, {
    initialState,
  });
};

jest.mock('react-datetime', () => {
  const OriginalModule = jest.requireActual('react-datetime');
  return (props) => {
    const newProps = { ...props };
    newProps.inputProps.readOnly = false;
    return <OriginalModule {...newProps} />;
  };
});

describe('<SuccessRateFilter />', () => {
  beforeEach(() => {
    jest.restoreAllMocks();
    jest.clearAllMocks();
  });

  test('should render success rate filter container on screen', () => {
    renderApp();
    expect(screen.getByTestId('success-rate-filter')).toBeVisible();
  });

  test('should render all necessary filters and widgets on screen', () => {
    renderApp();
    const testIds = [
      'success-rate-filter',
      'sr-filter-apply-btn',
      'sr-filter-clear-btn',
      'sr-tab-refresh',
      'sr-dashboard-date-filters',
    ];

    testIds.forEach((id) => expect(screen.getByTestId(id)).toBeVisible());
  });

  test('should trigger search for overall tab with correct payload', async () => {
    const srApiSpy = jest.spyOn(services, 'getSR');
    const storeData = store.getState();
    const getStateSpy = jest.spyOn(store, 'getState');
    getStateSpy.mockImplementation(() => {
      const clonedStore = cloneDeep(storeData);
      clonedStore.successRate.filters = {
        ...clonedStore.successRate.filters,
        startDate: moment('23-06-2023 | 12 PM', 'DD-MM-YYYY | h A'),
        endDate: moment('30-06-2023 | 12 PM', 'DD-MM-YYYY | h A'),
      };
      return clonedStore;
    });
    renderApp();

    await userEvent.click(screen.getByTestId('sr-filter-apply-btn'));
    await waitFor(() => {
      const expectedPayloadStruct = {
        from: 1687521600,
        to: 1688126400,
        filters: {
          method: DEFAULT_METHOD.Overall.map(({ method }) => method),
        },
      };
      expect(srApiSpy).toHaveBeenCalledWith(expect.objectContaining(expectedPayloadStruct));
    });
  });

  test('should trigger search for overall tab with correct payload', async () => {
    const srApiSpy = jest.spyOn(services, 'getSR');
    const getStateSpy = jest.spyOn(store, 'getState');
    const state = store.getState();
    const clonedStore = cloneDeep(state);
    clonedStore.successRate.activeTab = 'Card';
    clonedStore.successRate.tabs.Card.selectedDropdownFilterOptions = CARD_GROUPING_DATA;
    renderApp(clonedStore);

    getStateSpy.mockImplementation(() => {
      clonedStore.successRate.filters = {
        ...clonedStore.successRate.filters,
        startDate: moment('23-06-2023 | 12 PM', 'DD-MM-YYYY | h A'),
        endDate: moment('30-06-2023 | 12 PM', 'DD-MM-YYYY | h A'),
      };
      return clonedStore;
    });
    await userEvent.click(screen.getByTestId('sr-filter-apply-btn'));
    await waitFor(() => {
      const expectedPayloadStruct = {
        from: 1687521600,
        to: 1688126400,
        filters: { method: ['card'], type: ['credit'] },
      };
      expect(srApiSpy).toHaveBeenLastCalledWith(expect.objectContaining(expectedPayloadStruct));
    });
  });

  test('should reset filters after clicking reset button', async () => {
    const srApiSpy = jest.spyOn(services, 'getSR');
    const setActiveTabSpy = jest.spyOn(reducers, 'setActiveTab');
    renderApp();
    const comboBox = screen.getByRole('combobox') as HTMLElement;
    await userEvent.click(comboBox, {
      pointerEventsCheck: 0,
    });
    await userEvent.click(screen.getByText('Last 24 Hours'));
    expect(screen.getByLabelText('Date Filter')).toHaveTextContent('Last 24 Hours');
    await userEvent.click(screen.getByTestId('sr-filter-clear-btn'));
    await waitFor(() => {
      expect(screen.getByLabelText('Date Filter')).toHaveTextContent('Last 6 Hours');
      expect(setActiveTabSpy).toHaveBeenLastCalledWith('Overall');
      expect(srApiSpy).toHaveBeenLastCalledWith(
        expect.objectContaining({
          filters: {
            method: DEFAULT_METHOD.Overall.map(({ method }) => method),
          },
        }),
      );
    });
  });

  test('should render error if start date is greater than end date', async () => {
    renderApp();
    const startDateEl = screen.getByTestId('sr-dashboard-custom-date-input-startDate').firstChild
      ?.firstChild as HTMLInputElement;

    const endDateEl = screen.getByTestId('sr-dashboard-custom-date-input-endDate').firstChild
      ?.firstChild as HTMLInputElement;

    startDateEl.focus();
    await userEvent.clear(startDateEl);
    await userEvent.paste('30-06-2023 | 12 PM');
    endDateEl.focus();
    await userEvent.clear(endDateEl);
    await userEvent.paste('23-06-2023 | 12 PM');

    expect(screen.getByTestId('sr-filter-error')).toHaveTextContent(
      'Start date cannot be greater than end date',
    );
  });

  test('should render minimum range error if start date and end date is same', async () => {
    renderApp();
    const startDateEl = screen.getByTestId('sr-dashboard-custom-date-input-startDate').firstChild
      ?.firstChild as HTMLInputElement;

    const endDateEl = screen.getByTestId('sr-dashboard-custom-date-input-endDate').firstChild
      ?.firstChild as HTMLInputElement;

    startDateEl.focus();
    await userEvent.clear(startDateEl);
    await userEvent.paste('23-06-2023 | 12 PM');
    endDateEl.focus();
    await userEvent.clear(endDateEl);
    await userEvent.paste('23-06-2023 | 12 PM');

    expect(screen.getByTestId('sr-filter-error')).toHaveTextContent(
      'Please select a minimum range of 6 hours',
    );
  });
});
