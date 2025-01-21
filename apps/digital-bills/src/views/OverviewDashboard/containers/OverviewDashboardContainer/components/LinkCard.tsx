import React from 'react';
import { Box, Link, ArrowRightIcon } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';

type LinkCardProps = {
  label: string;
  href: string;
};

const LinkCard = ({ label, href }: LinkCardProps): React.ReactElement => {
  const navigate = useNavigate();
  const handleRouteChange = () => navigate(href);

  return (
    <Box marginY="spacing.8" paddingX="spacing.7">
      <Link
        variant="button"
        size="large"
        icon={ArrowRightIcon}
        iconPosition="right"
        onClick={handleRouteChange}
      >
        {label}
      </Link>
    </Box>
  );
};

export default LinkCard;
