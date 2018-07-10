import track, { trackGoToLinks } from 'merchant/containers/Home/ga';

export const trackTabClick = tabTitle => {
  track({
    eventAction: 'Click - Graph Tab',
    eventLabel: tabTitle,
  });
};

export const trackBreakdownChange = breakdown => {
  track({
    eventAction: 'Click - Time Breakdown',
    eventLabel: breakdown,
  });
};

export const trackSavedCardsHidden = percent => {
  track({
    eventAction: 'Data Hide - Saved Cards',
    eventLabel: `Saved cards ${percent}%`,
    eventValue: percent,
  });
};

export const trackTooltipDeepdive = () => {
  track({
    eventAction: 'Click - Tooltip Open',
    eventLabel: '',
  });
};

export { trackGoToLinks };
