import React from 'react';
import { connect } from 'react-redux';
import { Routes, Link, Route } from 'react-router-dom';
import BatchListContainer from 'merchant/views/PaymentPages/BatchUpload/List';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';

import { BATCH_PAYMENT_PAGES_BASE_URL } from 'merchant/views/PaymentPages/PaymentPages/constants';
import { RouteGuard } from 'merchant/components/ShowWhen';

const BatchDetailsContainer = (props) => {
  const { user, match } = props;
  const id = match?.params?.id;
  const title = match?.params?.title ?? 'Title';

  return (
    <tabbed-container>
      <header id="link-header">
        <div className="batch-payment-pages">
          <Link to={BATCH_PAYMENT_PAGES_BASE_URL}>
            <i className="i i-arrow-back" /> Batch Payment Pages
          </Link>
          <Link to={`${BATCH_PAYMENT_PAGES_BASE_URL}/${id}/payments#batchpaymentpages`}>
            <i className="i i-chevron-right" /> {title}
          </Link>
          <i className="i i-chevron-right" /> Batch Details
        </div>
      </header>

      <content>
        <ErrorBoundary resetOnProps>
          <Routes>
            <Route
              path="*"
              element={
                <RouteGuard additionalCondition={() => user?.isPaymentPageFileUploadEnabled}>
                  <BatchListContainer id={id} />
                </RouteGuard>
              }
            />
          </Routes>
        </ErrorBoundary>
      </content>
    </tabbed-container>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

export default connect(mapStateToProps, null)(BatchDetailsContainer);
