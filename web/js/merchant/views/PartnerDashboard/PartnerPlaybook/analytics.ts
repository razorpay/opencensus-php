import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import debounce from 'common/utils/debounce';

export const trackPageSectionCtaClicked = ({
  section,
  pageFold,
  folderName,
  folderDescription,
  title,
  description,
  ctaClicked,
}: {
  section: string;
  pageFold: number;
  folderName: string | null;
  folderDescription: string | null;
  title: string | null;
  description: string | null;
  ctaClicked: string;
}): void => {
  return analyticsTrackWithUserInfo({
    objectName: 'Partner Playbook Page Section Cta',
    actionName: 'Clicked',
    screen: section,
    properties: {
      section,
      pageFold,
      folderName,
      folderDescription,
      title,
      description,
      ctaClicked,
    },
  });
};

export const trackPageSectionReadSuccess = ({
  section,
  pageFold,
}: {
  section: string;
  pageFold: number;
}): void => {
  return analyticsTrackWithUserInfo({
    objectName: 'Partner Playbook Page Section Read',
    actionName: 'Success',
    screen: 'Partner Playbook',
    properties: {
      section,
      pageFold,
    },
  });
};

export const debouncedTrackPageSectionReadSuccess = debounce(trackPageSectionReadSuccess, 1000);

export const trackTopHeadingsClicked = ({ ctaClicked }: { ctaClicked: string }): void => {
  return analyticsTrackWithUserInfo({
    objectName: 'Partner Playbook Page Top Headings',
    actionName: 'Clicked',
    screen: 'Partner Playbook',
    properties: {
      ctaClicked,
    },
  });
};

export const trackSearchSectionClicked = (): void => {
  return analyticsTrackWithUserInfo({
    objectName: 'Partner Playbook Page Search Section',
    actionName: 'Clicked',
    screen: 'Partner Playbook',
    properties: {},
  });
};

export const trackSearchCtaClicked = ({ searchMessage }: { searchMessage: string }): void => {
  return analyticsTrackWithUserInfo({
    objectName: 'Partner Playbook Page Search Cta',
    actionName: 'Clicked',
    screen: 'Partner Playbook',
    properties: {
      searchMessage,
    },
  });
};
