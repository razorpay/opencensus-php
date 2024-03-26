import { ChangeType } from 'merchant/widgets/InsightsChart/subwidgets/InsightItem/types';

interface typeProps {
  variant: 'increase' | 'decrease';
}
export interface ArrowProps extends typeProps {
  fill: string;
}

export interface ChangeProps extends typeProps {
  text: string;
  isInverted: boolean;
  type: ChangeType;
}
