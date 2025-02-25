import trackEvents, { trackStepsAndNext } from '../../js/analytics';
import { getOptionLabel, UNREGISTERED_BUSINESS_ID } from '../../screens/screenHelpers';

const businessTypeEvents = {
  trackBusinessTypeSelect: (user, businessTypeValue) => {
    let businessTypeName = '';
    if (businessTypeValue === UNREGISTERED_BUSINESS_ID) {
      businessTypeName = getOptionLabel(user?.businessTypes?.unregistered, businessTypeValue);
    } else {
      businessTypeName = getOptionLabel(user?.businessTypes?.registered, businessTypeValue);
    }

    trackEvents.dataLake({
      type: 'initiated',
      eventName: 'select_business_type',
      data: {
        mid: user.mid,
        userid: user.id,
        source: businessTypeName,
      },
      toCleverTap: false,
    });

    trackEvents.segment({
      objectName: 'SignUp',
      actionName: `business type cta clicked`,
      screen: 'business type page',
      properties: {
        userid: user.id,
        businessTypeName,
      },
      toCleverTap: true,
    });

    trackEvents.segment({
      objectName: 'SignUp',
      actionName: `${businessTypeValue} cta clicked`,
      screen: 'business type page',
    });

    trackStepsAndNext('Select Business Type', businessTypeName);
  },

  trackBusinessTypeNext: () => {
    trackStepsAndNext('Select Business Type', 'Next');
  },
};

export default businessTypeEvents;
