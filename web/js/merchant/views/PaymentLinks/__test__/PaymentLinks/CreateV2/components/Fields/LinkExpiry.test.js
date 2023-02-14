import React from 'react';
import { render, screen, userEvent, fireEvent } from 'test-utils';
import LinkExpiry from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields/LinkExpiry';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';

jest.spyOn(track.lj.fields, 'expiryDate').mockImplementation(() => {});
jest.spyOn(track.segment.fields, 'expiryDate').mockImplementation(() => {});
jest.spyOn(track.lj.fields, 'expiryTime').mockImplementation(() => {});
jest.spyOn(track.segment.fields, 'expiryTime').mockImplementation(() => {});

const onChangeMock = jest.fn();

describe('LinkExpiry Component Unit Test', () => {
  afterEach(() => {
    track.lj.fields.expiryDate.mockClear();
    track.segment.fields.expiryDate.mockClear();
    track.lj.fields.expiryTime.mockClear();
    track.segment.fields.expiryTime.mockClear();
  });

  const renderApp = (props = {}) => {
    return render(<LinkExpiry onChange={onChangeMock} {...props} />);
  };
  test('should have "Link Expiry" as Label', () => {
    renderApp();
    expect(screen.getByText('Link Expiry')).toBeInTheDocument();
  });

  test('should have "Link Expiry" as Label', async () => {
    renderApp();
    const checkbox = screen.queryByRole('checkbox');
    expect(checkbox).toBeInTheDocument();
    await userEvent.click(checkbox);
    const dateInput = screen.getByPlaceholderText('DD-MM-YYYY');
    await fireEvent.change(dateInput, { target: { value: '01-01-2022' } });
  });
});
