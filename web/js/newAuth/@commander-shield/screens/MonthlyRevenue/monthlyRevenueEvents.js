import trackEvents, { trackStepsAndNext } from '../../js/analytics';
import { monthlyRevenueOptions, getOptionTitle } from '../screenHelpers';

const monthlyRevenueEvents = {
  trackMonthlyRevenueSelect: (user, monthlyRevenueValue) => {
    const monthlyRevenueName = getOptionTitle(monthlyRevenueOptions, monthlyRevenueValue);

    trackEvents.dataLake({
      type: 'initiated',
      eventName: 'select_revenue',
      data: {
        mid: user.mid,
        userid: user.id,
        source: monthlyRevenueName,
      },
      toCleverTap: true,
    });

    trackStepsAndNext('Monthly Revenue', monthlyRevenueName);
    trackEvents.segment({
      objectName: 'Monthly revenue',
      actionName: `${monthlyRevenueValue} cta clicked`,
      screen: 'monthly revenue page',
    });
  },

  trackMonthlyRevenueNext: () => {
    trackStepsAndNext('Monthly Revenue', 'Next');
    trackEvents.segment({
      objectName: 'SignUp',
      actionName: 'revenue next cta clicked',
      screen: 'monthly revenue page',
    });
  },
};

export default monthlyRevenueEvents;
