import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { ADDRESS_PROOF_TYPES } from '../../Constants/OnboardingConstants';
import DocumentUpload from '..';
import useActivation from '../../hooks/useActivation';
import * as ActivationDB from '../../services/data/ActivationDB';
import { render, waitForElementToBeRemoved, screen, fireEvent, waitFor } from 'test-utils';
const App: React.FC = () => {
  const { status } = useActivation();
  if (status === 'loading') return <div>Loading...</div>;
  return <DocumentUpload />;
};
const waitForLoadingToFinish = () => waitForElementToBeRemoved(screen.queryByText('Loading...'));
test('should render all option available for Address ', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  const proofTypeSlect = screen.getByPlaceholderText('SELECT PROOF TYPE');
  fireEvent.click(proofTypeSlect); //click on select
  Object.keys(ADDRESS_PROOF_TYPES).forEach((key) => {
    expect(screen.getByText(ADDRESS_PROOF_TYPES[key].label)).toBeInTheDocument();
  });
});
test('should manage the uploaded state of all types individually', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  const file = new File(['(⌐□_□)'], 'abc.png', { type: 'image/png' });
  const backUploadInput = screen.getAllByTestId('upload-input')[1];
  fireEvent.change(backUploadInput, { target: { files: [file] } }); //upload Back of the Aadhaar
  expect(screen.getByText('abc.png')).toBeInTheDocument();
  await waitFor(() => expect(screen.getByText('File Uploaded')).toBeInTheDocument());
  fireEvent.click(screen.getByPlaceholderText('SELECT PROOF TYPE')); //click on select
  fireEvent.click(screen.getByText('Voter Id')); //select Voter ID as preferred type
  /*     expect not uploaded state for Voter Id       */
  expect(screen.queryByText('abc.png')).not.toBeInTheDocument();
  expect(screen.queryByText('File Uploaded')).not.toBeInTheDocument();
});
test('on changing category or sub-category expected doc is visible or not', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.queryByText('Certificate of Incorporation')).not.toBeInTheDocument();
  ActivationDB.update({
    business_type: '4',
  });
  waitFor(() => expect(screen.queryByText('Certificate of Incorporation')).toBeInTheDocument());
});
