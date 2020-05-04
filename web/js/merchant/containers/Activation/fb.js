import { fireAnalyticsEvents } from 'common/utils/googleAnalytics';

export function fireActFBEvents(user) {
  const regFBEvents = [
    'activation_complete_success',
    'activation_complete_success_reg',
    'combo1',
    'combo2',
    'combo3',
    'combo6',
  ];
  const unregFBEvents = [
    'activation_complete_success',
    'activation_complete_success_unreg',
    'combo1',
    'combo4',
    'combo5',
    'combo7',
  ];
  let eventsToFire = [];

  if (!user.isUnregisteredBusiness && user.activation_flow !== 'blacklist') {
    eventsToFire = regFBEvents;
  } else {
    eventsToFire = unregFBEvents;
  }

  eventsToFire.forEach(evt => {
    fireAnalyticsEvents({
      fbData: evt,
    });
  });
}

export function fireKYCFBEvents(user) {
  let allEvents = ['kyc_complete_all'];
  const fbRegCombos = [
    'combo1',
    'combo2',
    'combo3',
    'combo4',
    'combo5',
    'combo6',
  ];
  const fbUnRegcombos = [
    'combo1',
    'combo2',
    'combo3',
    'combo4',
    'combo5',
    'combo7',
  ];

  if (user.isUnregisteredBusiness) {
    allEvents.push('kyc_complete_unreg');
    allEvents = [...allEvents, ...fbUnRegcombos];
  } else {
    const evt = `KYC_complete_${user.activation_flow}`;
    allEvents.push(evt);
    allEvents = [...allEvents, ...fbRegCombos];
  }

  allEvents.forEach(evt => {
    fireAnalyticsEvents({
      fbData: evt,
    });
  });
}
