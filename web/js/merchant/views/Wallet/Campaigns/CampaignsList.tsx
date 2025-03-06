import React, { useContext, useEffect, useRef, useState } from 'react';
import {
  TableBody,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableRow,
  TableCell,
  Text,
  Badge,
  IconButton,
  StopCircleIcon,
  PauseCircleIcon,
  PlayCircleIcon,
  Box,
  TablePagination,
  useToast,
  CheckCircleIcon,
  Modal,
  ModalFooter,
  Button,
  ModalBody,
  Spinner,
  SparklesIcon,
  PlusIcon,
} from '@razorpay/blade/components';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { fetchCampaigns, updateCampaign } from '../queries';
import { CAMPAIGN_STATUS, CAMPAIGN_STATUS_CONFIRMATION_MESSAGES } from './constants';
import { SessionContext } from '../context';
import { ValueOf } from './types';

const CampaignControls = ({ item, onCampaignChange }) => {
  if (item.status === CAMPAIGN_STATUS.TERMINATED.value) {
    return null;
  }
  return (
    <Box display="flex">
      <Box display="flex" marginRight="spacing.8">
        <IconButton
          icon={() => <StopCircleIcon color="feedback.icon.negative.intense" />}
          onClick={() => {
            onCampaignChange(item.id, CAMPAIGN_STATUS.TERMINATED.value);
          }}
          accessibilityLabel="stop"
          isDisabled={item.status === CAMPAIGN_STATUS.TERMINATED.value}
        />
      </Box>

      {item.status === CAMPAIGN_STATUS.ACTIVE.value ? (
        <IconButton
          icon={() => <PauseCircleIcon color="surface.icon.gray.normal" />}
          onClick={() => onCampaignChange(item.id, CAMPAIGN_STATUS.INACTIVE.value)}
          accessibilityLabel="pause"
          isDisabled={item.status === CAMPAIGN_STATUS.TERMINATED.value}
        />
      ) : (
        <IconButton
          icon={() => <PlayCircleIcon color="surface.icon.gray.normal" />}
          onClick={() => onCampaignChange(item.id, CAMPAIGN_STATUS.ACTIVE.value)}
          accessibilityLabel="resume"
          isDisabled={item.status === CAMPAIGN_STATUS.TERMINATED.value}
        />
      )}
    </Box>
  );
};

const campaign_id = {
  title: 'Campaign ID',
  value: (item) => <Text>{item.id}</Text>,
};

const campaign_name = {
  title: 'Campaign Name',
  value: (item) => <Text>{item.name}</Text>,
};

const campaign_status = {
  title: 'Status',
  value: (item) => (
    <Badge color={CAMPAIGN_STATUS[item.status].color}>{CAMPAIGN_STATUS[item.status].label}</Badge>
  ),
};

const campaign_controls = {
  title: '',
  value: (item, onCampaignChange) => {
    return <CampaignControls item={item} onCampaignChange={onCampaignChange} />;
  },
};

const table_columns = [campaign_id, campaign_name, campaign_status, campaign_controls];

const PAGE_SIZE = 10;

interface CampaignsListProps {
  onNewCampaignCreate: () => void;
}

