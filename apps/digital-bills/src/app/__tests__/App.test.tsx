import React from 'react';
import renderWithWrappers from '../../utils/testing/renderWithWrappers';
import App from '../App';

describe('App', () => {
  test('should render Digital Bills App on screen', () => {
    const { getByText } = renderWithWrappers(<App />);
    expect(getByText('Welcome to Digital Bills!')).not.toBeNull();
  });
});
