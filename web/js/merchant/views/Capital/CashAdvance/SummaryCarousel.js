import React, { useLayoutEffect, useState, useEffect, useRef, useCallback } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import moment from 'moment';

import Carousel from './Carousel';
import CashAdvanceCarouselSlide from './CashAdvanceCarouselSlide';
import {
  CASH_ADVANCE_CAROUSEL_VIEW_RULES,
  CASH_ADVANCE_SECTIONS,
  COLLECTIONS_PRODUCT_TYPES,
} from './constants';
import carouselRuleValidators from './CarouselRules';
import { getSlideByRule, getSlideContent } from './utils';
import Withdrawal from 'merchant/models/Capital/Withdrawals';
import Repayments from 'merchant/models/Capital/Repayments';
import { getProductType } from 'merchant/views/Capital/utils';

const Information = ({ message, backgroundColor }) => {
  return (
    <div className="overview-carousel-container">
      <div className="slides-container">
        <div className="slide-container active">
          <div
            className="slide"
            style={{ background: backgroundColor, fontSize: '20px', fontWeight: 'bold' }}
          >
            <img
              className="background-pattern-image"
              height="120%"
              width="120%"
              src="/dist/css/assets/capital/carousel-bg-pattern.svg"
            />
            {message}
          </div>
        </div>
      </div>
    </div>
  );
};

const generateSlides = (slideIds, withdrawalConfig, upcomingRepayments, balances) =>
  slideIds.reduce((acc, currSlideID) => {
    const slide = getSlideContent(currSlideID, withdrawalConfig, upcomingRepayments, balances);
    return currSlideID ? [...acc, slide] : acc;
  }, []);

