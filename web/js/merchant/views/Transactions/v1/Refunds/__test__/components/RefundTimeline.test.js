import RefundTimeline from 'merchant/views/Transactions/v1/Refunds/components/RefundTimeline';
import { render, screen } from 'test-utils';
import { RefundStatusLabel } from 'merchant/components/StatusLabel';
import {
  refund,
  RefundMilestones,
} from 'merchant/views/Transactions/v1/Refunds/__test__/mocks/fixtures';

jest.mock('merchant/components/StatusLabel', () => ({
  ...jest.requireActual('merchant/components/StatusLabel'),
  RefundStatusLabel: jest.fn().mockImplementation(({ status }) => <p>{status}</p>),
}));

describe('Refunds - RefundTimeline Component', () => {
  afterEach(() => {
    RefundStatusLabel.mockClear();
  });
  const expectRefundTimeLine = (expectedMileStones) => {
    let callIdx = 0;
    expectedMileStones.forEach((step) => {
      if (step.mode) {
        expect(screen.getAllByText(new RegExp(step.mode))[0]).toBeInTheDocument();
      }
      if (step.text) {
        expect(screen.getAllByText(new RegExp(step.text))[0]).toBeInTheDocument();
      }
      if (step.infoText) {
        expect(screen.getAllByText(new RegExp(step.text))[0]).toBeInTheDocument();
      }
      if (step.status) {
        expect(RefundStatusLabel.mock.calls[callIdx][0]).toMatchObject({
          status: step.status,
        });
        callIdx++;
      }
    });
  };
  test('should render refund timeline component', () => {
    render(
      <RefundTimeline
        refund={{
          ...refund,
          speed_processed: null,
        }}
      />,
    );
    expect(screen.getByTestId('refund-timeline')).toBeInTheDocument();
  });

  describe('When refund processed speed is normal', () => {
    describe('Refund timeline when requested speed is normal', () => {
      const drivingProps = {
        ...refund,
        speed_requested: 'normal',
        speed_processed: 'normal',
      };
      test('should render refund timeline when status is processed', () => {
        render(
          <RefundTimeline
            refund={{
              ...drivingProps,
              status: 'processed',
            }}
          />,
        );
        expectRefundTimeLine([
          RefundMilestones.Processing.Normal,
          RefundMilestones.Processed.Normal,
        ]);
      });

      test('should render refund timeline when status is processing', () => {
        render(
          <RefundTimeline
            refund={{
              ...drivingProps,
              status: 'processing',
            }}
          />,
        );
        expectRefundTimeLine([
          RefundMilestones.Processing.Normal,
          RefundMilestones.Text.RefundInitiated,
        ]);
      });
    });
    describe('Refund timeline when requested speed is instant', () => {
      const drivingProps = {
        ...refund,
        speed_requested: 'instant',
        speed_processed: 'normal',
      };
      test('should render refund timeline when status is processed', () => {
        render(
          <RefundTimeline
            refund={{
              ...drivingProps,
              status: 'processed',
            }}
          />,
        );
        expectRefundTimeLine([
          RefundMilestones.Processing.Instant,
          RefundMilestones.Text.SpeedUpdatedToNormal,
          RefundMilestones.Processing.Normal,
          RefundMilestones.Processed.Normal,
        ]);
      });
      test('should render refund timeline when status is failed', () => {
        render(
          <RefundTimeline
            refund={{
              ...drivingProps,
              status: 'failed',
              speed_change_time: new Date().getTime(),
            }}
          />,
        );
        expectRefundTimeLine([
          RefundMilestones.Processing.Instant,
          RefundMilestones.Text.SpeedUpdatedToNormal,
          RefundMilestones.Processing.Normal,
          RefundMilestones.Failed.Normal,
        ]);
      });
    });
  });

  describe('When refund processed speed is instant', () => {
    const drivingProps = {
      ...refund,
      speed_processed: 'instant',
    };
    test('should render refund timeline when status is processed', () => {
      render(
        <RefundTimeline
          refund={{
            ...drivingProps,
            status: 'processed',
          }}
        />,
      );
      expectRefundTimeLine([
        RefundMilestones.Processing.Instant,
        RefundMilestones.Processed.Instant,
      ]);
    });

    describe('When refund status is failed', () => {
      test('should render refund timeline', () => {
        render(
          <RefundTimeline
            refund={{
              ...drivingProps,
              status: 'failed',
              gateway_refund_support: true,
            }}
          />,
        );
        expectRefundTimeLine([
          RefundMilestones.Processing.Instant,
          RefundMilestones.Failed.Instant,
        ]);
      });
      test('should render refund timeline when instant refund failed', () => {
        render(
          <RefundTimeline
            refund={{
              ...drivingProps,
              status: 'failed',
              gateway_refund_support: false,
            }}
          />,
        );
        expectRefundTimeLine([
          RefundMilestones.Processing.Instant,
          RefundMilestones.Text.InstantRefundFailed,
          RefundMilestones.Failed.Instant,
        ]);
      });
    });
  });
});
