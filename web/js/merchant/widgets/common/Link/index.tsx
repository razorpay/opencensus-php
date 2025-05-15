import React from 'react';
import { Link } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';

import { titleCase } from 'common/utils/rzp-utils';
import { LinkWidgetProps } from 'merchant/widgets/common/Link/types';
import { getActionWidgetIcon, makeLink } from 'merchant/widgets/common/utils';
import { track } from 'merchant/widgets/utils';

export const LinkWidget: React.FC<LinkWidgetProps> = ({
  title,
  action,
  icon,
  icon_position: iconPosition,
  color,
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
        objectName: 'link',
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
    <Link
      variant={properties?.variant || 'anchor'}
      icon={getActionWidgetIcon(icon)}
      iconPosition={iconPosition}
      onClick={handleLinkClick}
      color={color || 'primary'}
    >
      {titleCase(title)}
    </Link>
  );
};
