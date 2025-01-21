import React, { useState, useEffect, useRef } from 'react';
import {
  Box,
  Modal,
  Button,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Link,
  Divider,
  BladeFile,
  Spinner,
  useToast,
} from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import { graphqlRequest } from 'common/services/graphql/graphql-client';
import BrandForm from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/components/BrandModalComponent/BrandForm';
import BrandInfo from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/components/BrandModalComponent/BrandInfo';
import useBrandCreateMutation from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/components/BrandModalComponent/hooks/useBrandCreateMutation';
import useBrandPreSignedUrlMutation from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/components/BrandModalComponent/hooks/useBrandPreSignedUrlMutation';
import useBrandUpdateMutation from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/components/BrandModalComponent/hooks/useBrandUpdateMutation';
import {
  BRAND_OPERATION_TYPE,
  INIT_BRAND_PAYLOAD,
} from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/constants';
import { BRAND_BY_ID_DATA_QUERY } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/queries';

import type {
  Brand,
  BrandByIdResponse,
  BrandModalInfoType,
  ModifiedFieldsMapType,
  BrandPayloadType,
} from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/types';

type BrandModalComponentProps = {
  onCloseModal: () => void;
  refetchBrandsList: () => void;
  brandModalInfo: BrandModalInfoType;
};

const BrandModalComponent = ({
  onCloseModal,
  refetchBrandsList,
  brandModalInfo,
}: BrandModalComponentProps): React.ReactElement => {
  const [shouldShowEditForm, setShouldShowEditForm] = useState<boolean>(false);
  const [brandPayload, setBrandPayload] = useState<BrandPayloadType>(INIT_BRAND_PAYLOAD);
  const [modifiedFieldsMap, setModifiedFieldsMap] = useState<ModifiedFieldsMapType>({});
  const [shouldShowBrandInfoLoader, setShouldShowBrandInfoLoader] = useState<boolean>(false);
  const isBrandLogoUploadingRef = useRef(false);
  const toast = useToast();

  const { operationType, selectedBrandId } = brandModalInfo;

  const fetchAndUpdateBrandPayload = async (brandInfo: Brand) => {
    setShouldShowBrandInfoLoader(true);
    const { name, description, logo } = brandInfo;
    let bladeFile: null | BladeFile = null;
    if (logo) {
      try {
        const response = await fetch(logo);
        if (response.status === 200) {
          let filename = 'Brand_Logo.png';
          const parsedUrl = new URL(logo);
          const contentDisposition = parsedUrl.searchParams.get('response-content-disposition');
          if (contentDisposition) {
            const matches = contentDisposition.match(/filename="?([^";]+)"?/);
            if (matches && matches[1]) {
              filename = decodeURIComponent(matches[1])?.split('_')[1];
            }
          }
          const blob = await response.blob();
          bladeFile = new File([blob], filename);
          bladeFile.status = 'success';
        }
      } catch (err) {
        toast.show({
          type: 'informational',
          color: 'negative',
          content: 'Error in fetching Brand logo!',
        });
      }
    }
    const updatedPayload = {
      name,
      description,
      logo: bladeFile,
    };
    setBrandPayload(updatedPayload);
    setShouldShowBrandInfoLoader(false);
  };

  const {
    data: brandInfoResponse,
    isFetching: isBrandInfoFetching,
    refetch,
  } = useQuery<BrandByIdResponse>({
    enabled: false,
    queryKey: ['brand_info', selectedBrandId],
    queryFn: () =>
      graphqlRequest({
        document: BRAND_BY_ID_DATA_QUERY,
        variables: { id: selectedBrandId },
      }),
    onSuccess: (brandInfoResponse) => {
      fetchAndUpdateBrandPayload(brandInfoResponse?.storeBrandById || {});
    },
    onError: () => {
      onCloseModal();
      toast.show({
        type: 'informational',
        color: 'negative',
        content: 'Error in fetching Brand information!',
      });
    },
  });

  useEffect(() => {
    if (operationType === BRAND_OPERATION_TYPE.CREATE) {
      setBrandPayload(INIT_BRAND_PAYLOAD);
    } else if (operationType === BRAND_OPERATION_TYPE.READ && selectedBrandId) {
      // 'operationType' is added in else if check, otherwise 'shouldShowEditForm' will be false before modal disappears, which affects the transition effect
      refetch();
      setShouldShowEditForm(false);
    }
  }, [operationType, selectedBrandId]);

  const { getLogoPreSignedUrl } = useBrandPreSignedUrlMutation();

  const updateBrandPayload = async <K extends keyof BrandPayloadType>(
    key: K,
    value: BrandPayloadType[K],
  ) => {
    const updatedBrandInfo = { ...brandPayload };
    if (key === 'logo') {
      if (value) {
        isBrandLogoUploadingRef.current = true;
        const { storeBrandLogoPreSignedUrl } = await getLogoPreSignedUrl(
          (value as BladeFile)?.name,
        );
        const presignedUrl = storeBrandLogoPreSignedUrl?.preSignedUrlInfo?.presignedUrl;
        if (storeBrandLogoPreSignedUrl.success) {
          try {
            // Upload the file to the S3 bucket with the presigned URL
            const response = await fetch(presignedUrl, {
              method: 'PUT',
              body: value,
              headers: {
                'Content-Type': (value as BladeFile)?.type,
              },
            });
            if (response.status === 200) {
              updatedBrandInfo.documentId =
                storeBrandLogoPreSignedUrl?.preSignedUrlInfo?.documentId;
              const uploadedFile = value as BladeFile;
              uploadedFile.status = 'success';
              updatedBrandInfo.logo = uploadedFile;
            }
          } catch (e) {
            toast.show({
              type: 'informational',
              color: 'negative',
              content: 'Error in uploading Brand logo!',
            });
          } finally {
            isBrandLogoUploadingRef.current = false;
          }
        }
      } else {
        updatedBrandInfo.documentId = '';
        updatedBrandInfo.logo = null;
      }
    } else {
      let updatedFieldValue = value;
      // Trim the value and check if it is not empty
      if (value && typeof value === 'string') {
        updatedFieldValue = value.trim() as BrandPayloadType[K];
        // If the value is not empty after trimming (space between words), update the field value without trimming
        if (typeof updatedFieldValue === 'string' && updatedFieldValue.length > 0) {
          updatedFieldValue = value;
        }
      }
      updatedBrandInfo[key] = updatedFieldValue;
    }
    if (!modifiedFieldsMap[key]) {
      setModifiedFieldsMap({ ...modifiedFieldsMap, [key]: true });
    }
    setBrandPayload(updatedBrandInfo);
  };

  const onSuccessHandler = () => {
    onCloseModal();
    setModifiedFieldsMap({});
    refetchBrandsList();
  };

  const { createBrand, isCreateBrandLoading } = useBrandCreateMutation({
    brandPayload,
    onSuccessHandler,
  });

  const { updateBrand, isUpdateBrandLoading } = useBrandUpdateMutation({
    brandId: selectedBrandId as string,
    brandPayload,
    modifiedFieldsMap,
    onSuccessHandler,
    selectedBrandInfo: brandInfoResponse?.storeBrandById!,
  });

  const handleModalDismiss = () => {
    if (shouldShowEditForm) {
      setModifiedFieldsMap({});
    }
    onCloseModal();
  };

  return (
    <>
      {/* In order to have modal transition effect on disappearance, two separate modal is constructed */}
      <Modal
        isOpen={operationType === BRAND_OPERATION_TYPE.CREATE}
        onDismiss={onCloseModal}
        size="medium"
      >
        <ModalHeader title="Add New Brand" />
        <ModalBody>
          <Box
            backgroundColor="surface.background.gray.moderate"
            borderColor="surface.border.gray.muted"
            borderRadius="medium"
          >
            <BrandForm brandPayload={brandPayload} updateBrandPayload={updateBrandPayload} />
          </Box>
        </ModalBody>
        <ModalFooter>
          <Box display="flex" gap="spacing.5" justifyContent="flex-end" width="100%">
            <Button
              variant="tertiary"
              onClick={onCloseModal}
              isDisabled={isCreateBrandLoading || isBrandLogoUploadingRef.current}
            >
              Cancel
            </Button>
            <Button
              onClick={() => createBrand()}
              isLoading={isCreateBrandLoading || isBrandLogoUploadingRef.current}
              isDisabled={!brandPayload?.name?.length || brandPayload?.name?.length > 50}
            >
              Add
            </Button>
          </Box>
        </ModalFooter>
      </Modal>
      <Modal
        isOpen={operationType === BRAND_OPERATION_TYPE.READ}
        onDismiss={handleModalDismiss}
        size="medium"
      >
        <ModalHeader
          title="Brand Details"
          trailing={
            <Link
              variant="button"
              onClick={() => setShouldShowEditForm(true)}
              isDisabled={isBrandInfoFetching || shouldShowBrandInfoLoader || shouldShowEditForm}
            >
              Edit
            </Link>
          }
        />
        <ModalBody>
          <Box
            backgroundColor="surface.background.gray.moderate"
            borderColor="surface.border.gray.muted"
            borderRadius="medium"
          >
            {isBrandInfoFetching || shouldShowBrandInfoLoader || !brandInfoResponse ? (
              <Box display="flex" alignItems="center" justifyContent="center" height="18vh">
                <Spinner accessibilityLabel="Brand info loading" />
              </Box>
            ) : (
              <>
                {brandInfoResponse && (
                  <BrandInfo selectedBrandInfo={brandInfoResponse.storeBrandById} />
                )}
                {shouldShowEditForm && (
                  <>
                    <Divider marginY="spacing.4" />
                    <BrandForm
                      brandPayload={brandPayload}
                      updateBrandPayload={updateBrandPayload}
                    />
                  </>
                )}
              </>
            )}
          </Box>
        </ModalBody>
        <ModalFooter>
          {shouldShowEditForm && (
            <Box display="flex" gap="spacing.5" justifyContent="flex-end" width="100%">
              <Button
                variant="tertiary"
                onClick={handleModalDismiss}
                isDisabled={isUpdateBrandLoading || isBrandLogoUploadingRef.current}
              >
                Cancel
              </Button>
              <Button
                onClick={() => updateBrand()}
                isLoading={isUpdateBrandLoading || isBrandLogoUploadingRef.current}
                isDisabled={!brandPayload?.name?.length || brandPayload?.name?.length > 50}
              >
                Save
              </Button>
            </Box>
          )}
        </ModalFooter>
      </Modal>
    </>
  );
};

export default BrandModalComponent;
