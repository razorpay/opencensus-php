import React from 'react';
import { ArrowLeftIcon, Link, LinkProps } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';

const BackToMarketplaceLink = (
  props: Pick<LinkProps, 'color' | 'alignSelf' | 'marginTop'>,
): JSX.Element => {
  const navigate = useNavigate();
  return (
    <Link
      variant="button"
      size="medium"
      icon={ArrowLeftIcon}
      iconPosition="left"
      onClick={(): void => navigate('/rize-marketplace')}
      {...props}
    >
      Back to marketplace
    </Link>
  );
};

export default BackToMarketplaceLink;
