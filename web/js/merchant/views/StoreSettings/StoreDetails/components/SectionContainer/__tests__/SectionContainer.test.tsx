import React from 'react';

import SectionContainer from 'merchant/views/StoreSettings/StoreDetails/components/SectionContainer';
import { SECTION_CONTAINER_PROPS } from 'merchant/views/StoreSettings/StoreDetails/components/SectionContainer/__tests__/mocks';
import { screen, render } from 'test-utils';

describe('SectionContainer', () => {
  test("should render 'SectionContainer' component as expected", () => {
    const children = <p>Test Children content</p>;
    render(
      <SectionContainer
        {...SECTION_CONTAINER_PROPS}
        groupTitle="Test Group Title"
        valueMap={{ storeType: 'Online', storeCode: '1234' }}
        children={children}
      />,
    );

    // Section Heading
    expect(screen.getByText('Test Section Heading')).toBeInTheDocument();

    // Group Title
    expect(screen.getByText('Test Group Title')).toBeInTheDocument();

    // Store Type field
    expect(screen.getByText('Store Type')).toBeInTheDocument();
    expect(screen.getByText('Online')).toBeInTheDocument();

    // Store Code field
    expect(screen.getByText('Store Code')).toBeInTheDocument();
    expect(screen.getByText('1234')).toBeInTheDocument();

    // Children
    expect(screen.getByText('Test Children content')).toBeInTheDocument();
  });

  test("should render 'SectionContainer' component with placeholder content '-' when field value is empty string", () => {
    render(
      <SectionContainer
        {...SECTION_CONTAINER_PROPS}
        valueMap={{ storeType: 'Online', storeCode: '' }}
      />,
    );

    // Section Heading
    expect(screen.getByText('Test Section Heading')).toBeInTheDocument();

    // Group Title
    expect(screen.queryByText('Test Group Title')).not.toBeInTheDocument();

    // Store Code field
    expect(screen.getByText('-')).toBeInTheDocument();

    // Children
    expect(screen.queryByText('Test Children content')).not.toBeInTheDocument();
  });
});
