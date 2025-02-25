import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import SettlementTimeline from 'merchant/views/Settlements/components/SettlementTimeline';
import { fireEvent, render, screen, waitFor } from 'test-utils';
import * as details from 'merchant/reducers/settlements/details';
import { TIMELINE_EVENTS } from 'merchant/views/Settlements/components/utils';
import * as modals from 'merchant_common/reducers/modals';
import * as trackEvents from 'merchant/reducers/trackEvents';

const state = {
  session: {
    mode: "test",
    user: {},
    org: {},
  },
  settlement: {},
};

describe('SettlementTimeline', () => {
  const fetchHolidaySpy = jest.spyOn(details, 'fetchHolidayList');
  const openModalSpy = jest.spyOn(modals, 'openModal');
  const trackEventSpy = jest.spyOn(trackEvents, 'trackEvents');

  const defaultProps = {
    entityType: 'refund',
    settlementDetails: {
      holidays: [],
      eligible_at: '1 Jan 2022',
      started_at: '1 Apr 2022',
      settled_at: '5 Apr 2022',
      method: 'Bank Transfer',
    },
    data: {
      transaction: {
        id: 'pay_123456',
        created_at: '10 Apr 2022',
        settled_at: '5 Apr 2022',
        settlement: {
          id: 'settle_123456',
          status: 'Payment processed',
          utr: 'HDFCR52010009844K',
        },
      },
    },
  };

  const renderApp = ({ state, ...rest }) => {
    return render(<SettlementTimeline {...defaultProps} {...rest} />, { initialState: state });
  };

  beforeEach(() => {
    fetchHolidaySpy.mockClear();
    openModalSpy.mockClear();
    trackEventSpy.mockClear();
  });

  test('should call fetch holiday on mount', async () => {
    const events = ['TRANSACTION_INFO'];
    renderApp({ state, events });
    await waitFor(() => {
      expect(fetchHolidaySpy).toHaveBeenCalledTimes(1);
    });
  });
  describe('TimeLine Events', () => {
    test('should render payment captured and refund processed', () => {
      const events = [TIMELINE_EVENTS.PAYMENT_CAPTURED, TIMELINE_EVENTS.REFUND_PROCESSED];
      renderApp({ state, events });

      const paymentText = screen.getByText(events[0]?.split('_').join(' ').toLowerCase());
      expect(paymentText).toBeInTheDocument();

      const refundText = screen.getByText(events[1]?.split('_').join(' ').toLowerCase());
      expect(refundText).toBeInTheDocument();
    });
    test('should render schedule info', () => {
      const events = [TIMELINE_EVENTS.SCHEDULE_INFO];
      renderApp({ state, events });

      const scheduleInfo = screen.getByText('Settlement schedule');
      expect(scheduleInfo).toBeInTheDocument();

      renderApp({ state, events, entityType: 'payment' });
      const methodInfo = screen.getByText(defaultProps.settlementDetails.method);
      expect(methodInfo).toBeInTheDocument();
    });
    test('should render holiday info', async () => {
      const events = [TIMELINE_EVENTS.HOLIDAY_INFO];
      let props = {
        settlementDetails: {
          ...defaultProps.settlementDetails,
          holidays: [
            { date: '1 Apr 2022', description: 'Its holiday' },
            { date: '2 May 2022', description: 'No settlement, its holiday' },
          ],
        },
      };
      renderApp({ state, events, ...props });

      const paymentText = screen.getByText('Bank Holidays');
      expect(paymentText).toBeInTheDocument();
      props.settlementDetails.holidays.forEach((each) => {
        const holidayText = screen.getByText(`(${each.description})`);
        expect(holidayText).toBeInTheDocument();
      });
      const viewHolidayBtn = screen.getByText('View Holidays');
      fireEvent.click(viewHolidayBtn);
      await waitFor(() => {
        expect(openModalSpy).toHaveBeenCalledTimes(1);
      });

      props = {
        settlementDetails: {
          ...defaultProps.settlementDetails,
          holidays: [{ date: '1 Apr 2022', description: 'Its holiday' }],
        },
      };
      const holidayDescription = screen.getByText(
        `(${props.settlementDetails.holidays[0].description})`,
      );
      expect(holidayDescription).toBeInTheDocument();
    });
    test('should render settlement info', () => {
      const events = [TIMELINE_EVENTS.SETTLEMENT_INFO];
      renderApp({ state, events });

      const settlementDateText = screen.getByText('Settlement Date');
      expect(settlementDateText).toBeInTheDocument();
    });

    test('should render settlement info', async () => {
      const events = [TIMELINE_EVENTS.SETTLEMENT_INFO];
      const props = {
        settlementDetails: {
          ...defaultProps.settlementDetails,
          is_settled: true,
        },
        showCustomSettlDetails: true,
        adminAsMerchant: true,
      };

      renderApp({ state, events, ...props });
      const entityText = screen.getByText(defaultProps.entityType);
      expect(entityText).toBeInTheDocument();
      const UTRText = screen.getByText(defaultProps.data.transaction.settlement.utr);
      expect(UTRText).toBeInTheDocument();
      const settlementId = screen.getAllByText(defaultProps.data.transaction.settlement.id);
      expect(settlementId).toHaveLength(2);
      const trackEventBtn = screen.getByLabelText('settlement link');
      // clicked without page
      fireEvent.click(trackEventBtn);
      await waitFor(() => {
        expect(trackEventSpy).toHaveBeenCalledTimes(0);
      });
    });

    test('should render settlement info', async () => {
      const events = [TIMELINE_EVENTS.SETTLEMENT_INFO];

      const props = {
        settlementDetails: {
          ...defaultProps.settlementDetails,
          is_settled: true,
        },
        showCustomSettlDetails: true,
        adminAsMerchant: true,
        page: 'Home Page',
      };

      renderApp({ state, events, ...props });
      const trackEvent = screen.getByLabelText('settlement link');
      fireEvent.click(trackEvent);
      await waitFor(() => {
        expect(trackEventSpy).toHaveBeenCalledTimes(1);
      });
    });
  });
});
