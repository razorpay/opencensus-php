import React from 'react';
import { connect } from 'react-redux';
import { Switch, Link } from 'react-router-dom';
import BatchListContainer from 'merchant/views/PaymentPages/BatchUpload/List';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';

const BatchDetailsContainer = (props) => {
  const { user, match } = props;
  const id = match?.params?.id;
  const title = match?.params?.title ?? 'Title';
  return (
    <tabbed-container>
      <header id="link-header">
        <div className="batch-payment-pages">
          <Link to="/paymentpages/batchpaymentpages">
            <i className="i i-arrow-back" /> Batch Payment Pages
          </Link>
          <Link to="/paymentpages/batchpaymentpages">
            <i className="i i-chevron-right" /> {title}
          </Link>
          <i className="i i-chevron-right" /> Batch Details
        </div>
      </header>

      <content>
        <ErrorBoundary resetOnProps>
          <Switch>
            <ShowWhenRoute
              path="/paymentpages/batchuploads/:id/:title"
              component={BatchListContainer}
              additionalCondition={() => user?.isPaymentPageFileUploadEnabled}
              id={id}
            />
          </Switch>
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
