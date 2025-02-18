import { Theme } from '@razorpay/blade/components';

interface GetWidgetContentTransitionParams {
  theme: Theme;
  id: string;
  selectedViewId: string;
}

export const getWidgetContentTransition = ({
  theme,
  id,
  selectedViewId,
}: GetWidgetContentTransitionParams) => {
  const delay = id !== selectedViewId ? `${theme.motion.duration.quick}ms` : '0s';
  const duration =
    id !== selectedViewId
      ? `${theme.motion.duration.xmoderate}ms`
      : `${theme.motion.duration.quick}ms`;
  const timing = theme.motion.easing.standard;
  return `all ${duration} ${timing} ${delay}`;
};
