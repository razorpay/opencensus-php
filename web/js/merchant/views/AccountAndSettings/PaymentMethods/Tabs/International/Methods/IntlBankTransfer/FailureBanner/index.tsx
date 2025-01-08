import React from 'react';
import { Alert } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { useFailureBanner, FailureBannerProps } from './states';

const FailureBanner = (props: FailureBannerProps) => {
  const { isLoading, banner, actions } = useFailureBanner(props);

  if (isLoading || !banner) {
    return null;
  }

  return (
    <Alert
      isFullWidth
      title={banner.title}
      color={banner.color}
      isDismissible={false}
      marginBottom="spacing.7"
      description={banner.description}
      actions={actions}
    />
  );
};

const mapStateToProps = ({ session }) => ({
  user: session.user,
});

export default connect(mapStateToProps)(FailureBanner);
