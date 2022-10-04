import React from 'react';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { classList } from 'common/utils/rzp-utils';

const CustomLottie = lazy(() =>
  import(/* webpackChunkName: 'CustomLottie' */ 'common/new-ui/Lottie'),
);

const BankAccountUpdateState = ({
  data: { lottieData, title, subtitle, info } = {},
  lottieDivClass = [],
}) => {
  return (
    <div className="bank-details-change-states-wrapper">
      <div className="bank-details-change-state">
        {lottieData && (
          <div className={classList(lottieDivClass)} data-testid="bank-update-state-animation">
            <SuspenseWithLoader>
              <CustomLottie
                animationData={lottieData}
                autoplay
                loop
                width="100px"
                isStopped={false}
              />
            </SuspenseWithLoader>
          </div>
        )}
        <div className="bank-details-header">{title}</div>
        <div>{subtitle}</div>
        {info && (
          <>
            <hr />
            <div className="bank-details-change-state-muted-text">{info}</div>
          </>
        )}
      </div>
    </div>
  );
};

export default BankAccountUpdateState;
