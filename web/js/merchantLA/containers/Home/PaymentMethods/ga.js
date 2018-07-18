import track from 'merchantLA/containers/Home/ga';

function getNameFromHierarchy(hierarchy, namesArr = []) {
  if (!hierarchy) {
    return namesArr.reverse().join(' > ');
  }

  namesArr.push(hierarchy.displayText);

  return getNameFromHierarchy(hierarchy.parent, namesArr);
}

const trackTreemapEvents = (hierarchy, eventAction) => {
  track({
    eventAction,
    eventLabel: getNameFromHierarchy(hierarchy),
  });
};

export const trackTreemapClick = hierarchy =>
  trackTreemapEvents(hierarchy, 'Click - Treemap tile');

export const trackBreadcrumbClick = hierarchy =>
  trackTreemapEvents(hierarchy, 'Click - Breadcrumb');
