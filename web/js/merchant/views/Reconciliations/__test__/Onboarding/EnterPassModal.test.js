import EnterPassModal from 'merchant/views/Reconciliations/Onboarding/EnterPasswordModal';
import { render, screen } from 'test-utils';

const renderModal = (props = {}) => {
  render(<EnterPassModal {...props} />);
};

describe('Tests for onboarding component enter modal - Recon Saas', () => {
  test('Should render password modal without errors', () => {
    expect(renderModal).not.toThrowError();
  });
  test('Should load file password modal', () => {
    renderModal({
      activePassFile: {},
      setActivePassFile: jest.fn(),
      handleSubmit: jest.fn(),
    });
    expect(screen.getByText(/Enter the document password/i)).toBeInTheDocument();
    expect(screen.getByText(/Uploaded file is password protected/i)).toBeInTheDocument();
  });
});
