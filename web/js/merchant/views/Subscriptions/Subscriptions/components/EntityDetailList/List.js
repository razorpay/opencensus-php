import React from 'react';
import Alert from 'common/ui/Forms/Alert';
import EntityDetailRow from './Row';
import Time from 'common/ui/Time';
import Amount from 'common/ui/Amount';

//TODO: Make this component generalized as per requirement later. Currently only used for subscriptions details view(invoice list)
/*
 // Usage: Check slider/details view of payments, plans, etc.
 // Constraint: 1. Pass props 'progressLoader' to <Table> only when progress loaders is shown instead of <Spinner>.
 2. Passing props 'customClass' is advised so as to have more control on `progress loader` length
 */

export default class EntityDetailList extends React.Component {
  constructor(props) {
    super();

    this.INVOICE_MAP = {};
    this.CREDIT_NOTE_MAP = {};

    this.state = {
      curLimit: props.moreAfterlimit,
    };
  }

  countHaltedInvoices(items) {
    return items.reduce((sum, item) => {
      return sum + (item.subscription_status === 'halted' ? 1 : 0);
    }, 0);
  }

  getRowList() {
    const {
      loading,
      items,
      goToLink,
      activeSecEntityId,
      onManualAttempt,
      subscriptionchargeAt,
      subscriptionStatus,
      subscriptionType,
      mode,
      subscriptionId,
      paymentMethod,
    } = this.props;

    if (loading) {
      return [<EntityDetailRow key="loading" item={{}} loading={loading} />];
    }

    const list = [];

    let limit = this.state.curLimit; // Show curLimit number of loaders. Also, default curLimit rows unless items.length is lesser
    if (!loading && items.length) {
      limit = items.length < this.state.curLimit ? items.length : limit;
    }

    let totalHaltedInvoiceToCheck = this.countHaltedInvoices(items);
    let isChargeAttemptFailed = false;
    for (let index = 0; index < limit; index++) {
      let item = {};
      if (items.length) {
        item = items[index]; // 0th is latest item
      }

      let isInvoiceWithAttemptsFailed = 0;

      if (item.subscription_status === 'halted' && item.status === 'issued') {
        isInvoiceWithAttemptsFailed =
          index < items.length - 1 && items[index + 1].status === 'paid' ? 1 : 2;
      } else if (totalHaltedInvoiceToCheck) {
        if (item.subscription_status === 'halted') {
          isInvoiceWithAttemptsFailed = totalHaltedInvoiceToCheck === 1 ? 1 : 2;
          totalHaltedInvoiceToCheck--;
        }
      }

      let isFirstInvoiceUpfront = false;
      let isFirstInvoiceRecurring = true;

      if (subscriptionType === 2) {
        // Start date : future, Invoice: Upfront
        isFirstInvoiceUpfront = true;
        isFirstInvoiceRecurring = false;
      } else if (subscriptionType === 3) {
        // Start date : immediate, Invoice: Upfront
        isFirstInvoiceUpfront = true;
      }

      // If 1st invoice is not recurring, newer invoices will have 1 lesser index than otherwise
      let recurringInvoiceIndex;
      if (isFirstInvoiceRecurring) {
        recurringInvoiceIndex = items.length ? items.length - index : index;
      } else {
        recurringInvoiceIndex = items.length ? items.length - index - 1 : index;
      }

      // Check if 1st(last in array) invoice is upfront invoice
      const isUpfrontInvoice = index === items.length - 1 ? isFirstInvoiceUpfront : false; // Set true for 1st invoice if it's upfront

      // Check for the latest invoice with status 'issued' and if any attempts failed
      // (To set authAttempts only for latest invoice for now)
      if (item.status === 'issued' && this.props.authAttempts > 0 && !isChargeAttemptFailed) {
        isChargeAttemptFailed = true;
      }

      if (item.id === 'inv_upcoming') {
        this.INVOICE_MAP.upcoming = index;
      } else {
        this.INVOICE_MAP[item.created_at] = index;
      }

      list.push(
        <EntityDetailRow
          mode={mode}
          paymentMethod={paymentMethod}
          key={index}
          item={item}
          loading={loading}
          goToLink={goToLink}
          isUpfront={isUpfrontInvoice}
          index={recurringInvoiceIndex}
          subscriptionId={subscriptionId}
          onManualAttempt={onManualAttempt}
          subscriptionType={subscriptionType}
          activeSecEntityId={activeSecEntityId}
          subscriptionStatus={subscriptionStatus}
          subscriptionchargeAt={subscriptionchargeAt}
          isInvoiceWithAttemptsFailed={isInvoiceWithAttemptsFailed}
          authAttempts={isChargeAttemptFailed ? this.props.authAttempts : null}
        />,
      );
    }

    return list;
  }

