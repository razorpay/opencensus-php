import './styles/index.styl';
import React, { useState, useEffect, Suspense } from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import {
  openModal as fnOpenModal,
  closeModal as fnCloseModal,
} from 'merchant_common/reducers/modals';
import { fetchFunctionalWithdrawalConfigByMerchantID as fnFetchWithdrawalConfig } from 'merchant/reducers/capital/withdrawals';
import {
  fetchProducts as fnFetchProducts,
  getApplications as fnGetApplications,
} from 'merchant/reducers/capital';
import lazy from 'merchant/routes/LazyLoader';
import { CAPITAL_PRODUCT_CODES, APPLICATION_STATES } from 'merchant/views/Capital/Loans/constants';
import Loader from 'common/ui/Loader';
import Lock from './components/Lock';
import { getPillContent, getPillVariant, getPillRenderDate, setPillRenderDate } from './utils';
import { EVENT_TYPES } from './analytics';
import { PILL_VARIENTS } from './constants';
import { CASH_ADVANCE_BASE_URL } from '../CashAdvance/constants';
import { trackEvent } from './trackingUtils';

const ProgressModal = lazy(() =>
  import(
    /* webpackChunkName: "CashAdvanceNudgesProgressModal" */ 'merchant/views/Capital/CashAdvanceNudges/components/ProgressModal'
  ),
);

const CashAdvanceNudge = (props) => {
  const {
    user,
    fetchProducts,
    getApplications,
    openModal,
    closeModal,
    products,
    applications,
    history,
  } = props;
  const hasWithdrawFeature = user.isWithdrawFeatureEnabled;
  const applicationsAlreadyFetched =
    applications?.data && Array.isArray(applications?.data?.applications);
  const isLoading = applications?.loading || products?.loading;
  const haveProductsData = products?.data && Array.isArray(products?.data);
  const latestApplication = applications?.data?.applications?.[0];
  const isMerchantEligibile = !!(user.isLOCEnabled && user.isOndemandSettlementEnabled);
  const [showPill, setShowPill] = useState(false);
  const variant = !isLoading ? getPillVariant(latestApplication) : null;

  const getProductDetails = () => {
    const { data = [] } = products;

    return data?.find((p) => p.name === CAPITAL_PRODUCT_CODES.CASH_ADVANCE);
  };

  const fetchApplications = async () => {
    const dontFetchApplications = Boolean(
      !haveProductsData || applicationsAlreadyFetched || applications?.errors || hasWithdrawFeature,
    );

    if (dontFetchApplications) return;
    const productDetails = getProductDetails();

    try {
      await getApplications({
        owner_type: 'MERCHANT',
        owner_id: user.current,
        product_id: productDetails.id,
      });
    } catch (e) {
      // empty catch
    }
  };

  const getProducts = async () => {
    const productsAlreadyFetched = products?.data?.length;

    if (productsAlreadyFetched || products?.errors || hasWithdrawFeature) return;

    try {
      await fetchProducts();
    } catch (error) {
      // empty catch
    }
  };

  useEffect(() => {
    if (hasWithdrawFeature) return;
    const show = Boolean(isMerchantEligibile && !isLoading && variant);

    if (show && variant === PILL_VARIENTS.PRE_APPLICATION) {
      const pillRenderDate = getPillRenderDate();
      if (pillRenderDate) {
        const renderDateMoment = moment(pillRenderDate, 'YYYY-MM-DD');
        const diff = moment().diff(renderDateMoment, 'days');
        if (diff <= 30) setShowPill(true);
      } else {
        setPillRenderDate();
        setShowPill(true);
      }
    } else if (show && PILL_VARIENTS.PROGRESS_APPLICATION) {
      setShowPill(true);
    }
  }, [isMerchantEligibile, isLoading, variant]);

  useEffect(() => {
    if (!isMerchantEligibile || hasWithdrawFeature) return;
    fetchApplications();
  }, [products?.data]);

  useEffect(() => {
    if (!isMerchantEligibile || hasWithdrawFeature) return;
    getProducts();
  }, []);

  useEffect(() => {
    if (!isMerchantEligibile || hasWithdrawFeature) return;
    if (showPill && variant)
      trackEvent({
        action: EVENT_TYPES.RENDERED,
        variant,
        latestApplication,
      });
  }, [showPill, variant]);

  const handlePillClick = () => {
    trackEvent({
      action: EVENT_TYPES.CLICKED,
      variant,
      latestApplication,
    });

    const isApplicationUnderReview =
      latestApplication?.status === APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW ||
      latestApplication?.status === APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS;
    if (isApplicationUnderReview) {
      openModal({
        component: (
          <Suspense fallback={<Loader />}>
            <ProgressModal status={latestApplication?.status} closeModal={closeModal} />
          </Suspense>
        ),
        size: 'medium',
        disableClose: true,
      });
    } else {
      history.push(CASH_ADVANCE_BASE_URL);
    }
  };

  const renderPillContent = () => {
    if (!variant) return null;
    const items = getPillContent(variant);

    return (
      <>
        <Lock />
        {items?.length > 1 ? (
          <div className="loc-nudge-list-container">
            <div className="list">
              {items?.map((item, idx) => (
                <div className="list-item" key={idx}>
                  {item}
                </div>
              ))}
              <div className="list-item">{items[0]}</div>
            </div>
          </div>
        ) : (
          <div className="list-item">{items[0]}</div>
        )}
      </>
    );
  };

  if (showPill && !hasWithdrawFeature)
    return (
      <div className="loc-nudge">
        <div
          className={`loc-nudge-container${variant ? ` ${variant}` : ''}`}
          onClick={handlePillClick}
        >
          {renderPillContent()}
        </div>
      </div>
    );

  return null;
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  products: state.loanApplicationDetails.products,
  applications: state.loanApplicationDetails.applications,
});

export default withRouter(
  connect(mapStateToProps, {
    openModal: fnOpenModal,
    closeModal: fnCloseModal,
    fetchWithdrawalConfig: fnFetchWithdrawalConfig,
    fetchProducts: fnFetchProducts,
    getApplications: fnGetApplications,
  })(CashAdvanceNudge),
);
