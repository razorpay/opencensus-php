import React from 'react';
import Amount from 'common/ui/Amount';
import Button from 'common/new-ui/Button';
import SwitchField from 'common/ui/Forms/SwitchField';
import withInternationalConfig from './InternationalConfig';
import { InternationalStatusLabel } from 'merchant/components/StatusLabel';
import ProductInfo from './components/InternationalConfigComponents/ProductInfo.js';

const statusMap = {
  approved: 'enabled',
  no_action_received: 'disabled',
  in_review: 'access_requested',
  rejected: 'request_rejected',
};

const InternationalCards = ({
  config: {
    isKycComplete,
    isWebsiteAdded,
    showStatusLabel,
    maxPaymentAmount,
    isTogglerVisible,
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

    if (isRequestAccessAllowed) {
      return (
        <Button.Primary
          class="pull-right"
          onClick={onRequestAccessClick}
          disabled={isRequestButtonDisabled}
        >
          Request Access
        </Button.Primary>
      );
    }

    if (currentStatusOnHeader) {
      return (
        <span class="access-status">
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
      <ul class="product-list">
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
        />
      </ul>
    );
  };

  return (
    <div class="international-card">
      <div class="heading">
        <li class="title">International Card</li>

        {isTogglerVisible && (
          <span class="toggler-btn">
            <SwitchField
              defaultChecked={internationalEnabled}
              onChange={(isChecked, postActionCB) => {
                toggleInternationalization(isChecked, postActionCB);
              }}
              type="prime"
            />
            {internationalEnabled ? (
              <b class="text-primary">Enabled</b>
            ) : (
              <b class="text-faded">Disabled</b>
            )}
          </span>
        )}

        {renderInternationalAccessOrStatus()}
      </div>

      <div class="body">
        <form class="form-horizontal">
          <div class="description">
            <div>
              {isInternationalPaymentsAllowed && (
                <span>Card payments on payment gateway, payment pages, links & invoices</span>
              )}
            </div>
            <div>{description}</div>
          </div>
          <div class="product-section">
            {renderProductsSection()}
            {isAnyProductIntlApproved && (
              <div>
                <span>
                  Limit per transaction:&nbsp;
                  <Amount value={maxPaymentAmount} currency={'INR'} />
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
