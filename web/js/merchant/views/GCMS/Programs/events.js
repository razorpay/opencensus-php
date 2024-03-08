import { track } from 'merchant/views/GCMS/shared/analytics';

const OBJECT_NAMES = {
  PROGRAM: 'Program',
  PROGRAMS_CARD: 'Programs Card',
  PROGRAMS_DETAILS: 'Programs Details',
};

export const trackProgramsPageLoadSuccess = () => {
  track({
    objectName: OBJECT_NAMES.PROGRAM,
    screen: 'ProgramsList',
    actionName: 'Page Load Success',
  });
};

export const trackProgramsCardClicked = ({ programId, programName }) => {
  track({
    objectName: OBJECT_NAMES.PROGRAMS_CARD,
    screen: 'ProgramsList',
    actionName: 'Clicked',
    properties: {
      programId,
      programName,
    },
  });
};

export const trackProgramsDetailsPageLoadSuccess = ({ programId, programName }) => {
  track({
    objectName: OBJECT_NAMES.PROGRAMS_DETAILS,
    screen: 'ProgramDetails',
    actionName: 'Page Load Success',
    properties: {
      programId,
      programName,
    },
  });
};
