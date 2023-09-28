import React, { useLayoutEffect, useRef } from 'react';
import moment from 'moment';

import { withRouter } from 'common/deprecated/withRouter';
import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import LoanStatusFooter from 'merchant/views/Capital/Loans/LoansCollections/Overview/OverviewStatus/LoanStatusFooter';
import {
  useLoanData,
  ACTIONS,
} from 'merchant/views/Capital/Loans/LoansCollections/Overview/OverviewStatus/PaymentContext';
import {
  COLLECTIONS_PAYMENT_TYPE,
  OVERVIEW_STATUS_VIEWS,
} from 'merchant/views/Capital/Loans/LoansCollections/constants';
import { LOANS_BASE_URL, LOANS_SECTIONS } from 'merchant/views/Capital/Loans/constants';

import { Container, Seperator } from './styles';
import { RenderMessage, isPaymentBeingProcessing, getBaseMessageConfig } from './utils';

function PaymentResult({ setView, history, onRefresh }) {
  const rootContainerRef = useRef();
  const previousHeight = useRef(274); // parent container .overview-status min-height 274

  const {
    state: {
      failedRepayments,
      successRepayments,
      totalDueInfo: { amount: totalDueAmount, date: dueDate },
    },
    dispatch,
  } = useLoanData();

  useLayoutEffect(() => {
    // animate height change - min height is 274px in overview tab
    const el = rootContainerRef.current;
    if (el) {
      const toHeight = el.offsetHeight;
      const fromHeight = previousHeight.current;
      el.style.height = `${fromHeight}px`;
      el.style.opacity = 0;
      requestAnimationFrame(() => {
        el.style.height = `${toHeight}px`;
        el.style.opacity = 1;
        el.style.transition = 'opacity 250ms,height 250ms';
        const clearnUpAnim = (e) => {
          if (e.target !== el) return;
          el.style.height = `auto`;
          el.style.transition = '';
          el.removeEventListener('transitionend', clearnUpAnim);
        };
        el.addEventListener('transitionend', clearnUpAnim);
      });
    }
  }, []);

  const totalAmountCollected = successRepayments.reduce((acc, { amount }) => acc + +amount, 0);
  const outstandingAfterRepayment = totalDueAmount - totalAmountCollected;

  const resetRepayments = () => {
    dispatch({ type: ACTIONS.RESET_REPAYMENT });
  };

  const onClose = () => {
    resetRepayments(); // onRefresh() rerenders whole tree so no reset needed there
    setView(OVERVIEW_STATUS_VIEWS.PAYMENT_AMOUNT);
  };

  const onViewRepaymentHistory = () => {
    history.push(`${LOANS_BASE_URL}${LOANS_SECTIONS.REPAYMENTS_HISTORY}`);
    onRefresh();
  };

  const getConfig = () => {
    const { success, failure, processing } = getBaseMessageConfig();
    success.button = <Button.Primary onClick={onRefresh}>Done</Button.Primary>;
    processing.button = <Button.Primary onClick={onRefresh}>Done</Button.Primary>;
    failure.button = <Button.Primary onClick={onClose}>Done</Button.Primary>;

    let config = {};
    // repayment could be failed bcuz of procesing state or some other error
    const getFailureConfig = (repayment, data, overrides = {}) => {
      const paymentBeingProcessed = isPaymentBeingProcessing(repayment);
      const failureConfig = paymentBeingProcessed ? processing : failure;
      failureConfig.data = data;
      data.some(({ id }) => !!id) &&
        failureConfig.headers.push({ header: 'Reference Id', key: 'id' }); // incase payment_reference_id available
      return { ...failureConfig, ...overrides };
    };

    if (successRepayments.length && failedRepayments.length) {
      // paid using multiple method - 1 success + 1 failure - Show success UI/Message at top
      config = success;
      config.data = successRepayments;
      config.title = 'Repayment Partially Successful!';
      config.className = 'payment_suc_fail__msg--multiple';
      config.containerClass = 'cont--warn';
      const failedRepayment = getFailureConfig(failedRepayments[0], failedRepayments, {
        button: null,
      });
      failedRepayment.title = isPaymentBeingProcessing(failedRepayments[0])
        ? failedRepayment.title
        : `Repayment via ${COLLECTIONS_PAYMENT_TYPE[failedRepayments[0].type]} has Failed!`;
      config.sibling = (
        <>
          <Seperator />
          <RenderMessage config={failedRepayment} />
        </>
      );
    } else if (successRepayments.length) {
      config = success; // success - paid using single method or multiple method
      config.data = successRepayments;
    } else if (
      failedRepayments.length === 2 &&
      failedRepayments.filter(isPaymentBeingProcessing).length === 1
    ) {
      // both failed but 1 processing - Show failed repayment at top
      const processingRepayment = failedRepayments.find(isPaymentBeingProcessing);
      const failedRepayment = failedRepayments.find(
        (repayment) => repayment !== processingRepayment,
      );
      config = getFailureConfig(failedRepayment, [failedRepayment], {
        className: 'payment_suc_fail__msg--multiple',
        title: 'Repayment Partially Failed!',
        containerClass: 'cont--processing',
      });
      config.button = success.button; // done with refresh functionality
      config.sibling = (
        <>
          <Seperator />
          <RenderMessage
            config={getFailureConfig(processingRepayment, [processingRepayment], {
              button: null,
            })}
          />
        </>
      );
    } else {
      // failure - paid using single method or multiple method - handles both failed due to server error or processing or 1 fail or 1 processing
      config = getFailureConfig(failedRepayments[0], failedRepayments);
    }

    return config;
  };

  const isAmountOutstanding = outstandingAfterRepayment > 0;
  const config = getConfig();
  const showFooter = !failedRepayments.some(isPaymentBeingProcessing);

  return (
    <Container ref={rootContainerRef} className={config.containerClass}>
      <RenderMessage config={config} />
      {showFooter && (
        <LoanStatusFooter icon={isAmountOutstanding && 'i i-info-alt text-teal'}>
          {isAmountOutstanding ? (
            <span>
              We will attempt to collect the remaining <Amount value={outstandingAfterRepayment} />{' '}
              from your settlement balance by {moment(dueDate, 'X').format('MMMM DD')}
            </span>
          ) : (
            <Button.Transparent onClick={onViewRepaymentHistory}>
              {' '}
              View Repayment History{' '}
            </Button.Transparent>
          )}
        </LoanStatusFooter>
      )}
    </Container>
  );
}

export default withRouter(PaymentResult);
