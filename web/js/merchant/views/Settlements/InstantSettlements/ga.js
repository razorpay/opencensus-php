import { setTrackData } from 'common/utils/googleAnalytics';
export const EVENT_CATEGORY_DASHBOARD_INSTANT_SETTLEMENT = 'Dashboard - Instant Settlement';

const EVENTS = {
  goToTabIS: {
    eventAction: 'Go To - Instant Settlements Tab',
    eventLabel: 'Settlements | Instant Settlements',
  },
  clickCTASettleNow: {
    eventAction: 'Click CTA - Settle Now',
    eventLabel: 'Summary | Settle Now CTA',
  },
  clickCTAEnableNow: {
    eventAction: 'Click CTA - Enable Now',
    eventLabel: 'Summary | Enable Now CTA',
  },
  clickCTAISClear: {
    eventAction: 'Click CTA - Clear Instant Settlements',
    eventLabel: 'Data Table | Clear CTA',
  },
  clickCTAISSearch: {
    eventAction: 'Click CTA - Search Instant Settlements',
    eventLabel: 'Data Table | Search CTA',
  },
  filterISStatus: {
    eventAction: 'Click - Status Filter',
    eventLabel: 'Data Table | Click Status Filter',
  },
  filterISCount: {
    eventAction: 'Click - Count Parameter',
    eventLabel: 'Data Table | Click Count Filed',
  },
  goToISDetails: {
    eventAction: 'Click Id - Instant Settlement Id',
    eventLabel: 'Data Table | Click Instant Settlement ID',
  },
  clickUTRISDetails: {
    eventAction: 'Click Id - On Demand UTR',
    eventLabel: 'Drawer | Click on On Demand UTR',
  },
  clickCTAViewMoreDetails: {
    eventAction: 'Click CTA - View More Details',
    eventLabel: 'Drawer | Click on View More Details CTA',
  },
  clickCTACloseISDetails: {
    eventAction: 'Click Close icon - Details Drawer',
    eventLabel: 'Drawer | Click on Close Icon',
  },
  visitPayoutDetails: {
    eventAction: 'View Payout Details Page',
    eventLabel: 'Payout Details Page | View batch payout details',
  },
  clickCTAISPayoutDetails: {
    eventAction: 'Click - Go back Instant Settlements',
    eventLabel: 'Payout Details Page | Go back to instant settlements',
  },
  hoverDeductionIconPayoutDetails: {
    eventAction: 'Hover - Deduction Info Icon',
    eventLabel: 'Payout Details Page | Hover Deductions info icon',
  },
  searchISIdPayoutDetails: {
    eventAction: 'Search - Instant Settlements',
    eventLabel: 'Payout Details Page | Search Instant Settlements',
  },
  filterISStatusPayoutDetails: {
    eventAction: 'Click - Status Filter',
    eventLabel: 'Payout Details Page | Click Status Filter',
  },
  clickCTAISClearPayoutDetails: {
    eventAction: 'Click CTA - Clear Seach Parameters',
    eventLabel: 'Payout Details Page | Clear Search CTA',
  },
  clickCTAISSearchPayoutDetails: {
    eventAction: 'Click CTA - Search Instant Settlements',
    eventLabel: 'Payout Details Page | Search CTA',
  },
  clickCTAEmptySettleNow: {
    eventAction: 'Click CTA - Settle Now',
    eventLabel: 'Instant Settlement | Empty State | Settle Now',
  },
  hoverLoadingTotalSettledAmountIconSettlementDetails: {
    eventAction: 'Hover - Total Settled Amount Info Icon',
    eventLabel: 'Drawer | Total Settled Amount info icon',
  },
  hoverLoadingTotalSettledAmountIconPayoutDetails: {
    eventAction: 'Hover - Total Settled Amount Info Icon',
    eventLabel: 'Payout Details Page | Total Settled Amount info icon',
  },
};

const trackIS = {};

Object.keys(EVENTS).forEach((key) => {
  trackIS[key] = (...params) => {
    setTrackData({
      eventCategory: EVENT_CATEGORY_DASHBOARD_INSTANT_SETTLEMENT,
      ...EVENTS[key],
      ...params,
    })();
  };
});

export default trackIS;
