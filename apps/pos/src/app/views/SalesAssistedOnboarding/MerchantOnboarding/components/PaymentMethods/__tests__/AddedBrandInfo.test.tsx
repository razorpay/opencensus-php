import React from 'react';
import { BRAND_EMI_VERIFICATION_STATUS_ENUM } from 'apps/pos/src/app/types/modular';
import { render, userEvent, screen } from 'apps/pos/src/services/test/test-utils';
import AddedBrandInfo, {
  AddedBrandInfoProps,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/BrandEMIForm/AddedBrandInfo';

const props: AddedBrandInfoProps = {
  storeType: 'Multi brand store',
  submitHandler: jest.fn(),
  addBrandHandler: jest.fn(),
  removeBrandHandler: jest.fn(),
  brands: [
    {
      label: 'Samsung',
      name: 'samsung',
      dealerCode: 'YU12',
      distributorCode: 'OP23',
      stateCode: 'KO11',
      verificationDetailsId: 'JIP323hI',
      verificationStatus: BRAND_EMI_VERIFICATION_STATUS_ENUM.PENDING,
    },
  ],
  isUpdateModularLoading: false,
};
describe('<AddedBrandInfo/>', () => {
  test('should show the added brands', () => {
    render(<AddedBrandInfo {...props} />);
    expect(screen.getByText(/brand information form/i)).toBeInTheDocument();
    expect(screen.getByText(/Multi brand store/i)).toBeInTheDocument();
    expect(screen.getByText(/brand name/i)).toBeInTheDocument();
    expect(screen.getByText(/samsung/i)).toBeInTheDocument();
    expect(screen.getByText(/YU12/i)).toBeInTheDocument();
    expect(screen.getByText(/OP23/i)).toBeInTheDocument();
    expect(screen.getByText(/KO11/i)).toBeInTheDocument();
    expect(screen.getByText(/Pending manual validation/i)).toBeInTheDocument();
  });
  test('should call add new brand handler when clicked', async () => {
    render(<AddedBrandInfo {...props} />);
    const addBrandBtn = screen.getByRole('button', { name: /add new brand/i });
    expect(addBrandBtn).toBeInTheDocument();
    await userEvent.click(addBrandBtn);
    screen.logTestingPlaygroundURL();
    expect(props.addBrandHandler).toHaveBeenCalled();
  });
  test('should delete a brand when clicked on delete icon', async () => {
    render(<AddedBrandInfo {...props} />);
    const removeBrandBtn = screen.getByLabelText('remove-icon');
    expect(removeBrandBtn).toBeInTheDocument();
    await userEvent.click(removeBrandBtn);
    expect(props.removeBrandHandler).toHaveBeenCalled();
  });
});
