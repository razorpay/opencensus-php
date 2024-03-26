import { TrackParameters } from 'merchant/widgets/types';

export interface LinkWidgetProps {
  title: string;
  action?: string;
  icon: string;
  icon_position: 'right' | 'left';
  action_params?: Record<string, any>;
  analyticsProperties?: TrackParameters;
}
