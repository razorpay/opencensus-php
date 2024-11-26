// Todo: delete this file, it's available in @dashboard/shared-ui
import React, { ReactNode, Suspense } from 'react';
import Loader, {
  FullPageLoader,
  CenterLoader,
  FullPageLoaderCenterToMainContent,
} from 'common/components/Loader';

const LOADER_TYPE = {
  default: Loader,
  full: FullPageLoader,
  center: CenterLoader,
  centerToMainContent: FullPageLoaderCenterToMainContent,
};

interface SuspensePropsInterface {
  type?: 'full' | 'center' | 'default' | 'centerToMainContent';
  children: ReactNode;
}

const SuspenseWithLoader: React.FC<SuspensePropsInterface> = ({
  type,
  children,
}: SuspensePropsInterface) => {
  const FallbackComponent = type ? LOADER_TYPE[type] : LOADER_TYPE.default;
  return <Suspense fallback={<FallbackComponent />}>{children}</Suspense>;
};

export default SuspenseWithLoader;

SuspenseWithLoader.defaultProps = {
  type: 'default',
};