const CampaignsList = ({ onNewCampaignCreate }: CampaignsListProps) => {
  const [campaignUpdateModalVisible, setCampaignUpdateModalVisible] = useState(false);
  const [campaignUpdateType, setCampaignUpdateType] = useState<
    ValueOf<typeof CAMPAIGN_STATUS>['value'] | ''
  >('');
  const [pageNumber, setPageNumber] = useState(1);
  const campaignIdToBeUpdated = useRef('');
  const { show } = useToast();
  const { mode } = useContext(SessionContext);

  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const isCreated = searchParams.get('created') === 'true';
  const queryClient = useQueryClient();

  const {
    isLoading: campaignsLoading,
    isRefetching: campaignsRefreshing,
    data: campaignsData,
    error: campaignsError,
    refetch,
  } = useQuery({
    queryKey: ['wallet:campaigns', mode, pageNumber],
    queryFn: () => fetchCampaigns({ mode: mode, page_no: pageNumber, page_size: PAGE_SIZE }),
  });

  const { mutate: initiateCampaignUpdate, isLoading: campaignUpdateLoading } = useMutation({
    mutationKey: ['wallet:campaigns:update', mode],
    mutationFn: updateCampaign,
    onSuccess: () => {
      refetch();
      show({
        content: 'Campaign updated successfully',
        color: 'positive',
        leading: CheckCircleIcon,
      });
      setCampaignUpdateModalVisible(false);
    },
    onError: () => {
      show({
        content: 'Failed to update campaign',
        color: 'negative',
      });
    },
  });

  const tableData = {
    nodes: campaignsData?.campaigns ?? [],
  };

  const handleCampaignControl = (campaign_id, status) => {
    setCampaignUpdateType(status);
    campaignIdToBeUpdated.current = campaign_id;

    setCampaignUpdateModalVisible(true);
  };

  const handleUpdateCampaign = () => {
    if (campaignIdToBeUpdated.current && campaignUpdateType) {
      initiateCampaignUpdate({
        mode,
        id: campaignIdToBeUpdated.current,
        status: campaignUpdateType,
      });
    }
  };

  const handlePageChange = ({ page }) => {
    setPageNumber(page + 1);
  };

  useEffect(() => {
    if (isCreated) {
      // Refetch the campaigns
      queryClient
        .invalidateQueries(['wallet:campaigns', mode])
        .then(() => {
          setPageNumber(1);
          refetch();
          // Clear the `created` parameter by navigating to the same page without the parameter
          navigate('/wallet/campaigns', { replace: true });
        })
        .catch((error) => {});
    }
  }, [isCreated, refetch, navigate]);

  if (campaignsLoading || !campaignsData || campaignsError) {
    return (
      <Box display="flex" flex={1} justifyContent="center" alignItems="center">
        <Spinner size="large" accessibilityLabel="loading" />
      </Box>
    );
  }

  if (!campaignsLoading && (!campaignsData?.campaigns || campaignsData?.campaigns?.length === 0)) {
    return (
      <Box
        display="flex"
        flex={1}
        flexDirection="column"
        justifyContent="center"
        alignItems="center"
        height="300px"
      >
        <SparklesIcon size="xlarge" />
        <Text marginY="spacing.4" weight="semibold" color="surface.text.gray.muted">
          Automate credits to wallets and run campaigns with ease.
        </Text>
        <Button icon={PlusIcon} onClick={onNewCampaignCreate}>
          New Campaign
        </Button>
      </Box>
    );
  }

  return (
    <>
      <Table
        isRefreshing={campaignsRefreshing}
        data={tableData}
        gridTemplateColumns="20% 30% 40% 10%"
        isLoading={campaignsLoading || Boolean(campaignsError)}
        pagination={
          <TablePagination
            paginationType="server"
            defaultPageSize={PAGE_SIZE}
            onPageChange={handlePageChange}
            showPageSizePicker={false}
            totalItemCount={campaignsData.pagination.total_pages * PAGE_SIZE}
            showPageNumberSelector
            currentPage={campaignsData.pagination.page_no - 1}
          />
        }
      >
        {(campaigns) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                {table_columns.map(({ title }) => (
                  <TableHeaderCell key={title}>{title}</TableHeaderCell>
                ))}
              </TableHeaderRow>
            </TableHeader>
            <TableBody>
              {campaigns.map((campaign, index) => (
                <TableRow key={campaign.id} item={campaign}>
                  {table_columns.map(({ title, value }) => (
                    <TableCell key={title}>{value(campaign, handleCampaignControl)}</TableCell>
                  ))}
                </TableRow>
              ))}
            </TableBody>
          </>
        )}
      </Table>
      <Modal
        isOpen={campaignUpdateModalVisible}
        onDismiss={() => {
          setCampaignUpdateModalVisible(false);
        }}
        size="small"
      >
        <ModalBody>
          <Text size="large" weight="semibold">
            {CAMPAIGN_STATUS_CONFIRMATION_MESSAGES[campaignUpdateType]?.title}
          </Text>
          <Text color="surface.text.gray.subtle" marginTop="spacing.3">
            {CAMPAIGN_STATUS_CONFIRMATION_MESSAGES[campaignUpdateType]?.message}
          </Text>
        </ModalBody>
        <ModalFooter>
          <Box display="flex" justifyContent="flex-end">
            <Button
              type="button"
              variant="tertiary"
              marginRight="spacing.5"
              onClick={() => {
                setCampaignUpdateModalVisible(false);
              }}
              isDisabled={campaignUpdateLoading}
            >
              Go Back
            </Button>
            <Button isLoading={campaignUpdateLoading} onClick={handleUpdateCampaign}>
              {CAMPAIGN_STATUS_CONFIRMATION_MESSAGES[campaignUpdateType]?.action}
            </Button>
          </Box>
        </ModalFooter>
      </Modal>
    </>
  );
};
export default CampaignsList;
