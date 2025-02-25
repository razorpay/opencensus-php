import HeaderNavigation from './HeaderNav/HeaderNav';
import React from 'react';
import {Sidebar} from './Sidebar';
import { Box } from '@razorpay/blade/components';
import { Error } from '../Error';
import { IOneNavigationResponse } from '@apps/shell/src/client/widgets/Sidebar/types';
import { Loader } from '@apps/shell/src/client/components/Loader';
import { MASTER_CONFIG } from './masterConfig';
import { useQuery } from '@tanstack/react-query';

const Navigation = ({ children }): JSX.Element => {
  const { data, isLoading, isError } = useQuery<IOneNavigationResponse>({
    queryKey: ['master-config'],
    queryFn: () => {
      return new Promise((resolve) => setTimeout(() => resolve(MASTER_CONFIG), 2000));
    },
  });

  if (isLoading) {
    return <Loader />;
  }

  if (isError) {
    return <Error />;
  }

  return (
    <Box
      backgroundColor="surface.background.gray.moderate"
      height="100vh"
      display="flex"
      flexDirection="column"
    >
      <Box
        paddingX="spacing.4"
        backgroundColor={{
          base: 'surface.background.cloud.subtle',
          m: 'transparent',
        }}
      >
        <HeaderNavigation brandImage={data.one_nav_config.brand_image} />
      </Box>
      <Box
        marginX={{ base: 'spacing.0', m: 'spacing.4' }}
        borderRadius="large"
        overflow="hidden"
        flex="1"
        borderWidth={{ base: 'none', m: 'thin' }}
        borderColor={{ base: undefined, m: 'surface.border.gray.muted' }}
        elevation={{ base: 'none', m: 'midRaised' }}
        backgroundColor="surface.background.gray.intense"
        borderBottomWidth="none"
        borderBottomRightRadius="none"
        borderBottomLeftRadius="none"
      >
        <Box display="flex" flexDirection="row" position="relative" height="100%" gap="spacing.2">
          <Sidebar data={data} />
          <Box marginLeft={{ base: 'spacing.0', m: '256px' }} width="100%" overflowY="auto">
            {children}
          </Box>
        </Box>
      </Box>
    </Box>
  );
};

export default Navigation;