  getCreditNotesRow = () => {
    const { creditNotes, goToLink } = this.props;

    return creditNotes.map((creditNote, index) => {
      this.CREDIT_NOTE_MAP[creditNote.created_at] = index;

      return (
        <div
          class="entity-detail-row clickable credit-note-row"
          onClick={() => goToLink(creditNote.id, index)}
          key={index}
        >
          <div class="row-item content">
            <i class="i i-replay item-left" />
            <div className="item-right">
              <div class="detail-row">
                <div class="row-element left">
                  <Time value={creditNote.created_at} format="MMM DD, YYYY" />
                </div>
                <span class="row-element right">
                  <Amount currency={creditNote.currency} value={creditNote.amount} />
                </span>
              </div>
              <div class="detail-row">
                <span class="label--secondary">Refund due to subscription update.</span>
              </div>
            </div>
          </div>
        </div>
      );
    });
  };

  mergeCreditNotesRows = () => {
    const { items, creditNotes, loading } = this.props;
    const rowList = this.getRowList();
    const creditNoteList = this.getCreditNotesRow();

    if (loading) {
      return rowList;
    }

    const createdAtList = [...creditNotes, ...items]
      .map((note) => note.created_at)
      .sort()
      .reverse();

    const components = [];
    if (this.INVOICE_MAP.upcoming >= 0) {
      const upcomingInvoice = rowList[this.INVOICE_MAP.upcoming];

      components.push(upcomingInvoice);

      delete this.INVOICE_MAP.upcoming;
    }

    createdAtList.forEach((id) => {
      const creditNoteLoc = this.CREDIT_NOTE_MAP[id];
      const invoiceLoc = this.INVOICE_MAP[id];

      if (creditNoteLoc >= 0) {
        components.push(creditNoteList[creditNoteLoc]);

        delete this.CREDIT_NOTE_MAP[id];
      }

      if (invoiceLoc >= 0) {
        components.push(rowList[invoiceLoc]);

        delete this.INVOICE_MAP[id];
      }
    });

    return components;
  };

  render() {
    const { title, subTitle, error, loading, items } = this.props;
    let showBtn = null;

    // 'Show More' btn is visible only when items are available and length of items is more than limit set by parent
    if (
      !loading &&
      items.length &&
      items.length > this.state.curLimit &&
      (this.state.curLimit !== 12 || this.state.curLimit !== items.length)
    ) {
      showBtn = (
        <button
          class="show-btn primary-link"
          style={{ display: 'block', margin: '0 auto' }}
          onClick={() =>
            this.setState({
              curLimit: items.length > 12 ? 12 : items.length,
            })
          }
        >
          Show All <i class="i i-chevron-down" />
        </button>
      );
    }

    let rowList = this.mergeCreditNotesRows();

    if (!loading && !items.length) {
      rowList = <h4 class="empty-table-message">{`No '${title}' Found!`}</h4>;
    }

    return (
      <div class="entity-detail-list">
        <div class="list-heading">
          <span class="label--primary">{title}</span>
          <span class="label--secondary">{subTitle}</span>
        </div>
        <div class="list-content">
          {error && <Alert type="error" message={error} />}
          {rowList}
        </div>
        {showBtn}
      </div>
    );
  }
}
