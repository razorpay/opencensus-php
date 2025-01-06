import React from 'react';

import BrandForm from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/components/BrandModalComponent/BrandForm';
import { screen, render, fireEvent, waitFor } from 'test-utils';

const App = ({ props }) => <BrandForm {...props} />;

describe('BrandForm', () => {
  test("should render 'BrandForm' component as expected on 'create' scenario", async () => {
    const updateBrandPayload = jest.fn();
    const props = {
      updateBrandPayload,
      brandPayload: {
        name: '',
        description: null,
        logo: null,
      },
    };

    render(<App props={props} />);

    // Brand Name
    expect(screen.getByText('Name')).toBeInTheDocument();
    const nameField = screen.getByPlaceholderText('Enter Brand Name');
    expect(nameField).toBeInTheDocument();
    fireEvent.change(nameField, { target: { value: 'Test Brand Name' } });
    expect(updateBrandPayload).toHaveBeenLastCalledWith('name', 'Test Brand Name');

    // Brand Description
    expect(screen.getByText('Description')).toBeInTheDocument();
    const descriptionField = screen.getByPlaceholderText('Enter Description');
    expect(descriptionField).toBeInTheDocument();
    fireEvent.change(descriptionField, { target: { value: 'Test Brand Description' } });
    expect(updateBrandPayload).toHaveBeenLastCalledWith('description', 'Test Brand Description');

    // Brand Logo
    expect(screen.getByText('Image')).toBeInTheDocument();
    expect(
      screen.getByText('Recommended Size = 128px x 128px. Max Size = 5MB'),
    ).toBeInTheDocument();
    const fileUploadContainer = screen
      .getByText('Image')
      .closest('div[data-blade-component=file-upload]')!;
    const fileUploadInput = fileUploadContainer.querySelector("[type='file']")!;
    const testFile = new File(['(⌐□_□)'], 'test_image.png');
    await waitFor(() => {
      fireEvent.change(fileUploadInput, { target: { files: [testFile] } });
    });
    expect(updateBrandPayload).toHaveBeenLastCalledWith('logo', testFile);
  });

  test("should render 'BrandForm' component as expected on 'update' scenario", () => {
    const updateBrandPayload = jest.fn();
    const props = {
      updateBrandPayload,
      brandPayload: {
        name: 'Test Brand Name',
        description: 'Test Brand Description',
        logo: new File([], 'test_image.png'),
      },
    };

    render(<App props={props} />);

    // Brand Name
    const nameField = screen.getByPlaceholderText('Enter Brand Name');
    expect(nameField).toHaveValue('Test Brand Name');

    // Error validation for 'Name' field (mandatory error)
    fireEvent.change(nameField, { target: { value: '' } });
    expect(screen.getByText('Name is a mandatory field')).toBeInTheDocument();
    fireEvent.change(nameField, { target: { value: '1' } });
    expect(screen.queryByText('Name is a mandatory field')).not.toBeInTheDocument();

    // Error validation for 'Name' field (maxCharacters error)
    fireEvent.change(nameField, {
      target: { value: 'Test Brand Name with more than 50 characters - validation' },
    });
    expect(screen.getByText('Brand name cannot be more than 50 characters')).toBeInTheDocument();
    fireEvent.change(nameField, {
      target: { value: 'Test Brand Name with less than 50 characters' },
    });
    expect(
      screen.queryByText('Brand name cannot be more than 50 characters'),
    ).not.toBeInTheDocument();

    // Brand Description
    const descriptionField = screen.getByPlaceholderText('Enter Description');
    expect(descriptionField).toHaveValue('Test Brand Description');

    // Brand Logo
    const brandLogoFileName = screen.getByText('test_image.png');
    expect(brandLogoFileName).toBeInTheDocument();

    // Brand Logo removal
    const fileUploadContainer = brandLogoFileName.closest('div[data-blade-component=file-upload]')!;
    const removeLogoButton = fileUploadContainer.querySelector("[aria-label='Remove File']")!;
    fireEvent.click(removeLogoButton);
    expect(updateBrandPayload).toHaveBeenLastCalledWith('logo', null);
  });
});
