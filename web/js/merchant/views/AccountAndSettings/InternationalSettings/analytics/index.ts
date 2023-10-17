import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';

const FIRS_DOWNLOAD_POPUP = 'firs download popup';
const FIRS_FILE_DOWNLOAD = 'firs file download';
const INTERNAL_FIRS_REQUEST = 'internal firs request';

const actions = {
  OPENED: 'opened',
  CLOSED: 'closed',
  CLICKED: 'clicked',
  RESPONSE: 'response',
};

const track = ({ properties = {}, ...args }): void => {
  analyticsTrack({
    screen: 'Account & Settings',
    ...args,
    properties: {
      ...getCommonSegmentProperties(window.rzp_user),
      section: 'International payments settings',
      subSection: 'Foreign inward remittance statement',
      ...properties,
    },
  });
};

export const trackDownloadPopupOpened = (month: string, year: number): void => {
  track({
    objectName: FIRS_DOWNLOAD_POPUP,
    actionName: actions.OPENED,
    properties: { month, year },
  });
};

export const trackDownloadPopupClosed = (month: string, year: number): void => {
  track({
    objectName: FIRS_DOWNLOAD_POPUP,
    actionName: actions.CLOSED,
    properties: { month, year },
  });
};

export const trackDownloadFirsClicked = (month: string, year: number, fileType: string): void => {
  track({
    objectName: FIRS_FILE_DOWNLOAD,
    actionName: actions.CLICKED,
    properties: { month, year, fileType },
  });
};

export const trackDownloadFirsResponse = (
  month: string,
  year: number,
  fileType: string,
  status: string,
): void => {
  track({
    objectName: FIRS_FILE_DOWNLOAD,
    actionName: actions.RESPONSE,
    properties: { month, year, fileType, status },
  });
};

export const trackRequestFirsButtonClick = (month: string, year: number): void => {
  track({
    objectName: INTERNAL_FIRS_REQUEST,
    actionName: actions.CLICKED,
    properties: { month, year },
  });
};

export const trackRequestFirsResponse = (month: string, year: number, status: string): void => {
  track({
    objectName: INTERNAL_FIRS_REQUEST,
    actionName: actions.RESPONSE,
    properties: { month, year, status },
  });
};
