import React, { useEffect } from 'react';
import { Card, CardBody, Box, Skeleton } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { CommonApiResponse, ShowNotificationType, User } from 'common/typings';
import { fetchWorkflowStatus as fetchWorkflowStatusAction } from 'merchant/reducers/workflows';
import { merchantFetch } from 'merchant/utils/ajax';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { showWorkflowStatus } from 'merchant/views/Account/Profile/components/WorkflowRequests/utils';
import InternationalCards from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards';
import { MerchantICProductStatus } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import { showNotification as showNotificationFn } from 'merchant_common/reducers/notifications';

import MethodSection from './Section';
import { MethodProps } from './types';

const MethodCard = ({
  item,
  user,
  showNotification,
  fetchWorkflowStatus,
}: MethodProps & {
  user: User;
  showNotification: ShowNotificationType;
  fetchWorkflowStatus: () => void;
}) => {
  const { refetch: refetchWorkflowStatus } = useQuery({
    queryKey: ['fetchWorkflowStatus'],
    queryFn: fetchWorkflowStatus,
    refetchOnWindowFocus: false,
  });

  const {
    isLoading,
    isError,
    data: productStatus,
    refetch: refetchProductStatus,
  } = useQuery<MerchantICProductStatus | null>({
    queryKey: ['product_international_workflow_status'],
    queryFn: () =>
      merchantFetch({
        url: 'merchants/product_international/workflow/status/all?version=v2',
      }).then(
        (res: CommonApiResponse<{ data: MerchantICProductStatus }>) => res.data?.data || null,
      ),
    refetchOnWindowFocus: false,
  });

  const handleOnSuccess = () => {
    showWorkflowStatus(user.id, WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI);
    refetchWorkflowStatus();
    refetchProductStatus();
  };

  useEffect(() => {
    if (isError) {
      showNotification({
        type: 'error',
        message: 'Failed to retrieve International Cards Info',
      });
    }
  }, [isError, showNotification]);

  if (!item || isLoading) {
    return (
      <Card elevation="none">
        <CardBody>
          <Box display="flex" flexDirection="column" gap="spacing.6">
            <Skeleton width="20%" height="20px" borderRadius="small" />
            <Skeleton width="45%" height="16px" borderRadius="small" />
          </Box>
        </CardBody>
      </Card>
    );
  }

  const leafListItem = item.list?.[0];

  return (
    <MethodSection id={item.slug || 'international-card'}>
      <InternationalCards
        onQuestionnaireSubmitSuccess={handleOnSuccess}
        productStatus={productStatus}
        instrument={leafListItem}
        user={user}
      />
    </MethodSection>
  );
};

const mapStateToProps = ({ session }) => ({
  user: session.user,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchWorkflowStatus: () =>
        fetchWorkflowStatusAction(WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI),
      showNotification: showNotificationFn,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(MethodCard);
