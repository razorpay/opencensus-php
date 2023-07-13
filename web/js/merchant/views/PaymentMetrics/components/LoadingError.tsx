import React from 'react';
import Spinner from 'common/ui/Spinner';
import {
  CenterContainer,
  LoadingErrorContainer,
  LoadingErrorContainerDescription,
  LoadingErrorContainerTitle,
} from './styled';
import { WarningSvg } from 'merchant/components/Home/GenericPanel';
import { LoadingErrorProps } from 'merchant/views/PaymentMetrics/types';
import {
  DEFAULT_LOADING_ERROR_TITLE,
  DEFAULT_LOADING_ERROR_DESCRIPTION,
} from 'merchant/views/PaymentMetrics/constants';

const LoadingError = ({
  isLoading = false,
  noData = false,
  error = '',
  showDescription = true,
}: LoadingErrorProps): React.ReactElement => {
  const title = error || DEFAULT_LOADING_ERROR_TITLE;
  const description = DEFAULT_LOADING_ERROR_DESCRIPTION;
  return (
    <CenterContainer>
      {isLoading && <Spinner />}
      {(error || noData) && (
        <LoadingErrorContainer>
          <div>
            <WarningSvg />
            <LoadingErrorContainerTitle>{title}</LoadingErrorContainerTitle>
          </div>
          {showDescription && (
            <LoadingErrorContainerDescription>{description}</LoadingErrorContainerDescription>
          )}
        </LoadingErrorContainer>
      )}
    </CenterContainer>
  );
};

export default LoadingError;
