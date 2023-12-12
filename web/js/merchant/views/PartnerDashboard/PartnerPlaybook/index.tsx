import React, { ComponentType, useState } from 'react';
import { Box } from '@razorpay/blade/components';
import { FormikValues, useFormik } from 'formik';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { useLocation, useNavigate } from 'react-router-dom';
import { bindActionCreators } from 'redux';
import * as Yup from 'yup';

import { ShowNotificationType, UseFormikReturnType } from 'common/typings';
import { encodeSensitiveFields, stringifyQueryParams } from 'common/utils/rzp-utils';
import { PlaybookFiltersType } from 'merchant/views/PartnerDashboard/PartnerPlaybook/types';
import { getDecodedParams } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/components/AllInvitesFilter';
import { showNotification } from 'merchant_common/reducers/notifications';

import { fetchPlaybookItems } from './api';
import FeedbackLoop from './components/FeedbackLoop';
import GoogleDrivePreview from './components/GoogleDrivePreview';
import IntroVideoModal from './components/IntroVideoModal';
import PlaybookSections from './components/PlaybookSections';
import PosterAndSearchBar from './components/PosterAndSearchBar';

const validationSchema = Yup.object().shape({
  query: Yup.string().trim().min(2, 'Search query should have at least 2 characters.').nullable(),
});

type PartnerPlaybookProps = {
  showNotification: ShowNotificationType;
};
const PartnerPlaybook = ({ showNotification }: PartnerPlaybookProps): JSX.Element => {
  const navigate = useNavigate();
  const location = useLocation();
  const decodedSearchParams = getDecodedParams(location.search);
  const [previewItem, setPreviewItem] = useState(null);
  const [isIntroVideoModalOpen, setIsIntroVideoModalOpen] = useState(false);
  const [isDriveModalOpen, setIsDriveModalOpen] = useState(false);
  let formik = {} as UseFormikReturnType;
  const {
    data: programItems,
    isInitialLoading,
    isFetching,
    refetch: onSearch,
  } = useQuery(
    ['filter-playbook-items', formik.values?.query],
    async () => {
      const values = (formik?.values || decodedSearchParams) as PlaybookFiltersType;
      const { data, success: isSuccessful } = await fetchPlaybookItems(values);
      if (!isSuccessful || !data) {
        showNotification?.({
          type: 'error',
          message: 'There was an error',
        });
      }
      return data || [];
    },
    {
      refetchOnWindowFocus: false,
      staleTime: Infinity,
      retry: false,
      onError: (_err) => {
        showNotification?.({
          type: 'error',
          message: 'There was an error',
        });
      },
    },
  );

  const handleFormSubmit = (formData) => {
    const searchParams = stringifyQueryParams(encodeSensitiveFields(formData));
    navigate({
      pathname: location.pathname,
      search: searchParams,
    });
    onSearch();
  };
  const initState: PlaybookFiltersType = {
    query: decodedSearchParams.query || '',
  };
  formik = useFormik<FormikValues>({
    initialValues: initState,
    validationSchema,
    validateOnChange: true,
    onSubmit: handleFormSubmit,
  });

  const onWatchIntroClick = () => {
    setIsIntroVideoModalOpen(true);
  };
  const openPreview = (item) => {
    setPreviewItem(item);
    setIsDriveModalOpen(true);
  };
  const closePreview = () => {
    setPreviewItem(null);
    setIsDriveModalOpen(false);
  };

  return (
    <Box
      display="flex"
      marginBottom="spacing.6"
      flexDirection="column"
      backgroundColor="surface.background.level3.lowContrast"
    >
      <PosterAndSearchBar formik={formik} onWatchIntroClick={onWatchIntroClick} />
      <PlaybookSections
        programItems={programItems}
        isLoading={isFetching || isInitialLoading}
        openPreview={openPreview}
      />
      <FeedbackLoop />
      <GoogleDrivePreview
        item={previewItem}
        isOpen={isDriveModalOpen}
        closePreview={closePreview}
      />
      <IntroVideoModal isOpen={isIntroVideoModalOpen} setIsOpen={setIsIntroVideoModalOpen} />
    </Box>
  );
};

export default connect<ComponentType<Omit<PartnerPlaybookProps, 'showNotification'>>>(
  null,
  (dispatch) => bindActionCreators({ showNotification }, dispatch),
)(PartnerPlaybook);
