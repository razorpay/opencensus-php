import styled from 'styled-components';
import { analyticsTrackWithUserInfo } from '@libs/shared-utils';

export const RATING_OPTIONS = [1, 2, 3, 4, 5];

export const trackFeedbackInput = (value: string) => {
  analyticsTrackWithUserInfo({
    objectName: 'rtux feedback form',
    actionName: 'feedback input',
    screen: 'homepage',
    properties: {
      value,
    },
  });
};

export const trackTaskCompletion = (value: string) => {
  analyticsTrackWithUserInfo({
    objectName: 'rtux feedback form',
    actionName: 'task completion selected',
    screen: 'homepage',
    properties: {
      value,
    },
  });
};

export const trackRating = (value: number) => {
  analyticsTrackWithUserInfo({
    objectName: 'rtux feedback form',
    actionName: 'rating selected',
    screen: 'homepage',
    properties: {
      value,
    },
  });
};

export const trackSubmit = ({ rating, taskCompletion, feedback }) => {
  analyticsTrackWithUserInfo({
    objectName: 'rtux feedback form',
    actionName: 'submit',
    screen: 'homepage',
    properties: {
      rating,
      taskCompletion,
      feedback,
    },
  });
};

export const trackDismiss = ({ rating, taskCompletion, feedback }) => {
  analyticsTrackWithUserInfo({
    objectName: 'rtux feedback form',
    actionName: 'dismiss',
    screen: 'homepage',
    properties: {
      rating,
      taskCompletion,
      feedback,
    },
  });
};

export const trackFeedbackFormLoad = () => {
  analyticsTrackWithUserInfo({
    objectName: 'rtux feedback form',
    actionName: 'load',
    screen: 'homepage',
  });
};

export const rtuxFeedbackFormKeys = {
  showWidget: 'show-rtux-feedback-form',
  seenCount: 'rtux-homepage-seen-count',
  localStorageUpdated: 'rtux-homepage-local-storage-updated',
};

export const RatingOption = styled.div`
  cursor: pointer;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
`;
