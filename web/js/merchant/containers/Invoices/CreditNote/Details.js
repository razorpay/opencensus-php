import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import { showNotification } from 'rzp/modules/notifications';

import { fetchCreditNote } from 'merchant/modules/invoices/details';

import CreditNoteDetails from 'merchant/components/Invoices/CreditNoteDetails';

@withRouter
@connect(null, {
  showNotification,
})
export default class CreditNoteDetailsContainer extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      errors: [],
      isLoading: true,
      creditNote: {},
      statusMsg: {},
    };
  }

  componentDidMount() {
    fetchCreditNote(this.props.credit_note_id)
      .then(resp => {
        this.setState({
          creditNote: resp.data,
          isLoading: false,
        });
      })
      .catch(({ errors }) => {
        this.setState({
          statusMsg: {
            type: 'error',
            message: errors,
          },
          isLoading: false,
        });
      });
  }

  render() {
    return <CreditNoteDetails {...this.state} />;
  }
}
