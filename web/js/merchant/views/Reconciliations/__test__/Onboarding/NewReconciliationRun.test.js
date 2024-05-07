import NewReconciliationRun from 'merchant/views/Reconciliations/Onboarding/NewReconciliationRun';
import { render } from 'test-utils';

import { PRODUCT } from './../constants';

const renderUpload = (props = {}) => {
  return render(<NewReconciliationRun {...props} />);
};

describe('Tests for creating new recon run - Recon Saas', () => {
  const item = PRODUCT.reconTypes[Object.keys(PRODUCT.reconTypes)[0]];
  const renderFn = () => renderUpload({ fileConfigs: item.file_config, reconType: item });
  test('Should render the new recon run screen without errors', () => {
    expect(renderFn).not.toThrowError();
  });

  test('Should render back button', () => {
    const { getByText } = renderFn();
    expect(getByText('Back')).toBeInTheDocument();
  });
});
