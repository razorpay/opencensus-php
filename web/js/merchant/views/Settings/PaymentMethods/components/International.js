import React from 'react';
import Amount from 'common/ui/Amount';
import SwitchField from 'common/ui/Forms/SwitchField';
import InternationalStatusLabel from 'merchant/components/InternationalStatusLabel';
import withInternationalConfig from '../../Configuration/InternationalConfig';
import Non3dsCardsActivation from './Non3dsCardsActivation';

const statusMap = {
  approved: 'activated',
  no_action_received: 'disabled',
  in_review: 'under_review',
  rejected: 'rejected',
};

const International = ({
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
  },
  productStatus,
  settlementDelay,
  onRequestAccessClick,
  toggleInternationalization,
}) => {
  if (!isWebsiteAdded || isInternationalBlackList) {
    return (
      <li class="international-leaf-item alert-info">
        <div>
          <div class="detail">
            <strong>International Cards</strong>
            {isInternationalBlackList && (
              <p>International Cards is not supported for your business type</p>
            )}
            {!isWebsiteAdded && (
              <p>Please update your website to request for international cards</p>
            )}
          </div>
        </div>
      </li>
    );
  }

  return (
    <li class="international-leaf-item">
      <div>
        <div class="detail">
          <strong>International Cards</strong>
          {isTogglerVisible && (
            <span class="toggler-btn" style={{ marginLeft: '10px' }}>
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
          <p class="desc">On Payment Gateway, Pages, Links and Invoices</p>
        </div>
        {isRequestAccessAllowed && (
          <button
            class="btn btn-primary ml-5"
            onClick={onRequestAccessClick}
            disabled={!isKycComplete}
          >
            {questionnaireStatus &&
            questionnaireStatus.new_flow &&
            questionnaireStatus.enablement_progress === 'in_progress'
              ? `Edit draft (${questionnaireStatus.percentage_completion}%)`
              : 'Request'}
          </button>
        )}
        {currentStatusOnHeader && (
          <InternationalStatusLabel status={statusMap[currentStatusOnHeader]} />
        )}
      </div>

      {internationalEnabled && <Non3dsCardsActivation />}

      {!!isAnyProductIntlApproved && (
        <>
          <div class="spacer-10" />

          <ProductInfo
            product="pg"
            title="On Payment Gateway"
            settlementCycle={settlementDelay}
            showStatusLabel={showStatusLabel}
            transactionSize={maxPaymentAmount}
            status={productStatus.pg.status}
            showRequestAccessBtn={productStatus.pg.isRequested}
            onRequestAccessClick={() => onRequestAccessClick({ triggerSource: 'pg' })}
          />

          <div class="spacer-20" />

          <ProductInfo
            product="otherProducts"
            showStatusLabel={showStatusLabel}
            settlementCycle={settlementDelay}
            transactionSize={maxPaymentAmount}
            title="Payment Pages, Links and Invoices"
            status={productStatus.otherProducts.status}
            showRequestAccessBtn={productStatus.otherProducts.isRequested}
            onRequestAccessClick={() => onRequestAccessClick({ triggerSource: 'otherProducts' })}
          />
        </>
      )}
    </li>
  );
};

const ProductInfo = ({
  title,
  status,
  product,
  transactionSize,
  settlementCycle,
  showStatusLabel,
  showRequestAccessBtn,
  onRequestAccessClick,
  questionnaireStatus,
}) => {
  let description;
  switch (status) {
    case 'rejected':
      description =
        'Currently we do not support international payments for these products. Please reach out to support for any queries';
      break;
    case 'in_review':
      description =
        'Request has been submitted. We are verifying your request. This would take roughly 3-5 days.';
      break;
    case 'no_action_received':
      description = `Raise a request to activate international card payments on ${
        product === 'pg' ? 'payment gateway' : 'other products'
      }`;
      break;
    case 'approved':
      description = (
        <>
          <p>
            Transaction Size Enabled :{' '}
            <strong>
              <Amount value={transactionSize} currency="INR" />
            </strong>
          </p>
          <p>
            Settlement Cycle :&nbsp;<strong>T+{settlementCycle}</strong>
          </p>
        </>
      );
      break;
    default:
      return null;
  }
  return (
    <div class="product-info">
      <div class="product-title">
        <strong>{title}</strong>

        {showRequestAccessBtn ? (
          <a role="button" class="ml-5" onClick={onRequestAccessClick}>
            <strong>
              {questionnaireStatus?.new_flow &&
              questionnaireStatus.enablement_progress === 'in_progress'
                ? `Edit draft (${questionnaireStatus.percentage_completion}%)`
                : 'Request Access'}
            </strong>
          </a>
        ) : (
          showStatusLabel && <InternationalStatusLabel status={statusMap[status]} />
        )}
      </div>
      <div class="spacer-10" />
      {description}
    </div>
  );
};

export default withInternationalConfig(International);
