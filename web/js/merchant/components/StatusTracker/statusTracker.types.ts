import { statusTrackerButtonStyles, STATUS_TRACKER_STATUS } from './constants';

type StatusTrackerButtonStylesT = keyof typeof statusTrackerButtonStyles;

type StatusTrackerButtonT = {
  label: JSX.Element | string;
  onClick: (params: unknown) => void;
  style?: StatusTrackerButtonStylesT;
};

type StatusTrackerStepsT = {
  title: JSX.Element | string;
  status: STATUS_TRACKER_STATUS;
  description?: JSX.Element | string;
  buttons?: StatusTrackerButtonT[];
  rightIllustration?: string;
  isActive?: boolean;
};
interface StatusType extends StatusTrackerStepsT {
  index: number;
  stepsLength: number;
}
type TrackerViewType = {
  stepsLength: number;
  currentSteps: StatusTrackerStepsT[];
  toggleView: (param?: unknown) => void;
  isViewMore: boolean;
  rightIllustration?: string;
};

/** @see The {@link https://docs.google.com/document/d/1DghSeQjubFj1Op47sH9MaSXSsE0j8IiE6e6mBOk4Yv8/edit?usp=sharing document } for more information */
type StatusTrackerPropsT = {
  title: JSX.Element | string;
  description: JSX.Element | string;
  steps: StatusTrackerStepsT[];
  rightIllustration?: string;
  onLoad?: (param?: unknown) => void;
  className?: string;
};

type TrackerLeftIllustrationType = {
  title: string | JSX.Element;
  description: string | JSX.Element;
};

export {
  StatusTrackerButtonStylesT,
  StatusTrackerButtonT,
  StatusTrackerStepsT,
  StatusTrackerPropsT,
  StatusType,
  TrackerViewType,
  TrackerLeftIllustrationType,
};
