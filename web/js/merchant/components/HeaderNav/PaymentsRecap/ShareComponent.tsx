import React from 'react';
import { Box, DownloadIcon, CopyIcon } from '@razorpay/blade/components';
import FacebookIcon from 'assets/razorpay-rewind/icons/facebook.svg';
import TwitterIcon from 'assets/razorpay-rewind/icons/twitter.svg';

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
    Icon: () => <CopyIcon color="brand.gray.700.lowContrast" size="large" />,
    action: 'copy',
  },
  {
    Icon: () => <DownloadIcon color="brand.gray.700.lowContrast" size="large" />,
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
      padding="spacing.5"
      alignItems="center"
      justifyContent="space-between"
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
