import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import { fetchCreditNote } from 'merchant/modules/invoices/details';

import CreditNoteDetails from 'merchant/components/Invoices/CreditNoteDetails';

@withRouter
@connect(null, {
  fetchCreditNote,
})
export default class CreditNoteDetailsContainer extends React.Component {
  constructor() {
    super();

    this.state = {
      isLoading: true,
      creditNote: {},
    };
  }

  componentDidMount() {
    // this.props.fetchCreditNote(this.props.id);
    setTimeout(() => {
      this.setState({
        isLoading: false,
        creditNote: {
          id: 'crnt_CdJ6bpQFE0Kgfi',
          customer_id: 'CSgSa6pi6wvAfa',
          merchant_id: '10000000000000',
          name: 'Test credit note',
          description: null,
          amount: 5000,
          amount_available: 5000,
          amount_refunded: 0,
          amount_allocated: 0,
          currency: 'INR',
          created_at: 1559561988,
          updated_at: 1559561988,
        },
      });
    }, 500);
  }

  render() {
    return <CreditNoteDetails {...this.state} />;
  }
}
