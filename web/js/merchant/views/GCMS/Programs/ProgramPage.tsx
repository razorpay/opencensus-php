import React, { memo, useEffect, useMemo, useState } from 'react';
import {
  Box,
  Text,
  EditIcon,
  Button,
  Tabs,
  TabList,
  TabItem,
  TabPanel,
  PlusIcon,
  Heading,
  ArrowLeftIcon,
  Spinner,
  Link,
  ToastContainer,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { useQuery } from '@tanstack/react-query';
import { useLocation, useNavigate, useParams, Navlink } from 'react-router-dom';

import EmptyList from 'merchant/components/EmptyList';
import { useStore } from '@federated/apps/shell/commonStore';
import { fetchProgramById, fetchProgramImage } from 'merchant/views/GCMS/Programs/queries';
import Wrapper from 'merchant/views/GCMS/shared/Wrapper';
import ProgramDetails from 'merchant/views/GCMS/Programs/ProgramDetails';
import LinkedResellers from 'merchant/views/GCMS/Programs/LinkedResellers';
import CreateProgram from 'merchant/views/GCMS/Programs/CreateProgram';

const TAB_VALUE = {
  PROGRAM_DETAILS: 'details',
  PROGRAM_RESELLERS: 'resellers',
};

const TABS = [
  {
    value: TAB_VALUE.PROGRAM_DETAILS,
    element: ProgramDetails,
    title: 'Details',
  },
  {
    value: TAB_VALUE.PROGRAM_RESELLERS,
    element: LinkedResellers,
    title: 'Resellers',
  },
];

const ProgramPage = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [selectedTab, setSelectedTab] = useState(TABS[0].value);
  const session = useStore((state) => state.session);
  const mode = session.mode;
  const location = useLocation();
  const navigate = useNavigate();
  const { programId } = useParams<{ programId: string }>();

  const {
    isLoading,
    data: program,
    refetch,
  } = useQuery({
    queryKey: ['gcms:programs:id', mode, programId],
    queryFn: () => fetchProgramById({ mode, programId }),
    retry: false,
    refetchOnWindowFocus: false,
  });
  const {
    isLoading: isImageLoading,
    data: programImage,
    refetch: refetchImage,
  } = useQuery({
    queryKey: ['gcms:programs:image', program?.policies.gift_card_file_storage_id, mode],
    queryFn: () =>
      fetchProgramImage({
        fileId: program?.policies.gift_card_file_storage_id,
        programId: program.id,
        mode,
      }),
    enabled: Boolean(program?.policies?.gift_card_file_storage_id),
    retry: false,
    refetchOnWindowFocus: false,
  });

  const handleGoBack = () => {
    const { prevPath = '' } = location?.state ?? {};
    if (prevPath) {
      return navigate(-1);
    }
    return navigate('/gcms/programs');
  };

  function getTabs() {
    return TABS.map((tab) => <TabItem value={tab.value}>{tab.title}</TabItem>);
  }

  const getTabData = useMemo(() => {
    return TABS.map((tab) => (
      <TabPanel value={tab.value}>
        <Box marginTop="24px">
          <tab.element
            program={program}
            programImage={programImage}
            isImageLoading={isImageLoading}
            selectedTab={selectedTab}
            isOpen={isOpen}
            closeModal={() => setIsOpen(false)}
          />
        </Box>
      </TabPanel>
    ));
  }, [program, programImage, isImageLoading, selectedTab, isOpen]);

  function handleHeaderCTA() {
    setIsOpen(true);
  }

  if (isLoading)
    return (
      <Box display="flex" alignItems="center" justifyContent="center" minHeight="200px">
        <Spinner accessibilityLabel="loading programs" label="loading..." labelPosition="bottom" />
      </Box>
    );

  if (!program) {
    return (
      <Box width="100%" height="100%">
        <EmptyList
          description={
            <React.Fragment>
              <Text>There are no programs yet!!</Text>
              <Text>Start creating new programs now.</Text>
            </React.Fragment>
          }
        />
      </Box>
    );
  }

  return (
    <Wrapper>
      <Box testID="program-page-container" paddingTop="24px">
        <Box display="flex" flexDirection="row" alignItems="center" justifyContent="space-between">
          <Box display="flex" alignItems="center" gap="spacing.2">
            <Link
              onClick={() => navigate('/gcms/programs')}
              icon={ArrowLeftIcon}
              size="large"
              color="neutral"
            ></Link>

            <Heading size="large" marginLeft="8px">
              {program.name}
            </Heading>
          </Box>
          <Button
            icon={selectedTab === TAB_VALUE.PROGRAM_DETAILS ? EditIcon : PlusIcon}
            variant="primary"
            onClick={handleHeaderCTA}
            testID="program-creation-flow"
            size="medium"
            isDisabled={isLoading || isImageLoading}
          >
            {selectedTab === TAB_VALUE.PROGRAM_DETAILS ? 'Edit' : 'Add Reseller'}
          </Button>
        </Box>
        <Box marginTop="20px">
          <Tabs orientation="horizontal" value={selectedTab} onChange={setSelectedTab}>
            <TabList>{getTabs()}</TabList>
            {getTabData}
          </Tabs>
        </Box>
      </Box>
      <ToastContainer />

      {isOpen && selectedTab === TAB_VALUE.PROGRAM_DETAILS && (
        <CreateProgram
          program={{ ...program, url: programImage }}
          onClose={() => setIsOpen(false)}
          refetch={refetch}
          refetchImage={refetchImage}
          editMode={2}
          submitText="Edit Program"
        />
      )}
    </Wrapper>
  );
};

export default memo(
  connect((state) => ({
    mode: state.session?.mode,
    merchantId: state.session?.user?.current,
  }))(ProgramPage),
);
