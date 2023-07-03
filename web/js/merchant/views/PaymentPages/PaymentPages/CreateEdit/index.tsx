import React, { useState } from 'react';
import { connect } from 'react-redux';
import Wysiwyg from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg';
import StoreFront from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront';
import TemplateSelection from './TemplateSelection';
import { withRouter } from 'react-router-dom';
import { getURLQueryParams } from 'common/utils/rzp-utils';

export const PAYMENT_PAGES_TYPES = {
  payment_page: 'payment_pages',
  storefront: 'storefront',
  batch_payment_page: 'batch_payment_page',
};

const keys = Object.values(PAYMENT_PAGES_TYPES);

type PaymentPageTypes = keyof typeof PAYMENT_PAGES_TYPES;

type PPPageTypeT = null | PaymentPageTypes;

const PaymentPagesCreateEditWrapper = (props): React.ReactElement | null => {
  const queryParams = getURLQueryParams(props.location.search);
  const [pageType, setPageType] = useState<PPPageTypeT>(
    keys.includes(queryParams.type) ? queryParams.type : null,
  );

  const handlePageType = (value) => {
    setPageType(value);
  };

  if (pageType === null) {
    return <TemplateSelection handlePageType={handlePageType} isMobile={props.isMobile} />;
  }
  if (pageType === PAYMENT_PAGES_TYPES.payment_page) {
    return <Wysiwyg {...props} />;
  }
  if (pageType === PAYMENT_PAGES_TYPES.storefront) {
    return <StoreFront {...props} />;
  }
  return null;
};

export default connect((state) => ({
  user: state.session.user,
  isMobile: state.app.isMobileResolution,
}))(withRouter(PaymentPagesCreateEditWrapper));
