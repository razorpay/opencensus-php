import NewReconciliation from 'merchant/views/Reconciliations/Onboarding/NewReconciliation';
import { getReadableFromKey } from 'merchant/views/Reconciliations/Onboarding/utils';
import { render, screen } from 'test-utils';

import { PRODUCT } from './../constants';

const renderUpload = (props = {}) => {
  render(<NewReconciliation {...props} />);
};

describe('Tests for creating new recon config - Recon Saas', () => {
  const item = PRODUCT.reconTypes[Object.keys(PRODUCT.reconTypes)[0]];
  const renderFn = () => renderUpload({ fileConfigs: item.file_config, reconType: item });
  test('Should render the create config files upload screen', () => {
    expect(renderFn).not.toThrowError();
  });

  test('Should render the file to upload name', () => {
    renderFn();
    const key = item?.file_config[0]?.source_name;
    const readableName = getReadableFromKey(key);
    expect(screen.getAllByText(readableName)[0]).toBeInTheDocument();
  });
});
