import track, { trackGoToLinks } from 'merchantLA/containers/Home/ga';

export const trackTabClick = (tabName, sectionTitle) => {
  track({
    eventAction: `Click - ${sectionTitle} Tab`,
    eventLabel: tabName,
  });
};

export const trackEntityClick = (tabName, sectionTitle) => {
  track({
    eventAction: `Open Details - ${tabName}`,
    eventLabel: `From ${sectionTitle}`,
  });
};

export { trackGoToLinks };
