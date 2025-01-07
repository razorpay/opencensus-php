import React from 'react';
import { Box, DownloadIcon, CopyIcon } from '@razorpay/blade/components';
import FacebookIcon from 'assets/razorpay_rewind/icons/facebook.svg';
import TwitterIcon from 'assets/razorpay_rewind/icons/twitter.svg';
import LinkedInIcon from 'assets/razorpay_rewind/icons/linkedIn.svg';

import { StyledIcon } from './styled';

const shareIcons = [
  {
    Icon: () => <img src={TwitterIcon} />,
    action: 'twitter',
  },
  {
    Icon: () => <img src={FacebookIcon} />,
    action: 'facebook',
  },
  {
    Icon: () => <img src={LinkedInIcon} />,
    action: 'linkedin',
  },
  {
    Icon: () => <CopyIcon color="surface.icon.gray.normal" size="large" />,
    action: 'copy',
  },
  {
    Icon: () => <DownloadIcon color="surface.icon.gray.normal" size="large" />,
    action: 'download',
  },
];

const ShareComponent: React.FC<{
  handleShareAction: (action: string) => void;
}> = ({ handleShareAction }) => {
  return (
    <Box
      display="flex"
      flexDirection="row"
      paddingX="spacing.11"
      paddingY={'spacing.5'}
      alignItems="center"
      justifyContent="space-around"
    >
      {shareIcons.map(({ Icon, action }) => (
        <StyledIcon key={action} onClick={() => handleShareAction(action)}>
          <Icon />
        </StyledIcon>
      ))}
    </Box>
  );
};

export default ShareComponent;
