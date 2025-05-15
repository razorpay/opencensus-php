import React from 'react';
import { connect } from 'react-redux';
import TicketsContainer from 'merchant/views/TicketSupport/components/TicketsContainer';
import Tickets from 'merchant/views/TicketSupport/components/Tickets';
import { useToast } from '@razorpay/blade/components';

const SupportTickets = ({ user }) => {
  const toast = useToast();

  return user.isMobileSignupCareActive ? <TicketsContainer /> : <Tickets toast={toast} />;
};
const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

export default connect(mapStateToProps, null)(SupportTickets);
