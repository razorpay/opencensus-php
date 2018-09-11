import track from 'merchantLA/containers/Home/ga';

export const trackGroupingChange = (grouping, sectionTitle) => {
  track({
    eventAction: 'Click - Grouping',
    eventLabel: `${grouping} from ${sectionTitle}`,
  });
};
