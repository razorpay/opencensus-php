import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';
import Reminders from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields/Reminders';

jest.spyOn(track.lj.fields, 'reminders').mockImplementation(() => {});
jest.spyOn(track.segment.fields, 'reminders').mockImplementation(() => {});

describe('Reminders - Unit Test', () => {
  afterEach(() => {
    track.lj.fields.reminders.mockClear();
    track.segment.fields.reminders.mockClear();
  });

  const renderApp = (props = {}) => {
    return render(<Reminders {...props} />);
  };

  test('should render disabled state when reminders are not enabled', () => {
    const props = {
      config: {
        isEnabled: false,
        configs_count: {
          without_expiry: 4,
          with_expiry: 1,
        },
      },
      extraProps: {},
      hasNoExpiry: true,
    };
    renderApp(props);
    expect(screen.queryByText('Reminders')).toBeInTheDocument();
    expect(screen.getByText(/Reminders is not set to payment links/i)).toBeInTheDocument();
    expect(screen.getByText(/Set it up/i)).toBeInTheDocument();
  });

  test('should render "send auto reminders" title in the document', () => {
    const props = {
      config: {
        isEnabled: true,
        configs_count: {
          without_expiry: 5,
          with_expiry: 1,
        },
      },
      hasNoExpiry: true,
    };
    renderApp(props);
    expect(screen.queryByText('Send auto reminders')).toBeInTheDocument();
  });

  test('should render "send auto reminders" state when reminders are enabled', async () => {
    const props = {
      config: {
        isEnabled: true,
        configs_count: {
          without_expiry: 5,
          with_expiry: 1,
        },
      },
      hasNoExpiry: true,
    };
    renderApp(props);
    const checkbox = screen.queryAllByRole('checkbox')[0];
    await userEvent.click(checkbox);
    await userEvent.tab(checkbox);
    expect(checkbox).toBeInTheDocument();
  });
});
