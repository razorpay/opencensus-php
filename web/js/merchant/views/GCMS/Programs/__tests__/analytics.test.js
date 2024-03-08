import * as analytics from 'common/utils/analytics';
import {
  trackProgramsPageLoadSuccess,
  trackProgramsDetailsPageLoadSuccess,
  trackProgramsCardClicked,
} from 'merchant/views/GCMS/Programs/events';

import { programsResponse } from './mocks/fixtures';
const trackSpy = jest.spyOn(analytics, 'analyticsTrack');

const commonProperties = {
  properties: {},
};

describe('Tests for analytics functions', () => {
  const sectionProperties = {
    location: 'GCMS',
    sessionId: 'not available',
    validity: 'not available',
  };

  const programId = programsResponse.data.items[0].id;
  const programName = programsResponse.data.items[0].name;

  test('should call trackProgramsPageLoadSuccess with correct parameters', () => {
    trackProgramsPageLoadSuccess();

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Program',
      screen: 'ProgramsList',
      actionName: 'Page Load Success',
      properties: {
        ...sectionProperties,
      },
    });
  });

  test('should call trackProgramsCardClicked with correct parameters', () => {
    trackProgramsCardClicked({ programId, programName });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Programs Card',
      screen: 'ProgramsList',
      actionName: 'Clicked',
      properties: {
        ...sectionProperties,
        programId,
        programName,
      },
    });
  });

  test('should call trackProgramsDetailsPageLoadSuccess with correct parameters', () => {
    trackProgramsDetailsPageLoadSuccess({ programId, programName });

    expect(trackSpy).toHaveBeenCalledWith({
      ...commonProperties,
      objectName: 'Programs Details',
      screen: 'ProgramDetails',
      actionName: 'Page Load Success',
      properties: {
        ...sectionProperties,
        programId,
        programName,
      },
    });
  });
});