function SummaryCarousel(props) {
  const carouselContainerRef = useRef();
  const [isCompactMode, setIsCompactMode] = useState(true);
  const [resizeLastDetected, setResizeLastDetected] = useState(Date.now());
  const [withdrawals, setWithdrawals] = useState({
    loading: true,
    error: false,
    data: [],
  });
  const [repayments, setRepayments] = useState({
    loading: true,
    error: false,
    data: [],
  });
  const [installments, setInstallments] = useState({
    loading: true,
    error: false,
    data: [],
  });
  const [planBalances, setPlanBalances] = useState({
    loading: true,
    error: false,
    data: [],
  });
  const reqAnimationFrameRef = useRef();
  const resizeObserverRef = useRef();

  const productType = getProductType(props.user);

  const fetchInstallments = (withdrawal) => {
    withdrawal
      .fetchInstallments({
        owner_id: props.user.current,
        from: moment().startOf('day').unix(),
        to: moment().add(30, 'days').unix(),
        product_type: productType,
      })
      .then(({ data: { repayment_schedule = [] } = {} } = {}) => {
        setInstallments((state) => ({
          ...state,
          loading: false,
          data: repayment_schedule,
        }));
      })
      .catch(() => {
        setInstallments((state) => ({
          ...state,
          loading: false,
          error: true,
        }));
      });
  };

  const fetchWithdrawals = (withdrawalInstance) => {
    withdrawalInstance
      .fetchWithdrawals({
        reference: [
          {
            reference_type: 'OWNER_ID',
            reference_id: props.user.current,
          },
        ],
        order_by: 'CREATED_AT',
        order_direction: 'desc',
        skip: 0,
        count: 25,
        product_type: productType,
      })
      .then(({ data: { withdrawal = [] } = {} } = {}) => {
        setWithdrawals((state) => ({
          ...state,
          loading: false,
          data: withdrawal,
        }));

        if (withdrawal.length) return fetchInstallments(withdrawalInstance);
        else {
          return setInstallments((state) => ({
            ...state,
            loading: false,
          }));
        }
      })
      .catch(() => {
        setWithdrawals((state) => ({
          ...state,
          loading: false,
          error: true,
        }));
        setInstallments((state) => ({
          ...state,
          loading: false,
          error: true,
        }));
      });
  };

  const fetchRepayments = (repayment) => {
    repayment
      .fetchRepayments({
        product_type: COLLECTIONS_PRODUCT_TYPES.CASH_ADVANCE,
        credit_id: props.user.current,
        order_by_type: 'ORDER_BY_TYPE_DESC',
        order_by_field: 'ORDER_BY_FIELD_CREATED_AT',
      })
      .then(({ data: { repayments: list = [] } = {} } = {}) => {
        setRepayments((state) => ({
          ...state,
          loading: false,
          data: list,
        }));
      })
      .catch(() => {
        setRepayments((state) => ({
          ...state,
          loading: false,
          error: true,
        }));
      });
  };

  const fetchPlanBalances = (repayment) => {
    repayment
      .fetchBalances({
        product_type: COLLECTIONS_PRODUCT_TYPES.CASH_ADVANCE,
        credit_id: props.user.current,
      })
      .then(({ data: { balances = [] } = {} } = {}) => {
        setPlanBalances((state) => ({
          ...state,
          loading: false,
          data: balances,
        }));
      })
      .catch(() => {
        setPlanBalances((state) => ({
          ...state,
          loading: false,
          error: true,
        }));
      });
  };

  const unobserve = useCallback(() => {
    if (resizeObserverRef.current) resizeObserverRef.current.disconnect();
  }, []);

  const observe = useCallback(() => {
    if (resizeObserverRef.current && carouselContainerRef.current)
      resizeObserverRef.current.observe(carouselContainerRef.current);
  }, []);

  // We dont want to consume it from store as, as withdrawals save in store
  // contains filtered/paginated withdrawals. and the carousel rule
  // validator function requires chronologically latest withdrawals
  useEffect(() => {
    const withdrawal = new Withdrawal();
    const repayment = new Repayments();

    fetchWithdrawals(withdrawal);
    fetchRepayments(repayment);
    fetchPlanBalances(repayment);
    // eslint-disable-next-line
  }, [props.withdrawalConfigurationDetails.data.configuration.principal_outstanding_balance]);

  useEffect(() => {
    setIsCompactMode(
      carouselContainerRef &&
        carouselContainerRef.current &&
        carouselContainerRef.current.clientWidth < 530,
    );
  }, [resizeLastDetected]);

  useLayoutEffect(() => {
    if (carouselContainerRef && carouselContainerRef.current) {
      resizeObserverRef.current = new ResizeObserver(() => {
        reqAnimationFrameRef.current = window.requestAnimationFrame(() => {
          setResizeLastDetected(Date.now());
        });
      });
      observe();
    }
    return () => {
      unobserve();
      if (reqAnimationFrameRef.current) {
        window.cancelAnimationFrame(reqAnimationFrameRef.current);
      }
    };
  }, []);

  // eslint-disable-next-line
  const carouselActionHandler = (actionId) => {
    switch (actionId) {
      case 'WITHDRAW':
        if (props.location.pathname.includes('repayments')) {
          return props.history.push('/capital/cash-advance/withdrawals');
        }
        // eslint-disable-next-line
        const withdrawalContainer = document.querySelector('.withdrawals__action-container');
        withdrawalContainer.classList.add('woggle');
        setTimeout(() => withdrawalContainer.classList.remove('woggle'), 1000);
        break;

      case 'REPAY':
        // eslint-disable-next-line
        const repaymentContainer = document.querySelector('.repay-container');
        repaymentContainer.classList.add('woggle');
        setTimeout(() => repaymentContainer.classList.remove('woggle'), 1000);
        break;

      case 'VIEW_WITHDRAWAL_DETAILS':
        return props.history.push('/capital/cash-advance/withdrawals');

      default:
        break;
    }
  };

  if (withdrawals.loading || repayments.loading || installments.loading || planBalances.loading) {
    return <Information backgroundColor="#0A65D0" message="Loading..." />;
  }

  if (withdrawals.error || repayments.error || installments.error || planBalances.error) {
    return <Information backgroundColor="#A04E4E" message="Oh snap! Something went wrong." />;
  }

  const withdrawalConfig = props.withdrawalConfigurationDetails.data;

  let lastRuleSatisfied;
  Object.keys(CASH_ADVANCE_CAROUSEL_VIEW_RULES).forEach((rule) => {
    const data = {
      withdrawalConfig,
      view: props.view,
      withdrawals: withdrawals.data,
      repayments: repayments.data,
      installments: installments.data,
    };
    if (carouselRuleValidators[rule](data)) {
      lastRuleSatisfied = rule;
    }
  });

  const slideIds = getSlideByRule(lastRuleSatisfied);
  const SLIDES = generateSlides(slideIds, withdrawalConfig, installments.data, planBalances.data);

  return (
    <div ref={carouselContainerRef} className="overview-carousel-container">
      <Carousel slides={SLIDES} autoRotate={true} interval={15000}>
        {SLIDES.map((slide) => (
          <Carousel.Slide slideID={slide.id} key={slide.id}>
            {(data) => (
              <CashAdvanceCarouselSlide
                {...data}
                slide={slide}
                containerRef={carouselContainerRef}
                isCompactMode={isCompactMode}
                handleAction={carouselActionHandler}
              />
            )}
          </Carousel.Slide>
        ))}
      </Carousel>
    </div>
  );
}

@connect((state) => {
  return {
    user: state.session.user,
    withdrawalConfigurationDetails: state.withdrawals.withdrawalConfiguration,
  };
})
class CarouselContainer extends React.Component {
  render() {
    const { withdrawalConfigurationDetails } = this.props;
    if (withdrawalConfigurationDetails.loading || !withdrawalConfigurationDetails.data) {
      return <Information backgroundColor="#0A65D0" message="Loading..." />;
    }

    if (withdrawalConfigurationDetails.error) {
      return <Information backgroundColor="#A04E4E" message="Oh snap! Something went wrong." />;
    }

    return <SummaryCarousel {...this.props} view={CASH_ADVANCE_SECTIONS.WITHDRAWALS} />;
  }
}
export default withRouter(CarouselContainer);
