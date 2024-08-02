import React from 'react';
import { makeSize } from '@razorpay/blade/utils';
import { connect } from 'react-redux';
import styled from 'styled-components';

import { LimitInfo } from './LimitInfo';

const Container = styled.div(
  ({ theme }) => `
  display: flex;
  flex-direction: column;
  margin: 0 ${makeSize(theme.spacing[6])};
  gap: ${makeSize(theme.spacing[5])};
  & > *:first-child {
    margin-top: ${makeSize(theme.spacing[5])};
  }
`,
);

const OdsBanners = ({ user }) => {
  if (!user.isOndemandSettlementEnabled) {
    return null;
  }

  return (
    <Container>
      <LimitInfo />
    </Container>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

const OdsWrapper = connect(mapStateToProps)(OdsBanners);

export { OdsWrapper as OdsBanners };
