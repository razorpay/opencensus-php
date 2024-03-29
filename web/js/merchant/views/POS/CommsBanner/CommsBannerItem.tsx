import React from 'react';
import { Box, Button, Link, Text } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import { useLocation, useNavigate, useParams } from 'react-router-dom';

import { STATUS_ASSETS_MAPPING } from 'merchant/views/POS/constants';
import { getCommsAnalytics } from 'merchant/views/POS/helpers';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';
import { CommsBannerItemVariants, CommsItem } from 'merchant/views/POS/types';

import { CommsBannerItemConnctor, IconContainer, IconShadow } from './styles';

type Connector = {
  isHidden: boolean;
  variant: CommsBannerItemVariants;
};

interface STAGE extends Omit<CommsItem, 'description'> {
  description: JSX.Element | string | null;
}

type CommsBannerItem = {
  commsItem: STAGE;
  leftConnector: Connector;
  rightConnector: Connector;
  isLast: boolean;
};

const CommsBannerItem = ({
  commsItem,
  leftConnector,
  rightConnector,
  isLast,
}: CommsBannerItem): JSX.Element => {
  const { isMobile, matchedBreakpoint } = useBladeBreakpoints();
  const navigate = useNavigate();
  const isMobileOrTablet = isMobile || matchedBreakpoint === 'm';
  const { title, description, cta, status } = commsItem;

  const { pathname } = useLocation();
  const { productName } = useParams();

  const handleOnCTAClick = (url, name) => {
    const { l2FunnelStage, subSection } = getCommsAnalytics({ pathname, productName });
    analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
      label: name,
      section: 'Merchant Activation Status Bar',
      whatsAppUpdates: 'No',
      subSection,
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage,
    });
    if (url.startsWith('https://')) {
      window.location.assign(url);
    } else {
      navigate(url);
    }
  };

  return (
    <Box
      width="100%"
      maxWidth={{ base: '450px', l: '350px' }}
      display="flex"
      flexDirection={{ base: 'row', l: 'column' }}
      alignItems={{ base: 'stretch', l: 'center' }}
    >
      <Box
        height={{ base: 'auto', l: '30px' }}
        width="100%"
        display="flex"
        flexDirection={{ base: 'column', l: 'row' }}
        marginBottom={{ base: 'spacing.0', l: 'spacing.5' }}
        alignItems="center"
        maxWidth={{ base: '30px', l: '100%' }}
      >
        {!isMobileOrTablet ? (
          <CommsBannerItemConnctor
            aria-label={`${leftConnector.isHidden ? 'hidden' : 'visible'}-connector`}
            isHidden={leftConnector.isHidden}
            variant={leftConnector.variant}
          />
        ) : null}
        <Box>
          <IconShadow variant={STATUS_ASSETS_MAPPING?.[status]?.variant}>
            <IconContainer variant={STATUS_ASSETS_MAPPING?.[status]?.variant}>
              <Box
                height="16px"
                width="16px"
                backgroundColor={
                  status === 'pending' || !STATUS_ASSETS_MAPPING?.[status]?.icon
                    ? 'surface.background.gray.intense'
                    : 'transparent'
                }
                display="flex"
                alignItems="center"
                justifyContent="center"
              >
                {STATUS_ASSETS_MAPPING?.[status]?.icon}
              </Box>
            </IconContainer>
          </IconShadow>
        </Box>
        <CommsBannerItemConnctor
          aria-label={`${rightConnector.isHidden ? 'hidden' : 'visible'}-connector`}
          isHidden={rightConnector.isHidden}
          variant={rightConnector.variant}
          isMobileOrTablet={isMobileOrTablet}
        />
      </Box>
      <Box
        display="flex"
        flexDirection="column"
        alignItems={{ base: 'flex-start', l: 'center' }}
        paddingBottom={{ base: isLast ? 'spacing.0' : 'spacing.10', l: 'spacing.3' }}
        paddingLeft={{ base: 'spacing.5', l: 'spacing.0' }}
      >
        <Box width="100%" marginBottom="spacing.3" minHeight={{ base: '50px', l: '85px' }}>
          <Text
            textAlign={isMobileOrTablet ? 'left' : 'center'}
            marginBottom="spacing.3"
            size="large"
          >
            {title}
          </Text>
          <Box marginX={{ base: '0px', l: 'spacing.8' }} display="flex" justifyContent="center">
            {typeof description === 'string' ? (
              <Text textAlign={isMobileOrTablet ? 'left' : 'center'}>{description}</Text>
            ) : (
              description
            )}
          </Box>
        </Box>
        <Box display="flex" alignItems="center" gap="spacing.5">
          {cta
            ? cta.map(({ name, url, type }) => (
                <React.Fragment key={name}>
                  {name && type === 'link' ? (
                    <Link variant="button" onClick={() => handleOnCTAClick(url, name)}>
                      {name}
                    </Link>
                  ) : null}
                  {name && type === 'button' ? (
                    <Button size="small" onClick={() => handleOnCTAClick(url, name)}>
                      {name}
                    </Button>
                  ) : null}
                </React.Fragment>
              ))
            : null}
        </Box>
      </Box>
    </Box>
  );
};

export default CommsBannerItem;
