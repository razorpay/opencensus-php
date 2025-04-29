import React from 'react';
import { Box, Text, Button, Heading, ArrowUpRightIcon, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { ANALYTICS_ONENAV } from '../constants';
import { analyticsTrack, getCommonAnalyticsProperties } from '@libs/shared-utils';
import { useConnectedNavigationStore } from '@federated/apps/shell/connected-navigation/connectedNavigationStore';

type Action = {
  title: string;
  properties: {
    variant?: 'primary' | 'secondary' | 'tertiary';
  };
  icon_position?: 'right' | 'left';
  action_params: {
    url: string;
  };
};

const GrowthPage = () => {
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedDeviceType === 'mobile';
  const { products } = useConnectedNavigationStore();

  const selectedProduct = (products as any)?.selectedProduct;
  const data = selectedProduct?.selectAction?.pageData || {};
  const title = data?.title;
  const description = data?.description;
  const actions = data?.actions;
  const imageSrc = data?.imageSrc;

  const handleButtonClick = (action: Action) => {
    // TODO: Need to verify once analytics changes are done (verified, @swapnil to remove this comment)
    const page = location.pathname?.replace(/[\/_-]/g, '');
    const analyticsInfo = {
      objectName: 'Growth Page CTA',
      actionName: 'Clicked',
      screen: ANALYTICS_ONENAV.SCREEN,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
        version: 'v1',
        page,
        bu_title: selectedProduct?.title,
        variant: action.properties.variant,
        title: action.title,
        experiment_name: ANALYTICS_ONENAV.EXPERIMENT_NAME,
      },
    };
    analyticsTrack(analyticsInfo);
    window.open(action.action_params.url, '_blank');
  };

  return (
    <Box
      display="flex"
      flexDirection={{ base: 'column', m: 'column', l: 'row' }}
      alignItems="center"
      justifyContent={{ base: 'center', m: 'center', l: 'space-between' }}
      paddingX={{ base: 'spacing.4' }}
      paddingY={{ base: 'spacing.4', m: '154px', l: '154px' }}
      backgroundColor={'surface.background.gray.intense'}
      maxWidth={{ base: '100%', m: '100%', l: '1200px' }}
      margin={{ base: 'spacing.0', m: 'spacing.0', l: ['spacing.0', 'auto'] }}
    >
      <Box
        order={{ base: 1, m: 1, l: 2 }}
        width="100%"
        maxWidth={{ base: '296px', m: '456px', l: '456px', xl: '456px' }}
        height="auto"
        marginBottom={{ base: 'spacing.6', m: 'spacing.6', l: 'spacing.0' }}
      >
        <img
          src={imageSrc}
          alt="Growth Page"
          style={{
            width: '100%',
            height: 'auto',
            objectFit: 'cover',
          }}
        />
      </Box>

      <Box
        order={{ base: 2, m: 2, l: 1 }}
        display="flex"
        flexDirection="column"
        justifyContent="center"
        alignItems={{ base: 'center', m: 'center', l: 'flex-start' }}
        textAlign={{ base: 'center', m: 'center', l: 'left' }}
        marginRight={{ base: 'spacing.0', m: '16px', l: '16px', xl: '16px' }}
        maxWidth={{ base: '100%', m: '100%', l: '584px' }}
      >
        <Heading
          as="h1"
          size={'2xlarge'}
          weight="semibold"
          marginBottom="spacing.2"
          color={'surface.text.gray.normal'}
        >
          {title}
        </Heading>
        <Text
          variant="body"
          weight={'medium'}
          size={isMobile ? 'large' : 'medium'}
          marginBottom="spacing.4"
          color={'surface.text.gray.muted'}
        >
          {description}
        </Text>
        <Box display="flex" gap="spacing.4" marginTop={{ base: '16px', l: '40px', m: '40px' }}>
          {actions.map((action: Action, index: number) => {
            return (
              <Button
                key={`${action.title}_${index}`}
                variant={action.properties?.variant}
                icon={ArrowUpRightIcon}
                iconPosition={index == 0 ? 'right' : action.icon_position}
                onClick={() => handleButtonClick(action)}
              >
                {action.title}
              </Button>
            );
          })}
        </Box>
      </Box>
    </Box>
  );
};

export default GrowthPage;
