import Stepper from 'common/new-ui/Stepper';
import moment from 'moment';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';

const ReminderStepsDetails = ({
  isRemindersEnabled,
  nextReminders,
  isAutoRemindersUpdating,
  isPaymentLinkClosed,
}) => {
  const reminderStepsData = nextReminders
    .map((reminder) => {
      const currDate = moment(undefined);
      const reminderDate = moment(reminder * 1000);
      const isPendingState = reminderDate.isAfter(currDate);

      if (isPaymentLinkClosed && isPendingState) {
        return null;
      }

      const newReminder = {
        status: !isRemindersEnabled ? 'disabled' : isPendingState > 0 ? 'pending' : 'completed',
        time_to_sent: reminder,
      };

      return {
        status: newReminder.status,
        type: <i className={`i i-${newReminder.status === 'completed' ? 'check-circle' : 'bullet'}`} />,
        label: isAutoRemindersUpdating ? (
          <PlaceholderLoader />
        ) : (
          moment(newReminder.time_to_sent * 1000).format('DD MMM YYYY')
        ),
      };
    })
    .filter((ele) => ele !== null);

  return <Stepper list={reminderStepsData} />;
};

export default ReminderStepsDetails;
