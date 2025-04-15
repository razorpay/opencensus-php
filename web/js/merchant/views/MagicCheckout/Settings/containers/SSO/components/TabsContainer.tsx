import React from 'react';

import { Link, VideoIcon } from '@razorpay/blade/components';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import NavContainer from 'merchant/views/MagicCheckout/common/components/NavContainer';
import { SSO_SETTINGS_ROUTES } from 'merchant/views/MagicCheckout/Settings/containers/SSO/routes';
import { VideoGuideWrapper } from 'merchant/views/MagicCheckout/Settings/containers/SSO/styled';

const TabsContainer: React.FC = () => {
  const path = '/magic/settings/sso';
  const additionalStyles = {
    container: {
      marginTop: '0px',
      paddingTop: '0px',
      border: 'none',
    },
    header: {
      borderTop: 'none',
      borderLeft: 'none',
      borderRight: 'none',
    },
    content: {
      border: 'none',
    },
  };

  // TODO: Add video source and unhide in phase II
  return (
    <SuspenseWithLoader type="center">
      <NavContainer navItems={SSO_SETTINGS_ROUTES} basePath={path} additionalStyles={additionalStyles}>
        <VideoGuideWrapper>
          <Link
            icon={VideoIcon}
            iconPosition="left"
            rel="noreferrer noopener"
            target="_blank"
            variant="anchor"
          >
            Watch Video Guide
          </Link>
        </VideoGuideWrapper>
      </NavContainer>
    </SuspenseWithLoader>
  );
};

export default TabsContainer;
