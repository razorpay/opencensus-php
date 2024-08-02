import React from 'react';

import ModalCloseReasons from 'merchant/views/Settlements/Settlements/components/Modals/ModalCloseReasons';
import { CLOSE_OPTIONS } from 'merchant/views/Settlements/Settlements/data';
import * as ModalActions from 'merchant_common/reducers/modals';
import { fireEvent, render, screen, waitFor } from 'test-utils';

const mockGoBackToInitialModalView = jest.fn();

describe('ModalCloseReaons.js', () => {
  const closeModalsSpy = jest.spyOn(ModalActions, 'closeModal');

  beforeEach(() => {
    closeModalsSpy.mockClear();
    window.rzpAnalytics.mockReset();
  });

  const renderApp = ({ user, hasMIDLevelLimit } = {}) =>
    render(
      <ModalCloseReasons
        eventCategory="Dashboard - Early Settlement"
        closeOrigin="OnDemand"
        goBackToInitialModalView={mockGoBackToInitialModalView}
        user={user}
        hasMIDLevelLimit={hasMIDLevelLimit}
      />,
      {
        showModal: true,
      },
    );

  test('should display all close reasons', () => {
    renderApp();

    CLOSE_OPTIONS.forEach((option) => {
      expect(screen.getByText(new RegExp(option.label))).toBeInTheDocument();
    });
  });

  test('should render CTAs properly', () => {
    renderApp();

    expect(screen.getByRole('button', { name: 'Go Back' })).toBeInTheDocument();
    const confirmBtn = screen.getByRole('button', { name: 'Confirm & Close' });
    expect(confirmBtn).toBeInTheDocument();
    expect(confirmBtn).toBeDisabled();
  });

  test('should enable CTA on option select', () => {
    renderApp();

    const labelRadio = screen.getByLabelText(CLOSE_OPTIONS[0].label);

    expect(labelRadio.checked).toEqual(false);
    fireEvent.click(labelRadio);
    expect(labelRadio.checked).toEqual(true);
    const confirmBtn = screen.getByRole('button', { name: 'Confirm & Close' });
    expect(confirmBtn).not.toBeDisabled();
  });

  test('should be able to write in the textarea', () => {
    renderApp();

    const textboxInput = screen.getByRole('textbox');

    fireEvent.change(textboxInput, {
      target: { value: 'Sample text' },
    });

    expect(screen.getByText(/Sample text/)).toBeInTheDocument();
  });

  test('should trigger callback fn and close modal', async () => {
    renderApp();

    const goBackBtn = screen.getByRole('button', { name: 'Go Back' });
    fireEvent.click(goBackBtn);

    await waitFor(() => {
      expect(mockGoBackToInitialModalView).toBeCalled();
    });
    expect(closeModalsSpy).toBeCalled();
  });

  test('should trigger close modal on submit click', async () => {
    renderApp();

    const labelRadio = screen.getByLabelText(CLOSE_OPTIONS[0].label);
    fireEvent.click(labelRadio);

    const goBackBtn = screen.getByRole('button', { name: 'Confirm & Close' });
    fireEvent.click(goBackBtn);

    await waitFor(() => {
      expect(window.rzpAnalytics).toHaveBeenLastCalledWith({
        eventAction: 'Click CTA - Confirm & Close',
        eventCategory: 'Day 1 ES',
        eventLabel: 'ES Settlement | Merchant Churn | undefined',
      });
    });
    expect(closeModalsSpy).toBeCalled();
  });

  test('should render merchant level limit nudge', () => {
    renderApp({
      user: {
        isOndemandSettlementEnabled: true,
        isOndemandSettlementsRestricted: false,
        isAutomaticSettlementEnabled: true,
      },
      hasMIDLevelLimit: true,
    });
    expect(screen.getByText(/Why can't I settle more money?/i)).toBeInTheDocument();
  });
});
