import { renderWidgetProps } from 'merchant/widgets/types';
import { subWidgetKeyToComponentMapping } from 'merchant/widgets/Carousel/mapping';
import { renderWidget } from 'merchant/widgets/utils';

export const getSubWidget = ({
  widget,
  isLoading = false,
  queryKey = [],
  analyticsProperties = {},
}: renderWidgetProps) =>
  renderWidget({
    widget,
    isLoading,
    queryKey,
    analyticsProperties,
    widgetMapping: subWidgetKeyToComponentMapping,
  });
