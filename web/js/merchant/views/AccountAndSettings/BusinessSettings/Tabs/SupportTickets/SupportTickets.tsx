import React from 'react';
import { connect } from 'react-redux';
import TicketsContainer from 'merchant/views/TicketSupport/components/TicketsContainer';
import Tickets from 'merchant/views/TicketSupport/components/Tickets';

const SupportTickets = ({ user }) => {
  return user.isMobileSignupCareActive ? <TicketsContainer /> : <Tickets />;
};
const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

export default connect(mapStateToProps, null)(SupportTickets);
