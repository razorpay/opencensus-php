import React from 'react';
import { connect } from 'react-redux';

import { AsyncBtn } from 'common/new-ui/Button';

import GoogleIcon from 'assets/google-icon.svg';

const GoogleSignInButton = ({ onClick, isLoading }: any): JSX.Element => {
  return (
    <AsyncBtn.Primary
      onClick={onClick}
      type="button"
      style={{ marginRight: 0 }}
      isPending={isLoading}
    >
      <img src={GoogleIcon} alt="icon" style={{ marginRight: '16px' }} />
      <span style={{ fontWeight: 600 }}>Sign in with Google</span>
    </AsyncBtn.Primary>
  );
};

const mapStateToProps = (state: Record<string, any>) => ({
  isLoading: state.magicAnalyticsSettings.isLoading.fetchOauth,
});

export default connect(mapStateToProps, null)(GoogleSignInButton);
