import React from 'react';
import { Button } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';

import { titleCase } from 'common/utils/rzp-utils';
import { ButtonWidgetProps } from 'merchant/widgets/common/Button/types';
import { getActionWidgetIcon, makeLink } from 'merchant/widgets/common/utils';
import { track } from 'merchant/widgets/utils';

export const ButtonWidget: React.FC<ButtonWidgetProps> = ({
  title,
  action,
  icon,
  icon_position: iconPosition,
  properties,
  action_params: params,
  analyticsProperties,
}): JSX.Element | null => {
  const navigate = useNavigate();

  if (!action) return null;

  const isAbsoluteUrl = /^http/i.test(action);
  const navigationLink = !isAbsoluteUrl && makeLink(action, params);
  const { screen = '', ...rest } = analyticsProperties ?? {};

  const handleLinkClick = (e) => {
    e.stopPropagation();
    if (isAbsoluteUrl || navigationLink) {
      track({
        objectName: 'button',
        actionName: 'clicked',
        screen,
        properties: { ...rest, action, actionLabel: title },
      });
      if (navigationLink) {
        navigate(navigationLink);
      } else {
        window.open(action, '_blank');
      }
    }
  };

  return (
    <Button
      variant={properties?.variant ?? 'primary'}
      icon={getActionWidgetIcon(icon)}
      iconPosition={iconPosition}
      onClick={handleLinkClick}
    >
      {titleCase(title)}
    </Button>
  );
};
