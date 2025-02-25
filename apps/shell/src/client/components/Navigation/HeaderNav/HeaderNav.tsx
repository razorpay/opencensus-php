import React from 'react';
import { Box, UserIcon, BellIcon, TextInput, SearchIcon, Link } from '@razorpay/blade/components';

import { RazorpayLinesSvg } from './RazorpaySvgLines';
import { BrandImage } from './styled';
import { useStore } from '@federated/apps/shell/commonStore';

const HeaderNavigation = ({ brandImage = '' }) => {
  const mode = useStore((state) => state.session.mode);

  // Temp fn for checking sync
  const handleModeChange = () => {
    useStore.setState((state) => ({
      ...state,
      session: {
        ...state.session,
        mode: mode === 'live' ? 'test' : 'live',
      },
    }));
  };

  return (
    <Box position="relative" display="flex" height="56px" alignItems="center" width="100%">
      <Box position="absolute" bottom="-10px" left="0px" display={{ base: 'none', m: 'initial' }}>
        <RazorpayLinesSvg />
      </Box>
      <Box
        width={{ base: undefined, m: '264px' }}
        textAlign="center"
        display={{ base: 'none', m: 'initial' }}
      >
        <BrandImage src={brandImage} />
      </Box>
      <Box flex="1" display={{ base: 'none', m: 'flex' }} justifyContent="space-between">
        <Box width="400px">
          <TextInput
            leadingIcon={SearchIcon}
            label=""
            placeholder="Search for products, settings & more"
          />
        </Box>
        <Box display="flex" alignItems="center" gap="spacing.3">
          <Link onClick={handleModeChange} marginX="spacing.4">
            Switch Merchant ({mode})
          </Link>
          <BellIcon marginRight="spacing.5" color="surface.icon.gray.subtle" size="medium" />
          <UserIcon color="surface.icon.gray.subtle" size="medium" />
        </Box>
      </Box>
    </Box>
  );
};

export default HeaderNavigation;
