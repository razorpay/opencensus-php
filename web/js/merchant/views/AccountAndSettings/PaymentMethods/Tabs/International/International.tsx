import React, { useEffect, useRef, useState } from 'react';
import { connect } from 'react-redux';
import PaymentMethodsSection from 'merchant/views/AccountAndSettings/PaymentMethods/components/Section';
import { PaymentMethodsFields } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';
import { setFeatureFlag as setFeatureFlagFn } from 'merchant/reducers/b2bExports/actions';
import { fetchFeatureStatus as fetchFeatureStatusFn } from 'merchant/reducers/config';
import { bindActionCreators } from 'redux';
import { MerchantICProductStatus } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import { isInternationalLeafItemDisabled } from 'merchant/views/AccountAndSettings/PaymentMethods/utils';
import LocalWireTransfer from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer';
import InstantBankTransfer from 'merchant/views/Settings/PaymentMethods/components/InstantBankTransfer';
import Paypal from 'merchant/views/Settings/PaymentMethods/components/Paypal';
import LeafListItem from 'merchant/views/Settings/PaymentMethods/components/LeafListItem';
import {
  LeafListItemSection,
  LeafListItem as LeafListItemDiv,
} from 'merchant/views/AccountAndSettings/PaymentMethods/components/LeafListItem';
import InternationalCards from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards';
import {
  CommonApiResponse,
  ShowNotificationType,
  User,
  InstrumentListItem,
  LeafListItem as LeafListItemType,
  FetchFeatureStatusType,
  SetFeatureFlagType,
} from 'common/typings';
import Firc from 'merchant/views/Settings/Configuration/components/FircAnnouncements/Firc';
import { fetchWorkflowStatus as fetchWorkflowStatusAction } from 'merchant/reducers/workflows';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { showNotification as showNotificationFn } from 'merchant_common/reducers/notifications';
import { merchantFetch } from 'merchant/utils/ajax';
import IntoViewUsingQueryParams from 'common/ui/IntoViewUsingQueryParams';
import { showWorkflowStatus } from 'merchant/views/Account/Profile/components/WorkflowRequests/utils';
import { fetchUser as fetchUserFn } from 'merchant/reducers/session';

type NewType = {
  user: User;
  instrument?: InstrumentListItem;
  isB2BEnabled: boolean;
  fetchWorkflowStatus: () => void;
  showNotification: ShowNotificationType;
  fetchUser: () => void;
  setFeatureFlag: SetFeatureFlagType;
  fetchFeatureStatus: FetchFeatureStatusType;
};

export type Props = NewType;

const International = ({
  user,
  instrument,
  isB2BEnabled,
  fetchWorkflowStatus,
  showNotification,
  fetchUser,
  setFeatureFlag,
  fetchFeatureStatus,
}: Props): JSX.Element => {
  /**
   * this checks if B2B instrument container has to be shown to a
   * merchant using feature flag status
   */
  useEffect(() => {
    const checkB2BFeatureFlag = async () => {
      if (user.id) {
        try {
          const response = await fetchFeatureStatus(user.id, 'enable_intl_bank_transfer');
          /* istanbul ignore else */
          if (response?.success && response.data) {
            setFeatureFlag({ isB2BEnabled: response.data.status });
          }
          // eslint-disable-next-line no-empty
        } catch {}
      }
    };

    checkB2BFeatureFlag();
  }, [user.id]);

  const [productStatus, setProductStatus] = useState<MerchantICProductStatus | null>(null);
  const [isLoading, setLoading] = useState(true);
  const listItemRef = useRef<HTMLDivElement>(null);

  const getProductStatus = () =>
    merchantFetch({
      url: 'merchants/product_international/workflow/status/all?version=v2',
    })
      .then((res: CommonApiResponse<{ data: MerchantICProductStatus }>) => {
        setProductStatus(res.data?.data || null);
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: 'Failed to retrieve International Cards Info',
        });
      });

  const retrieveProductsInfo = () => {
    // fetchUser is added to make sure value of international is always latest when user opens this tab
    Promise.allSettled([getProductStatus(), fetchWorkflowStatus(), fetchUser()]).finally(() => {
      setLoading(false);
    });
  };

  useEffect(() => {
    retrieveProductsInfo();
  }, []);

  const renderLeafListItem = (leafList: LeafListItemType) => {
    if (leafList.slug === 'localcurrencytransfer' && user?.international) {
      return <LocalWireTransfer leafList={leafList} />;
    } else if (leafList.slug === 'instantbanktransfer') {
      return <InstantBankTransfer leafList={leafList} />;
    }

    return leafList.list.map((leafListItem) => {
      const commonProps = {
        instrument: leafListItem,
        key: leafListItem.name,
      };
      switch (leafListItem.slug) {
        case 'internationalcards':
          return (
            <InternationalCards
              onQuestionnaireSubmitSuccess={() => {
                showWorkflowStatus(user.id, WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI);
                retrieveProductsInfo();
              }}
              productStatus={productStatus}
              {...commonProps}
            />
          );
        case 'paypal':
          return (
            <IntoViewUsingQueryParams
              queryKey="instrument"
              queryValue="paypal"
              elementRef={listItemRef}
              key={leafListItem.name}
            >
              <Paypal {...commonProps} isIERevamp />
            </IntoViewUsingQueryParams>
          );
        default:
          return <LeafListItem {...commonProps} />;
      }
    });
  };

  return (
    <>
      {!isLoading && user?.international && <Firc />}
      <PaymentMethodsSection type={PaymentMethodsFields.INTERNATIONAL} showLoader={isLoading}>
        <LeafListItemSection className="methods-view">
          {instrument?.leafList.map((leafListItem) => {
            if (
              isInternationalLeafItemDisabled({
                leafList: leafListItem,
                isB2BEnabled,
                user,
              })
            ) {
              return null;
            }

            return (
              <LeafListItemDiv
                data-testid="leaf-list-item"
                key={leafListItem.header}
                className="level-3"
                ref={listItemRef}
              >
                {renderLeafListItem(leafListItem)}
              </LeafListItemDiv>
            );
          })}
        </LeafListItemSection>
      </PaymentMethodsSection>
    </>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  instrument: state.instrumentRequests.leafInstrument,
  isB2BEnabled: state.b2bExportsAccounts.featureFlags?.isB2BEnabled,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      setFeatureFlag: setFeatureFlagFn,
      fetchFeatureStatus: fetchFeatureStatusFn,
      fetchWorkflowStatus: () =>
        fetchWorkflowStatusAction(WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI),
      showNotification: showNotificationFn,
      fetchUser: fetchUserFn,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(International);
