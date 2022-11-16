import React, { useEffect } from 'react';
import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';
import SwitchField from 'common/ui/Forms/SwitchField';
import { formatFromNow } from 'common/utils/rzp-utils';
import withInternationalConfig from './InternationalConfig';
import InternationalStatusLabel from 'merchant/components/InternationalStatusLabel';
import ProductInfo from './components/InternationalConfigComponents/ProductInfo';
import { trackIsButtonVisible } from './Questionnaire/analytics';

const statusMap = {
  approved: 'enabled',
  no_action_received: 'disabled',
  in_review: 'under_review',
  rejected: 'rejected',
};

const InternationalCards = ({
  config: {
    isKycComplete,
    isWebsiteAdded,
    showStatusLabel,
    maxPaymentAmount,
    isTogglerVisible,
    questionnaireStatus,
    internationalEnabled,
    currentStatusOnHeader,
    isRequestAccessAllowed,
    isInternationalBlackList,
    isAnyProductIntlApproved,
    isInternationalPaymentsAllowed,
  },
  description,
  productStatus,
  settlementDelay,
  onRequestAccessClick,
  toggleInternationalization,
}) => {
  const pgProductStatus = productStatus.pg.status;
  const otherProductsStatus = productStatus.otherProducts.status;

  const renderInternationalAccessOrStatus = () => {
    const isRequestButtonDisabled = !isWebsiteAdded || !isKycComplete;
    const buttonText =
      questionnaireStatus?.new_flow && questionnaireStatus?.enablement_progress === 'in_progress'
        ? 'Edit Draft'
        : 'Request Access';

    if (isRequestAccessAllowed) {
      return (
        <Button.Primary
          className="pull-right"
          onClick={onRequestAccessClick}
          disabled={isRequestButtonDisabled}
        >
          {buttonText}
        </Button.Primary>
      );
    }

    if (currentStatusOnHeader) {
      return (
        <span className="access-status">
          <InternationalStatusLabel status={statusMap[currentStatusOnHeader]} />
        </span>
      );
    }

    return null;
  };

  const renderProductsSection = () => {
    if (!isAnyProductIntlApproved || isInternationalBlackList) {
      return null;
    }

    return (
      <ul className="product-list">
        <ProductInfo
          title="Payment Gateway"
          status={statusMap[pgProductStatus]}
          showRequestAccessBtn={productStatus.pg.isRequested}
          onRequestAccessClick={(e) => {
            e.preventDefault();
            onRequestAccessClick({ triggerSource: 'pg' });
          }}
          isWebsiteAdded={isWebsiteAdded}
          isKycComplete={isKycComplete}
          product="pg"
          showStatusLabel={showStatusLabel}
          questionnaireStatus={questionnaireStatus}
        />

        <ProductInfo
          title="Payment Pages, Payment Links & Invoices"
          status={statusMap[otherProductsStatus]}
          showRequestAccessBtn={productStatus.otherProducts.isRequested}
          onRequestAccessClick={(e) => {
            e.preventDefault();
            onRequestAccessClick({ triggerSource: 'otherProducts' });
          }}
          isWebsiteAdded={isWebsiteAdded}
          isKycComplete={isKycComplete}
          product="otherProducts"
          showStatusLabel={showStatusLabel}
          questionnaireStatus={questionnaireStatus}
        />
      </ul>
    );
  };

  useEffect(() => {
    trackIsButtonVisible(isRequestAccessAllowed, 'Request');
  }, [isRequestAccessAllowed]);

  return (
    <div className="international-card">
      <div className="heading">
        <li className="title">International Card</li>

        {isTogglerVisible && (
          <span className="toggler-btn">
            <SwitchField
              defaultChecked={internationalEnabled}
              onChange={(isChecked, postActionCB) => {
                toggleInternationalization(isChecked, postActionCB);
              }}
              type="prime"
            />
            {internationalEnabled ? (
              <b className="text-primary">Enabled</b>
            ) : (
              <b className="text-faded">Disabled</b>
            )}
          </span>
        )}

        {renderInternationalAccessOrStatus()}
      </div>

      <div className="body">
        <form className="form-horizontal">
          <div className="description">
            <div style={{ display: 'flex', alignItems: 'center', margin: '10px 0' }}>
              {isInternationalPaymentsAllowed && (
                <span>Card payments on payment gateway, payment pages, links & invoices</span>
              )}
              {isRequestAccessAllowed &&
                questionnaireStatus?.new_flow &&
                questionnaireStatus.enablement_progress === 'in_progress' && (
                  <span className="questionnaire-status">{`${
                    questionnaireStatus.percentage_completion
                  }% details are complete | ${formatFromNow(
                    questionnaireStatus.last_updated_at,
                  )}`}</span>
                )}
            </div>
            <div>{description}</div>
          </div>
          <div className="product-section">
            {renderProductsSection()}
            {isAnyProductIntlApproved && (
              <div>
                <span>
                  Limit per transaction:&nbsp;
                  <Amount value={maxPaymentAmount} currency="INR" />
                </span>
                &nbsp;&nbsp;
                <span>
                  {settlementDelay ? (
                    <>|&nbsp;&nbsp;Settlement Cycle: T+{settlementDelay} days</>
                  ) : null}
                </span>
              </div>
            )}
          </div>
        </form>
      </div>
    </div>
  );
};

export default withInternationalConfig(InternationalCards);
