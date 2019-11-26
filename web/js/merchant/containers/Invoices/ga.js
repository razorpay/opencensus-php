import defaultTrack, { setTrackData } from 'common/utils/googleAnalytics';

const eventCategory = 'Dashboard - Invoice';

export const track = setTrackData({
  eventCategory,
});

/*
* Track click on duplicate invoice button
* */
export function trackClickDuplicateInvoice() {
  track({
    eventAction: 'Click - Duplicate Invoice',
  });
}

/*
* Track click on saving duplicate invoice
* */
export function trackSaveDuplicateInvoice() {
  track({
    eventAction: 'Save - Duplicate Invoice',
  });
}

const eventCategoryInternational = 'Dashboard - International - Invoice';

export function trackChangeCurrencySettings() {
  defaultTrack({
    eventCategory: eventCategoryInternational,
    eventAction: 'Click - change currency settings',
  });
}

export function trackSelectBillingAddress(country) {
  defaultTrack({
    eventCategory: eventCategoryInternational,
    eventAction: 'Select - billing address',
    label: country,
  });
}

export function trackSelectShippingAddress(country) {
  defaultTrack({
    eventCategory: eventCategoryInternational,
    eventAction: 'Select - shipping address',
    label: country,
  });
}

export function trackSearchFilterForInternational(filterValue) {
  defaultTrack({
    eventCategory: eventCategoryInternational,
    eventAction: 'Select - Search filter',
    label: filterValue,
  });
}
