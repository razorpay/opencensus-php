import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import PaymentSchedule from 'merchant/views/Settlements/components/PaymentSchedule';
import { fireEvent, render, screen } from 'test-utils';

describe('PaymentSchedule', () => {
  const state = {
    paymentType: 'domestic',
    schedules: {
      'domestic:default': 'T+2 1PM',
      'international:default': 'T+7 9AM',
    },
  };
  const App = (props) => <PaymentSchedule {...props} />;

  test('should render payment schedule', () => {
    render(<App {...state} />);
    const paymentScheduleInfo = screen.getByText(`${state.paymentType} Payments`);
    expect(paymentScheduleInfo).toBeInTheDocument();
  });

  describe('Schedule Payment Type', () => {
    test('should render single schedule payment type', () => {
      render(<App {...state} />);
      const singleScheduleText = screen.getByText(
        `${state.schedules[`${state.paymentType}:default`]}`,
      );
      expect(singleScheduleText).toBeInTheDocument();
    });
    test('should render multi schedule payment type', () => {
      const props = {
        ...state,
        schedules: {
          ...state.schedules,
          'domestic:main': 'T+2 1PM',
        },
      };
      render(<App {...props} />);
      const multiScheduleText = screen.getByText(
        `${props.schedules[`${props.paymentType}:default`]}`,
      );
      expect(multiScheduleText).toBeInTheDocument();
      const note = `Some ${props.paymentType} payment methods have a different schedule.`;
      const noteText = screen.getByText(note);
      expect(noteText).toBeInTheDocument();
    });
  });
  describe('ToggleClick', () => {
    const props = {
      ...state,
      schedules: {
        ...state.schedules,
        'domestic:main': 'T+2 1PM',
      },
    };
    test('should render view toggle click', () => {
      render(<App {...props} />);
      const viewButton = screen.getByText('View schedules');
      fireEvent.click(viewButton);
      const scheduleBtnTitle = screen.getByText('Hide schedules');
      expect(scheduleBtnTitle).toBeInTheDocument();
      expect(screen.getByText('Settlement schedule')).toBeInTheDocument();
      expect(screen.getByText('Payment method')).toBeInTheDocument();
    });
    test('should render hide toggle click', () => {
      render(<App {...props} />);
      const scheduleBtnTitle = screen.getByText('View schedules');
      expect(scheduleBtnTitle).toBeInTheDocument();
    });
  });
  describe('Other Methods', () => {
    test('should render methods other than default', () => {
      const state = {
        paymentType: 'domestic',
        schedules: {
          'domestic:default': 'T+2 1PM',
          'international:default': 'T+7 9AM',
          'domestic:main': 'T+2 3PM',
          'domestic:primary': 'T+2 2PM',
        },
      };
      render(<App {...state} />);
      const viewButton = screen.getByText('View schedules');
      fireEvent.click(viewButton);
      const otherMethods = screen.getAllByLabelText('methods');
      expect(otherMethods[0]).toHaveClass('mb-6 schedule-row other-methods-row');
    });
  });
});
