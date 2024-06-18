import React, { useEffect, useRef, useState } from 'react';
import { Box, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { useSplitzService } from 'common/splitz';
import {
  CommonApiResponse,
  ShowNotificationType,
  User,
  InstrumentListItem,
  LeafListItem as LeafListItemType,
} from 'common/typings';
import IntoViewUsingQueryParams from 'common/ui/IntoViewUsingQueryParams';
import { isExperimentActive } from 'common/utils/rzp-utils';
import { fetchUser as fetchUserFn } from 'merchant/reducers/session';
import { fetchWorkflowStatus as fetchWorkflowStatusAction } from 'merchant/reducers/workflows';
import lazy from 'merchant/routes/LazyLoader';
import { merchantFetch } from 'merchant/utils/ajax';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { showWorkflowStatus } from 'merchant/views/Account/Profile/components/WorkflowRequests/utils';
import { PaymentMethodsFields } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';
import InternationalCards from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards';
import {
  LeafListItemSection,
  LeafListItem as LeafListItemDiv,
} from 'merchant/views/AccountAndSettings/PaymentMethods/components/LeafListItem';
import PaymentMethodsSection from 'merchant/views/AccountAndSettings/PaymentMethods/components/Section';
import { MerchantICProductStatus } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import { isInternationalLeafItemDisabled } from 'merchant/views/AccountAndSettings/PaymentMethods/utils';
import Firc from 'merchant/views/Settings/Configuration/components/FircAnnouncements/Firc';
import LeafListItem from 'merchant/views/Settings/PaymentMethods/components/LeafListItem';
import { showNotification as showNotificationFn } from 'merchant_common/reducers/notifications';

const Paypal = lazy(
  () =>
    import(
      /* webpackChunkName: "Paypal" */ 'merchant/views/Settings/PaymentMethods/components/Paypal'
    ),
);

const LocalWireTransfer = lazy(
  () =>
    import(
      /* webpackChunkName: "LocalWireTransfer" */ 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer'
    ),
);

const InstantBankTransfer = lazy(
  () =>
    import(
      /* webpackChunkName: "InstantBankTransfer" */ 'merchant/views/Settings/PaymentMethods/components/InstantBankTransfer'
    ),
);

const SwiftBankTransfer = lazy(
  () =>
    import(
      /* webpackChunkName: "SwiftBankTransfer" */ 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/SwiftBankTransfer'
    ),
);

const UnlockMoreMethods = lazy(
  () =>
    import(
      /* webpackChunkName: "UnlockMoreMethods" */ 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/UnlockMoreMethods'
    ),
);

type NewType = {
  user: User;
  instrument?: InstrumentListItem;
  showMoreInternationalMethods: boolean;
  fetchWorkflowStatus: () => void;
  showNotification: ShowNotificationType;
  fetchUser: () => void;
};

export type Props = NewType;

const International = ({
  user,
  instrument,
  showMoreInternationalMethods,
  fetchWorkflowStatus,
  showNotification,
  fetchUser,
}: Props): JSX.Element => {
  const [productStatus, setProductStatus] = useState<MerchantICProductStatus | null>(null);
  const [isLoading, setLoading] = useState(true);
  const listItemRef = useRef<HTMLDivElement>(null);
  const { abExperiments: { showIntlMethodEnablement, userApiDecomp } = {} } = useSplitzService();

  const isIntlMethodExpEnabled = isExperimentActive(showIntlMethodEnablement);
  const isUserApiDecompEnabled = isExperimentActive(userApiDecomp);

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
    const productInfoApi = [getProductStatus(), fetchWorkflowStatus()];

    if (!isUserApiDecompEnabled) {
      // fetchUser is added to make sure value of international is always latest when user opens this tab
      productInfoApi.push(fetchUser());
    }

    Promise.allSettled(productInfoApi).finally(() => {
      setLoading(false);
    });
  };

  useEffect(() => {
    retrieveProductsInfo();
  }, []);

  const renderLeafListItem = (leafList: LeafListItemType) => {
    if (leafList.slug === 'localcurrencytransfer') {
      return (
        <SuspenseWithLoader>
          <LocalWireTransfer leafList={leafList} />
        </SuspenseWithLoader>
      );
    } else if (leafList?.slug === 'swiftbanktransfer') {
      return (
        <SuspenseWithLoader>
          <SwiftBankTransfer leafList={leafList} />
        </SuspenseWithLoader>
      );
    } else if (leafList.slug === 'instantbanktransfer') {
      return (
        <SuspenseWithLoader>
          <InstantBankTransfer leafList={leafList} />
        </SuspenseWithLoader>
      );
    }

    if (leafList.slug === 'moreinternationalmethods') {
      return (
        <SuspenseWithLoader key={leafList.slug}>
          <UnlockMoreMethods
            instrument={leafList}
            productPaCbStatus={productStatus?.products_pa_cb}
          />
        </SuspenseWithLoader>
      );
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
              <SuspenseWithLoader>
                <Paypal {...commonProps} isIERevamp />
              </SuspenseWithLoader>
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
                user,
                showMoreInternationalMethods:
                  showMoreInternationalMethods && isIntlMethodExpEnabled,
              })
            ) {
              return null;
            }

            if (Array.isArray(leafListItem.leafList)) {
              return (
                <Box key={leafListItem.header}>
                  <Text marginBottom="spacing.4" size="large" color="surface.text.gray.subtle">
                    {leafListItem.header}
                  </Text>
                  {leafListItem.leafList.map((leafListItem) => (
                    <Box key={leafListItem.header} marginBottom="spacing.5">
                      <LeafListItemDiv
                        data-testid="leaf-list-item"
                        className="level-3 leve-4"
                        ref={listItemRef}
                      >
                        {renderLeafListItem(leafListItem)}
                      </LeafListItemDiv>
                    </Box>
                  ))}
                </Box>
              );
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

const mapStateToProps = ({ session, instrumentRequests, unlockIntlPaymentMethods }) => ({
  user: session.user,
  instrument: instrumentRequests.leafInstrument,
  showMoreInternationalMethods: unlockIntlPaymentMethods.showMorePaymentMethodsSection,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchWorkflowStatus: () =>
        fetchWorkflowStatusAction(WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI),
      showNotification: showNotificationFn,
      fetchUser: fetchUserFn,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(International);
