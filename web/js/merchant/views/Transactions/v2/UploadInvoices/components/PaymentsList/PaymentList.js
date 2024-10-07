import React from 'react';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';

import { withRouter } from 'common/deprecated/withRouter';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchExportPayments as fetchAll } from 'merchant/reducers/collection';
import PaymentsListFilter from 'merchant/views/Transactions/v2/UploadInvoices/components/PaymentsListFilter';
import PaymentTable from 'merchant/views/Transactions/v2/UploadInvoices/components/PaymentsTable';
import { onPaginate, onSearch } from 'merchant/views/Transactions/v2/common/utils';

const PaymentListWrapper = (props) => {
  const navigate = useNavigate();
  return <PaymentList navigate={navigate} {...props} />;
};

class PaymentList extends ListContainer {
  render() {
    const { loading, history } = this.props;
    const { count, skip } = this.state;

    return (
      <>
        <PaymentsListFilter onSubmit={onSearch(history)} loading={loading} />
        <PaymentTable
          count={count}
          skip={skip}
          paginate={onPaginate(this.paginate)}
          {...this.props}
        />
      </>
    );
  }
}

export default withRouter(
  connect(
    (state) => ({
      ...state.exportPayments,
    }),
    { fetchAll },
  )(PaymentListWrapper),
);
