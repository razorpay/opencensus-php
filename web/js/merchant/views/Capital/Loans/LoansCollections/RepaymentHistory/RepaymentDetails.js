import React from 'react';
import moment from 'moment';
import { connect } from 'react-redux';

import { withRouter } from 'common/deprecated/withRouter';
import Amount from 'common/ui/Amount';
import Spinner from 'common/ui/Spinner';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { computePrincipalAndInterest } from 'merchant/views/Capital/CashAdvance/utils';
import api from 'merchant/views/Capital/Loans/LoansCollections/api';
import { getCollectionMethod } from 'merchant/views/Capital/Loans/LoansCollections/util';
import { STATUS_LABELS, StatusPillClasses } from 'merchant/views/Capital/Loans/constants';
import { usePromise } from 'merchant/views/Capital/components/Await';

const RepaymentDetails = ({ id }) => {
  const { value: repayment, loading, error } = usePromise(api.getRepaymentDetails({ id }));

  const getRepaymentMeta = () => {
    const { status, created_at } = repayment.data;
    const createdAt = created_at ? moment.unix(created_at).format('LLL') : '--';
    const collectionMethod = getCollectionMethod(repayment.data);

    const data = [
      {
        label: 'Repayment Status',
        value: (
          <span className={`status-pill status-pill-${StatusPillClasses[status]}`}>
            {STATUS_LABELS[status]}
          </span>
        ),
      },
      {
        label: 'Collected At',
        value: <span>{createdAt}</span>,
      },
      {
        label: 'Repaid Via',
        value: <span>{collectionMethod}</span>,
      },
    ];

    return (
      <React.Fragment>
        <div className="block-note purple m-b">
          <strong>Loan Repayment Details</strong>
        </div>
        {data.map(({ label, value }) => (
          <EntityDetailRow key={label} label={label}>
            {value}
          </EntityDetailRow>
        ))}
      </React.Fragment>
    );
  };

  const getRepaymentBreakup = () => {
    const { amount = 0, breakups = [] } = repayment.data;
    const { BALANCE_TYPE_PRINCIPAL = 0, BALANCE_TYPE_INTEREST = 0 } =
      computePrincipalAndInterest(breakups);

    const data = [
      {
        label: 'Total Repaid Amount',
        value: <Amount value={Number(amount)} />,
      },
      {
        label: 'Principal Repaid',
        value: <Amount value={BALANCE_TYPE_PRINCIPAL} />,
      },
      {
        label: 'Interest Repaid',
        value: <Amount value={BALANCE_TYPE_INTEREST} />,
      },
    ];

    return (
      <div style={{ marginTop: '20px' }}>
        <div className="block-note purple m-b">
          <strong>Repayment Breakup</strong>
        </div>
        {data.map(({ label, value }) => (
          <EntityDetailRow key={label} label={label}>
            {value}
          </EntityDetailRow>
        ))}
      </div>
    );
  };

  const renderContent = () => {
    return (
      <div className="panel panel-default SliderPanel">
        <div className="panel-heading">
          <div className="settlement-actions-wrapper p-r-32">
            <span className="flex-occupy no-margin">Repayment Id: {id}</span>
          </div>
        </div>
        <div className="SliderPanel__Body">
          <div className="list-group details-row-container">
            <div className="m-all p-all">
              {getRepaymentMeta()}
              {getRepaymentBreakup()}
            </div>
          </div>
        </div>
      </div>
    );
  };

  return (
    <div className="content-wrapper content-sm txn-details CA--entity-details loan-repayment-details-modal">
      {loading ? (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      ) : error ? (
        <div
          className="flex"
          style={{
            justifyContent: 'center',
            alignItems: 'center',
            fontSize: '20px',
            marginTop: '150px',
          }}
        >
          <i className="i i-info-circle text-danger" style={{ marginRight: '4px' }} />
          <p>Oh snap! Something went wrong.</p>
        </div>
      ) : (
        renderContent()
      )}
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(withRouter(RepaymentDetails));
