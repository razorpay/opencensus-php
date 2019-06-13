import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import { showNotification } from 'rzp/modules/notifications';

import { fetchCreditNote } from 'merchant/modules/invoices/details';

import CreditNoteDetails from 'merchant/components/Invoices/CreditNoteDetails';

@withRouter
@connect(null, {
  fetchCreditNote,
  showNotification,
})
export default class CreditNoteDetailsContainer extends React.Component {
  constructor(props) {
    super();

    this.state = {
      isLoading: true,
      creditNote: {},
    };
  }

  componentDidMount() {
    this.props
      .fetchCreditNote(this.props.id)
      .then(resp => {
        this.setState({
          creditNote: resp,
        });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  }

  render() {
    return <CreditNoteDetails {...this.state} />;
  }
}
