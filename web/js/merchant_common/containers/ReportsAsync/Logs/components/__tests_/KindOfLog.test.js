import { render, screen } from 'common/services/test/test-utils';
import KindOfLog, {
  NO_OF_MS_IN_A_SEC,
  SECONDS_IN_A_DAY,
} from 'merchant_common/containers/ReportsAsync/Logs/components/KindOfLog';
import { getFormattedDate } from 'merchant_common/containers/ReportsAsync/utils';

describe('Kind Of Log', () => {
  const defaultProps = {
    createdAt: new Date().getTime(),
  };

  const App = (props) => <KindOfLog {...defaultProps} {...props} />;

  test('should show requested time when schedule id is not null', () => {
    // 1 minute less than current time
    const { rerender } = render(<App createdAt={new Date().getTime() / NO_OF_MS_IN_A_SEC - 60} />);
    expect(screen.queryByText('Scheduled')).not.toBeInTheDocument();
    expect(screen.getByText('Requested')).toBeInTheDocument();
    expect(screen.getByText('a min ago')).toBeInTheDocument();
    const timeLessThan1Day = new Date().getTime() / NO_OF_MS_IN_A_SEC - SECONDS_IN_A_DAY;
    rerender(<App createdAt={timeLessThan1Day} />);
    expect(screen.getByText(getFormattedDate(timeLessThan1Day))).toBeInTheDocument();
  });

  test('should show scheduled when schedule id is not null', () => {
    render(<App scheduleId="scheduleId" />);
    expect(screen.getByText('Scheduled')).toBeInTheDocument();
    expect(screen.queryByText('Requested')).not.toBeInTheDocument();
  });
});
