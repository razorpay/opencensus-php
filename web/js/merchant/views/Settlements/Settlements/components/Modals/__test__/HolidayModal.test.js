import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, fireEvent } from 'test-utils';
import HolidayModal from 'merchant/views/Settlements/Settlements/components/Modals/HolidayModal';
import { holidayList } from 'merchant/views/Settlements/Settlements/components/Modals/__test__/mocks/fixtures/HolidayModal';
import * as ModalActions from 'merchant_common/reducers/modals';

describe('HolidayModal.js', () => {
  const modalsSpy = jest.spyOn(ModalActions, 'closeModal');

  beforeEach(() => {
    modalsSpy.mockClear();
  });

  /**
   * mock holiday list is for the year 2022
   */
  beforeAll(() => {
    jest.useFakeTimers('modern').setSystemTime(new Date('2022-01-01'));
  });

  const renderApp = () =>
    render(<HolidayModal holidayList={holidayList} />, {
      showModal: true,
    });

  test('should render table tag', () => {
    renderApp();
    expect(screen.queryByRole('table')).toBeInTheDocument();
    expect(screen.queryByText(/Holidays List/)).toBeInTheDocument();
  });

  test('should render columns correctly', () => {
    renderApp();
    expect(screen.queryByText('Date')).toBeInTheDocument();
    expect(screen.queryByText('Name')).toBeInTheDocument();
  });

  test('should render table data correctly', () => {
    renderApp();
    holidayList.data['2022'].forEach((item) => {
      expect(screen.queryByText(item.description)).toBeInTheDocument();
    });
  });

  test('should close modal on close click', () => {
    renderApp();
    const closeBtn = screen.queryByTestId('modal-header-close-btn');
    expect(closeBtn).toBeInTheDocument();
    fireEvent.click(closeBtn);
    expect(modalsSpy).toHaveBeenCalledTimes(1);
  });
});
